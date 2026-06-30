<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Programas de Formación';
$pageSubtitle = 'Gestión de programas y visualización de fichas asociadas';
$activePage   = 'programas';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="card fade-in">
  <div class="card-header">
    <div>
      <div class="card-title">📚 Programas de Formación</div>
      <div class="card-subtitle" id="programas-count">Cargando...</div>
    </div>
  </div>

  <!-- Búsqueda -->
  <div class="filters-bar" style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
    <div class="filter-search" style="max-width:400px; flex:1;">
      <span class="search-icon">🔍</span>
      <input type="text" id="search-programa" class="form-control" placeholder="Buscar por código o nombre del programa..." oninput="filterProgramas()" />
    </div>
  </div>

  <div class="f-grid" id="grid-programas">
    <div style="grid-column: 1 / -1; text-align: center; padding: 32px;">
      <span class="spinner"></span>
    </div>
  </div>
</div>

<style>
  .f-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    padding: 10px 0;
  }
  .prog-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .prog-card:hover {
    transform: translateY(-5px);
    border-color: var(--sena-green);
    box-shadow: 0 12px 24px rgba(0,166,80,0.1);
  }
  .prog-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: var(--sena-green);
    opacity: 0; transition: 0.3s;
  }
  .prog-card:hover::after { opacity: 1; }

  .pc-header { display: flex; justify-content: space-between; align-items: flex-start; }
  .pc-code { font-family: monospace; font-size: 14px; font-weight: 700; color: var(--text-muted); background: var(--bg-input); padding: 4px 8px; border-radius: 6px; }
  .pc-name { font-size: 16px; font-weight: 800; color: var(--text-primary); line-height: 1.4; }
  
  .pc-stats { display: flex; gap: 12px; }
  .pc-stat-badge {
    background: rgba(56,139,253,0.1); border: 1px solid rgba(56,139,253,0.2);
    color: var(--info); padding: 8px 12px; border-radius: 8px;
    font-size: 13px; font-weight: 600; display: flex; flex-direction: column; align-items: center; flex: 1;
  }
  .pc-stat-badge.green { background: rgba(0,166,80,0.1); border-color: rgba(0,166,80,0.2); color: var(--success); }
  .pc-stat-badge.warn { background: rgba(210,153,34,0.1); border-color: rgba(210,153,34,0.2); color: var(--warning); }
  .pc-stat-val { font-size: 18px; font-weight: 800; }
  
  .pc-footer { margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
</style>

<script>
const API_PROG = '../api/programas.php';
let allProgramas = [];

async function init() {
  await loadProgramas();
}

async function loadProgramas() {
  const data = await fetch(API_PROG + '?action=list').then(r => r.json());
  allProgramas = data;
  renderProgramas(data);
}

function renderProgramas(data) {
  document.getElementById('programas-count').textContent = data.length + ' programa(s) registrados';
  const grid = document.getElementById('grid-programas');
  
  if (!data.length) {
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--text-secondary);">No hay programas registrados</div>';
    return;
  }

  grid.innerHTML = data.map(p => `
    <div class="prog-card fade-in" onclick="window.location.href='fichas.php?programa=${encodeURIComponent(p.id_programa)}'">
      <div class="pc-header">
        <span class="pc-code">Cód. ${esc(p.codigo)}</span>
      </div>
      <div class="pc-name" title="${esc(p.nombre)}">${esc(p.nombre)}</div>
      
      <div class="pc-stats">
        <div class="pc-stat-badge">
          <span class="pc-stat-val">${p.total_fichas}</span>
          <span>Fichas</span>
        </div>
        <div class="pc-stat-badge green">
          <span class="pc-stat-val">${p.total_aprendices}</span>
          <span>Aprendices</span>
        </div>
        <div class="pc-stat-badge warn">
          <span class="pc-stat-val">${p.total_competencias}</span>
          <span>Competencias</span>
        </div>
      </div>
      
      <div class="pc-footer">
        <span style="font-size:13px; color:var(--text-secondary);">Ver fichas asociadas</span>
        <span style="color:var(--sena-green);">→</span>
      </div>
    </div>
  `).join('');
}

function filterProgramas() {
  const query = document.getElementById('search-programa').value.toLowerCase().trim();
  const filtered = allProgramas.filter(p => {
    return p.nombre.toLowerCase().includes(query) || p.codigo.toLowerCase().includes(query);
  });
  renderProgramas(filtered);
}

function esc(str) {
  return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
