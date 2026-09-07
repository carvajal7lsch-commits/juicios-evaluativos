<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Seguimiento de Aprendices';
$pageSubtitle = 'Busca y consulta el avance individual de cada aprendiz';
$activePage   = 'aprendices';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- Toolbar única: búsqueda + filtros + vista. Sustituye la página casi vacía anterior. -->
<div class="toolbar is-sticky mb-5">
  <div class="search-field">
    <?= icon('search') ?>
    <input type="text" id="q" class="form-control" placeholder="Nombre, apellido o documento…"
           oninput="debouncedLoad()" autocomplete="off" aria-label="Buscar aprendiz" />
  </div>
  <select id="f-programa" class="form-control" style="width:220px" onchange="onProgramaChange()" aria-label="Filtrar por programa">
    <option value="">Todos los programas</option>
  </select>
  <select id="f-ficha" class="form-control" style="width:170px" onchange="loadAprendices()" aria-label="Filtrar por ficha">
    <option value="">Todas las fichas</option>
  </select>
  <select id="f-avance" class="form-control" style="width:170px" onchange="render()" aria-label="Filtrar por nivel de avance">
    <option value="">Todo el avance</option>
    <option value="alto">Excelente (80–100%)</option>
    <option value="medio">En proceso (50–79%)</option>
    <option value="bajo">Crítico (0–49%)</option>
  </select>
  <button class="btn btn-ghost btn-sm" onclick="clearFilters()" id="btn-clear" hidden>
    <?= icon('x') ?> Limpiar
  </button>
  <div class="segmented push-right" role="group" aria-label="Modo de vista">
    <button type="button" id="v-table" class="active" onclick="setView('table')" title="Vista de tabla" aria-label="Vista de tabla"><?= icon('table') ?></button>
    <button type="button" id="v-grid" onclick="setView('grid')" title="Vista de tarjetas" aria-label="Vista de tarjetas"><?= icon('grid') ?></button>
  </div>
</div>

<div class="cluster-between mb-3">
  <span class="text-sm text-secondary" id="result-count">Cargando aprendices…</span>
  <span class="text-xs text-muted" id="limit-note" hidden><?= icon('info') ?> Se muestran los primeros 100 — afina la búsqueda para ver otros</span>
</div>

<!-- Vista tabla -->
<div class="card card-flush" id="view-table">
  <div class="table-wrap is-scrollable">
    <table class="table">
      <thead>
        <tr>
          <th>Aprendiz</th>
          <th>Documento</th>
          <th>Ficha</th>
          <th>Programa</th>
          <th>Estado</th>
          <th style="width:170px">Avance</th>
          <th class="col-actions"></th>
        </tr>
      </thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>
</div>

<!-- Vista tarjetas -->
<div class="grid-cards" id="view-grid" hidden></div>

<style>
  .ap-card {
    display: flex; flex-direction: column; gap: 10px;
    background: var(--bg-card); border: 1px solid var(--border);
    border-left: 3px solid var(--ap-color, var(--brand));
    border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);
    padding: var(--space-4); text-decoration: none; color: inherit;
    transition: var(--transition);
  }
  .ap-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--border-strong); border-left-color: var(--ap-color, var(--brand)); }
  .ap-name { font-size: 14.5px; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
  .ap-meta { font-size: 12px; color: var(--text-secondary); }
  .ap-prog { font-size: 11.5px; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .avance-cell { display: flex; align-items: center; gap: 9px; }
  .avance-cell .progress-bar-wrap { flex: 1; min-width: 56px; }
  .avance-pct { font-size: 12px; font-weight: 700; width: 38px; text-align: right; font-variant-numeric: tabular-nums; }
</style>

<script>
const API = '../api/dashboard.php';
let rows = [];
let allFichas = [];
let view = 'table';
let searchTimer = null;

/* ── Carga inicial: filtros + primer listado ───────────────────── */
async function init() {
  try {
    const d = await fetch(API + '?action=get_filtros_globales').then(r => r.json());
    allFichas = d.fichas || [];
    document.getElementById('f-programa').innerHTML =
      '<option value="">Todos los programas</option>' +
      (d.programas || []).map(p => `<option value="${esc(p.id_programa)}">${esc(p.nombre)}</option>`).join('');
    renderFichaOptions();
  } catch (e) { showToast('No se pudieron cargar los filtros.', 'danger'); }

  // Preselección vía querystring (?ficha=... o ?programa=...)
  const qs = new URLSearchParams(location.search);
  if (qs.get('programa')) { document.getElementById('f-programa').value = qs.get('programa'); renderFichaOptions(); }
  if (qs.get('ficha'))    document.getElementById('f-ficha').value = qs.get('ficha');

  loadAprendices();
}

function renderFichaOptions() {
  const prog = document.getElementById('f-programa').value;
  const list = prog ? allFichas.filter(f => String(f.id_programa) === String(prog)) : allFichas;
  document.getElementById('f-ficha').innerHTML =
    '<option value="">Todas las fichas</option>' +
    list.map(f => `<option value="${esc(f.ficha)}">${esc(f.ficha)}</option>`).join('');
}

function onProgramaChange() { renderFichaOptions(); loadAprendices(); }

function debouncedLoad() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadAprendices, 280);
}

function currentFilters() {
  return {
    q:    document.getElementById('q').value.trim(),
    prog: document.getElementById('f-programa').value,
    ficha:document.getElementById('f-ficha').value,
    niv:  document.getElementById('f-avance').value
  };
}

function clearFilters() {
  document.getElementById('q').value = '';
  document.getElementById('f-programa').value = '';
  document.getElementById('f-avance').value = '';
  renderFichaOptions();
  loadAprendices();
}

async function loadAprendices() {
  const f = currentFilters();
  document.getElementById('btn-clear').hidden = !(f.q || f.prog || f.ficha || f.niv);
  skeleton();

  const params = new URLSearchParams({ action: 'tabla_aprendices' });
  if (f.q)     params.set('global_search', f.q);
  if (f.prog)  params.set('global_programa', f.prog);
  if (f.ficha) params.set('global_ficha', f.ficha);

  try {
    rows = await fetch(API + '?' + params).then(r => r.json());
  } catch (e) {
    rows = [];
    showToast('Error al consultar aprendices.', 'danger');
  }
  render();
}

/* ── Cálculo de avance ─────────────────────────────────────────── */
function pctOf(a) {
  return a.total_resultados > 0 ? Math.round((a.aprobados / a.total_resultados) * 100) : 0;
}
function nivelOf(pct) {
  if (pct >= 80) return { key: 'alto',  cls: 'green',  color: 'var(--success-solid)' };
  if (pct >= 50) return { key: 'medio', cls: 'warn',   color: 'var(--warning-solid)' };
  return { key: 'bajo', cls: 'danger', color: 'var(--danger-solid)' };
}

function visibleRows() {
  const niv = document.getElementById('f-avance').value;
  return niv ? rows.filter(a => nivelOf(pctOf(a)).key === niv) : rows;
}

/* ── Render ────────────────────────────────────────────────────── */
function skeleton() {
  document.getElementById('result-count').innerHTML = '<span class="spinner spinner-sm"></span> Buscando…';
  const sk = Array.from({ length: 6 }, () => '<tr><td colspan="7"><span class="skeleton skeleton-row"></span></td></tr>').join('');
  document.getElementById('tbody').innerHTML = sk;
  document.getElementById('view-grid').innerHTML =
    Array.from({ length: 6 }, () => '<span class="skeleton" style="height:118px;border-radius:var(--radius-lg)"></span>').join('');
}

function render() {
  const data = visibleRows();
  const f = currentFilters();

  document.getElementById('result-count').textContent =
    data.length ? `${data.length} aprendiz${data.length === 1 ? '' : 'es'}` : 'Sin resultados';
  document.getElementById('limit-note').hidden = rows.length < 100;

  if (!data.length) {
    const isFiltered = f.q || f.prog || f.ficha || f.niv;
    const empty = `<div class="empty-state">
        <div class="empty-icon">${ic(isFiltered ? 'search' : 'users')}</div>
        <div class="empty-title">${isFiltered ? 'Ningún aprendiz coincide' : 'Aún no hay aprendices'}</div>
        <p>${isFiltered
          ? 'Prueba con otro nombre o documento, o quita algún filtro.'
          : 'Importa un reporte de Sofia Plus desde el Dashboard para cargar aprendices.'}</p>
      </div>`;
    document.getElementById('tbody').innerHTML = `<tr><td colspan="7">${empty}</td></tr>`;
    document.getElementById('view-grid').innerHTML = `<div class="card" style="grid-column:1/-1">${empty}</div>`;
    return;
  }

  document.getElementById('tbody').innerHTML = data.map(a => {
    const pct = pctOf(a), n = nivelOf(pct);
    const url = `aprendiz_seguimiento.php?ficha=${encodeURIComponent(a.ficha)}&documento=${encodeURIComponent(a.documento)}`;
    return `<tr>
      <td><div class="fw-600">${esc(a.nombre)} ${esc(a.apellidos)}</div></td>
      <td class="mono text-secondary">${esc(a.documento)}</td>
      <td class="mono">${esc(a.ficha)}</td>
      <td class="text-secondary truncate" style="max-width:260px" title="${esc(a.programa)}">${esc(a.programa)}</td>
      <td><span class="badge ${badgeEstado(a.estado)}">${esc(a.estado)}</span></td>
      <td>
        <div class="avance-cell">
          <div class="progress-bar-wrap"><div class="progress-bar ${n.cls}" style="width:${pct}%"></div></div>
          <span class="avance-pct">${pct}%</span>
        </div>
        <div class="text-xs text-muted" style="margin-top:2px">${a.aprobados}/${a.total_resultados} RAPs</div>
      </td>
      <td class="col-actions"><a href="${url}" class="btn btn-outline btn-sm">${ic('trending-up')} Avance</a></td>
    </tr>`;
  }).join('');

  document.getElementById('view-grid').innerHTML = data.map(a => {
    const pct = pctOf(a), n = nivelOf(pct);
    const url = `aprendiz_seguimiento.php?ficha=${encodeURIComponent(a.ficha)}&documento=${encodeURIComponent(a.documento)}`;
    return `<a href="${url}" class="ap-card fade-in" style="--ap-color:${n.color}">
      <div class="cluster-between" style="align-items:flex-start">
        <div class="flex-1">
          <div class="ap-name">${esc(a.nombre)} ${esc(a.apellidos)}</div>
          <div class="ap-meta mono">${esc(a.documento)} · Ficha ${esc(a.ficha)}</div>
        </div>
        <span class="badge ${badgeEstado(a.estado)}">${esc(a.estado)}</span>
      </div>
      <div class="ap-prog">${esc(a.programa)}</div>
      <div>
        <div class="avance-cell">
          <div class="progress-bar-wrap"><div class="progress-bar ${n.cls}" style="width:${pct}%"></div></div>
          <span class="avance-pct">${pct}%</span>
        </div>
        <div class="text-xs text-muted" style="margin-top:4px">${a.aprobados} de ${a.total_resultados} RAPs aprobados</div>
      </div>
    </a>`;
  }).join('');
}

/* ── Vista tabla / tarjetas (recordada por navegador) ──────────── */
function setView(v) {
  view = v;
  document.getElementById('view-table').hidden = v !== 'table';
  document.getElementById('view-grid').hidden  = v !== 'grid';
  document.getElementById('v-table').classList.toggle('active', v === 'table');
  document.getElementById('v-grid').classList.toggle('active', v === 'grid');
  try { localStorage.setItem('aprendices-view', v); } catch (e) {}
}

try { const v = localStorage.getItem('aprendices-view'); if (v) setView(v); } catch (e) {}
init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
