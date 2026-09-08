<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Avance por Fases';
$pageSubtitle = 'Quién se está descolgando en cada fase del proyecto formativo';
$activePage   = 'fases_dashboard';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- Un solo filtro, arriba, que rige todo lo que hay debajo -->
<div class="toolbar mb-5">
  <span class="toolbar-label"><?= icon('school') ?> Ficha</span>
  <select id="sel-ficha" class="form-control flex-1" style="max-width:520px;" onchange="loadDashboard()" aria-label="Seleccionar ficha">
    <option value="">Cargando fichas…</option>
  </select>
  <span class="text-sm text-secondary" id="ficha-hint">Selecciona una ficha para ver su avance por fases</span>
</div>

<div class="card" id="state-idle">
  <div class="empty-state">
    <div class="empty-icon"><?= icon('target') ?></div>
    <div class="empty-title">Selecciona una ficha</div>
    <p>Elige una ficha en el selector superior para ver cómo avanza el grupo en cada fase del proyecto formativo y quién se está quedando atrás.</p>
  </div>
</div>

<div class="card" id="state-empty" hidden>
  <div class="empty-state">
    <div class="empty-icon"><?= icon('layers') ?></div>
    <div class="empty-title">Esta ficha aún no tiene proyecto formativo</div>
    <p id="empty-detalle">El programa asociado a esta ficha no tiene fases con resultados de aprendizaje asignados, o no hay aprendices activos en ella.</p>
    <a href="fases.php" class="btn btn-primary" id="empty-cta"><?= icon('layers') ?> Configurar su proyecto formativo</a>
  </div>
</div>

<div id="dashboard-container" hidden>

  <!-- ══════════════════════════════════════════════════════════════
       PANORAMA POR FASE
       El número grande es avance CONTINUO (RAPs aprobados sobre RAPs
       evaluables), no el "todo o nada" anterior que dejaba las cuatro
       fases en 0% hasta el final del programa. La barra apilada de
       debajo es lo que de verdad importa: si el grupo va compacto o se
       está partiendo en dos.
       ══════════════════════════════════════════════════════════════ -->
  <div class="card card-flush mb-5">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('layers') ?> Avance por fase</div>
        <div class="card-subtitle">
          Cada tarjeta es una fase. El número es el avance medio del grupo; la barra de abajo reparte
          a los aprendices por cuánto llevan. <strong>Toca una fase para ver el detalle.</strong>
        </div>
      </div>
      <div class="dist-legend" id="dist-legend"></div>
    </div>
    <div class="fases-row" id="fases-row"></div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════
       DETALLE DE LA FASE SELECCIONADA
       Dos listas que se leen de arriba abajo como una lista de tareas:
       a quién citar, y qué RAP desatascar.
       ══════════════════════════════════════════════════════════════ -->
  <div class="grid-main-aside" id="detalle-panel">
    <div class="card card-flush">
      <div class="card-header">
        <div>
          <div class="card-title"><?= icon('user-search') ?> Quién va rezagado <span id="det-fase-a" class="det-fase"></span></div>
          <div class="card-subtitle" id="det-aprendices-sub">Cargando…</div>
        </div>
      </div>
      <div class="bar-list" id="lista-aprendices"></div>
    </div>

    <div class="card card-flush">
      <div class="card-header">
        <div>
          <div class="card-title"><?= icon('siren') ?> Qué frena la fase <span id="det-fase-b" class="det-fase"></span></div>
          <div class="card-subtitle" id="det-raps-sub">Cargando…</div>
        </div>
      </div>
      <div class="bar-list" id="lista-raps"></div>
    </div>
  </div>
</div>

<style>
  /* ── Panorama de fases ─────────────────────────────────────────── */
  .fases-row {
    display: grid; gap: var(--space-3);
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    padding: 0 var(--space-5) var(--space-5);
  }
  .fase-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: var(--space-4);
    text-align: left; font: inherit; color: inherit; width: 100%;
    cursor: pointer; transition: var(--transition);
  }
  .fase-card:hover { border-color: var(--brand-soft-bd); background: var(--bg-card-hover); }
  .fase-card.is-current { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }

  .fase-idx { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: .09em; }
  .fase-name {
    font-size: 13.5px; font-weight: 700; color: var(--text-primary); margin: 2px 0 12px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em;
  }
  /* Cifra protagonista: misma sans que el resto y cifras proporcionales.
     tabular-nums a este tamaño deja los dígitos flotando separados. */
  .fase-pct { font-size: 30px; font-weight: 800; line-height: 1; letter-spacing: -.03em; color: var(--seq-6); }

  /* Medidor: una razón contra su límite, en el tono más oscuro de la rampa */
  /* La pista usa --border, no --bg-subtle: en oscuro el sutil queda a un
     paso de la superficie de la tarjeta y la pista desaparece. */
  .fase-meter { height: 6px; background: var(--border); border-radius: var(--radius-sm); overflow: hidden; margin: 10px 0 14px; }
  .fase-meter-fill { height: 100%; background: var(--seq-6); border-radius: var(--radius-sm); transition: width .5s cubic-bezier(.4,0,.2,1); }

  /* Barra apilada: reparto del grupo. El hueco de 2px es superficie,
     no un borde: separa los tramos sin dibujar una línea encima. */
  .dist-bar { display: flex; gap: 2px; height: 12px; }
  .dist-seg { min-width: 3px; transition: opacity var(--transition); }
  .dist-seg:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
  .dist-seg:last-child  { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
  .dist-seg:only-child  { border-radius: var(--radius-sm); }
  .dist-0 { background: var(--seq-1); }
  .dist-1 { background: var(--seq-2); }
  .dist-2 { background: var(--seq-4); }
  .dist-3 { background: var(--seq-5); }
  .dist-4 { background: var(--seq-6); }

  .dist-legend { display: flex; flex-wrap: wrap; gap: 10px; font-size: 11px; color: var(--text-secondary); }
  .dist-legend span { display: flex; align-items: center; gap: 5px; }
  .dist-legend i { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

  .fase-foot { display: flex; align-items: center; gap: 6px; font-size: 11.5px; margin-top: 12px; font-weight: 600; }
  .fase-foot .ic { width: 13px; height: 13px; flex-shrink: 0; }
  .fase-foot.is-alerta { color: var(--danger); }
  .fase-foot.is-ok     { color: var(--text-muted); font-weight: 500; }
  .fase-meta { font-size: 11px; color: var(--text-muted); margin-top: 4px; font-variant-numeric: tabular-nums; }

  /* ── Listas de barras del detalle ──────────────────────────────── */
  .det-fase { font-weight: 500; color: var(--text-secondary); }
  .bar-list { padding: 0 var(--space-5) var(--space-5); display: flex; flex-direction: column; gap: 2px; max-height: 62vh; overflow-y: auto; }
  .bar-row {
    display: block; width: 100%; text-align: left; font: inherit; color: inherit;
    padding: 9px 10px; border: 1px solid transparent; border-radius: var(--radius-md); text-decoration: none;
  }
  a.bar-row:hover { background: var(--bg-subtle); border-color: var(--border); color: inherit; }
  .bar-head { display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px; }
  .bar-label { font-size: 12.5px; font-weight: 600; flex: 1; min-width: 0; }
  .bar-code { font-size: 10.5px; font-family: ui-monospace, Consolas, monospace; color: var(--text-muted); flex-shrink: 0; }
  .bar-val { font-size: 12px; font-weight: 800; font-variant-numeric: tabular-nums; flex-shrink: 0; }
  .bar-track { height: 8px; background: var(--border); border-radius: var(--radius-sm); overflow: hidden; }
  /* Una sola serie, un solo tono: la longitud ya codifica la magnitud.
     El rojo aparece sólo cuando significa "en riesgo", con etiqueta al lado. */
  .bar-fill { height: 100%; background: var(--seq-4); border-radius: var(--radius-sm); transition: width .5s cubic-bezier(.4,0,.2,1); }
  .bar-row.is-alerta .bar-fill { background: var(--danger-solid); }
  .bar-row.is-alerta .bar-val  { color: var(--danger); }
  .bar-foot { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-secondary); margin-top: 5px; flex-wrap: wrap; }
  .bar-foot strong { color: var(--text-primary); font-variant-numeric: tabular-nums; }
  /* La descripción del RAP necesita el ancho entero de la tarjeta lateral:
     apretada junto al código se quedaba en tres palabras y un puntito. */
  .bar-desc { font-size: 12.5px; font-weight: 600; line-height: 1.35; margin: 3px 0 7px;
              display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .bar-comp { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
  .bar-flag { display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: var(--danger); }
  .bar-flag .ic { width: 12px; height: 12px; }
</style>

<script>
const API = '../api/fases_dashboard.php';

// Las cinco clases del reparto, en orden. El orden es el del dato (0 → 100%),
// así que la rampa secuencial se aplica tal cual, sin invertir ni ciclar.
const CLASES = [
  { key: 'sin_iniciar', cls: 'dist-0', label: 'Sin iniciar' },
  { key: 'inicial',     cls: 'dist-1', label: '1–49%' },
  { key: 'medio',       cls: 'dist-2', label: '50–79%' },
  { key: 'avanzado',    cls: 'dist-3', label: '80–99%' },
  { key: 'completo',    cls: 'dist-4', label: 'Fase completa' }
];

let fasesData = [], faseActual = null, fichas = [];

function show(id, visible) { document.getElementById(id).hidden = !visible; }
function fichaActual() { return document.getElementById('sel-ficha').value; }

/** Etiqueta de una ficha en el desplegable. */
function opcionFicha(f) {
  const prog = (f.programa || '').length > 48 ? f.programa.slice(0, 48) + '…' : (f.programa || '');
  const cola = f.fases > 0 ? `${f.aprendices} aprendices` : 'sin proyecto formativo';
  return `<option value="${esc(f.ficha)}">${esc(f.ficha)} — ${esc(prog)} · ${esc(cola)}</option>`;
}

/**
 * Qué ficha abrir al entrar.
 *
 * Entrar a un panel vacío que sólo dice "elige algo" obliga a adivinar, y
 * aquí adivinar mal es fácil: sólo los programas con proyecto formativo
 * cargado tienen algo que mostrar. Por eso el orden es: lo que pide la URL,
 * lo último que miró este usuario, y si no, la primera ficha con datos.
 */
function fichaPreferida() {
  const existe = v => fichas.some(f => f.ficha === v);
  const conDatos = v => fichas.some(f => f.ficha === v && f.fases > 0 && f.aprendices > 0);

  const pedida = new URLSearchParams(location.search).get('ficha');
  if (pedida && existe(pedida)) return pedida;

  const ultima = recordado('fases-ficha');
  if (conDatos(ultima)) return ultima;

  return (fichas.find(f => f.fases > 0 && f.aprendices > 0) ?? fichas[0])?.ficha ?? '';
}

async function init() {
  const sel = document.getElementById('sel-ficha');
  try {
    fichas = await fetch(API + '?action=fichas').then(r => r.json());
  } catch (e) {
    sel.innerHTML = '<option value="">No se pudieron cargar las fichas</option>';
    showToast('No se pudieron cargar las fichas.', 'danger');
    return;
  }

  // Separar las fichas sin proyecto evita la sorpresa de elegir una y caer
  // en el panel vacío sin entender por qué.
  const listas    = fichas.filter(f => f.fases > 0);
  const pendientes = fichas.filter(f => f.fases === 0);

  sel.innerHTML = '<option value="">Selecciona una ficha…</option>'
    + (listas.length ? `<optgroup label="Con proyecto formativo">${listas.map(opcionFicha).join('')}</optgroup>` : '')
    + (pendientes.length ? `<optgroup label="Sin proyecto formativo configurado">${pendientes.map(opcionFicha).join('')}</optgroup>` : '');

  document.getElementById('dist-legend').innerHTML =
    CLASES.map(c => `<span><i class="${c.cls}"></i>${esc(c.label)}</span>`).join('');

  const inicial = fichaPreferida();
  if (inicial) {
    sel.value = inicial;
    loadDashboard();
  }
}

async function loadDashboard() {
  const ficha = fichaActual();
  const hint  = document.getElementById('ficha-hint');

  if (!ficha) {
    show('dashboard-container', false); show('state-empty', false); show('state-idle', true);
    hint.textContent = 'Selecciona una ficha para ver su avance por fases';
    return;
  }

  hint.innerHTML = '<span class="spinner spinner-sm"></span> Cargando…';
  show('state-idle', false);
  recordar('fases-ficha', ficha);

  let payload;
  try {
    payload = await fetch(`${API}?action=estadisticas_fases&ficha=${encodeURIComponent(ficha)}`).then(r => r.json());
  } catch (e) {
    payload = null;
    showToast('Error al consultar las fases de la ficha.', 'danger');
  }

  fasesData = payload?.fases ?? [];

  if (!fasesData.length) {
    // Decir QUÉ programa es y llevar directo a configurarlo: el mensaje
    // genérico dejaba al usuario buscando a mano cuál de los cinco era.
    const f = fichas.find(x => x.ficha === ficha);
    if (f) {
      document.getElementById('empty-detalle').innerHTML = f.aprendices === 0
        ? `La ficha <strong>${esc(ficha)}</strong> no tiene aprendices activos.`
        : `El programa <strong>${esc(f.programa)}</strong> todavía no tiene fases con RAPs asignados.
           En cuanto lo configures, sus ${f.aprendices} aprendices aparecerán aquí.`;
      document.getElementById('empty-cta').href = `fases.php?programa=${encodeURIComponent(f.id_programa)}`;
    }
    show('dashboard-container', false); show('state-empty', true);
    hint.textContent = 'Sin proyecto formativo configurado';
    return;
  }

  show('state-empty', false); show('dashboard-container', true);

  // Sumar los rezagados de las cuatro fases contaria cuatro veces a la misma
  // persona: casi siempre es el mismo grupito el que arrastra todas las fases.
  // Se informa la fase peor parada, que es un dato que sí se sostiene.
  const peor = fasesData.reduce((a, b) => (b.rezagados > a.rezagados ? b : a), fasesData[0]);
  hint.textContent = `${payload.total_aprendices} aprendices activos · ${fasesData.length} fases`
    + (peor.rezagados ? ` · ${peor.rezagados} rezagados en ${peor.nombre_fase}` : ' · sin rezagos');

  renderFases();

  // Se abre en la primera fase con rezagados; si no hay ninguno, en la primera.
  const foco = fasesData.find(f => f.rezagados > 0) ?? fasesData[0];
  selectFase(foco.id_fase);
}

function renderFases() {
  document.getElementById('fases-row').innerHTML = fasesData.map((f, idx) => {
    const total = f.total_aprendices || 1;
    const segmentos = CLASES
      .map(c => ({ ...c, n: f.distribucion[c.key] || 0 }))
      .filter(c => c.n > 0)
      .map(c => `<div class="dist-seg ${c.cls}" style="flex:${c.n}"
                      title="${c.n} de ${f.total_aprendices} — ${esc(c.label)}"></div>`)
      .join('');

    const pie = f.rezagados > 0
      ? `<div class="fase-foot is-alerta">${ic('siren')} ${f.rezagados} rezagado${f.rezagados === 1 ? '' : 's'}</div>`
      : `<div class="fase-foot is-ok">${ic('check-circle')} Grupo parejo</div>`;

    return `
      <button type="button" class="fase-card fade-in ${f.id_fase === faseActual ? 'is-current' : ''}"
              data-fase="${f.id_fase}" onclick="selectFase(${f.id_fase})"
              aria-pressed="${f.id_fase === faseActual}">
        <div class="fase-idx">Fase ${idx + 1}</div>
        <div class="fase-name" title="${esc(f.nombre_fase)}">${esc(f.nombre_fase)}</div>
        <div class="cluster-sm" style="align-items:baseline">
          <span class="fase-pct">${f.pct_avance}%</span>
          <span class="text-xs text-secondary">avance del grupo</span>
        </div>
        <div class="fase-meter"><div class="fase-meter-fill" style="width:${f.pct_avance}%"></div></div>
        <div class="dist-bar">${segmentos || '<div class="dist-seg dist-0" style="flex:1"></div>'}</div>
        ${pie}
        <div class="fase-meta">${f.total_raps} RAPs · ${f.total_aprendices} aprendices · mediana ${f.pct_mediana}%</div>
      </button>`;
  }).join('');
}

async function selectFase(idFase) {
  faseActual = idFase;
  document.querySelectorAll('.fase-card').forEach(el => {
    const activa = Number(el.dataset.fase) === idFase;
    el.classList.toggle('is-current', activa);
    el.setAttribute('aria-pressed', activa);
  });

  const nombre = fasesData.find(f => f.id_fase === idFase)?.nombre_fase ?? '';
  document.getElementById('det-fase-a').textContent = '· ' + nombre;
  document.getElementById('det-fase-b').textContent = '· ' + nombre;
  document.getElementById('det-aprendices-sub').innerHTML = '<span class="spinner spinner-sm"></span> Cargando…';
  document.getElementById('det-raps-sub').innerHTML = '<span class="spinner spinner-sm"></span> Cargando…';

  let d;
  try {
    d = await fetch(`${API}?action=detalle_fase&ficha=${encodeURIComponent(fichaActual())}&id_fase=${idFase}`)
          .then(r => r.json());
  } catch (e) {
    showToast('No se pudo cargar el detalle de la fase.', 'danger');
    return;
  }
  if (d.error) { showToast(d.error, 'danger'); return; }

  renderAprendices(d);
  renderRaps(d);
}

function renderAprendices(d) {
  const rezagados = d.aprendices.filter(a => a.rezagado).length;

  document.getElementById('det-aprendices-sub').innerHTML = rezagados
    ? `<strong>${rezagados}</strong> de ${d.fase.total_aprendices} van por debajo del ${d.fase.umbral_rezago}%,
       la mitad del avance mediano del grupo (${d.fase.pct_mediana}%). El umbral es del grupo, no del calendario:
       una fase que nadie ha empezado no genera avisos.`
    : `Los ${d.fase.total_aprendices} aprendices avanzan a un ritmo parecido (mediana ${d.fase.pct_mediana}%).
       Nadie queda por debajo de la mitad del grupo.`;

  document.getElementById('lista-aprendices').innerHTML = d.aprendices.map(a => `
    <a class="bar-row ${a.rezagado ? 'is-alerta' : ''}"
       href="aprendiz_seguimiento.php?documento=${encodeURIComponent(a.documento)}"
       title="Ver el seguimiento de ${esc(a.nombre)}">
      <div class="bar-head">
        <span class="bar-label truncate">${esc(a.nombre)}</span>
        <span class="bar-val">${a.pct}%</span>
      </div>
      <div class="bar-track"><div class="bar-fill" style="width:${a.pct}%"></div></div>
      <div class="bar-foot">
        <span><strong>${a.aprobados}</strong> de ${d.fase.total_raps} RAPs</span>
        <span class="text-muted">·</span>
        <span><strong>${a.pendientes}</strong> por aprobar</span>
        ${a.rezagado ? `<span class="bar-flag">${ic('siren')} Rezagado</span>` : ''}
      </div>
    </a>`).join('');
}

function renderRaps(d) {
  const sinNadie = d.raps.filter(r => r.aprobados === 0).length;

  document.getElementById('det-raps-sub').innerHTML = sinNadie
    ? `<strong>${sinNadie}</strong> de ${d.fase.total_raps} RAPs no los tiene aprobados <em>nadie</em> del grupo.
       Suele significar que están sin calificar, no que el grupo falle.`
    : `Los ${d.fase.total_raps} RAPs de la fase tienen al menos un aprendiz aprobado.
       Los de arriba son los que menos avanzan.`;

  document.getElementById('lista-raps').innerHTML = d.raps.map(r => `
    <div class="bar-row ${r.aprobados === 0 ? 'is-alerta' : ''}">
      <div class="bar-head">
        <span class="bar-code">${esc(r.codigo)}</span>
        <span class="bar-val" style="margin-left:auto">${r.pct}%</span>
      </div>
      <div class="bar-desc" title="${esc(r.descripcion)}">${esc(r.descripcion)}</div>
      <div class="bar-track"><div class="bar-fill" style="width:${r.pct}%"></div></div>
      <div class="bar-foot">
        <span><strong>${r.aprobados}</strong> de ${d.fase.total_aprendices} aprendices</span>
        ${r.aprobados === 0 ? `<span class="bar-flag">${ic('alert-triangle')} Sin calificar</span>` : ''}
      </div>
      <div class="bar-comp truncate" title="${esc(r.competencia)}">${esc(r.competencia)}</div>
    </div>`).join('');
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
