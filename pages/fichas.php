<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Fichas de Formación';
$pageSubtitle = 'Programas en ejecución y gestión de registros asociados';
$activePage   = 'fichas';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ══ TOOLBAR ══ -->
<div class="toolbar is-sticky mb-4">
  <div class="search-field">
    <?= icon('search') ?>
    <input type="text" id="search-ficha" class="form-control"
           placeholder="Buscar por número de ficha o programa…" oninput="filterFichas()" aria-label="Buscar ficha" />
  </div>
  <select id="filter-programa" class="form-control" style="width:230px" onchange="filterFichas()" aria-label="Filtrar por programa">
    <option value="">Todos los programas</option>
  </select>
  <select id="filter-estado" class="form-control" style="width:180px" onchange="filterFichas()" aria-label="Filtrar por estado">
    <option value="">Todos los estados</option>
  </select>
  <button class="btn btn-ghost btn-sm" id="btn-clear" onclick="clearFilters()" hidden><?= icon('x') ?> Limpiar</button>

  <div class="segmented push-right" role="group" aria-label="Modo de vista">
    <button type="button" id="btn-view-table" class="active" onclick="setView('table')" title="Vista de tabla" aria-label="Vista de tabla"><?= icon('table') ?></button>
    <button type="button" id="btn-view-grid" onclick="setView('grid')" title="Vista de tarjetas" aria-label="Vista de tarjetas"><?= icon('grid') ?></button>
  </div>
</div>

<!-- Barra contextual de selección: sólo aparece cuando hay fichas marcadas -->
<div class="selection-bar mb-4" id="selection-bar" hidden>
  <span class="fw-600"><?= icon('check-circle') ?> <span id="bulk-count">0</span> ficha(s) seleccionada(s)</span>
  <button class="btn btn-ghost btn-sm" onclick="clearSelection()">Deseleccionar</button>
  <button class="btn btn-danger btn-sm push-right" onclick="openBulkDeleteModal()"><?= icon('trash') ?> Eliminar seleccionadas</button>
</div>

<div class="cluster-between mb-3">
  <span class="text-sm text-secondary" id="fichas-count">Cargando…</span>
</div>

<!-- ══ VISTA TABLA ══ -->
<div class="card card-flush" id="view-container-table">
  <div class="table-wrap is-scrollable">
    <table class="table">
      <thead>
        <tr>
          <th style="width:38px"><input type="checkbox" id="chk-all-fichas" onchange="toggleAllFichas(this)" aria-label="Seleccionar todas las fichas"></th>
          <th>Ficha</th>
          <th>Programa de Formación</th>
          <th>Estado</th>
          <th>Modalidad</th>
          <th>Periodo</th>
          <th>Aprendices</th>
          <th class="col-actions">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-fichas"></tbody>
    </table>
  </div>
</div>

<!-- ══ VISTA TARJETAS ══ -->
<div id="view-container-grid" class="grid-cards" hidden></div>

<!-- ══ MODAL: borrado individual ══ -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="del-title">
    <div class="modal-header">
      <div class="cluster-sm">
        <div class="modal-danger-icon"><?= icon('alert-triangle') ?></div>
        <div>
          <div class="modal-title text-danger" id="del-title">Borrado en cascada</div>
          <div class="modal-sub">Esta acción es irreversible</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-delete')" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <p class="text-sm text-secondary mb-4">
        Vas a eliminar la ficha <strong class="mono text-lg" style="color:var(--text-primary)" id="del-ficha-num"></strong>.
        Se borrará permanentemente:
      </p>
      <ul class="danger-list mb-4">
        <li><?= icon('users') ?> Todos los aprendices asociados a esta ficha</li>
        <li><?= icon('file-pen') ?> Todos los juicios evaluativos de dichos aprendices</li>
        <li><?= icon('school') ?> El registro principal de la ficha</li>
      </ul>
      <div class="form-group mb-0">
        <label class="form-label" for="confirm-ficha-input">Para confirmar, escribe el número de la ficha</label>
        <input type="text" id="confirm-ficha-input" class="form-control mono" autocomplete="off" oninput="checkDeleteMatch()" />
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-delete')">Cancelar</button>
      <button class="btn btn-danger" id="btn-confirm-delete" disabled onclick="executeDelete()"><?= icon('trash') ?> Eliminar definitivamente</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: borrado masivo ══ -->
<div class="modal-overlay" id="modal-bulk-delete">
  <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="bulk-title">
    <div class="modal-header">
      <div class="cluster-sm">
        <div class="modal-danger-icon"><?= icon('trash') ?></div>
        <div>
          <div class="modal-title text-danger" id="bulk-title">Borrado masivo</div>
          <div class="modal-sub">Esta acción es irreversible</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-bulk-delete')" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <div id="bulk-delete-content">
        <p class="text-sm text-secondary mb-4">
          Vas a eliminar <strong class="text-lg" style="color:var(--text-primary)" id="bulk-del-count">0</strong> fichas.
          Se borrará permanentemente:
        </p>
        <ul class="danger-list mb-0">
          <li><?= icon('users') ?> Todos los aprendices asociados a estas fichas</li>
          <li><?= icon('file-pen') ?> Todos los juicios evaluativos de dichos aprendices</li>
          <li><?= icon('school') ?> Los registros de las fichas</li>
        </ul>
      </div>
      <div id="bulk-delete-progress" class="text-center" hidden style="padding:28px 0">
        <div class="spinner" style="width:36px;height:36px;border-width:3px;border-top-color:var(--danger-solid)"></div>
        <div class="fw-600 text-danger" style="margin-top:14px">Eliminando registros en cascada…</div>
        <div class="text-sm text-muted">Por favor no cierres esta ventana</div>
      </div>
    </div>
    <div class="modal-footer" id="bulk-delete-actions">
      <button class="btn btn-outline" onclick="closeModal('modal-bulk-delete')">Cancelar</button>
      <button class="btn btn-danger" id="btn-confirm-bulk-delete" onclick="executeBulkDelete()"><?= icon('trash') ?> Eliminar definitivamente</button>
    </div>
  </div>
</div>

<style>
  .selection-bar {
    display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;
    background: var(--brand-soft); border: 1px solid var(--brand-soft-bd);
    border-radius: var(--radius-lg); padding: 9px 14px; font-size: 13px;
  }
  .modal-danger-icon {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--danger-soft); color: var(--danger);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .modal-danger-icon .ic { width: 19px; height: 19px; }
  .danger-list { display: flex; flex-direction: column; gap: 7px; font-size: 13px; color: var(--danger); }
  .danger-list li { display: flex; align-items: center; gap: 8px; }
  .danger-list .ic { width: 15px; height: 15px; flex-shrink: 0; }

  .ficha-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);
    padding: var(--space-4);
    display: flex; flex-direction: column; gap: 10px;
    cursor: pointer; transition: var(--transition);
  }
  .ficha-card:hover { transform: translateY(-2px); border-color: var(--brand-soft-bd); box-shadow: var(--shadow-md); }
  .fc-number { font-family: ui-monospace, Consolas, monospace; font-size: 17px; font-weight: 800; color: var(--brand-text); letter-spacing: -.02em; }
  .fc-program {
    font-size: 14px; font-weight: 700; color: var(--text-primary); line-height: 1.35;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.7em;
  }
  .fc-meta { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 7px; }
  .fc-meta .ic { width: 14px; height: 14px; color: var(--text-muted); }
  .fc-footer {
    margin-top: auto; padding-top: var(--space-3); border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
  }
  .aprendices-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--info-soft); color: var(--info); border: 1px solid var(--info-bd);
    padding: 3px 9px; border-radius: var(--radius-pill); font-weight: 700; font-size: 12px;
  }
  .aprendices-chip .ic { width: 13px; height: 13px; }
  #tbody-fichas tr { cursor: pointer; }
</style>

<script>
const API_FICHAS = '../api/fichas.php';
let allFichas = [];
let targetFichaToDelete = null;
let targetFichasBulk = [];

async function init() {
  skeleton();
  await Promise.all([loadFichas(), loadEstados(), loadProgramas()]);

  // Preselección de programa vía ?programa=<id>
  const prog = new URLSearchParams(location.search).get('programa');
  if (prog) {
    const sel = document.getElementById('filter-programa');
    const opt = Array.from(sel.options).find(o => o.dataset.id == prog || o.value == prog);
    if (opt) { sel.value = opt.value; filterFichas(); }
  }
}

function skeleton() {
  document.getElementById('tbody-fichas').innerHTML =
    Array.from({ length: 6 }, () => '<tr><td colspan="8"><span class="skeleton skeleton-row"></span></td></tr>').join('');
}

async function loadFichas() {
  try {
    allFichas = await fetch(API_FICHAS + '?action=list').then(r => r.json());
    renderFichas(allFichas);
  } catch (e) { showToast('No se pudieron cargar las fichas.', 'danger'); }
}

async function loadProgramas() {
  try {
    const programas = await fetch(API_FICHAS + '?action=get_programas').then(r => r.json());
    const sel = document.getElementById('filter-programa');
    programas.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.nombre;               // se filtra localmente por nombre
      opt.dataset.id = p.id_programa;
      opt.textContent = p.nombre;
      sel.appendChild(opt);
    });
  } catch (e) {}
}

async function loadEstados() {
  try {
    const estados = await fetch(API_FICHAS + '?action=get_estados').then(r => r.json());
    const sel = document.getElementById('filter-estado');
    sel.innerHTML = '<option value="">Todos los estados</option>' +
      estados.filter(Boolean).map(e => `<option value="${esc(e)}">${esc(e)}</option>`).join('');
  } catch (e) {}
}

function renderFichas(data) {
  const tbody = document.getElementById('tbody-fichas');
  const grid  = document.getElementById('view-container-grid');

  document.getElementById('fichas-count').textContent = data.length === allFichas.length
    ? `${data.length} ficha${data.length === 1 ? '' : 's'} registrada${data.length === 1 ? '' : 's'}`
    : `${data.length} de ${allFichas.length} fichas`;

  if (!data.length) {
    const empty = `<div class="empty-state">
      <div class="empty-icon">${ic(allFichas.length ? 'search' : 'school')}</div>
      <div class="empty-title">${allFichas.length ? 'Sin resultados' : 'No hay fichas registradas'}</div>
      <p>${allFichas.length
        ? 'Ninguna ficha coincide con los filtros aplicados.'
        : 'Importa un reporte de Sofia Plus desde el Dashboard para cargar fichas.'}</p>
    </div>`;
    tbody.innerHTML = `<tr><td colspan="8">${empty}</td></tr>`;
    grid.innerHTML  = `<div class="card" style="grid-column:1/-1">${empty}</div>`;
    updateSelectionBar();
    return;
  }

  tbody.innerHTML = data.map(f => {
    const url = `ficha_detalle.php?ficha=${encodeURIComponent(f.ficha)}`;
    return `<tr onclick="window.location.href='${url}'">
      <td onclick="event.stopPropagation()">
        <input type="checkbox" class="chk-ficha" value="${esc(f.ficha)}" onchange="updateSelectionBar()"
               aria-label="Seleccionar ficha ${esc(f.ficha)}">
      </td>
      <td class="mono fw-800 text-brand" style="font-size:14px">${esc(f.ficha)}</td>
      <td class="truncate" style="max-width:280px" title="${esc(f.programa)}"><span class="fw-600">${esc(f.programa)}</span></td>
      <td><span class="badge ${badgeFicha(f.estado)}">${esc(f.estado)}</span></td>
      <td class="text-sm text-secondary">${esc(f.modalidad)}</td>
      <td class="text-xs text-secondary mono">${esc(f.fecha_inicio)}<br><span class="text-muted">a</span> ${esc(f.fecha_fin)}</td>
      <td><span class="aprendices-chip">${ic('users')} ${f.total_aprendices}</span></td>
      <td class="col-actions" onclick="event.stopPropagation()">
        <div class="cluster-sm" style="justify-content:flex-end;flex-wrap:nowrap">
          <a href="${url}" class="btn btn-outline btn-sm">${ic('eye')} Ver</a>
          <button class="btn btn-danger-soft btn-sm btn-icon" onclick="openDeleteModal('${esc(f.ficha)}')"
                  title="Eliminar ficha ${esc(f.ficha)}" aria-label="Eliminar ficha ${esc(f.ficha)}">${ic('trash')}</button>
        </div>
      </td>
    </tr>`;
  }).join('');

  grid.innerHTML = data.map(f => {
    const url = `ficha_detalle.php?ficha=${encodeURIComponent(f.ficha)}`;
    return `<div class="ficha-card fade-in" onclick="window.location.href='${url}'">
      <div class="cluster-between">
        <span class="fc-number">${esc(f.ficha)}</span>
        <span class="badge ${badgeFicha(f.estado)}">${esc(f.estado)}</span>
      </div>
      <div class="fc-program" title="${esc(f.programa)}">${esc(f.programa)}</div>
      <div class="fc-meta">${ic('map-pin')} ${esc(f.modalidad)}</div>
      <div class="fc-meta">${ic('calendar')} <span class="mono text-xs">${esc(f.fecha_inicio)} — ${esc(f.fecha_fin)}</span></div>
      <div class="fc-footer">
        <span class="aprendices-chip">${ic('users')} ${f.total_aprendices} aprendices</span>
        <button class="btn btn-danger-soft btn-sm btn-icon"
                onclick="event.stopPropagation(); openDeleteModal('${esc(f.ficha)}')"
                title="Eliminar ficha" aria-label="Eliminar ficha ${esc(f.ficha)}">${ic('trash')}</button>
      </div>
    </div>`;
  }).join('');

  updateSelectionBar();
}

/* ── Filtros ── */
function filterFichas() {
  const q       = document.getElementById('search-ficha').value.toLowerCase().trim();
  const estado  = document.getElementById('filter-estado').value.trim();
  const progSel = document.getElementById('filter-programa');
  const prog    = progSel.value;
  const progId  = progSel.options[progSel.selectedIndex]?.dataset?.id;

  document.getElementById('btn-clear').hidden = !(q || estado || prog);

  renderFichas(allFichas.filter(f => {
    const matchSearch = f.ficha.toLowerCase().includes(q) || (f.programa || '').toLowerCase().includes(q);
    const matchEstado = estado === '' || (f.estado || '').trim() === estado;
    const matchProg   = prog === '' || f.programa === prog || progId == prog;
    return matchSearch && matchEstado && matchProg;
  }));
}

function clearFilters() {
  document.getElementById('search-ficha').value = '';
  document.getElementById('filter-estado').value = '';
  document.getElementById('filter-programa').value = '';
  filterFichas();
}

/* ── Vista tabla / tarjetas ── */
function setView(view) {
  document.getElementById('view-container-table').hidden = view !== 'table';
  document.getElementById('view-container-grid').hidden  = view !== 'grid';
  document.getElementById('btn-view-table').classList.toggle('active', view === 'table');
  document.getElementById('btn-view-grid').classList.toggle('active', view === 'grid');
  try { localStorage.setItem('fichas_view_pref', view); } catch (e) {}
}
try { const pref = localStorage.getItem('fichas_view_pref'); if (pref) setView(pref); } catch (e) {}

/* ── Selección múltiple ── */
function toggleAllFichas(source) {
  document.querySelectorAll('.chk-ficha').forEach(cb => cb.checked = source.checked);
  updateSelectionBar();
}

function clearSelection() {
  document.querySelectorAll('.chk-ficha').forEach(cb => cb.checked = false);
  const all = document.getElementById('chk-all-fichas');
  if (all) all.checked = false;
  updateSelectionBar();
}

function updateSelectionBar() {
  const selected = document.querySelectorAll('.chk-ficha:checked').length;
  const total    = document.querySelectorAll('.chk-ficha').length;
  document.getElementById('selection-bar').hidden = selected === 0;
  document.getElementById('bulk-count').textContent = selected;
  const all = document.getElementById('chk-all-fichas');
  if (all) {
    all.checked = selected === total && total > 0;
    all.indeterminate = selected > 0 && selected < total;
  }
}

/* ── Borrado individual ── */
function openDeleteModal(ficha) {
  targetFichaToDelete = ficha;
  document.getElementById('del-ficha-num').textContent = ficha;
  document.getElementById('confirm-ficha-input').value = '';
  document.getElementById('btn-confirm-delete').disabled = true;
  openModal('modal-delete');
}

function checkDeleteMatch() {
  document.getElementById('btn-confirm-delete').disabled =
    document.getElementById('confirm-ficha-input').value.trim() !== targetFichaToDelete;
}

async function executeDelete() {
  if (!targetFichaToDelete) return;
  const btn = document.getElementById('btn-confirm-delete');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Eliminando…';

  try {
    const res = await fetch(API_FICHAS + '?ficha=' + encodeURIComponent(targetFichaToDelete), { method: 'DELETE' })
      .then(r => r.json());
    if (res.error) {
      showToast(esc(res.error), 'danger');
    } else {
      showToast(esc(res.mensaje), 'success');
      closeModal('modal-delete');
      clearFilters();
      await loadFichas();
    }
  } catch (err) {
    showToast('Error de red al intentar eliminar la ficha.', 'danger');
  } finally {
    btn.innerHTML = ic('trash') + ' Eliminar definitivamente';
    btn.disabled = false;
  }
}

/* ── Borrado masivo ── */
function openBulkDeleteModal() {
  targetFichasBulk = Array.from(document.querySelectorAll('.chk-ficha:checked')).map(cb => cb.value);
  if (!targetFichasBulk.length) return;
  document.getElementById('bulk-del-count').textContent = targetFichasBulk.length;
  document.getElementById('bulk-delete-progress').hidden = true;
  document.getElementById('bulk-delete-content').hidden = false;
  document.getElementById('bulk-delete-actions').hidden = false;
  document.getElementById('btn-confirm-bulk-delete').disabled = false;
  openModal('modal-bulk-delete');
}

async function executeBulkDelete() {
  if (!targetFichasBulk.length) return;
  document.getElementById('bulk-delete-content').hidden = true;
  document.getElementById('bulk-delete-actions').hidden = true;
  document.getElementById('bulk-delete-progress').hidden = false;

  try {
    const res = await fetch(API_FICHAS + '?action=bulk_delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fichas: targetFichasBulk })
    }).then(r => r.json());

    if (res.error) {
      showToast(esc(res.error), 'danger');
      document.getElementById('bulk-delete-progress').hidden = true;
      document.getElementById('bulk-delete-content').hidden = false;
      document.getElementById('bulk-delete-actions').hidden = false;
    } else {
      showToast(esc(res.mensaje), 'success');
      targetFichasBulk = [];
      closeModal('modal-bulk-delete');
      await loadFichas();
      clearSelection();
    }
  } catch (err) {
    showToast('Error de red al intentar eliminar las fichas.', 'danger');
    document.getElementById('bulk-delete-progress').hidden = true;
    document.getElementById('bulk-delete-content').hidden = false;
    document.getElementById('bulk-delete-actions').hidden = false;
  }
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
