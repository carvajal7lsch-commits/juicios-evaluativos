<?php
define('ROOT_PATH', dirname(__DIR__));
$ficha = $_GET['ficha'] ?? '';
if (!$ficha) { header('Location: fichas.php'); exit; }

$pageTitle    = 'Ficha ' . $ficha;
$pageSubtitle = 'Panel de control centralizado de la ficha de formación';
$activePage   = 'fichas';

require_once ROOT_PATH . '/assets/icons.php';
$pageActions = '
  <button class="btn btn-outline btn-sm" onclick="openImportModal()">' . icon('upload') . ' Importar CSV</button>
  <button class="btn btn-primary btn-sm" onclick="openRegistroModal()">' . icon('pencil') . ' Registrar juicio</button>';

require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ══ CABECERA DE LA FICHA ══ -->
<div class="hero-ficha mb-5" id="hero-meta" hidden>
  <div class="hf-main">
    <div class="cluster-sm">
      <span class="hf-ficha mono" id="hf-ficha"></span>
      <span class="badge" id="hf-estado"></span>
    </div>
    <div class="hf-programa" id="hf-programa"></div>
    <div class="cluster text-sm text-secondary" style="margin-top:8px">
      <span><?= icon('calendar') ?> <span id="hf-fechas"></span></span>
      <span><?= icon('map-pin') ?> <span id="hf-modalidad"></span></span>
    </div>
  </div>
  <div class="hf-stats">
    <div class="hf-stat"><div class="hf-stat-val text-brand" id="stat-aprendices">0</div><div class="hf-stat-lbl">Aprendices</div></div>
    <div class="hf-stat"><div class="hf-stat-val text-warning" id="stat-competencias">0</div><div class="hf-stat-lbl">Competencias</div></div>
    <div class="hf-stat"><div class="hf-stat-val text-info" id="stat-resultados">0</div><div class="hf-stat-lbl">RAPs totales</div></div>
  </div>
</div>

<div class="tabs mb-5" role="tablist">
  <button id="tab-btn-aprendices" class="tab active" role="tab" onclick="switchTab('tab-aprendices',this)"><?= icon('users') ?> Aprendices</button>
  <button id="tab-btn-competencias" class="tab" role="tab" onclick="switchTab('tab-competencias',this)"><?= icon('book') ?> Programa y Resultados</button>
  <button id="tab-btn-juicios" class="tab" role="tab" onclick="switchTab('tab-juicios',this)"><?= icon('file-pen') ?> Juicios Evaluativos</button>
</div>

<!-- ══ TAB: APRENDICES ══ -->
<div id="tab-aprendices" class="tab-content active">
  <div class="toolbar mb-4">
    <span class="toolbar-label">Estado</span>
    <div class="cluster-sm" id="filter-pills-aprendices"></div>
    <div class="search-field push-right" style="max-width:280px">
      <?= icon('search') ?>
      <input type="text" id="search-aprendices" class="form-control" placeholder="Buscar por nombre o documento…"
             oninput="filterAprendices()" aria-label="Buscar aprendiz" />
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('users') ?> Listado de Aprendices</div>
        <div class="card-subtitle" id="aprendices-count">Cargando…</div>
      </div>
    </div>
    <div class="table-wrap is-scrollable">
      <table class="table">
        <thead>
          <tr><th>Nombre completo</th><th>Documento</th><th>Estado</th><th style="width:200px">Avance individual</th><th class="col-actions"></th></tr>
        </thead>
        <tbody id="tbody-aprendices">
          <tr><td colspan="5"><span class="skeleton skeleton-row"></span></td></tr>
          <tr><td colspan="5"><span class="skeleton skeleton-row"></span></td></tr>
          <tr><td colspan="5"><span class="skeleton skeleton-row"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══ TAB: COMPETENCIAS ══ -->
<div id="tab-competencias" class="tab-content">
  <div class="toolbar mb-4">
    <div class="search-field">
      <?= icon('search') ?>
      <input type="text" id="search-competencias" class="form-control"
             placeholder="Buscar por competencia, código o resultado de aprendizaje…"
             oninput="filterCompetencias()" aria-label="Buscar competencia" />
    </div>
    <span class="text-sm text-secondary" id="competencias-count"></span>
  </div>

  <div class="card card-flush">
    <div class="table-wrap is-scrollable">
      <table class="table">
        <thead>
          <tr><th>Competencia / Resultado</th><th style="width:130px">Código</th><th style="width:230px">Avance de la ficha (activos)</th></tr>
        </thead>
        <tbody id="tbody-competencias">
          <tr><td colspan="3"><span class="skeleton skeleton-row"></span></td></tr>
          <tr><td colspan="3"><span class="skeleton skeleton-row"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══ TAB: JUICIOS ══ -->
<div id="tab-juicios" class="tab-content">
  <div class="kpi-grid mb-5">
    <div class="kpi-card" style="--kpi-color:var(--danger-solid)">
      <div class="kpi-icon"><?= icon('alert-triangle') ?></div>
      <div class="kpi-body">
        <div class="kpi-value" id="radar-riesgo-count">0</div>
        <div class="kpi-label">Aprendices en riesgo crítico</div>
        <div class="kpi-trend">Progreso menor al 30%</div>
      </div>
    </div>
    <div class="kpi-card" style="--kpi-color:var(--warning-solid)">
      <div class="kpi-icon"><?= icon('siren') ?></div>
      <div class="kpi-body">
        <div class="kpi-value" id="radar-gap-count">0</div>
        <div class="kpi-label">Inconsistencias detectadas</div>
        <div class="kpi-trend">RAPs con registros incompletos</div>
      </div>
    </div>
    <div class="kpi-card" style="--kpi-color:var(--brand)">
      <div class="kpi-icon"><?= icon('file-pen') ?></div>
      <div class="kpi-body">
        <div class="kpi-value" id="radar-total-count">0</div>
        <div class="kpi-label">Registros históricos</div>
        <div class="kpi-trend">Juicios en la base de datos</div>
      </div>
    </div>
  </div>

  <div class="grid-main-aside">
    <!-- Explorador de juicios -->
    <div class="card card-flush">
      <div class="card-header">
        <div>
          <div class="card-title"><?= icon('search') ?> Explorador de juicios</div>
          <div class="card-subtitle">Filtra por estado o busca un aprendiz o RAP concreto</div>
        </div>
      </div>

      <div class="explorer-controls">
        <div class="cluster-sm mb-3" id="quick-filter-pills">
          <button class="pill pill-danger" data-filter="No Aprobado" onclick="setQuickFilter('No Aprobado', this)"><?= icon('x-circle') ?> No aprobados</button>
          <button class="pill" data-filter="Por evaluar" onclick="setQuickFilter('Por evaluar', this)"><?= icon('clock') ?> Por evaluar</button>
          <button class="pill" data-filter="Reciente" onclick="setQuickFilter('Reciente', this)"><?= icon('calendar') ?> Últimos 30 días</button>
          <button class="pill" data-filter="all" onclick="setQuickFilter('all', this)"><?= icon('layers') ?> Ver todo</button>
        </div>
        <div class="search-field">
          <?= icon('search') ?>
          <input type="text" id="search-juicios" class="form-control"
                 placeholder="Nombre del aprendiz, documento o código de RAP…"
                 oninput="filterJuicios()" aria-label="Buscar juicio" />
        </div>
      </div>

      <div id="juicios-results-container" class="juicios-grid"></div>
    </div>

    <!-- Alertas de gestión -->
    <div class="stack-lg">
      <div class="card" style="border-top:3px solid var(--danger-solid)">
        <div class="card-header mb-3">
          <div class="card-title"><?= icon('alert-triangle') ?> Requieren atención</div>
        </div>
        <div class="stack-sm" id="radar-list-riesgo"></div>
      </div>

      <div class="card" style="border-top:3px solid var(--warning-solid)">
        <div class="card-header mb-3">
          <div class="card-title"><?= icon('siren') ?> RAPs sin calificar del todo</div>
        </div>
        <div class="stack-sm" id="radar-list-gaps"></div>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: REGISTRO MANUAL ══ -->
<div class="modal-overlay" id="modal-registro">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reg-title">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="reg-title"><?= icon('pencil') ?> Registrar juicio evaluativo</div>
        <div class="modal-sub">Sólo aprendices de la ficha <?= htmlspecialchars($ficha) ?></div>
      </div>
      <button class="modal-close" onclick="closeRegistroModal()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <form id="form-juicio" onsubmit="saveJuicio(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="jf-aprendiz">Aprendiz <span class="req">*</span></label>
          <select id="jf-aprendiz" class="form-control" required onchange="loadResultadosParaAprendiz()">
            <option value="">Selecciona un aprendiz…</option>
          </select>
        </div>
        <div id="resultados-section" hidden>
          <div class="form-group">
            <label class="form-label" for="jf-resultado">Resultado de aprendizaje <span class="req">*</span></label>
            <select name="id_resultado" id="jf-resultado" class="form-control" required onchange="checkResultadoStatus()">
              <option value="">Selecciona un resultado…</option>
            </select>
            <div id="resultado-status" style="margin-top:8px"></div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="jf-tipo">Tipo de juicio <span class="req">*</span></label>
              <select name="estado" id="jf-tipo" class="form-control" required></select>
            </div>
            <div class="form-group">
              <label class="form-label" for="jf-funcionario">Funcionario evaluador <span class="req">*</span></label>
              <select name="documento_funcionario" id="jf-funcionario" class="form-control" required></select>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="jf-obs">Observaciones</label>
            <textarea name="observaciones" id="jf-obs" class="form-control" rows="3" placeholder="Opcional…"></textarea>
          </div>
          <div id="juicio-result" style="margin-top:14px"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeRegistroModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-save-juicio"><?= icon('save') ?> Guardar juicio</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: IMPORTACIÓN MASIVA ══ -->
<div class="modal-overlay" id="modal-import">
  <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="imp-title">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="imp-title"><?= icon('upload') ?> Importación masiva de juicios</div>
        <div class="modal-sub">Exclusivo para la ficha <?= htmlspecialchars($ficha) ?></div>
      </div>
      <button class="modal-close" onclick="closeImportModal()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <div class="dropzone" id="dz-juicios" onclick="document.getElementById('file-juicios').click()">
        <input type="file" id="file-juicios" accept=".csv" hidden onchange="handleFileJuicios(this.files[0])" />
        <div class="dropzone-icon"><?= icon('folder-open') ?></div>
        <div class="dropzone-title">Arrastra el CSV de juicios aquí</div>
        <div class="dropzone-sub">Debe contener las columnas <strong>documento_aprendiz</strong> y <strong>codigo_resultado</strong></div>
      </div>

      <div id="jpreview-section" style="margin-top:20px" hidden>
        <div class="cluster-between mb-3">
          <div>
            <div class="section-title">Vista previa</div>
            <div class="section-sub" id="jpreview-count"></div>
          </div>
          <button class="btn btn-success btn-sm" id="btn-jimport" onclick="doJuiciosImport()">
            <?= icon('upload') ?> Confirmar importación
          </button>
        </div>
        <div class="table-wrap" style="max-height:260px">
          <table class="table table-compact">
            <thead><tr><th>#</th><th>Doc. Aprendiz</th><th>Cód. Resultado</th><th>Juicio</th></tr></thead>
            <tbody id="jpreview-body"></tbody>
          </table>
        </div>
      </div>

      <div id="jimport-result" style="margin-top:16px"></div>
    </div>
  </div>
</div>

<style>
  /* ── Cabecera de ficha: el bloque de stats se estira en vez de dejar un hueco ── */
  .hero-ficha {
    display: flex; flex-wrap: wrap; gap: var(--space-5);
    align-items: center; justify-content: space-between;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-left: 3px solid var(--brand);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--space-5);
  }
  .hf-main { min-width: 260px; flex: 1; }
  .hf-ficha { font-size: 24px; font-weight: 800; color: var(--brand-text); letter-spacing: -.02em; }
  .hf-programa { font-size: 14.5px; font-weight: 600; color: var(--text-primary); margin-top: 4px; line-height: 1.4; }
  .hero-ficha .cluster .ic { width: 14px; height: 14px; color: var(--text-muted); }
  .hf-stats { display: flex; gap: var(--space-2); flex-wrap: wrap; }
  .hf-stat {
    background: var(--bg-subtle); border: 1px solid var(--border);
    border-radius: var(--radius-md); padding: 10px 18px;
    text-align: center; min-width: 108px;
  }
  .hf-stat-val { font-size: 22px; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
  .hf-stat-lbl { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; font-weight: 700; margin-top: 2px; }

  /* ── Explorador de juicios ── */
  .explorer-controls { padding: 0 var(--space-5) var(--space-4); border-bottom: 1px solid var(--border); }
  .juicios-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-3); padding: var(--space-4);
    max-height: 620px; overflow-y: auto;
  }
  .juicio-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-left: 3px solid var(--j-color, var(--text-muted));
    border-radius: var(--radius-md); padding: 13px;
    display: flex; flex-direction: column; gap: 10px;
  }
  .juicio-rap {
    background: var(--bg-subtle); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 8px 10px;
  }
  .juicio-rap-cod { font-size: 10px; font-weight: 800; color: var(--brand-text); text-transform: uppercase; letter-spacing: .05em; font-family: ui-monospace, Consolas, monospace; }
  .juicio-rap-txt {
    font-size: 12px; line-height: 1.45; color: var(--text-secondary); margin-top: 2px;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
  }
  .juicio-foot { display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 11px; color: var(--text-muted); }
  .juicio-foot .ic { width: 12px; height: 12px; }

  /* ── Alertas laterales ── */
  .alert-item {
    border-radius: var(--radius-md); padding: 10px 12px;
    border: 1px solid; font-size: 12px;
  }
  .alert-item.is-danger  { background: var(--danger-soft);  border-color: var(--danger-bd); }
  .alert-item.is-warning { background: var(--warning-soft); border-color: var(--warning-bd); cursor: pointer; }
  .alert-item.is-warning:hover { border-color: var(--warning-solid); }
  .rap-chip {
    display: inline-block; background: var(--bg-card); border: 1px solid var(--border);
    padding: 2px 7px; border-radius: var(--radius-sm); font-size: 10px; font-weight: 700; margin: 2px 2px 0 0;
  }

  /* ── Tabla de competencias ── */
  .comp-head-row td { background: var(--brand-soft); border-bottom: 2px solid var(--brand-soft-bd) !important; }
  .comp-head-code { font-size: 10.5px; font-weight: 800; color: var(--brand-text); text-transform: uppercase; letter-spacing: .06em; }
  .comp-head-name { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-top: 2px; }
  .rap-row td { vertical-align: top; }
  .rap-row td:first-child { padding-left: 30px; }
  .faltantes-box {
    margin-top: 9px; background: var(--danger-soft); border: 1px solid var(--danger-bd);
    border-radius: var(--radius-md); padding: 9px 11px; cursor: pointer;
  }
  .faltantes-box:hover { border-color: var(--danger-solid); }
  .faltantes-title { font-size: 10px; font-weight: 800; color: var(--danger); letter-spacing: .05em; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
  .faltantes-title .ic { width: 12px; height: 12px; }
  .avance-cell { display: flex; align-items: center; gap: 8px; }
  .avance-cell .progress-bar-wrap { flex: 1; }
  .avance-pct { font-size: 12px; font-weight: 800; min-width: 36px; text-align: right; font-variant-numeric: tabular-nums; }
</style>

<script>
const FICHA = <?= json_encode($ficha) ?>;
const API   = '../api/ficha_detalle.php?ficha=' + encodeURIComponent(FICHA);

let allJuiciosData = [], allAprendicesData = [], allCompetenciasData = [], selectedStates = [];
let currentQuickFilter = null, csvJuicios = [];

function switchTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
  btn.setAttribute('aria-selected', 'true');
}

async function init() {
  await Promise.all([loadMeta(), loadAprendices(), loadCompetencias(), loadJuicios()]);

  // Si venimos con ?openAvance=<doc>, saltar directo al seguimiento del aprendiz
  const doc = new URLSearchParams(location.search).get('openAvance');
  if (doc && allAprendicesData.some(a => a.documento === doc)) {
    location.href = `aprendiz_seguimiento.php?ficha=${encodeURIComponent(FICHA)}&documento=${encodeURIComponent(doc)}`;
  }
}

/* ================================================================
   META DE LA FICHA
   ================================================================ */
async function loadMeta() {
  try {
    const meta = await fetch(API + '&action=meta').then(r => r.json());
    if (meta.error) { showToast(esc(meta.error), 'danger'); setTimeout(() => location.href = 'fichas.php', 1500); return; }

    document.getElementById('hf-ficha').textContent     = meta.ficha;
    document.getElementById('hf-programa').textContent  = meta.codigo_programa + ' — ' + meta.programa;
    document.getElementById('hf-fechas').textContent    = meta.fecha_inicio + ' a ' + meta.fecha_fin;
    document.getElementById('hf-modalidad').textContent = meta.modalidad;

    const b = document.getElementById('hf-estado');
    b.textContent = meta.estado;
    b.className = 'badge ' + badgeFicha(meta.estado);

    document.getElementById('stat-aprendices').textContent   = meta.total_aprendices;
    document.getElementById('stat-competencias').textContent = meta.total_competencias;
    document.getElementById('stat-resultados').textContent   = meta.total_resultados ?? 0;

    document.getElementById('hero-meta').hidden = false;
  } catch (e) { showToast('No se pudo cargar la información de la ficha.', 'danger'); }
}

/* ================================================================
   APRENDICES
   ================================================================ */


async function loadAprendices() {
  try {
    allAprendicesData = await fetch(API + '&action=aprendices').then(r => r.json());
  } catch (e) { allAprendicesData = []; }

  const estados = new Set();
  allAprendicesData.forEach(a => {
    const raw = (a.estado || 'Desconocido').trim().toLowerCase();
    a.estado_normalized = raw.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    estados.add(a.estado_normalized);
  });

  document.getElementById('filter-pills-aprendices').innerHTML =
    Array.from(estados).sort().map(e =>
      `<button type="button" class="pill" data-state="${esc(e)}" onclick="toggleStatusFilter('${esc(e)}', this)">${esc(e)}</button>`
    ).join('');

  renderAprendices(allAprendicesData);
}

function toggleStatusFilter(state, el) {
  const idx = selectedStates.indexOf(state);
  if (idx > -1) selectedStates.splice(idx, 1); else selectedStates.push(state);
  el.classList.toggle('active');
  filterAprendices();
}

function renderAprendices(data) {
  const tb = document.getElementById('tbody-aprendices');
  document.getElementById('aprendices-count').textContent =
    `${data.length} de ${allAprendicesData.length} aprendices`;

  if (!data.length) {
    tb.innerHTML = `<tr><td colspan="5"><div class="empty-state">
      <div class="empty-icon">${ic('users')}</div>
      <div class="empty-title">Sin aprendices</div>
      <p>Ningún aprendiz coincide con los filtros aplicados.</p>
    </div></td></tr>`;
    return;
  }

  tb.innerHTML = data.map(a => {
    const aprob = parseInt(a.raps_aprobados || 0);
    const total = parseInt(a.total_raps || 0);
    const pct   = total > 0 ? Math.round(aprob / total * 100) : 0;
    const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';
    return `<tr>
      <td class="fw-600">${esc(a.nombre)} ${esc(a.apellidos)}</td>
      <td class="mono text-sm text-secondary">${esc(a.documento)}</td>
      <td><span class="badge ${badgeEstado(a.estado)}">${esc(a.estado_normalized)}</span></td>
      <td>
        <div class="avance-cell">
          <div class="progress-bar-wrap"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
          <span class="avance-pct">${pct}%</span>
        </div>
        <div class="text-xs text-muted" style="margin-top:3px">${aprob} de ${total} RAPs</div>
      </td>
      <td class="col-actions">
        <a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(FICHA)}&documento=${encodeURIComponent(a.documento)}"
           class="btn btn-outline btn-sm">${ic('trending-up')} Avance</a>
      </td>
    </tr>`;
  }).join('');
}

function filterAprendices() {
  const q = document.getElementById('search-aprendices').value.toLowerCase().trim();
  renderAprendices(allAprendicesData.filter(a =>
    (`${a.nombre} ${a.apellidos}`.toLowerCase().includes(q) || a.documento.toLowerCase().includes(q)) &&
    (selectedStates.length === 0 || selectedStates.includes(a.estado_normalized))
  ));
}

/* ================================================================
   COMPETENCIAS Y RESULTADOS
   ================================================================ */
async function loadCompetencias() {
  try {
    allCompetenciasData = await fetch(API + '&action=competencias').then(r => r.json());
    renderCompetencias(allCompetenciasData);
  } catch (e) { showToast('No se pudieron cargar las competencias.', 'danger'); }
}

function renderCompetencias(data) {
  const container = document.getElementById('tbody-competencias');

  if (!data.length) {
    document.getElementById('competencias-count').textContent = '';
    container.innerHTML = `<tr><td colspan="3"><div class="empty-state">
      <div class="empty-icon">${ic('book')}</div>
      <div class="empty-title">Sin resultados</div>
      <p>Ninguna competencia o RAP coincide con la búsqueda.</p>
    </div></td></tr>`;
    return;
  }

  const grouped = data.reduce((acc, r) => {
    (acc[r.competencia] ??= { name: r.competencia, code: r.cod_comp, hrs: r.duracion_horas, raps: [] }).raps.push(r);
    return acc;
  }, {});

  const grupos = Object.values(grouped);
  document.getElementById('competencias-count').textContent =
    `${grupos.length} competencia${grupos.length === 1 ? '' : 's'} · ${data.length} RAPs`;

  container.innerHTML = grupos.map(c => {
    const avgPct = Math.round(c.raps.reduce((sum, r) => {
      const total = parseInt(r.total_activos || 0);
      return sum + (total > 0 ? parseInt(r.aprobados_count || 0) / total : 0);
    }, 0) / c.raps.length * 100);

    const head = `<tr class="comp-head-row">
      <td colspan="2">
        <div class="comp-head-code">Competencia ${esc(c.code)}</div>
        <div class="comp-head-name">${esc(c.name)}</div>
        <div class="cluster-sm text-xs text-secondary" style="margin-top:5px">
          <span>${ic('timer')} ${c.hrs} horas</span>
          <span>${ic('book')} ${c.raps.length} resultados</span>
        </div>
      </td>
      <td class="text-right">
        <div class="fw-800 text-brand" style="font-size:19px">${avgPct}%</div>
        <div class="text-xs text-muted fw-700" style="text-transform:uppercase">Avance grupal</div>
      </td>
    </tr>`;

    const rows = c.raps.map(r => {
      const aprob = parseInt(r.aprobados_count || 0);
      const total = parseInt(r.total_activos || 0);
      const pct   = total > 0 ? Math.round(aprob / total * 100) : 0;
      const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';

      const faltantes = (r.faltantes && r.faltantes.length)
        ? `<div class="faltantes-box" onclick="quickSearchApprentice('', '${esc(r.cod_res)}')"
                role="button" tabindex="0" title="Ver estos aprendices en el explorador de juicios">
             <div class="faltantes-title">${ic('siren')} Faltan por calificar</div>
             <div>${r.faltantes.map(f => `<span class="rap-chip">${esc(f)}</span>`).join('')}</div>
           </div>`
        : '';

      return `<tr class="rap-row">
        <td><div class="fw-600 text-sm">${esc(r.resultado)}</div>${faltantes}</td>
        <td class="mono text-xs text-muted">${esc(r.cod_res)}</td>
        <td>
          <div class="avance-cell">
            <div class="progress-bar-wrap"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
            <span class="avance-pct">${pct}%</span>
          </div>
          <div class="text-xs text-muted text-right" style="margin-top:3px">${aprob} de ${total} aprobados</div>
        </td>
      </tr>`;
    }).join('');

    return head + rows;
  }).join('');
}

function filterCompetencias() {
  const q = document.getElementById('search-competencias').value.toLowerCase().trim();
  if (!q) return renderCompetencias(allCompetenciasData);
  renderCompetencias(allCompetenciasData.filter(r =>
    (r.competencia || '').toLowerCase().includes(q) ||
    (r.cod_res || '').toLowerCase().includes(q) ||
    (r.resultado || '').toLowerCase().includes(q)
  ));
}

/* ================================================================
   JUICIOS + RADAR DE ALERTAS
   ================================================================ */
async function loadJuicios() {
  try {
    allJuiciosData = await fetch(API + '&action=juicios').then(r => r.json());
    renderJuicios([]);
    renderRadar(await fetch(API + '&action=audit').then(r => r.json()));
  } catch (e) { showToast('No se pudieron cargar los juicios.', 'danger'); }
}

function renderRadar(auditData) {
  document.getElementById('radar-total-count').textContent = allJuiciosData.length;

  // ── Inconsistencias: RAPs aprobados por unos sí y otros no ──
  const gaps = (auditData || []).filter(r => r.total_aprobados > 0 && r.total_aprobados < r.total_grupo);
  document.getElementById('radar-gap-count').textContent = gaps.length;

  const gapList = document.getElementById('radar-list-gaps');
  gapList.innerHTML = gaps.length === 0
    ? `<p class="text-sm text-muted text-center">${ic('check-circle')} No se detectan inconsistencias grupales</p>`
    : gaps.slice(0, 5).map(g => `
        <div class="alert-item is-warning" onclick="quickSearchApprentice('', '${esc(g.codigo)}')"
             role="button" tabindex="0">
          <div class="fw-700 mono" style="font-size:11px">${esc(g.codigo)}</div>
          <div class="text-warning fw-800 text-xs" style="margin-top:2px">Faltan ${g.total_grupo - g.total_aprobados} aprendices</div>
          <div style="margin-top:6px">${g.faltantes.slice(0, 5).map(f => `<span class="rap-chip">${esc(f)}</span>`).join('')}</div>
        </div>`).join('');

  // ── Aprendices en riesgo ──
  const enRiesgo = allAprendicesData.filter(a => {
    const pct = a.total_raps > 0 ? a.raps_aprobados / a.total_raps * 100 : 0;
    return pct < 30 && esActivo(a.estado);
  });
  document.getElementById('radar-riesgo-count').textContent = enRiesgo.length;

  const riesgoList = document.getElementById('radar-list-riesgo');
  riesgoList.innerHTML = enRiesgo.length === 0
    ? `<p class="text-sm text-muted text-center">${ic('check-circle')} No hay aprendices en riesgo crítico</p>`
    : enRiesgo.slice(0, 5).map(a => {
        const pct = Math.round(a.raps_aprobados / a.total_raps * 100);
        return `<div class="alert-item is-danger cluster-between">
          <div class="flex-1">
            <div class="fw-700" style="font-size:12px">${esc(a.nombre + ' ' + a.apellidos)}</div>
            <div class="text-danger fw-600 text-xs">Progreso: ${pct}%</div>
          </div>
          <a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(FICHA)}&documento=${encodeURIComponent(a.documento)}"
             class="btn btn-outline btn-sm">Ver</a>
        </div>`;
      }).join('');
}

function setQuickFilter(f, el) {
  document.querySelectorAll('#quick-filter-pills .pill').forEach(b => b.classList.remove('active'));
  if (el) el.classList.add('active');
  currentQuickFilter = f;
  if (f === 'all') document.getElementById('search-juicios').value = '';
  filterJuicios();
}

function quickSearchApprentice(name, rapCode = '') {
  switchTab('tab-juicios', document.getElementById('tab-btn-juicios'));
  setQuickFilter('all', document.querySelector('#quick-filter-pills .pill[data-filter="all"]'));
  const input = document.getElementById('search-juicios');
  input.value = `${name} ${rapCode}`.trim();
  filterJuicios();
  input.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function filterJuicios() {
  const q = (document.getElementById('search-juicios').value || '').toLowerCase().trim();

  if (!q && !currentQuickFilter) { renderJuicios([]); return; }

  renderJuicios(allJuiciosData.filter(j => {
    const est = (j.estado || '').toLowerCase();
    const matchSearch = !q || `${j.aprendiz} ${j.documento_aprendiz} ${j.cod_resultado} ${j.resultado}`.toLowerCase().includes(q);

    let matchFilter = true;
    if (currentQuickFilter === 'No Aprobado') {
      matchFilter = est.includes('no aprobado');
    } else if (currentQuickFilter === 'Por evaluar') {
      matchFilter = est.includes('por evaluar') || est.includes('pendiente') || est === '' || est === 'null' || !j.estado;
    } else if (currentQuickFilter === 'Reciente') {
      if (!j.fecha_registro) return false;
      const limite = new Date(); limite.setDate(limite.getDate() - 30);
      matchFilter = new Date(j.fecha_registro) >= limite;
    }
    return matchSearch && matchFilter;
  }));
}

const JUICIO_CFG = {
  'aprobado':    { color: 'var(--success-solid)', badge: 'badge-success', icon: 'check-circle',   label: 'Aprobado' },
  'no aprobado': { color: 'var(--danger-solid)',  badge: 'badge-danger',  icon: 'x-circle',       label: 'No Aprobado' },
  'por evaluar': { color: 'var(--warning-solid)', badge: 'badge-warning', icon: 'clock',          label: 'Por evaluar' },
  'pendiente':   { color: 'var(--warning-solid)', badge: 'badge-warning', icon: 'clock',          label: 'Por evaluar' }
};

function renderJuicios(data) {
  const container = document.getElementById('juicios-results-container');
  const q = (document.getElementById('search-juicios').value || '').trim();

  // Estado inicial: aún no se ha buscado ni filtrado
  if (!q && !currentQuickFilter) {
    container.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
      <div class="empty-icon">${ic('search')}</div>
      <div class="empty-title">Empieza a buscar o elige un filtro</div>
      <p>Los ${allJuiciosData.length} registros históricos de esta ficha aparecerán aquí de forma organizada.</p>
    </div>`;
    return;
  }

  if (!data.length) {
    container.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
      <div class="empty-icon">${ic('inbox')}</div>
      <div class="empty-title">Sin registros para esta búsqueda</div>
      <p>${q ? `No existe ningún juicio que coincida con «${esc(q)}».` : 'No hay juicios que cumplan el filtro seleccionado.'}</p>
    </div>`;
    return;
  }

  container.innerHTML = data.slice(0, 500).map(j => {
    const c = JUICIO_CFG[(j.estado || 'Por evaluar').trim().toLowerCase()] || JUICIO_CFG['por evaluar'];
    return `<div class="juicio-card" style="--j-color:${c.color}">
      <div class="cluster-between" style="align-items:flex-start">
        <div class="flex-1">
          <div class="fw-700 text-sm">${esc(j.aprendiz)}</div>
          <div class="text-xs text-muted mono">${esc(j.documento_aprendiz)}</div>
        </div>
        <span class="badge ${c.badge}">${ic(c.icon)} ${esc(c.label)}</span>
      </div>
      <div class="juicio-rap">
        <div class="juicio-rap-cod">${esc(j.cod_resultado)}</div>
        <div class="juicio-rap-txt">${esc(j.resultado)}</div>
      </div>
      <div class="juicio-foot">
        <span>${ic('calendar')} ${j.fecha_registro ? esc(j.fecha_registro.substring(0, 16)) : 'Sin fecha'}</span>
        <span>Por: ${esc(j.funcionario || 'SENA')}</span>
      </div>
    </div>`;
  }).join('') + (data.length > 500
    ? `<p class="text-sm text-muted text-center" style="grid-column:1/-1;padding:14px">
         Mostrando los primeros 500 de ${data.length} resultados.</p>`
    : '');
}

/* ================================================================
   MODAL: REGISTRO MANUAL
   ================================================================ */
function openRegistroModal() {
  const sel = document.getElementById('jf-aprendiz');
  sel.innerHTML = '<option value="">Selecciona un aprendiz…</option>' +
    allAprendicesData.map(a =>
      `<option value="${esc(a.documento)}">${esc(a.nombre + ' ' + a.apellidos)} — ${esc(a.documento)}</option>`).join('');
  if (document.getElementById('jf-tipo').options.length === 0) loadFormSelects();
  openModal('modal-registro');
}

function closeRegistroModal() {
  closeModal('modal-registro');
  document.getElementById('resultados-section').hidden = true;
  document.getElementById('form-juicio').reset();
  document.getElementById('juicio-result').innerHTML = '';
  document.getElementById('resultado-status').innerHTML = '';
}

async function loadFormSelects() {
  try {
    const [tipos, funcionarios] = await Promise.all([
      fetch('../api/juicios.php?action=tipos').then(r => r.json()),
      fetch('../api/juicios.php?action=funcionarios').then(r => r.json())
    ]);
    document.getElementById('jf-tipo').innerHTML =
      tipos.map(t => `<option value="${esc(t.nombre)}">${esc(t.nombre)}</option>`).join('');
    document.getElementById('jf-funcionario').innerHTML =
      (funcionarios.length ? '' : '<option value="00000000">(Sin funcionario)</option>') +
      funcionarios.map(f => `<option value="${esc(f.documento)}">${esc(f.nombre_completo)}</option>`).join('');
  } catch (e) {}
}

async function loadResultadosParaAprendiz() {
  const doc = document.getElementById('jf-aprendiz').value;
  const section = document.getElementById('resultados-section');
  if (!doc) { section.hidden = true; return; }
  section.hidden = false;

  const data = await fetch('../api/juicios.php?action=resultados_by_aprendiz&documento=' + encodeURIComponent(doc)).then(r => r.json());
  const sel = document.getElementById('jf-resultado');
  sel.innerHTML = '<option value="">Selecciona un resultado…</option>';

  let lastComp = '', og = null;
  data.forEach(r => {
    if (r.competencia !== lastComp) {
      og = document.createElement('optgroup');
      og.label = r.competencia;
      sel.appendChild(og);
      lastComp = r.competencia;
    }
    const o = document.createElement('option');
    o.value = r.id_resultado;
    o.textContent = r.codigo + ' — ' + r.descripcion.substring(0, 60);
    o.dataset.juicio = r.juicio_actual || '';
    (og || sel).appendChild(o);
  });
}

function checkResultadoStatus() {
  const sel = document.getElementById('jf-resultado');
  const juicio = sel.options[sel.selectedIndex]?.dataset?.juicio;
  document.getElementById('resultado-status').innerHTML = juicio
    ? `<div class="alert alert-warning">${ic('alert-triangle')}<div>Ya existe un juicio <strong>${esc(juicio)}</strong>. Al guardar, se actualizará.</div></div>`
    : '';
}

async function saveJuicio(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  data.documento_aprendiz = document.getElementById('jf-aprendiz').value;

  const btn = document.getElementById('btn-save-juicio');
  const res = document.getElementById('juicio-result');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Guardando…';

  try {
    const r = await fetch('../api/juicios.php?action=save', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    }).then(r => r.json());

    if (r.ok) {
      res.innerHTML = `<div class="alert alert-success">${ic('check-circle')}<div>Juicio guardado correctamente.</div></div>`;
      showToast('Juicio guardado.', 'success');
      loadJuicios(); loadMeta(); loadAprendices();
      setTimeout(closeRegistroModal, 1200);
    } else {
      res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>${esc(r.error)}</div></div>`;
    }
  } catch (err) {
    res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error de red al guardar.</div></div>`;
  }

  btn.disabled = false;
  btn.innerHTML = ic('save') + ' Guardar juicio';
}

/* ================================================================
   MODAL: IMPORTACIÓN CSV
   ================================================================ */
function openImportModal() { openModal('modal-import'); }
function closeImportModal() { closeModal('modal-import'); cancelJuiciosImport(); }

function handleFileJuicios(file) {
  if (!file) return;
  Papa.parse(file, {
    header: true, skipEmptyLines: true,
    complete: r => { csvJuicios = r.data; showJuiciosPreview(r.data); }
  });
}

function showJuiciosPreview(rows) {
  document.getElementById('jpreview-section').hidden = false;
  document.getElementById('jpreview-count').textContent = `${rows.length} filas detectadas — se muestran las primeras 50`;
  document.getElementById('jpreview-body').innerHTML = rows.slice(0, 50).map((r, i) => `
    <tr>
      <td class="text-muted">${i + 1}</td>
      <td class="mono text-sm">${esc(r.documento_aprendiz || r.DOCUMENTO_APRENDIZ || '')}</td>
      <td class="mono text-sm">${esc(r.codigo_resultado || r.CODIGO_RESULTADO || '')}</td>
      <td><span class="badge badge-info">${esc(r.tipo_juicio || r.TIPO_JUICIO || 'Aprobado')}</span></td>
    </tr>`).join('');
}

async function doJuiciosImport() {
  const btn = document.getElementById('btn-jimport');
  const res = document.getElementById('jimport-result');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Importando…';

  try {
    const r = await fetch('../api/juicios.php?action=bulk', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ rows: csvJuicios })
    }).then(r => r.json());

    if (r.error) {
      res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>${esc(r.error)}</div></div>`;
    } else {
      res.innerHTML = `<div class="alert alert-success">${ic('check-circle')}<div>${r.insertados} insertados, ${r.actualizados} actualizados.</div></div>`;
      showToast('Importación completada.', 'success');
      loadJuicios(); loadMeta(); loadAprendices();
      setTimeout(closeImportModal, 1800);
    }
  } catch (e) {
    res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error de red durante la importación.</div></div>`;
  }

  btn.disabled = false;
  btn.innerHTML = ic('upload') + ' Confirmar importación';
}

function cancelJuiciosImport() {
  document.getElementById('jpreview-section').hidden = true;
  document.getElementById('file-juicios').value = '';
  document.getElementById('jimport-result').innerHTML = '';
  csvJuicios = [];
}

/* Arrastrar y soltar sobre la zona de importación */
const dzJ = document.getElementById('dz-juicios');
dzJ.addEventListener('dragover', e => { e.preventDefault(); dzJ.classList.add('dragover'); });
dzJ.addEventListener('dragleave', () => dzJ.classList.remove('dragover'));
dzJ.addEventListener('drop', e => { e.preventDefault(); dzJ.classList.remove('dragover'); handleFileJuicios(e.dataTransfer.files[0]); });

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
