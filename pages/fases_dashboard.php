<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Dashboard de Fases';
$pageSubtitle = 'Medición de cumplimiento por fases del proyecto formativo';
$activePage   = 'fases_dashboard';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- Selector de ficha en toolbar: antes ocupaba un card completo -->
<div class="toolbar mb-5">
  <span class="toolbar-label"><?= icon('school') ?> Ficha</span>
  <select id="sel-ficha" class="form-control flex-1" style="max-width:520px;" onchange="loadDashboard()" aria-label="Seleccionar ficha">
    <option value="">Cargando fichas…</option>
  </select>
  <span class="text-sm text-secondary" id="ficha-hint">Selecciona una ficha para ver su avance por fases</span>
</div>

<!-- Estado inicial: aún no se ha elegido ficha -->
<div class="card" id="state-idle">
  <div class="empty-state">
    <div class="empty-icon"><?= icon('target') ?></div>
    <div class="empty-title">Selecciona una ficha</div>
    <p>Elige una ficha en el selector superior para ver el porcentaje de cumplimiento de cada fase del proyecto formativo.</p>
  </div>
</div>

<!-- Estado: la ficha no tiene fases evaluables -->
<div class="card" id="state-empty" hidden>
  <div class="empty-state">
    <div class="empty-icon"><?= icon('layers') ?></div>
    <div class="empty-title">Aún no hay fases configuradas o evaluadas</div>
    <p>El programa asociado a esta ficha no tiene fases con resultados de aprendizaje asignados, o no hay aprendices activos en ella.</p>
    <a href="fases.php" class="btn btn-primary"><?= icon('layers') ?> Ir a Gestión de Fases</a>
  </div>
</div>

<!-- Contenido -->
<div id="dashboard-container" hidden>
  <div class="kpi-grid mb-5" id="fases-kpi-grid"></div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('chart-bar') ?> Cumplimiento por Fase</div>
        <div class="card-subtitle">Porcentaje de aprendices que aprobaron los RAPs de cada fase</div>
      </div>
      <div class="cluster-sm text-xs text-secondary">
        <span class="cluster-sm"><span class="legend-dot" style="background:var(--success-solid)"></span>≥80%</span>
        <span class="cluster-sm"><span class="legend-dot" style="background:var(--warning-solid)"></span>50–79%</span>
        <span class="cluster-sm"><span class="legend-dot" style="background:var(--danger-solid)"></span>&lt;50%</span>
      </div>
    </div>
    <div class="chart-container chart-lg"><canvas id="chart-fases"></canvas></div>
  </div>
</div>

<style>
  .legend-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
  .fase-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-left: 3px solid var(--fase-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--space-4);
  }
  .fase-idx { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: .09em; }
  .fase-name {
    font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 2px 0 12px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.7em;
  }
  .fase-pct { font-size: 28px; font-weight: 800; line-height: 1; color: var(--fase-color); letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
  .fase-foot { display: flex; justify-content: space-between; gap: 10px; font-size: 11.5px; margin-top: 10px; flex-wrap: wrap; }
  .fase-foot .ic { width: 13px; height: 13px; }
</style>

<script>
const API = '../api/fases_dashboard.php';
let chartFases = null;
let fasesData = [];

function show(id, visible) { document.getElementById(id).hidden = !visible; }

async function init() {
  const sel = document.getElementById('sel-ficha');
  try {
    const fichas = await fetch(API + '?action=fichas').then(r => r.json());
    sel.innerHTML = '<option value="">Selecciona una ficha…</option>' + fichas.map(f => {
      const prog = (f.programa || '').length > 55 ? f.programa.slice(0, 55) + '…' : (f.programa || '');
      return `<option value="${esc(f.ficha)}">${esc(f.ficha)} — ${esc(prog)}</option>`;
    }).join('');
  } catch (e) {
    sel.innerHTML = '<option value="">No se pudieron cargar las fichas</option>';
    showToast('No se pudieron cargar las fichas.', 'danger');
  }
}

async function loadDashboard() {
  const ficha = document.getElementById('sel-ficha').value;
  const hint  = document.getElementById('ficha-hint');

  if (!ficha) {
    show('dashboard-container', false); show('state-empty', false); show('state-idle', true);
    hint.textContent = 'Selecciona una ficha para ver su avance por fases';
    return;
  }

  hint.innerHTML = '<span class="spinner spinner-sm"></span> Cargando…';
  show('state-idle', false);

  try {
    fasesData = await fetch(`${API}?action=estadisticas_fases&ficha=${encodeURIComponent(ficha)}`).then(r => r.json());
  } catch (e) {
    fasesData = [];
    showToast('Error al consultar las fases de la ficha.', 'danger');
  }

  if (!fasesData || !fasesData.length) {
    show('dashboard-container', false); show('state-empty', true);
    hint.textContent = 'Sin fases evaluables';
    return;
  }

  show('state-empty', false); show('dashboard-container', true);
  const prom = Math.round(fasesData.reduce((s, f) => s + Number(f.pct_cumplimiento), 0) / fasesData.length);
  hint.textContent = `${fasesData.length} fases · ${prom}% promedio`;

  renderKPIs(fasesData);
  renderChart(fasesData);
}

/** Color semántico según el nivel de cumplimiento. */
function nivel(pct) {
  if (pct >= 80) return { color: 'var(--success-solid)', raw: token('success-solid') };
  if (pct >= 50) return { color: 'var(--warning-solid)', raw: token('warning-solid') };
  return { color: 'var(--danger-solid)', raw: token('danger-solid') };
}

function renderKPIs(data) {
  document.getElementById('fases-kpi-grid').innerHTML = data.map((f, idx) => `
    <div class="fase-card fade-in" style="--fase-color:${nivel(f.pct_cumplimiento).color}">
      <div class="fase-idx">Fase ${idx + 1}</div>
      <div class="fase-name" title="${esc(f.nombre_fase)}">${esc(f.nombre_fase)}</div>
      <div class="cluster-sm" style="align-items:baseline">
        <span class="fase-pct">${f.pct_cumplimiento}%</span>
        <span class="text-xs text-secondary">cumplimiento</span>
      </div>
      <div class="progress-bar-wrap" style="margin-top:10px">
        <div class="progress-bar" style="width:${f.pct_cumplimiento}%; background:${nivel(f.pct_cumplimiento).color}"></div>
      </div>
      <div class="fase-foot">
        <span class="text-success fw-600">${ic('check-circle')} ${f.aprendices_aprobados} aprobados</span>
        <span class="text-warning fw-600">${ic('clock')} ${f.aprendices_pendientes} pendientes</span>
      </div>
      <div class="text-xs text-muted text-right" style="margin-top:6px">${f.total_raps} RAPs a evaluar</div>
    </div>
  `).join('');
}

function renderChart(data) {
  if (chartFases) chartFases.destroy();

  chartFases = new Chart(document.getElementById('chart-fases'), {
    type: 'bar',
    data: {
      labels: data.map(f => f.nombre_fase),
      datasets: [{
        label: '% Cumplimiento',
        data: data.map(f => Number(f.pct_cumplimiento)),
        backgroundColor: data.map(f => nivel(f.pct_cumplimiento).raw),
        borderRadius: 6,
        borderSkipped: false,
        maxBarThickness: 72
      }]
    },
    options: {
      responsive: true,
      // Ejes y tooltip heredan el color del tema desde applyChartDefaults()
      scales: {
        y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, border: { display: false } },
        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, maxRotation: 0, autoSkip: false,
             callback: function (v) { const l = this.getLabelForValue(v); return l.length > 18 ? l.slice(0, 18) + '…' : l; } } }
      },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => c.parsed.y + '% de aprendices aprobados' } }
      }
    }
  });
}

// Las barras usan colores calculados en JS: hay que recalcularlos al cambiar de tema
document.addEventListener('themechange', () => { if (fasesData.length) renderChart(fasesData); });

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
