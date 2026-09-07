<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Resumen general de formación y juicios evaluativos';
$activePage   = 'dashboard';

// El importador vive ahora en un modal: su disparador va al topbar.
require_once ROOT_PATH . '/assets/icons.php';
$pageActions = '
  <button class="btn btn-outline btn-sm" onclick="refreshDashboard()" title="Actualizar datos">'
    . icon('refresh') . '<span class="sr-only">Actualizar</span></button>
  <button class="btn btn-primary" onclick="openModal(\'modal-sofia\')">'
    . icon('upload-cloud') . ' Importar Sofia Plus</button>';

require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ══ TOOLBAR ÚNICA — antes eran dos cards de filtros separados ══ -->
<div class="toolbar is-sticky mb-5">
  <div class="search-field">
    <?= icon('search') ?>
    <input type="text" id="f-search" class="form-control" placeholder="Buscar aprendiz por nombre o documento…"
           oninput="debouncedRefresh()" autocomplete="off" aria-label="Buscar aprendiz" />
  </div>
  <select id="global-prog" class="form-control" style="width:200px" onchange="onProgChange()" aria-label="Filtrar por programa">
    <option value="">Todos los programas</option>
  </select>
  <select id="global-ficha" class="form-control" style="width:170px" onchange="applyGlobalFilters()" aria-label="Filtrar por ficha">
    <option value="">Todas las fichas</option>
  </select>
  <select id="f-avance" class="form-control" style="width:170px" onchange="renderTabla()" aria-label="Filtrar por nivel de avance">
    <option value="">Todo el avance</option>
    <option value="alto">Excelente (80–100%)</option>
    <option value="medio">En proceso (50–79%)</option>
    <option value="bajo">Crítico (0–49%)</option>
  </select>
  <button class="btn btn-ghost btn-sm" id="btn-clear-filters" onclick="clearFilters()" hidden>
    <?= icon('x') ?> Limpiar
  </button>
</div>

<!-- ══ KPIs ══ -->
<div class="kpi-grid mb-5" id="kpi-grid">
  <div class="kpi-card" style="--kpi-color:var(--brand)">
    <div class="kpi-icon"><?= icon('users') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="kpi-aprendices">—</div>
      <div class="kpi-label">Total Aprendices</div>
      <div class="kpi-trend"><span id="kpi-activos" class="trend-up">—</span></div>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:var(--success-solid)">
    <div class="kpi-icon"><?= icon('check-circle') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="kpi-aprobados">—</div>
      <div class="kpi-label">Juicios Aprobados</div>
      <div class="kpi-trend"><span id="kpi-pct-aprob" class="trend-up">—</span> de aprobación</div>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:var(--warning-solid)">
    <div class="kpi-icon"><?= icon('clock') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="kpi-pendientes">—</div>
      <div class="kpi-label">RAPs sin aprobar</div>
      <div class="kpi-trend">Pendiente de etapa lectiva</div>
    </div>
  </div>
  <!-- Sustituye al KPI "No Aprobados": la base no registra ese estado, así
       que marcaba 0 permanentemente. Las fichas atrasadas salen del diagrama
       de ritmo y sí son accionables. -->
  <div class="kpi-card" style="--kpi-color:var(--danger-solid)">
    <div class="kpi-icon"><?= icon('alert-triangle') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="kpi-atrasadas">—</div>
      <div class="kpi-label">Fichas atrasadas</div>
      <div class="kpi-trend" id="kpi-atrasadas-det">respecto a su calendario</div>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:var(--info-solid)">
    <div class="kpi-icon"><?= icon('clipboard-list') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="kpi-fichas">—</div>
      <div class="kpi-label">Fichas Activas</div>
      <div class="kpi-trend"><span id="kpi-competencias">—</span> competencias</div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     RITMO DE LAS FICHAS — avance real contra avance esperado
     Una barra por ficha con la marca de lo esperado. Sustituye al
     scatter con diagonal, que obligaba a medir a ojo la distancia
     a una recta de 45° para extraer el único dato que importa.
     ══════════════════════════════════════════════════════════════ -->
<div class="card mb-5">
  <div class="card-header">
    <div>
      <div class="card-title"><?= icon('activity') ?> Ritmo de las fichas</div>
      <div class="card-subtitle">
        Mide la <strong>etapa lectiva</strong>. La barra es lo aprobado; la marca <span class="ritmo-inline-mark"></span>
        es lo que debería llevar según el tiempo transcurrido. La diferencia es el atraso.
      </div>
    </div>
    <div class="cluster-sm">
      <div class="ritmo-resumen">
        <span class="ritmo-resumen-val" id="ritmo-desvio">—</span>
        <span class="ritmo-resumen-lbl">atraso promedio</span>
      </div>
    </div>
  </div>

  <div class="ritmo-list" id="ritmo-list"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MAPA DE CALOR — la lectura clave son las COLUMNAS
     Una fila roja es un aprendiz con problemas; una columna roja es
     un problema de la competencia o de cómo se está enseñando.
     ══════════════════════════════════════════════════════════════ -->
<div class="card card-flush mb-5">
  <div class="card-header">
    <div>
      <div class="card-title"><?= icon('grid') ?> <span id="hm-titulo">Mapa de calor por competencia</span></div>
      <div class="card-subtitle" id="hm-subtitulo">Cargando…</div>
    </div>
    <div class="hm-legend">
      <span>0%</span>
      <span class="hm-swatches">
        <i style="background:var(--seq-1)"></i><i style="background:var(--seq-2)"></i><i style="background:var(--seq-3)"></i>
        <i style="background:var(--seq-4)"></i><i style="background:var(--seq-5)"></i><i style="background:var(--seq-6)"></i>
      </span>
      <span>100%</span>
    </div>
  </div>
  <div id="heatmap-host" style="padding:0 var(--space-5) var(--space-5)"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     CALIFICACIONES INCOMPLETAS
     Agrupadas por ficha + competencia, que es como se califica en la
     práctica. Antes se listaba RAP por RAP y el mismo hecho aparecía
     repetido cuatro veces con códigos ilegibles.
     ══════════════════════════════════════════════════════════════ -->
<div class="card mb-5">
  <div class="card-header">
    <div>
      <div class="card-title"><?= icon('siren') ?> Calificaciones a medias</div>
      <div class="card-subtitle">
        Competencias que se calificaron a una parte del grupo y quedaron sin terminar.
        No aparecen las que aún no se han empezado.
      </div>
    </div>
    <div class="ritmo-resumen">
      <span class="ritmo-resumen-val is-neg" id="inc-total">—</span>
      <span class="ritmo-resumen-lbl">juicios por registrar</span>
    </div>
  </div>

  <div class="ritmo-list" id="inc-list"></div>

  <div class="empty-state" id="inc-vacio" hidden>
    <div class="empty-icon"><?= icon('check-circle') ?></div>
    <div class="empty-title">Nada a medias</div>
    <p>Ninguna competencia quedó calificada a una parte del grupo. Lo que falta está sin empezar, no a medio evaluar.</p>
  </div>
</div>

<!-- ══ TABLA ══ -->
<div class="card card-flush mb-5">
  <div class="card-header">
    <div>
      <div class="card-title"><?= icon('users') ?> Seguimiento por Aprendiz</div>
      <div class="card-subtitle" id="tabla-count">Cargando…</div>
    </div>
    <a href="fichas.php" class="btn btn-outline btn-sm">Ver todas las fichas <?= icon('arrow-right') ?></a>
  </div>
  <div class="table-wrap is-scrollable">
    <table class="table" id="tabla-aprendices">
      <thead>
        <tr>
          <th>Aprendiz</th><th>Documento</th><th>Ficha</th><th>Programa</th>
          <th>Estado</th><th style="width:160px">Avance</th><th class="col-actions"></th>
        </tr>
      </thead>
      <tbody id="tabla-body"></tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: IMPORTADOR DE REPORTE SOFIA PLUS
     Antes ocupaba toda la primera pantalla del dashboard.
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-sofia">
  <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="sofia-title">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="sofia-title"><?= icon('upload-cloud') ?> Importar Reporte Sofia Plus</div>
        <div class="modal-sub">Carga aprendices, juicios, competencias y resultados de forma automática</div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-sofia')" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>

    <div class="modal-body">
      <div class="dropzone" id="sf-dropzone"
           onclick="document.getElementById('sf-file').click()"
           ondragover="event.preventDefault(); this.classList.add('dragover')"
           ondragleave="this.classList.remove('dragover')"
           ondrop="event.preventDefault(); this.classList.remove('dragover'); handleSofiaFile(event.dataTransfer.files[0])">
        <input type="file" id="sf-file" accept=".xlsx,.xls,.csv" hidden onchange="handleSofiaFile(this.files[0])" />
        <div class="dropzone-icon"><?= icon('upload-cloud') ?></div>
        <div class="dropzone-title">Arrastra aquí el reporte de Sofia Plus</div>
        <div class="dropzone-sub">o haz clic para seleccionarlo — .xlsx / .xls / .csv</div>
      </div>

      <div id="sf-preview" hidden>
        <div class="section-head">
          <div class="section-title"><?= icon('clipboard-list') ?> Datos detectados en el encabezado</div>
        </div>
        <div class="sf-meta-grid mb-4">
          <div><div class="text-xs text-muted">Ficha</div><div class="fw-800 text-brand mono" id="sf-ficha">—</div></div>
          <div><div class="text-xs text-muted">Programa</div><div class="fw-600 text-sm" id="sf-programa">—</div></div>
          <div><div class="text-xs text-muted">Código</div><div class="mono text-sm" id="sf-codigo">—</div></div>
          <div><div class="text-xs text-muted">Estado</div><div class="text-sm" id="sf-estado-ficha">—</div></div>
          <div><div class="text-xs text-muted">Periodo</div><div class="text-sm" id="sf-periodo">—</div></div>
        </div>

        <div class="kpi-grid mb-4">
          <div class="kpi-card" style="--kpi-color:var(--info-solid)">
            <div class="kpi-body"><div class="kpi-value" id="sf-n-aprendices">0</div><div class="kpi-label">Aprendices</div></div>
          </div>
          <div class="kpi-card" style="--kpi-color:var(--success-solid)">
            <div class="kpi-body"><div class="kpi-value" id="sf-n-juicios">0</div><div class="kpi-label">Juicios con valor</div></div>
          </div>
          <div class="kpi-card" style="--kpi-color:var(--warning-solid)">
            <div class="kpi-body"><div class="kpi-value" id="sf-n-comp">0</div><div class="kpi-label">Competencias</div></div>
          </div>
          <div class="kpi-card" style="--kpi-color:var(--brand)">
            <div class="kpi-body"><div class="kpi-value" id="sf-n-res">0</div><div class="kpi-label">Resultados</div></div>
          </div>
        </div>

        <div class="table-wrap" style="max-height:240px">
          <table class="table table-compact" style="font-size:12px">
            <thead><tr><th>Tipo Doc</th><th>Documento</th><th>Nombre</th><th>Apellidos</th><th>Estado</th><th>Competencia</th><th>Resultado</th><th>Juicio</th></tr></thead>
            <tbody id="sf-tbody-preview"></tbody>
          </table>
        </div>
      </div>

      <div id="sf-result" class="mb-0" style="margin-top:16px"></div>
    </div>

    <div class="modal-footer" id="sf-footer" hidden>
      <button class="btn btn-outline" onclick="cancelSofiaImport()"><?= icon('x') ?> Cancelar</button>
      <button class="btn btn-success" id="btn-sf-import" onclick="doSofiaImport()"><?= icon('upload-cloud') ?> Importar todo al sistema</button>
    </div>
  </div>
</div>

<style>
  /* ── Ritmo de las fichas: barra de avance + marca de lo esperado ── */
  .ritmo-resumen { text-align: right; }
  .ritmo-resumen-val {
    display: block; font-size: 24px; font-weight: 800; line-height: 1;
    letter-spacing: -.03em; font-variant-numeric: tabular-nums;
  }
  .ritmo-resumen-lbl { font-size: 10.5px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }

  /* Muestra de la marca dentro del texto explicativo del encabezado */
  .ritmo-inline-mark {
    display: inline-block; width: 2px; height: 11px; vertical-align: -1px;
    background: var(--text-primary); border-radius: 1px; margin: 0 2px;
  }

  .ritmo-list { display: flex; flex-direction: column; gap: 2px; }

  .ritmo-row {
    display: block; padding: 11px 12px;
    border: 1px solid transparent; border-radius: var(--radius-md);
    text-decoration: none; color: inherit;
    transition: background var(--transition), border-color var(--transition);
  }
  .ritmo-row:hover { background: var(--bg-subtle); border-color: var(--border); color: inherit; }

  .ritmo-row-head { display: flex; align-items: baseline; gap: 9px; margin-bottom: 7px; }
  .ritmo-ficha { font-size: 14px; font-weight: 800; letter-spacing: -.01em; }
  .ritmo-prog { font-size: 12px; color: var(--text-secondary); flex: 1; min-width: 0; }
  .ritmo-badge {
    font-size: 11px; font-weight: 700; white-space: nowrap;
    padding: 2px 9px; border-radius: var(--radius-pill);
    border: 1px solid transparent;
  }

  /* Pista 0–100%: la barra es lo real, la marca lo esperado */
  .ritmo-track {
    position: relative; height: 16px;
    background: var(--bg-input);
    border-radius: var(--radius-sm);
    overflow: hidden;
  }
  .ritmo-fill { height: 100%; border-radius: var(--radius-sm) 0 0 var(--radius-sm); transition: width .5s cubic-bezier(.4,0,.2,1); }
  .ritmo-target {
    position: absolute; top: -3px; bottom: -3px; width: 3px;
    background: var(--text-primary);
    border-radius: 2px;
    transform: translateX(-1.5px);
    box-shadow: 0 0 0 2px var(--bg-card);
  }

  /* Colores por estado, tomados de la paleta divergente validada */
  .ritmo-row.is-neg .ritmo-fill  { background: var(--div-neg); }
  .ritmo-row.is-mid .ritmo-fill  { background: var(--div-mid); }
  .ritmo-row.is-pos .ritmo-fill  { background: var(--div-pos); }
  .ritmo-row.is-neg .ritmo-badge { background: var(--danger-soft);  color: var(--danger);  border-color: var(--danger-bd); }
  .ritmo-row.is-mid .ritmo-badge { background: var(--bg-subtle);    color: var(--text-secondary); border-color: var(--border); }
  .ritmo-row.is-pos .ritmo-badge { background: var(--brand-soft);   color: var(--brand-text); border-color: var(--brand-soft-bd); }
  .ritmo-resumen-val.is-neg { color: var(--danger); }
  .ritmo-resumen-val.is-mid { color: var(--text-secondary); }
  .ritmo-resumen-val.is-pos { color: var(--brand-text); }

  .ritmo-row-foot {
    display: flex; align-items: center; flex-wrap: wrap; gap: 5px;
    margin-top: 6px; font-size: 11.5px; color: var(--text-secondary);
  }
  .ritmo-row-foot strong { color: var(--text-primary); font-variant-numeric: tabular-nums; }
  .ritmo-sep { color: var(--text-muted); }

  @media (max-width: 720px) {
    .ritmo-row-head { flex-wrap: wrap; }
    .ritmo-prog { order: 3; flex-basis: 100%; }
  }

  /* ── Calificaciones a medias: agrupadas por ficha ── */
  .inc-group { border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--space-3); }
  .inc-group-head {
    display: flex; align-items: baseline; gap: 10px;
    padding: 9px 12px; background: var(--bg-subtle);
    border-bottom: 1px solid var(--border);
    text-decoration: none; color: inherit;
  }
  .inc-group-head:hover { background: var(--bg-card-hover); color: inherit; }
  .inc-ficha { font-size: 13.5px; font-weight: 800; }
  .inc-prog { font-size: 12px; color: var(--text-secondary); flex: 1; min-width: 0; text-transform: none; }
  .inc-badge {
    font-size: 11px; font-weight: 700; white-space: nowrap;
    padding: 2px 9px; border-radius: var(--radius-pill);
    background: var(--warning-soft); color: var(--warning); border: 1px solid var(--warning-bd);
  }

  .inc-row { padding: 9px 12px; border-bottom: 1px solid var(--border); }
  .inc-row:last-child { border-bottom: none; }
  .inc-comp { font-size: 12.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 5px; }
  /* La parte llena es lo ya calificado; el hueco es el trabajo que queda */
  .inc-track { position: relative; height: 10px; background: var(--warning-soft); border: 1px solid var(--warning-bd); border-radius: var(--radius-sm); overflow: hidden; }
  .inc-fill  { height: 100%; background: var(--success-solid); transition: width .5s cubic-bezier(.4,0,.2,1); }
  .inc-foot {
    display: flex; align-items: center; flex-wrap: wrap; gap: 5px;
    margin-top: 5px; font-size: 11.5px; color: var(--text-secondary);
  }
  .inc-foot strong { color: var(--text-primary); font-variant-numeric: tabular-nums; }

  .sf-meta-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: var(--space-3);
    background: var(--bg-subtle); border: 1px solid var(--border);
    border-radius: var(--radius-md); padding: var(--space-4);
  }
</style>

<script>
const API = '../api/dashboard.php';
let globalParams = '';
let allGlobalFichas = [];
let tablaData = [];

/* ================================================================
   FILTROS
   ================================================================ */
async function init() {
  await loadGlobalFiltersData();
  await refreshDashboard();
}

async function loadGlobalFiltersData() {
  try {
    const d = await fetch(API + '?action=get_filtros_globales').then(r => r.json());
    allGlobalFichas = d.fichas || [];
    document.getElementById('global-prog').innerHTML =
      '<option value="">Todos los programas</option>' +
      (d.programas || []).map(p => `<option value="${esc(p.id_programa)}">${esc(p.nombre)}</option>`).join('');
    renderGlobalFichasOptions();
  } catch (e) { showToast('No se pudieron cargar los filtros.', 'danger'); }
}

function renderGlobalFichasOptions(progId = '') {
  const sel = document.getElementById('global-ficha');
  const prev = sel.value;
  const list = progId ? allGlobalFichas.filter(f => String(f.id_programa) === String(progId)) : allGlobalFichas;
  sel.innerHTML = '<option value="">Todas las fichas</option>' +
    list.map(f => `<option value="${esc(f.ficha)}">${esc(f.ficha)} — ${esc((f.programa || '').substring(0, 28))}</option>`).join('');
  if (list.some(f => String(f.ficha) === prev)) sel.value = prev;
}

function onProgChange() {
  renderGlobalFichasOptions(document.getElementById('global-prog').value);
  applyGlobalFilters();
}

function applyGlobalFilters() {
  const prog   = document.getElementById('global-prog').value;
  const ficha  = document.getElementById('global-ficha').value;
  const search = document.getElementById('f-search').value.trim();
  const avance = document.getElementById('f-avance').value;

  document.getElementById('btn-clear-filters').hidden = !(prog || ficha || search || avance);
  globalParams = `&global_programa=${encodeURIComponent(prog)}&global_ficha=${encodeURIComponent(ficha)}&global_search=${encodeURIComponent(search)}`;
  refreshDashboard();
}

let filterTimer;
function debouncedRefresh() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(applyGlobalFilters, 320);
}

function clearFilters() {
  document.getElementById('global-prog').value = '';
  document.getElementById('f-search').value = '';
  document.getElementById('f-avance').value = '';
  // Vaciar la ficha ANTES de repoblar: renderGlobalFichasOptions conserva
  // la selección previa si sigue estando en la lista.
  document.getElementById('global-ficha').value = '';
  renderGlobalFichasOptions();
  applyGlobalFilters();
}

async function refreshDashboard() {
  showSkeletons();
  await Promise.all([loadKPIs(), loadTabla(), loadRitmo(), loadHeatmap(), loadAtascados()]);
}

function showSkeletons() {
  document.getElementById('tabla-count').innerHTML = '<span class="spinner spinner-sm"></span> Cargando…';
  document.getElementById('tabla-body').innerHTML =
    Array.from({ length: 6 }, () => '<tr><td colspan="7"><span class="skeleton skeleton-row"></span></td></tr>').join('');
}

/* ================================================================
   KPIs
   ================================================================ */
async function loadKPIs() {
  try {
    const d = await fetch(API + '?action=kpis' + globalParams).then(r => r.json());
    const n = v => Number(v || 0).toLocaleString('es-CO');
    document.getElementById('kpi-aprendices').textContent   = n(d.total_aprendices);
    document.getElementById('kpi-activos').textContent      = n(d.activos) + ' activos';
    document.getElementById('kpi-aprobados').textContent    = n(d.aprobados);
    document.getElementById('kpi-pct-aprob').textContent    = d.pct_aprobacion + '%';
    document.getElementById('kpi-pendientes').textContent   = n(d.pendientes);
    document.getElementById('kpi-fichas').textContent       = d.total_fichas;
    document.getElementById('kpi-competencias').textContent = d.total_competencias;
  } catch (e) { showToast('Error al cargar los indicadores.', 'danger'); }
}

/* ================================================================
   RITMO DE LAS FICHAS — avance real vs. esperado
   Se dibuja en HTML, no en canvas: con seis fichas el texto directo
   se lee mejor que un gráfico, hereda el tema por CSS y es accesible.
   ================================================================ */
let ritmoData = [];

/** ±10 puntos alrededor de lo esperado se consideran "en ritmo". */
const RITMO_TOLERANCIA = 10;

async function loadRitmo() {
  try {
    ritmoData = await fetch(API + '?action=ritmo_fichas' + globalParams).then(r => r.json());
  } catch (e) { ritmoData = []; }
  renderRitmo();
}

function ritmoEstado(desvio) {
  if (desvio >  RITMO_TOLERANCIA) return { cls: 'is-pos', txt: 'adelantada' };
  if (desvio < -RITMO_TOLERANCIA) return { cls: 'is-neg', txt: 'atrasada' };
  return { cls: 'is-mid', txt: 'en ritmo' };
}

function renderRitmo() {
  const cont = document.getElementById('ritmo-list');
  const atrasadas = ritmoData.filter(f => f.desvio < -RITMO_TOLERANCIA);

  // El KPI global se alimenta del mismo cálculo
  document.getElementById('kpi-atrasadas').textContent = atrasadas.length;
  document.getElementById('kpi-atrasadas-det').textContent =
    ritmoData.length ? `de ${ritmoData.length} fichas con calendario` : 'sin fichas con calendario';

  const medio = ritmoData.length
    ? Math.round(ritmoData.reduce((s, f) => s + f.desvio, 0) / ritmoData.length)
    : 0;
  const elMedio = document.getElementById('ritmo-desvio');
  elMedio.textContent = medio + ' pts';
  elMedio.className = 'ritmo-resumen-val ' + ritmoEstado(medio).cls;

  if (!ritmoData.length) {
    cont.innerHTML = `<div class="empty-state">
      <div class="empty-icon">${ic('activity')}</div>
      <div class="empty-title">Sin fichas con calendario</div>
      <p>No hay fichas con fechas de inicio y fin válidas para los filtros aplicados.</p>
    </div>`;
    return;
  }

  // Peor primero: lo que necesita atención queda arriba
  const filas = [...ritmoData].sort((a, b) => a.desvio - b.desvio);

  cont.innerHTML = filas.map(f => {
    const est = ritmoEstado(f.desvio);
    // `dias_restantes` apunta al cierre de la LECTIVA, no al de la ficha:
    // fecha_fin de Sofia Plus incluye los 6 meses de etapa productiva.
    const plazo = f.en_productiva
      ? `en etapa productiva desde ${f.fecha_fin_lectiva}`
      : `${f.dias_restantes} días de lectiva`;

    return `<a class="ritmo-row ${est.cls}" href="ficha_detalle.php?ficha=${encodeURIComponent(f.ficha)}">
      <div class="ritmo-row-head">
        <span class="ritmo-ficha mono">${esc(f.ficha)}</span>
        <span class="ritmo-prog truncate" title="${esc(f.programa)}">${esc(f.programa)}</span>
        <span class="ritmo-badge">${f.desvio > 0 ? '+' : ''}${Math.round(f.desvio)} pts · ${est.txt}</span>
      </div>

      <div class="ritmo-track" role="img"
           aria-label="Ficha ${esc(f.ficha)}: ${f.pct_avance}% aprobado frente al ${f.pct_calendario}% esperado">
        <div class="ritmo-fill" style="width:${f.pct_avance}%"></div>
        <div class="ritmo-target" style="left:${f.pct_calendario}%"></div>
      </div>

      <div class="ritmo-row-foot">
        <span><strong>${f.pct_avance}%</strong> aprobado</span>
        <span class="ritmo-sep">·</span>
        <span>debería ir por <strong>${f.pct_calendario}%</strong></span>
        <span class="ritmo-sep">·</span>
        <span>${f.aprobados.toLocaleString('es-CO')} de ${f.esperado.toLocaleString('es-CO')} RAPs</span>
        <span class="push-right">${esc(plazo)}</span>
      </div>
    </a>`;
  }).join('');
}

/* ================================================================
   MAPA DE CALOR — adaptativo según haya ficha seleccionada o no
   ================================================================ */
let heatmapData = null;

async function loadHeatmap() {
  const host  = document.getElementById('heatmap-host');
  const prog  = document.getElementById('global-prog').value;
  const ficha = document.getElementById('global-ficha').value;

  // Sin filtro, la rejilla sería de 57 competencias: cada ficha sólo tiene
  // las de su programa, así que más del 70% de las celdas saldrían vacías y
  // el mapa dejaría de leerse. Se pide un filtro en lugar de dibujar ruido.
  if (!prog && !ficha) {
    heatmapData = null;
    document.getElementById('hm-titulo').textContent = 'Mapa de calor por competencia';
    document.getElementById('hm-subtitulo').textContent = 'Requiere acotar a un programa o una ficha';
    host.innerHTML = `<div class="empty-state">
      <div class="empty-icon">${ic('grid')}</div>
      <div class="empty-title">Elige un programa o una ficha</div>
      <p>Cada programa tiene sus propias competencias. Sin acotar, el mapa mezclaría las
         de todos los programas y quedaría casi vacío.</p>
      <button class="btn btn-outline" onclick="document.getElementById('global-prog').focus()">
        ${ic('filter')} Filtrar por programa
      </button>
    </div>`;
    return;
  }

  host.innerHTML = '<span class="skeleton" style="height:180px;border-radius:var(--radius-md)"></span>';
  try {
    heatmapData = await fetch(API + '?action=heatmap' + globalParams).then(r => r.json());
  } catch (e) { heatmapData = null; }
  renderHeatmap();
}

/** 0 → clase hm-0; 1..100 repartido en seis pasos de la rampa. */
function hmClase(pct, sinDatos) {
  if (sinDatos) return 'hm-na';
  if (pct <= 0)  return 'hm-0';
  if (pct <= 20) return 'hm-1';
  if (pct <= 40) return 'hm-2';
  if (pct <= 60) return 'hm-3';
  if (pct <= 80) return 'hm-4';
  if (pct < 100) return 'hm-5';
  return 'hm-6';
}

function renderHeatmap() {
  const host = document.getElementById('heatmap-host');
  const porAprendiz = heatmapData?.modo === 'aprendiz';

  document.getElementById('hm-titulo').textContent =
    porAprendiz ? 'Aprendiz × Competencia' : 'Ficha × Competencia';

  if (!heatmapData || !heatmapData.filas.length || !heatmapData.columnas.length) {
    document.getElementById('hm-subtitulo').textContent = 'Sin datos para el filtro actual';
    host.innerHTML = `<div class="empty-state">
      <div class="empty-icon">${ic('grid')}</div>
      <div class="empty-title">Nada que mostrar</div>
      <p>No hay aprendices activos ni competencias asociadas con los filtros aplicados.</p>
    </div>`;
    return;
  }

  const { filas, columnas, celdas } = heatmapData;
  document.getElementById('hm-subtitulo').innerHTML = porAprendiz
    ? `${filas.length} aprendices × ${columnas.length} competencias — <strong>una fila roja es un aprendiz en riesgo; una columna roja es un problema de la competencia</strong>`
    : `${filas.length} fichas × ${columnas.length} competencias — selecciona una ficha en el filtro para bajar al detalle por aprendiz`;

  // Índice de acceso rápido fila→columna
  const mapa = new Map();
  celdas.forEach(c => mapa.set(c.fila + '|' + c.col, c));

  // Promedio por competencia: la fila de totales del pie
  const promedios = columnas.map(col => {
    const props = celdas.filter(c => c.col === col.id);
    const tot = props.reduce((s, c) => s + c.total, 0);
    const apr = props.reduce((s, c) => s + c.aprobados, 0);
    return tot > 0 ? Math.round(apr / tot * 100) : 0;
  });

  const head = columnas.map(c =>
    `<th title="${esc(c.nombre)}"><abbr style="text-decoration:none" title="${esc(c.nombre)}">${esc(c.codigo)}</abbr></th>`
  ).join('');

  const body = filas.map(fila => {
    const cells = columnas.map(col => {
      const c = mapa.get(fila.id + '|' + col.id);
      if (!c) return `<td class="hm-cell hm-na" title="Sin RAPs asociados"></td>`;
      const tip = `${fila.nombre} — ${col.nombre}: ${c.aprobados} de ${c.total} RAPs (${c.pct}%)`;
      return `<td class="hm-cell ${hmClase(c.pct, false)}" title="${esc(tip)}">${c.pct}</td>`;
    }).join('');
    const enlace = porAprendiz
      ? `<a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(document.getElementById('global-ficha').value)}&documento=${encodeURIComponent(fila.id)}">${esc(fila.nombre)}</a>`
      : `<a href="ficha_detalle.php?ficha=${encodeURIComponent(fila.id)}" class="mono">${esc(fila.nombre)}</a>`;
    return `<tr><th title="${esc(fila.nombre)}">${enlace}</th>${cells}</tr>`;
  }).join('');

  const foot = promedios.map((p, i) =>
    `<td class="hm-cell ${hmClase(p, false)}" title="${esc(columnas[i].nombre)}: ${p}% promedio del grupo">${p}</td>`
  ).join('');

  host.innerHTML = `
    <div class="heatmap-scroll">
      <table class="heatmap">
        <thead><tr><th class="hm-corner">${porAprendiz ? 'Aprendiz' : 'Ficha'}</th>${head}</tr></thead>
        <tbody>${body}</tbody>
        <tfoot><tr><th>Promedio del grupo</th>${foot}</tr></tfoot>
      </table>
    </div>`;
}

/* ================================================================
   CALIFICACIONES A MEDIAS
   Lista accionable en HTML, no un gráfico: aquí el lector no compara
   magnitudes, va a ir a terminar de calificar algo. Agrupada por
   ficha porque sin agrupar se mezclaban competencias de programas
   distintos y no se sabía de qué grupo hablaba cada fila.
   ================================================================ */
let atascadosData = [];

async function loadAtascados() {
  try {
    atascadosData = await fetch(API + '?action=raps_atascados' + globalParams).then(r => r.json());
  } catch (e) { atascadosData = []; }
  renderAtascados();
}

function renderAtascados() {
  const cont  = document.getElementById('inc-list');
  const vacio = document.getElementById('inc-vacio');

  // Que no haya nada aquí es buena noticia, no un fallo
  if (!atascadosData.length) {
    cont.innerHTML = '';
    cont.hidden = true;
    vacio.hidden = false;
    document.getElementById('inc-total').textContent = '0';
    return;
  }
  cont.hidden = false;
  vacio.hidden = true;

  const total = atascadosData.reduce((s, g) => s + g.faltantes, 0);
  document.getElementById('inc-total').textContent = total.toLocaleString('es-CO');

  cont.innerHTML = atascadosData.map(g => `
    <section class="inc-group">
      <a class="inc-group-head" href="ficha_detalle.php?ficha=${encodeURIComponent(g.ficha)}">
        <span class="inc-ficha mono">${esc(g.ficha)}</span>
        <span class="inc-prog truncate" title="${esc(g.programa)}">${esc(g.programa)}</span>
        <span class="inc-badge">${g.faltantes} por registrar</span>
      </a>
      ${g.items.map(it => {
        // Si los RAPs de la competencia no van todos igual, se muestra el rango
        const hechos = it.uniforme ? `${it.aprob_min}` : `${it.aprob_min}–${it.aprob_max}`;
        return `<div class="inc-row">
          <div class="inc-comp truncate" title="${esc(it.competencia)}">${esc(it.competencia)}</div>
          <div class="inc-track" role="img"
               aria-label="${esc(it.competencia)}: calificados ${hechos} de ${it.aprendices} aprendices, faltan ${it.faltantes} juicios">
            <div class="inc-fill" style="width:${it.pct_hecho}%"></div>
          </div>
          <div class="inc-foot">
            <span>calificados <strong>${hechos}</strong> de <strong>${it.aprendices}</strong></span>
            <span class="ritmo-sep">·</span>
            <span><strong>${it.raps}</strong> RAP${it.raps === 1 ? '' : 's'}</span>
            <span class="push-right"><strong>${it.faltantes}</strong> juicios</span>
          </div>
        </div>`;
      }).join('')}
    </section>`).join('');
}

/* ================================================================
   TABLA
   ================================================================ */
async function loadTabla() {
  const params = new URLSearchParams({ action: 'tabla_aprendices' });
  try {
    tablaData = await fetch(API + '?' + params + globalParams).then(r => r.json());
  } catch (e) { tablaData = []; }
  renderTabla();
}

function renderTabla() {
  const tbody = document.getElementById('tabla-body');
  const avance = document.getElementById('f-avance').value;
  document.getElementById('btn-clear-filters').hidden =
    !(document.getElementById('global-prog').value || document.getElementById('global-ficha').value ||
      document.getElementById('f-search').value.trim() || avance);

  let data = tablaData;
  if (avance) {
    data = data.filter(a => {
      const pct = a.total_resultados > 0 ? Math.round(a.aprobados / a.total_resultados * 100) : 0;
      return avance === 'alto' ? pct >= 80 : avance === 'medio' ? (pct >= 50 && pct < 80) : pct < 50;
    });
  }

  document.getElementById('tabla-count').textContent =
    `${data.length} aprendiz${data.length === 1 ? '' : 'es'}` + (tablaData.length >= 100 ? ' (primeros 100)' : '');

  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
      <div class="empty-icon">${ic('search')}</div>
      <div class="empty-title">Sin resultados</div>
      <p>Ningún aprendiz coincide con los filtros aplicados.</p>
    </div></td></tr>`;
    return;
  }

  tbody.innerHTML = data.map(a => {
    const pct   = a.total_resultados > 0 ? Math.round(a.aprobados / a.total_resultados * 100) : 0;
    const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';
    const prog  = (a.programa || '').length > 38 ? a.programa.substring(0, 38) + '…' : (a.programa || '');
    return `<tr>
      <td class="fw-600">${esc(a.nombre + ' ' + a.apellidos)}</td>
      <td class="mono text-secondary">${esc(a.documento)}</td>
      <td class="mono">${esc(a.ficha)}</td>
      <td class="text-sm text-secondary" title="${esc(a.programa)}">${esc(prog)}</td>
      <td><span class="badge ${badgeEstado(a.estado)}">${esc(a.estado)}</span></td>
      <td>
        <div class="progress-bar-wrap"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
        <div class="progress-label"><span>${pct}%</span><span>${a.aprobados}/${a.total_resultados}</span></div>
      </td>
      <td class="col-actions">
        <a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(a.ficha)}&documento=${encodeURIComponent(a.documento)}"
           class="btn btn-outline btn-sm">${ic('trending-up')} Avance</a>
      </td>
    </tr>`;
  }).join('');
}

/* ================================================================
   IMPORTADOR SOFIA PLUS
   ================================================================ */
let sfParsedMeta = {};
let sfParsedRows = [];

function handleSofiaFile(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    try {
      const wb = XLSX.read(e.target.result, { type: 'binary', cellDates: true });
      const ws = wb.Sheets[wb.SheetNames[0]];
      parseSofiaReport(XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', blankrows: true }));
    } catch (err) {
      showToast('No se pudo leer el archivo. ¿Es un Excel válido?', 'danger');
    }
  };
  reader.readAsBinaryString(file);
}

function parseSofiaReport(allRows) {
  // ── 1. Buscar la fila de encabezados de datos ──
  let headerRowIdx = -1;
  for (let i = 0; i < Math.min(allRows.length, 30); i++) {
    const row = allRows[i].map(c => String(c).toLowerCase().trim());
    if (row.some(c => c.includes('tipo') && (c.includes('documento') || c.includes('doc'))) ||
        row.some(c => c.includes('número') || c.includes('numero') || c === 'número de documento')) {
      headerRowIdx = i;
      break;
    }
  }

  if (headerRowIdx === -1) {
    showToast('No se pudo detectar la fila de encabezados. Verifica que sea el reporte correcto de Sofia Plus.', 'danger', 7000);
    return;
  }

  // ── 2. Extraer metadatos del encabezado ──
  const meta = {};
  for (let i = 0; i < headerRowIdx; i++) {
    const row = allRows[i];
    if (!row || !row.length) continue;

    for (let j = 0; j < row.length; j++) {
      const cellText = String(row[j] || '').trim().toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/:/g, '');
      if (!cellText) continue;

      let nextVal = '';
      for (let k = j + 1; k < row.length; k++) {
        if (String(row[k] || '').trim() !== '') {
          nextVal = row[k] instanceof Date ? row[k].toISOString().split('T')[0] : String(row[k] || '').trim();
          break;
        }
      }
      if (!nextVal) continue;

      if (cellText.includes('ficha') && cellText.includes('caracterizacion') && !cellText.includes('estado')) meta.ficha = nextVal;
      else if (cellText === 'codigo' || cellText === 'cogigo') meta.codigo_programa = nextVal; // Sofia Plus escribe "Cógigo"
      else if (cellText === 'version') meta.version = nextVal;
      else if (cellText.includes('denominacion')) meta.nombre_programa = nextVal;
      else if (cellText.includes('estado') && cellText.includes('ficha')) meta.estado_ficha = nextVal;
      else if (cellText.includes('fecha') && cellText.includes('inicio')) meta.fecha_inicio = nextVal;
      else if (cellText.includes('fecha') && cellText.includes('fin')) meta.fecha_fin = nextVal;
      else if (cellText.includes('modalidad')) meta.modalidad = nextVal;
    }
  }

  // Fallback ficha: buscar un número de 6-8 dígitos en la cabecera
  if (!meta.ficha) {
    for (let i = 0; i < headerRowIdx; i++) {
      allRows[i].forEach(c => { const s = String(c).trim(); if (/^\d{6,8}$/.test(s)) meta.ficha = s; });
    }
  }

  // ── 3. Mapear columnas y filas de datos ──
  const colHeaders = allRows[headerRowIdx].map(c => String(c).trim().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, ''));
  const findCol = (...keys) => colHeaders.findIndex(h => keys.some(k => h.includes(k)));

  const cols = {
    tipo_doc   : findCol('tipo de documento', 'tipo documento', 'tipo doc'),
    num_doc    : findCol('número de documento', 'numero de documento', 'num doc', 'número doc'),
    nombre     : findCol('nombre'),
    apellidos  : findCol('apellidos', 'apellido'),
    estado     : findCol('estado'),
    competencia: findCol('competencia'),
    resultado  : findCol('resultado de aprendizaje', 'resultado aprendizaje', 'resultado'),
    juicio     : findCol('juicio de evaluaci', 'juicio evaluacion', 'juicio'),
    fecha_j    : findCol('fecha y hora', 'fecha juicio', 'fecha'),
    funcionario: findCol('funcionario', 'instructor'),
  };

  // "nombre" puede coincidir con "nombre del programa" — evitarlo
  if (cols.nombre >= 0 && colHeaders[cols.nombre].includes('programa')) {
    cols.nombre = colHeaders.findIndex(h => h === 'nombre' || (h.includes('nombre') && !h.includes('programa')));
  }

  const dataRows = allRows.slice(headerRowIdx + 1).filter(r =>
    r.some(c => String(c).trim() !== '') && String(r[cols.num_doc] ?? '').trim() !== ''
  );

  sfParsedMeta = meta;
  sfParsedRows = dataRows.map(r => {
    const get = idx => {
      if (idx < 0) return '';
      const val = r[idx];
      if (val instanceof Date) {
        const p = n => String(n).padStart(2, '0');
        return `${val.getFullYear()}-${p(val.getMonth() + 1)}-${p(val.getDate())} ${p(val.getHours())}:${p(val.getMinutes())}:${p(val.getSeconds())}`;
      }
      return String(val ?? '').trim();
    };
    return {
      tipo_documento       : get(cols.tipo_doc),
      numero_documento     : get(cols.num_doc),
      nombre               : get(cols.nombre),
      apellidos            : get(cols.apellidos),
      estado               : get(cols.estado),
      competencia          : get(cols.competencia),
      resultado_aprendizaje: get(cols.resultado),
      juicio_evaluacion    : get(cols.juicio),
      fecha_juicio         : get(cols.fecha_j),
      funcionario          : get(cols.funcionario),
    };
  });

  showSofiaPreview();
}

function showSofiaPreview() {
  const meta = sfParsedMeta, rows = sfParsedRows;

  const fichaEl = document.getElementById('sf-ficha');
  fichaEl.textContent = meta.ficha || 'No detectada';
  fichaEl.classList.toggle('text-danger', !meta.ficha);
  fichaEl.classList.toggle('text-brand', !!meta.ficha);

  document.getElementById('sf-programa').textContent     = meta.nombre_programa || '—';
  document.getElementById('sf-codigo').textContent       = (meta.codigo_programa || '—') + (meta.version ? ' v' + meta.version : '');
  document.getElementById('sf-estado-ficha').textContent = meta.estado_ficha || '—';
  document.getElementById('sf-periodo').textContent      = (meta.fecha_inicio || '—') + ' → ' + (meta.fecha_fin || '—');

  document.getElementById('sf-n-aprendices').textContent = new Set(rows.map(r => r.numero_documento).filter(Boolean)).size;
  document.getElementById('sf-n-juicios').textContent    = rows.filter(r => r.juicio_evaluacion).length;
  document.getElementById('sf-n-comp').textContent       = new Set(rows.map(r => r.competencia).filter(Boolean)).size;
  document.getElementById('sf-n-res').textContent        = new Set(rows.map(r => r.resultado_aprendizaje).filter(Boolean)).size;

  const judgeColor = { 'Aprobado': 'badge-success', 'No Aprobado': 'badge-danger', 'Aprobacion': 'badge-success' };
  document.getElementById('sf-tbody-preview').innerHTML = rows.slice(0, 50).map(r => `
    <tr>
      <td style="white-space:nowrap">${esc(r.tipo_documento.substring(0, 12))}</td>
      <td class="mono">${esc(r.numero_documento)}</td>
      <td>${esc(r.nombre)}</td>
      <td>${esc(r.apellidos)}</td>
      <td><span class="badge badge-muted">${esc(r.estado)}</span></td>
      <td class="text-secondary truncate" style="max-width:150px" title="${esc(r.competencia)}">${esc(r.competencia)}</td>
      <td class="text-secondary truncate" style="max-width:150px" title="${esc(r.resultado_aprendizaje)}">${esc(r.resultado_aprendizaje)}</td>
      <td>${r.juicio_evaluacion
            ? `<span class="badge ${judgeColor[r.juicio_evaluacion] || 'badge-info'}">${esc(r.juicio_evaluacion)}</span>`
            : '<span class="text-muted text-xs">—</span>'}</td>
    </tr>`).join('');

  document.getElementById('sf-preview').hidden  = false;
  document.getElementById('sf-footer').hidden   = false;
  document.getElementById('sf-dropzone').hidden = true;
}

async function doSofiaImport() {
  if (!sfParsedMeta.ficha) {
    showToast('No se detectó la ficha en el encabezado. Verifica el archivo.', 'warning', 6000);
    return;
  }
  const btn = document.getElementById('btn-sf-import');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Importando…';

  const res = document.getElementById('sf-result');
  try {
    const r = await fetch('../api/importar_reporte.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ficha_meta: sfParsedMeta, rows: sfParsedRows })
    }).then(r => r.json());

    if (r.error) {
      res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error: ${esc(r.error)}</div></div>`;
    } else {
      res.innerHTML = `<div class="alert alert-success">${ic('check-circle')}<div>
          <strong>Importación completada</strong> — Ficha <strong>${esc(sfParsedMeta.ficha)}</strong><br>
          Aprendices: ${r.aprendices.insertados} nuevos · ${r.aprendices.actualizados} actualizados<br>
          Competencias: ${r.competencias.insertadas} nuevas · ${r.competencias.ya_existian} ya existían<br>
          Resultados: ${r.resultados.insertados} nuevos<br>
          Juicios: ${r.juicios.insertados} insertados · ${r.juicios.actualizados} actualizados · ${r.juicios.sin_juicio} sin valor
          ${r.errores?.length ? '<br><strong>Avisos:</strong> ' + esc(r.errores.slice(0, 3).join(' | ')) : ''}
        </div></div>`;
      cancelSofiaImport();
      showToast('Reporte importado correctamente.', 'success');
      await loadGlobalFiltersData();
      await refreshDashboard();
    }
  } catch (e) {
    res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error de red durante la importación.</div></div>`;
  }

  btn.disabled = false;
  btn.innerHTML = ic('upload-cloud') + ' Importar todo al sistema';
}

function cancelSofiaImport() {
  document.getElementById('sf-preview').hidden  = true;
  document.getElementById('sf-footer').hidden   = true;
  document.getElementById('sf-dropzone').hidden = false;
  document.getElementById('sf-file').value = '';
  sfParsedMeta = {}; sfParsedRows = [];
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
