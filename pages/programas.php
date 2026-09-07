<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Programas de Formación';
$pageSubtitle = 'Gestión de programas y visualización de fichas asociadas';
$activePage   = 'programas';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- Toolbar: búsqueda + contador, sin envolverla en un card entero -->
<div class="toolbar mb-5">
  <div class="search-field">
    <?= icon('search') ?>
    <input type="text" id="search-programa" class="form-control"
           placeholder="Buscar por código o nombre del programa..." oninput="filterProgramas()"
           aria-label="Buscar programa" />
  </div>
  <div class="toolbar-sep" aria-hidden="true"></div>
  <span class="text-sm text-secondary" id="programas-count">Cargando…</span>
</div>

<div class="grid-cards" id="grid-programas">
  <div class="card"><span class="skeleton skeleton-text" style="width:35%"></span><span class="skeleton skeleton-text" style="width:85%;height:18px"></span><span class="skeleton skeleton-row"></span></div>
  <div class="card"><span class="skeleton skeleton-text" style="width:35%"></span><span class="skeleton skeleton-text" style="width:70%;height:18px"></span><span class="skeleton skeleton-row"></span></div>
  <div class="card"><span class="skeleton skeleton-text" style="width:35%"></span><span class="skeleton skeleton-text" style="width:90%;height:18px"></span><span class="skeleton skeleton-row"></span></div>
</div>

<style>
  .prog-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--space-5);
    transition: var(--transition);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    text-align: left;
    width: 100%;
    font: inherit;
    color: inherit;
  }
  .prog-card:hover {
    transform: translateY(-2px);
    border-color: var(--green-soft-bd);
    box-shadow: var(--shadow-md);
  }
  .pc-code {
    font-family: ui-monospace, Consolas, monospace;
    font-size: 11.5px; font-weight: 700;
    color: var(--text-secondary);
    background: var(--bg-subtle);
    border: 1px solid var(--border);
    padding: 3px 8px; border-radius: var(--radius-sm);
    align-self: flex-start;
  }
  .pc-name {
    font-size: 15px; font-weight: 700; color: var(--text-primary);
    line-height: 1.35; letter-spacing: -.01em;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
  }
  .pc-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); }
  .pc-stat {
    background: var(--bg-subtle);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 8px 6px;
    text-align: center;
  }
  .pc-stat-val { font-size: 17px; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
  .pc-stat-lbl { font-size: 10.5px; color: var(--text-secondary); font-weight: 500; margin-top: 2px; }
  .pc-stat.is-brand .pc-stat-val { color: var(--brand-text); }
  .pc-stat.is-green .pc-stat-val { color: var(--success); }
  .pc-stat.is-warn  .pc-stat-val { color: var(--warning); }
  .pc-footer {
    margin-top: auto; padding-top: var(--space-3);
    border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 12.5px; color: var(--text-secondary);
  }
  .prog-card:hover .pc-footer { color: var(--brand-text); }
  .pc-footer .ic { transition: transform var(--transition); }
  .prog-card:hover .pc-footer .ic { transform: translateX(3px); }
</style>

<script>
const API_PROG = '../api/programas.php';
let allProgramas = [];

async function init() {
  await loadProgramas();
}

async function loadProgramas() {
  try {
    allProgramas = await fetch(API_PROG + '?action=list').then(r => r.json());
    renderProgramas(allProgramas);
  } catch (e) {
    document.getElementById('grid-programas').innerHTML = emptyState(
      'alert-triangle', 'No se pudieron cargar los programas', 'Revisa la conexión con el servidor e inténtalo de nuevo.'
    );
  }
}

function emptyState(iconName, title, text) {
  return `<div class="card" style="grid-column:1/-1;">
    <div class="empty-state">
      <div class="empty-icon">${ic(iconName)}</div>
      <div class="empty-title">${esc(title)}</div>
      <p>${esc(text)}</p>
    </div>
  </div>`;
}

function renderProgramas(data) {
  const total = allProgramas.length;
  document.getElementById('programas-count').textContent =
    data.length === total
      ? `${total} programa${total === 1 ? '' : 's'} registrado${total === 1 ? '' : 's'}`
      : `${data.length} de ${total} programas`;

  const grid = document.getElementById('grid-programas');

  if (!data.length) {
    grid.innerHTML = emptyState('book', 'Sin resultados',
      total ? 'Ningún programa coincide con la búsqueda.' : 'Aún no hay programas registrados en el sistema.');
    return;
  }

  grid.innerHTML = data.map(p => `
    <button type="button" class="prog-card fade-in"
            onclick="window.location.href='fichas.php?programa=${encodeURIComponent(p.id_programa)}'">
      <span class="pc-code">${esc(p.codigo)}</span>
      <div class="pc-name" title="${esc(p.nombre)}">${esc(p.nombre)}</div>
      <div class="pc-stats">
        <div class="pc-stat is-brand"><div class="pc-stat-val">${p.total_fichas}</div><div class="pc-stat-lbl">Fichas</div></div>
        <div class="pc-stat is-green"><div class="pc-stat-val">${p.total_aprendices}</div><div class="pc-stat-lbl">Aprendices</div></div>
        <div class="pc-stat is-warn"><div class="pc-stat-val">${p.total_competencias}</div><div class="pc-stat-lbl">Competencias</div></div>
      </div>
      <div class="pc-footer">
        <span>Ver fichas asociadas</span>
        ${ic('arrow-right')}
      </div>
    </button>
  `).join('');
}

function filterProgramas() {
  const q = document.getElementById('search-programa').value.toLowerCase().trim();
  if (!q) return renderProgramas(allProgramas);
  renderProgramas(allProgramas.filter(p =>
    (p.nombre || '').toLowerCase().includes(q) || (p.codigo || '').toLowerCase().includes(q)
  ));
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
