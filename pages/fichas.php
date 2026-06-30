<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Fichas de Formación';
$pageSubtitle = 'Gestión de programas en ejecución y borrado de registros asociados';
$activePage   = 'fichas';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="card fade-in">
  <div class="card-header">
    <div>
      <div class="card-title">🏫 Fichas Cargadas en el Sistema</div>
      <div class="card-subtitle" id="fichas-count">Cargando...</div>
    </div>
  </div>

  <!-- Búsqueda y Filtros -->
  <div class="filters-bar" style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
    <div class="filter-search" style="max-width:400px; flex:1;">
      <span class="search-icon">🔍</span>
      <input type="text" id="search-ficha" class="form-control" placeholder="Buscar por número de ficha o programa..." oninput="filterFichas()" />
    </div>
    <select id="filter-programa" class="form-control" style="width:250px;" onchange="filterFichas()">
      <option value="">Todos los programas</option>
    </select>
    <select id="filter-estado" class="form-control" style="width:200px;" onchange="filterFichas()">
      <option value="">Todos los estados</option>
      <!-- Se cargan dinámicamente -->
    </select>
    
    <!-- Selector de Vista (Nuevo) -->
    <div class="view-switcher">
      <button class="view-btn active" id="btn-view-table" onclick="setView('table')" title="Vista de Tabla">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/><line x1="15" y1="9" x2="15" y2="21"/></svg>
      </button>
      <button class="view-btn" id="btn-view-grid" onclick="setView('grid')" title="Vista de Cuadrícula">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="17" y="3" width="5" height="5" rx="1"/><rect x="3" y="10" width="5" height="5" rx="1"/><rect x="10" y="10" width="5" height="5" rx="1"/><rect x="17" y="10" width="5" height="5" rx="1"/><rect x="3" y="17" width="5" height="5" rx="1"/><rect x="10" y="17" width="5" height="5" rx="1"/><rect x="17" y="17" width="5" height="5" rx="1"/></svg>
      </button>
    </div>
    
    <button class="btn btn-danger" id="btn-bulk-delete" style="display:none;" onclick="openBulkDeleteModal()">
      🗑️ Eliminar (<span id="bulk-count">0</span>)
    </button>
  </div>

  <div class="table-wrap" id="view-container-table">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 40px; text-align: center;"><input type="checkbox" id="chk-all-fichas" onchange="toggleAllFichas(this)"></th>
          <th>Ficha</th>
          <th>Programa de Formación</th>
          <th>Estado</th>
          <th>Modalidad</th>
          <th>Periodo</th>
          <th>Aprendices</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-fichas">
        <tr><td colspan="8" style="text-align:center;padding:32px;"><span class="spinner"></span></td></tr>
      </tbody>
    </table>
  </div>

  <!-- Vista de Cuadrícula (Nuevo) -->
  <div id="view-container-grid" class="fichas-grid-container" style="display:none;">
    <div id="grid-fichas" class="f-grid"></div>
  </div>
</div>

<style>
  /* Segmented Control Styles Refined */
  .view-switcher {
    display: flex;
    background: var(--bg-input);
    padding: 3px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    margin-left: auto;
    height: 36px;
    align-items: stretch;
  }
  .view-btn {
    border: none;
    background: transparent;
    padding: 0 10px;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-muted);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .view-btn.active {
    background: var(--border);
    color: var(--info);
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  }
  .view-btn:not(.active):hover {
    color: var(--text-secondary);
  }
  .view-btn svg {
    width: 18px;
    height: 18px;
  }

  /* Grid View Styles */
  .f-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    padding: 20px 0;
  }
  .ficha-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .ficha-card:hover {
    transform: translateY(-5px);
    border-color: var(--sena-blue-lt);
    box-shadow: 0 12px 24px rgba(26,77,181,0.1);
  }
  .ficha-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: var(--sena-blue-lt);
    opacity: 0; transition: 0.3s;
  }
  .ficha-card:hover::after { opacity: 1; }

  .fc-header { display: flex; justify-content: space-between; align-items: flex-start; }
  .fc-number { font-family: monospace; font-size: 18px; font-weight: 800; color: var(--sena-blue-lt); }
  .fc-program { font-size: 15px; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
  .fc-meta { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
  .fc-footer { margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
  
  #tbody-fichas tr { transition: all 0.2s ease; cursor: pointer; }
  #tbody-fichas tr:hover { background: rgba(26,77,181,0.04) !important; transform: translateX(4px); }
  #tbody-fichas td { position: relative; }
  #tbody-fichas tr:hover td:first-child::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0; width: 4px;
    background: var(--sena-blue-lt);
    border-radius: 0 4px 4px 0;
  }
</style>

<!-- Modal de Confirmación de Borrado -->
<div id="modal-delete" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="card" style="width:480px; max-width:90%; animation:fadeInUp 0.3s ease; border:1px solid rgba(218,54,51,0.4); box-shadow:0 12px 32px rgba(218,54,51,0.15);">
    <div class="card-header" style="border-bottom:1px solid var(--border); margin-bottom:16px; padding-bottom:16px;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="background:rgba(218,54,51,0.1); width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;">⚠️</div>
        <div>
          <div class="card-title" style="color:var(--danger);">Borrado Crítico en Cascada</div>
          <div class="card-subtitle">Esta acción es irreversible</div>
        </div>
      </div>
    </div>
    
    <div style="font-size:14px; color:var(--text-secondary); margin-bottom:20px; line-height:1.5;">
      Estás a punto de eliminar la ficha <strong id="del-ficha-num" style="color:var(--text-primary); font-family:monospace; font-size:16px;"></strong>.<br><br>
      Esto eliminará permanentemente:
      <ul style="margin-top:8px; margin-left:24px; color:var(--danger);">
        <li>Todos los aprendices asociados a esta ficha.</li>
        <li>Todos los juicios evaluativos de dichos aprendices.</li>
        <li>El registro principal de la ficha.</li>
      </ul>
    </div>

    <div class="form-group">
      <label class="form-label">Para confirmar, escribe el número de la ficha:</label>
      <input type="text" id="confirm-ficha-input" class="form-control" autocomplete="off" oninput="checkDeleteMatch()" />
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
      <button class="btn btn-outline" onclick="closeDeleteModal()">Cancelar</button>
      <button class="btn btn-danger" id="btn-confirm-delete" disabled onclick="executeDelete()">Eliminar Definitivamente</button>
    </div>
  </div>
</div>

<!-- Modal de Borrado Masivo -->
<div id="modal-bulk-delete" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="card" style="width:480px; max-width:90%; animation:fadeInUp 0.3s ease; border:1px solid rgba(218,54,51,0.4); box-shadow:0 12px 32px rgba(218,54,51,0.15);">
    <div class="card-header" style="border-bottom:1px solid var(--border); margin-bottom:16px; padding-bottom:16px;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="background:rgba(218,54,51,0.1); width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;">🗑️</div>
        <div>
          <div class="card-title" style="color:var(--danger);">Borrado Masivo</div>
          <div class="card-subtitle">Esta acción es irreversible</div>
        </div>
      </div>
    </div>
    
    <div style="font-size:14px; color:var(--text-secondary); margin-bottom:20px; line-height:1.5;">
      Estás a punto de eliminar <strong id="bulk-del-count" style="color:var(--text-primary); font-size:16px;">0</strong> fichas.<br><br>
      Esto eliminará permanentemente:
      <ul style="margin-top:8px; margin-left:24px; color:var(--danger);">
        <li>Todos los aprendices asociados a estas fichas.</li>
        <li>Todos los juicios evaluativos de dichos aprendices.</li>
        <li>Los registros de las fichas.</li>
      </ul>
    </div>
    
    <div id="bulk-delete-animation" style="display:none; text-align:center; padding:20px; flex-direction:column; align-items:center; gap:10px;">
        <div class="spinner" style="width:40px; height:40px; border-width:4px; border-top-color:var(--danger);"></div>
        <div style="color:var(--danger); font-weight:600; font-size:15px; margin-top:10px;">Eliminando registros en cascada...</div>
        <div style="font-size:13px; color:var(--text-muted);">Por favor no cierres esta ventana</div>
    </div>

    <div id="bulk-delete-actions" style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
      <button class="btn btn-outline" onclick="closeBulkDeleteModal()">Cancelar</button>
      <button class="btn btn-danger" id="btn-confirm-bulk-delete" onclick="executeBulkDelete()">Eliminar Definitivamente</button>
    </div>
  </div>
</div>

<script>
const API_FICHAS = '../api/fichas.php';
let allFichas = [];
let targetFichaToDelete = null;

// Mover modales al final del body para que ocupen toda la pantalla (ejecutado sincronamente)
const mDel = document.getElementById('modal-delete');
if (mDel) document.body.appendChild(mDel);

const mBulk = document.getElementById('modal-bulk-delete');
if (mBulk) document.body.appendChild(mBulk);

async function init() {
  await Promise.all([
    loadFichas(),
    loadEstados(),
    loadProgramas()
  ]);

  const urlParams = new URLSearchParams(window.location.search);
  const prog = urlParams.get('programa');
  if (prog) {
      setTimeout(() => {
          const sel = document.getElementById('filter-programa');
          for (let i = 0; i < sel.options.length; i++) {
              if (sel.options[i].dataset.id == prog || sel.options[i].value == prog) { 
                  sel.selectedIndex = i; 
                  break; 
              }
          }
          filterFichas();
      }, 100);
  }
}

async function loadFichas() {
  // If the page is opened with ?programa=... we fetch all and filter in JS,
  // or we can just fetch all and filter locally which is faster.
  const data = await fetch(API_FICHAS + '?action=list').then(r => r.json());
  allFichas = data;
  renderFichas(data);
}

async function loadProgramas() {
  const programas = await fetch(API_FICHAS + '?action=get_programas').then(r => r.json());
  const select = document.getElementById('filter-programa');
  programas.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.nombre; // Filtrar por nombre localmente
    opt.dataset.id = p.id_programa;
    opt.textContent = p.nombre;
    select.appendChild(opt);
  });
}

async function loadEstados() {
  const estados = await fetch(API_FICHAS + '?action=get_estados').then(r => r.json());
  const select = document.getElementById('filter-estado');
  // Mantener solo la opción por defecto
  select.innerHTML = '<option value="">Todos los estados</option>';
  estados.forEach(e => {
    if (!e) return;
    const opt = document.createElement('option');
    opt.value = e;
    opt.textContent = e;
    select.appendChild(opt);
  });
}

function renderFichas(data) {
  document.getElementById('fichas-count').textContent = data.length + ' ficha(s) registradas';
  const tbody = document.getElementById('tbody-fichas');
  const grid  = document.getElementById('grid-fichas');
  
  if (!data.length) {
    const empty = '<div class="empty-state"><div class="empty-icon">🏫</div><p>No hay fichas registradas</p></div>';
    tbody.innerHTML = `<tr><td colspan="7">${empty}</td></tr>`;
    grid.innerHTML  = empty;
    return;
  }

  const estadoBadge = {
    'En Ejecución': 'badge-success',
    'Terminada': 'badge-info',
    'Suspendida': 'badge-warning',
    'En Convocatoria': 'badge-muted'
  };

  // Render Table
  tbody.innerHTML = data.map(f => `
    <tr onclick="window.location.href='ficha_detalle.php?ficha=${esc(f.ficha)}'">
      <td style="text-align:center;" onclick="event.stopPropagation()">
        <input type="checkbox" class="chk-ficha" value="${esc(f.ficha)}" onchange="updateBulkDeleteBtn()">
      </td>
      <td><strong style="font-family:monospace; font-size:15px; color:var(--sena-blue-lt);">${esc(f.ficha)}</strong></td>
      <td style="max-width:250px;">
        <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${esc(f.programa)}">${esc(f.programa)}</div>
      </td>
      <td><span class="badge ${estadoBadge[f.estado] || 'badge-muted'}">${esc(f.estado)}</span></td>
      <td style="font-size:13px; color:var(--text-secondary);">${esc(f.modalidad)}</td>
      <td style="font-size:12px; color:var(--text-secondary);">
        ${f.fecha_inicio} <br>
        <span style="color:var(--text-muted)">a</span> ${f.fecha_fin}
      </td>
      <td>
        <span style="background:rgba(56,139,253,0.1); color:var(--info); padding:4px 10px; border-radius:12px; font-weight:700; font-size:13px;">
          👥 ${f.total_aprendices}
        </span>
      </td>
      <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end;">
        <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); window.location.href='ficha_detalle.php?ficha=${esc(f.ficha)}'">
          👁️ Ver Detalles
        </button>
        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); openDeleteModal('${esc(f.ficha)}')">
          🗑️ Eliminar
        </button>
      </td>
    </tr>
  `).join('');

  // Render Grid (Nuevo)
  grid.innerHTML = data.map(f => `
    <div class="ficha-card fade-in" onclick="window.location.href='ficha_detalle.php?ficha=${esc(f.ficha)}'">
      <div class="fc-header">
        <span class="fc-number">${esc(f.ficha)}</span>
        <span class="badge ${estadoBadge[f.estado] || 'badge-muted'}">${esc(f.estado)}</span>
      </div>
      <div class="fc-program" title="${esc(f.programa)}">${esc(f.programa)}</div>
      <div class="fc-meta">📍 ${esc(f.modalidad)}</div>
      <div class="fc-meta" style="font-size:12px; color:var(--text-muted);">📅 ${f.fecha_inicio} a ${f.fecha_fin}</div>
      <div class="fc-footer">
        <div style="font-weight:700; color:var(--sena-blue); font-size:14px;">👥 ${f.total_aprendices} aprendices</div>
        <button class="btn btn-danger btn-sm" style="padding:6px;" onclick="event.stopPropagation(); openDeleteModal('${esc(f.ficha)}')" title="Eliminar ficha">🗑️</button>
      </div>
    </div>
  `).join('');
  
  updateBulkDeleteBtn();
}

let currentView = 'table';
function setView(view) {
  currentView = view;
  document.getElementById('view-container-table').style.display = view === 'table' ? 'block' : 'none';
  document.getElementById('view-container-grid').style.display = view === 'grid' ? 'block' : 'none';
  
  document.getElementById('btn-view-table').classList.toggle('active', view === 'table');
  document.getElementById('btn-view-grid').classList.toggle('active', view === 'grid');
  
  localStorage.setItem('fichas_view_pref', view);
}

// Cargar preferencia al iniciar
document.addEventListener('DOMContentLoaded', () => {
  const pref = localStorage.getItem('fichas_view_pref');
  if (pref) setView(pref);
});

function filterFichas() {
  const query = document.getElementById('search-ficha').value.toLowerCase().trim();
  const estadoFilter = document.getElementById('filter-estado').value.trim();
  
  const progSel = document.getElementById('filter-programa');
  // Support matching by ID (if passed via URL) or by name
  const progFilterVal = progSel.value;
  const progFilterId = progSel.options[progSel.selectedIndex]?.dataset?.id;

  const filtered = allFichas.filter(f => {
    const matchSearch = f.ficha.toLowerCase().includes(query) || 
                        (f.programa && f.programa.toLowerCase().includes(query));
    
    // Comparación robusta del estado
    const fEstado = (f.estado || '').trim();
    const matchEstado = (estadoFilter === '' || fEstado === estadoFilter);
    
    // Filtro por programa
    let matchProg = true;
    if (progFilterVal !== '') {
        // match either by name or id
        matchProg = (f.programa === progFilterVal || progFilterId == progFilterVal /* in case value is id from URL config */);
    }
    
    return matchSearch && matchEstado && matchProg;
  });
  renderFichas(filtered);
}

// ── Lógica del Modal de Borrado ──
function openDeleteModal(ficha) {
  targetFichaToDelete = ficha;
  document.getElementById('del-ficha-num').textContent = ficha;
  document.getElementById('confirm-ficha-input').value = '';
  document.getElementById('btn-confirm-delete').disabled = true;
  
  const modal = document.getElementById('modal-delete');
  modal.style.display = 'flex';
  setTimeout(() => document.getElementById('confirm-ficha-input').focus(), 100);
}

function closeDeleteModal() {
  document.getElementById('modal-delete').style.display = 'none';
  targetFichaToDelete = null;
}

function checkDeleteMatch() {
  const input = document.getElementById('confirm-ficha-input').value.trim();
  document.getElementById('btn-confirm-delete').disabled = (input !== targetFichaToDelete);
}

async function executeDelete() {
  if (!targetFichaToDelete) return;
  
  const btn = document.getElementById('btn-confirm-delete');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;margin-right:6px;border-width:2px;"></span> Eliminando...';

  try {
    const res = await fetch(API_FICHAS + '?ficha=' + encodeURIComponent(targetFichaToDelete), {
      method: 'DELETE'
    }).then(r => r.json());

    if (res.error) {
      showToast('❌ ' + res.error, 'danger');
    } else {
      // Mostrar resumen de éxito
      showToast(`✅ ${res.mensaje}`, 'success');
      closeDeleteModal();
      document.getElementById('search-ficha').value = '';
      loadFichas(); // Recargar lista
    }
  } catch (err) {
    showToast('⚠️ Error de red al intentar eliminar la ficha.', 'danger');
  } finally {
    btn.innerHTML = 'Eliminar Definitivamente';
  }
}

// ── Lógica de Borrado Masivo ──
function toggleAllFichas(source) {
  const checkboxes = document.querySelectorAll('.chk-ficha');
  checkboxes.forEach(cb => cb.checked = source.checked);
  updateBulkDeleteBtn();
}

function updateBulkDeleteBtn() {
  const selected = document.querySelectorAll('.chk-ficha:checked').length;
  const btn = document.getElementById('btn-bulk-delete');
  const countSpan = document.getElementById('bulk-count');
  
  if (selected > 0) {
    btn.style.display = 'inline-flex';
    countSpan.textContent = selected;
  } else {
    btn.style.display = 'none';
  }
  
  const chkAll = document.getElementById('chk-all-fichas');
  if (chkAll) {
      const allCb = document.querySelectorAll('.chk-ficha').length;
      chkAll.checked = (selected === allCb && allCb > 0);
  }
}

let targetFichasBulk = [];

function openBulkDeleteModal() {
  targetFichasBulk = Array.from(document.querySelectorAll('.chk-ficha:checked')).map(cb => cb.value);
  if (!targetFichasBulk.length) return;
  
  document.getElementById('bulk-del-count').textContent = targetFichasBulk.length;
  
  // Resetear estados del modal
  document.getElementById('bulk-delete-animation').style.display = 'none';
  document.getElementById('bulk-delete-actions').style.display = 'flex';
  document.getElementById('btn-confirm-bulk-delete').disabled = false;
  
  const modal = document.getElementById('modal-bulk-delete');
  modal.style.display = 'flex';
}

function closeBulkDeleteModal() {
  document.getElementById('modal-bulk-delete').style.display = 'none';
  targetFichasBulk = [];
}

async function executeBulkDelete() {
  if (!targetFichasBulk.length) return;
  
  const btn = document.getElementById('btn-confirm-bulk-delete');
  btn.disabled = true;
  
  // Mostrar animación de borrado
  document.getElementById('bulk-delete-actions').style.display = 'none';
  document.getElementById('bulk-delete-animation').style.display = 'flex';

  try {
    const res = await fetch(API_FICHAS + '?action=bulk_delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fichas: targetFichasBulk })
    }).then(r => r.json());

    if (res.error) {
      showToast('❌ ' + res.error, 'danger');
    } else {
      showToast(`✅ ${res.mensaje}`, 'success');
      document.getElementById('chk-all-fichas').checked = false;
      targetFichasBulk = [];
      await loadFichas();
      updateBulkDeleteBtn(); // Forzar actualización del botón para que se oculte
      closeBulkDeleteModal();
    }
  } catch (err) {
    showToast('⚠️ Error de red al intentar eliminar las fichas.', 'danger');
    // Restaurar estado en caso de error
    document.getElementById('bulk-delete-animation').style.display = 'none';
    document.getElementById('bulk-delete-actions').style.display = 'flex';
    btn.disabled = false;
  }
}

// ── Sistema de Notificaciones (Toast) ──
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:12px;';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  const bgColors = { success: 'rgba(0,166,80,0.95)', danger: 'rgba(218,54,51,0.95)', warning: 'rgba(210,153,34,0.95)' };
  
  toast.style.cssText = `
    background: ${bgColors[type] || 'rgba(56,139,253,0.95)'};
    color: white;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  `;
  toast.innerHTML = message;
  
  container.appendChild(toast);
  
  // Animar entrada
  requestAnimationFrame(() => {
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
  });

  // Remover después de 5 segundos
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(20px)';
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

function esc(str) {
  return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
