<?php
define('ROOT_PATH', dirname(__DIR__));
$ficha     = $_GET['ficha'] ?? '';
$documento = $_GET['documento'] ?? '';

if (!$ficha || !$documento) { header('Location: fichas.php'); exit; }

$pageTitle    = 'Seguimiento de Aprendiz';
$pageSubtitle = 'Detalle individual de juicios y avance por competencia';
$activePage   = 'aprendices';

require_once ROOT_PATH . '/assets/icons.php';
$pageActions = '<a href="ficha_detalle.php?ficha=' . urlencode($ficha) . '" class="btn btn-outline btn-sm">'
             . icon('arrow-left') . ' Volver a la ficha ' . htmlspecialchars($ficha) . '</a>';

require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ══ IDENTIDAD DEL APRENDIZ ══ -->
<div class="card ap-header mb-5">
  <div class="ap-avatar" id="ap-avatar">—</div>
  <div class="flex-1">
    <div class="cluster-sm">
      <h2 class="ap-title" id="ap-nombre-completo">Cargando aprendiz…</h2>
      <span id="ap-estado-badge"></span>
    </div>
    <div class="cluster text-sm text-secondary" style="margin-top:4px">
      <span><?= icon('user') ?> <span class="mono"><?= htmlspecialchars($documento) ?></span></span>
      <span><?= icon('school') ?> Ficha <span class="mono"><?= htmlspecialchars($ficha) ?></span></span>
      <span id="ap-programa"></span>
    </div>
  </div>
  <div class="ap-global">
    <div class="ap-global-val" id="av-pct-global">0%</div>
    <div class="ap-global-lbl">Avance global</div>
  </div>
</div>

<!-- ══ KPIs — actúan como filtros de la tabla ══ -->
<div class="kpi-grid mb-5" id="kpi-filters">
  <button type="button" class="kpi-card" data-filter="Aprobado" style="--kpi-color:var(--success-solid)" onclick="setFilter('Aprobado')">
    <div class="kpi-icon"><?= icon('check-circle') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="av-aprobados">0</div>
      <div class="kpi-label">Resultados aprobados</div>
    </div>
  </button>
  <button type="button" class="kpi-card" data-filter="No Aprobado" style="--kpi-color:var(--danger-solid)" onclick="setFilter('No Aprobado')">
    <div class="kpi-icon"><?= icon('x-circle') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="av-noaprobados">0</div>
      <div class="kpi-label">No aprobados</div>
    </div>
  </button>
  <button type="button" class="kpi-card" data-filter="Por evaluar" style="--kpi-color:var(--warning-solid)" onclick="setFilter('Por evaluar')">
    <div class="kpi-icon"><?= icon('clock') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="av-pendientes-k">0</div>
      <div class="kpi-label">Por evaluar</div>
    </div>
  </button>
  <button type="button" class="kpi-card is-active" data-filter="all" style="--kpi-color:var(--brand)" onclick="setFilter('all')">
    <div class="kpi-icon"><?= icon('target') ?></div>
    <div class="kpi-body">
      <div class="kpi-value" id="av-total">0</div>
      <div class="kpi-label">Ver todos los RAPs</div>
    </div>
  </button>
</div>

<div class="grid-main-aside">
  <!-- ── Tabla de resultados ── -->
  <div class="card card-flush">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('clipboard-list') ?> Resultados de Aprendizaje</div>
        <div class="card-subtitle" id="tabla-sub">Listado completo de juicios registrados</div>
      </div>
      <div class="cluster-sm">
        <span class="badge badge-brand" id="filter-chip" hidden></span>
        <div class="search-field" style="width:220px;flex:none">
          <?= icon('search') ?>
          <input type="text" id="av-search-input" class="form-control" placeholder="Buscar RAP o código…"
                 oninput="renderTable()" aria-label="Buscar resultado de aprendizaje">
        </div>
      </div>
    </div>
    <div class="table-wrap is-scrollable">
      <table class="table">
        <thead>
          <tr><th>Competencia / Resultado</th><th style="width:130px">Estado</th><th style="width:180px">Registro</th></tr>
        </thead>
        <tbody id="av-detalle-body">
          <tr><td colspan="3"><span class="skeleton skeleton-row"></span></td></tr>
          <tr><td colspan="3"><span class="skeleton skeleton-row"></span></td></tr>
          <tr><td colspan="3"><span class="skeleton skeleton-row"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Panel lateral ── -->
  <div class="stack-lg">
    <div class="card">
      <div class="card-header mb-3">
        <div class="card-title"><?= icon('chart-pie') ?> Distribución de juicios</div>
      </div>
      <div class="chart-container" style="height:200px"><canvas id="chart-dist"></canvas></div>
    </div>

    <div class="card">
      <div class="card-header mb-3">
        <div>
          <div class="card-title"><?= icon('activity') ?> Avance por competencia</div>
          <div class="card-subtitle">RAPs aprobados sobre el total</div>
        </div>
      </div>
      <div class="stack-sm" id="av-comp-list"></div>
    </div>
  </div>
</div>

<style>
  .ap-header { display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap; border-left: 3px solid var(--brand); }
  .ap-avatar {
    width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--brand), var(--green));
    color: #fff; font-weight: 800; font-size: 18px;
    display: flex; align-items: center; justify-content: center;
  }
  .ap-title { font-size: 19px; font-weight: 800; letter-spacing: -.02em; }
  .ap-header .cluster .ic { width: 14px; height: 14px; color: var(--text-muted); }
  .ap-global { text-align: right; padding-left: var(--space-4); border-left: 1px solid var(--border); }
  .ap-global-val { font-size: 32px; font-weight: 800; line-height: 1; color: var(--brand-text); letter-spacing: -.03em; }
  .ap-global-lbl { font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }
  @media (max-width: 720px) { .ap-global { border-left: none; padding-left: 0; text-align: left; } }

  #kpi-filters .kpi-card { text-align: left; font: inherit; }
  #kpi-filters .kpi-card.is-active { border-color: var(--kpi-color); box-shadow: var(--shadow-ring); }

  .comp-row-name {
    font-size: 12px; font-weight: 600;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  .comp-group-row td {
    background: var(--bg-subtle); font-weight: 700; font-size: 11.5px;
    color: var(--brand-text); text-transform: uppercase; letter-spacing: .04em;
    position: sticky; top: 33px; z-index: 1;
  }
  .rap-cell { padding-left: 28px !important; }
</style>

<script>
const FICHA = <?= json_encode($ficha) ?>;
const DOC   = <?= json_encode($documento) ?>;
const API   = '../api/competencias.php';

let rawData = [], compData = [], currentFilter = 'all', chartDist = null;

async function init() {
  try {
    const [comp, detalle, aprendices] = await Promise.all([
      fetch(API + '?action=avance_aprendiz&documento=' + encodeURIComponent(DOC)).then(r => r.json()),
      fetch(API + '?action=detalle_aprendiz&documento=' + encodeURIComponent(DOC)).then(r => r.json()),
      fetch('../api/ficha_detalle.php?action=aprendices&ficha=' + encodeURIComponent(FICHA)).then(r => r.json())
    ]);

    const aprendiz = aprendices.find(a => a.documento === DOC);
    if (aprendiz) {
      const nombre = `${aprendiz.nombre} ${aprendiz.apellidos}`;
      document.getElementById('ap-nombre-completo').textContent = nombre;
      document.getElementById('ap-avatar').textContent = iniciales(aprendiz.nombre, aprendiz.apellidos);
      
      document.getElementById('ap-estado-badge').innerHTML =
        `<span class="badge ${badgeEstado(aprendiz.estado)}">${esc(aprendiz.estado)}</span>`;
      if (aprendiz.programa) {
        document.getElementById('ap-programa').innerHTML = ic('book') + ' ' + esc(aprendiz.programa);
      }
    } else {
      document.getElementById('ap-nombre-completo').textContent = 'Aprendiz no encontrado en esta ficha';
    }

    rawData  = detalle;
    compData = comp;
    updateKPIs(detalle);
    renderTable();
    renderDistChart();
    renderCompList();
  } catch (e) {
    showToast('No se pudo cargar el seguimiento del aprendiz.', 'danger');
  }
}

function iniciales(n, a) {
  return ((n || '').trim()[0] || '') + ((a || '').trim()[0] || '') || '—';
}

/** Normaliza el juicio a una de las cuatro categorías del sistema. */
function juicioDe(d) {
  const j = (d.juicio || '').trim().toLowerCase();
  if (j === 'aprobado') return 'Aprobado';
  if (j === 'no aprobado') return 'No Aprobado';
  if (j === '' || j === 'null' || j === 'por evaluar') return 'Por evaluar';
  return d.juicio.trim();
}

const JUICIO_BADGE = { 'Aprobado': 'badge-success', 'No Aprobado': 'badge-danger', 'Por evaluar': 'badge-warning' };

function updateKPIs(data) {
  const cuenta = t => data.filter(d => juicioDe(d) === t).length;
  const aprob = cuenta('Aprobado');
  document.getElementById('av-aprobados').textContent    = aprob;
  document.getElementById('av-noaprobados').textContent  = cuenta('No Aprobado');
  document.getElementById('av-pendientes-k').textContent = cuenta('Por evaluar');
  document.getElementById('av-total').textContent        = data.length;
  document.getElementById('av-pct-global').textContent   = (data.length ? Math.round(aprob / data.length * 100) : 0) + '%';
}

function setFilter(f) {
  currentFilter = f;
  document.querySelectorAll('#kpi-filters .kpi-card').forEach(c =>
    c.classList.toggle('is-active', c.dataset.filter === f));

  const chip = document.getElementById('filter-chip');
  chip.hidden = f === 'all';
  if (f !== 'all') chip.innerHTML = ic('filter') + ' ' + esc(f);

  renderTable();
}

function renderTable() {
  const q  = document.getElementById('av-search-input').value.toLowerCase().trim();
  const tb = document.getElementById('av-detalle-body');

  const data = rawData.filter(d => {
    const matchSearch = !q ||
      `${d.codigo} ${d.descripcion} ${d.competencia}`.toLowerCase().includes(q);
    const matchType = currentFilter === 'all' || juicioDe(d) === currentFilter;
    return matchSearch && matchType;
  });

  document.getElementById('tabla-sub').textContent =
    `${data.length} de ${rawData.length} resultados de aprendizaje`;

  if (!data.length) {
    tb.innerHTML = `<tr><td colspan="3"><div class="empty-state">
      <div class="empty-icon">${ic('search')}</div>
      <div class="empty-title">Sin resultados</div>
      <p>Ningún RAP coincide con el filtro o la búsqueda actual.</p>
    </div></td></tr>`;
    return;
  }

  let lastComp = '';
  tb.innerHTML = data.map(d => {
    let header = '';
    if (d.competencia !== lastComp) {
      header = `<tr class="comp-group-row"><td colspan="3">${esc(d.competencia)}</td></tr>`;
      lastComp = d.competencia;
    }
    const j = juicioDe(d);
    return header + `<tr>
      <td class="rap-cell">
        <div class="text-xs text-muted mono">${esc(d.codigo)}</div>
        <div class="text-sm" style="line-height:1.45">${esc(d.descripcion)}</div>
      </td>
      <td><span class="badge ${JUICIO_BADGE[j] || 'badge-muted'}">${esc(j)}</span></td>
      <td>${d.fecha_registro
        ? `<div class="text-sm fw-600 mono">${esc(d.fecha_registro.substring(0, 16))}</div>
           <div class="text-xs text-secondary">Por: ${esc(d.funcionario || 'SENA')}</div>`
        : '<span class="text-muted text-sm">Sin registro</span>'}
      </td>
    </tr>`;
  }).join('');
}

/* ── Donut de distribución: sustituye al gráfico de barras duplicado ── */
function renderDistChart() {
  const orden = ['Aprobado', 'No Aprobado', 'Por evaluar'];
  const conteo = orden.map(t => rawData.filter(d => juicioDe(d) === t).length);
  const colores = [token('success-solid'), token('danger-solid'), token('warning-solid')];

  if (chartDist) chartDist.destroy();
  chartDist = new Chart(document.getElementById('chart-dist'), {
    type: 'doughnut',
    data: { labels: orden, datasets: [{ data: conteo, backgroundColor: colores, borderWidth: 2, borderColor: token('bg-card') }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } }
  });
}

function renderCompList() {
  const cont = document.getElementById('av-comp-list');
  if (!compData.length) {
    cont.innerHTML = `<p class="text-sm text-muted text-center">Sin competencias asociadas.</p>`;
    return;
  }
  cont.innerHTML = compData.map(c => {
    const pct = c.total_resultados > 0 ? Math.round(c.aprobados / c.total_resultados * 100) : 0;
    const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';
    return `<div>
      <div class="cluster-between" style="gap:8px;margin-bottom:4px">
        <span class="comp-row-name" title="${esc(c.competencia)}">${esc(c.competencia)}</span>
        <span class="text-xs text-secondary mono" style="flex-shrink:0">${c.aprobados}/${c.total_resultados}</span>
      </div>
      <div class="progress-bar-wrap"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
    </div>`;
  }).join('');
}

document.addEventListener('themechange', () => { if (rawData.length) renderDistChart(); });

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
