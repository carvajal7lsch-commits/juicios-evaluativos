<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Fases y Actividades';
$pageSubtitle = 'Configuración del Proyecto Formativo y asignación de RAPs';
$activePage   = 'fases';

require_once ROOT_PATH . '/assets/icons.php';
$pageActions = '
  <button class="btn btn-danger-soft btn-sm" id="btn-borrar-estructura" onclick="borrarEstructura()" hidden>'
    . icon('trash') . ' Borrar estructura</button>
  <button class="btn btn-primary" onclick="openModal(\'modal-pdf-import\')">'
    . icon('zap') . ' Importar PDFs</button>';

require_once ROOT_PATH . '/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>

<!-- ══ TOOLBAR: selector de programa (antes ocupaba un card completo) ══ -->
<div class="toolbar mb-5">
  <span class="toolbar-label"><?= icon('book') ?> Programa</span>
  <select id="sel-programa" class="form-control flex-1" style="max-width:560px" onchange="loadFases()" aria-label="Seleccionar programa">
    <option value="">Cargando programas…</option>
  </select>
  <span class="text-sm text-secondary" id="prog-hint">Elige un programa para ver o construir su proyecto formativo</span>
</div>

<!-- Estado inicial -->
<div class="card" id="state-idle">
  <div class="empty-state">
    <div class="empty-icon"><?= icon('layers') ?></div>
    <div class="empty-title">Selecciona un programa de formación</div>
    <p>Podrás organizar sus fases y actividades, o importar los PDF de los proyectos formativos (uno o varios) para que el sistema detecte la estructura automáticamente.</p>
    <button class="btn btn-primary" onclick="openModal('modal-pdf-import')"><?= icon('zap') ?> Importar PDFs de proyectos</button>
  </div>
</div>

<!-- ══ PANEL PRINCIPAL ══ -->
<div class="grid-main-aside" id="panel-fases" hidden>
  <!-- Fases y actividades -->
  <div class="card card-flush">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('layers') ?> Fases del Proyecto</div>
        <div class="card-subtitle">Organiza el proyecto en fases y sus actividades</div>
      </div>
      <button class="btn btn-outline btn-sm" onclick="openModalFase()"><?= icon('plus') ?> Nueva fase</button>
    </div>
    <div id="lista-fases" class="fases-list"></div>
  </div>

  <!-- Asignación de RAPs -->
  <div class="card card-flush" id="panel-asignacion" data-locked="true">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('target') ?> <span id="titulo-actividad">Resultados de Aprendizaje</span></div>
        <div class="card-subtitle" id="subtitulo-asignacion">Selecciona una actividad para asignar RAPs</div>
      </div>
    </div>

    <div class="rap-controls">
      <div class="search-field mb-3">
        <?= icon('search') ?>
        <input type="text" id="search-rap" class="form-control" placeholder="Buscar RAP o competencia…"
               oninput="filterRAPs()" aria-label="Buscar RAP" />
      </div>
      <div class="cluster-between">
        <div class="cluster-sm">
          <button id="btn-filter-all" class="pill active" onclick="setRapFilter('all')">Todos</button>
          <button id="btn-filter-assigned" class="pill" onclick="setRapFilter('assigned')">Asignados</button>
        </div>
        <button id="btn-edit-mode" class="btn btn-outline btn-sm" onclick="toggleEditMode()">
          <?= icon('lock') ?> Modo lectura
        </button>
      </div>
    </div>

    <div id="lista-raps" class="raps-list">
      <div class="empty-state">
        <div class="empty-icon"><?= icon('target') ?></div>
        <p>Selecciona una actividad en el panel izquierdo.</p>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: importación por PDF ══ -->
<div class="modal-overlay" id="modal-pdf-import">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="pdf-imp-title">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="pdf-imp-title"><?= icon('zap') ?> Importación automática (PDF)</div>
        <div class="modal-sub">Sube uno o varios proyectos formativos: el sistema detecta Programa, Fases, Actividades y RAPs</div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-pdf-import')" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <!-- Un solo input para todos los modales: el de carga, la vista previa y la lista -->
      <input type="file" id="pdf-file" accept=".pdf,application/pdf" multiple hidden onchange="handlePDFFiles(this.files)" />
      <div class="dropzone" id="pdf-dropzone" onclick="document.getElementById('pdf-file').click()">
        <span class="badge badge-info dropzone-badge"><?= icon('layers') ?> Admite varios PDF a la vez</span>
        <div class="dropzone-icon"><?= icon('file-text') ?></div>
        <div class="dropzone-title">Arrastra aquí los PDF de los Proyectos Formativos</div>
        <div class="dropzone-sub">Suelta todos los que quieras juntos. No necesitas elegir el programa: se detecta por el código del PDF</div>
        <span class="btn btn-outline btn-sm dropzone-pick"><?= icon('folder-open') ?> Seleccionar archivos</span>
        <div class="dropzone-tip">En la ventana de selección mantén <kbd>Ctrl</kbd> para marcar varios, o <kbd>Ctrl</kbd> + <kbd>A</kbd> para todos</div>
      </div>
      <div id="pdf-loader" class="text-center" style="margin-top:18px" hidden>
        <span class="spinner"></span>
        <div class="text-sm fw-600 text-brand pdf-loader-text" style="margin-top:10px">Leyendo e interpretando el documento…</div>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: varios PDF — una fila por proyecto, se importan en cola ══ -->
<div class="modal-overlay" id="modal-pdf-batch">
  <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="pdf-batch-title">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="pdf-batch-title"><?= icon('layers') ?> Proyectos formativos a importar</div>
        <div class="modal-sub" id="pdf-batch-sub"></div>
      </div>
      <button class="modal-close" onclick="cancelPdfBatch()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>

    <div class="modal-body" id="pdf-batch-body">
      <div class="alert alert-info mb-4">
        <?= icon('info') ?>
        <div>Revisa el <strong>programa</strong> de cada PDF: se preselecciona con el código que trae el documento.
        Con <strong>Ver</strong> puedes revisar la estructura detectada antes de importar.</div>
      </div>
      <div class="table-wrap" style="max-height:52vh">
        <table class="table table-compact" style="font-size:12.5px">
          <thead><tr><th>Archivo</th><th style="min-width:230px">Programa</th><th>Estructura</th><th>Estado</th><th class="col-actions"></th></tr></thead>
          <tbody id="pdf-batch-tbody"></tbody>
        </table>
      </div>
      <div id="pdf-batch-loader" class="text-center" style="margin-top:14px" hidden>
        <span class="spinner spinner-sm"></span>
        <span class="text-sm fw-600 text-brand pdf-loader-text"></span>
      </div>
      <div id="pdf-batch-result" style="margin-top:16px"></div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" id="btn-pdf-add" style="margin-right:auto" onclick="document.getElementById('pdf-file').click()"
              title="También puedes arrastrar más PDF sobre esta ventana"><?= icon('plus') ?> Agregar más PDF</button>
      <button class="btn btn-outline" id="btn-pdf-batch-cancel" onclick="cancelPdfBatch()">Cancelar</button>
      <button class="btn btn-success" id="btn-pdf-batch-import" onclick="doPdfBatchImport()"><?= icon('upload-cloud') ?> Importar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: vista previa de la estructura extraída ══ -->
<div class="modal-overlay" id="modal-pdf-preview">
  <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="pdf-prev-title" style="height:88vh">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="pdf-prev-title"><?= icon('check-circle') ?> Documento analizado</div>
        <div class="modal-sub" id="pdf-prog-subtitle">Verifica la estructura extraída antes de guardarla</div>
      </div>
      <button class="modal-close" onclick="closePdfPreview()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>

    <div class="pdf-kpis">
      <div class="kpi-card" style="--kpi-color:var(--brand)">
        <div class="kpi-body"><div class="kpi-value" id="pdf-kpi-fases">0</div><div class="kpi-label">Fases detectadas</div></div>
      </div>
      <div class="kpi-card" style="--kpi-color:var(--success-solid)">
        <div class="kpi-body"><div class="kpi-value" id="pdf-kpi-acts">0</div><div class="kpi-label">Actividades</div></div>
      </div>
      <div class="kpi-card" style="--kpi-color:var(--warning-solid)">
        <div class="kpi-body"><div class="kpi-value" id="pdf-kpi-raps">0</div><div class="kpi-label">RAPs identificados</div></div>
      </div>
    </div>

    <div class="modal-body" id="pdf-preview-content"></div>

    <div class="modal-footer" style="justify-content:space-between">
      <div class="cluster-sm flex-1">
        <button class="btn btn-outline" id="btn-pdf-preview-add" onclick="document.getElementById('pdf-file').click()"><?= icon('plus') ?> Agregar más PDF</button>
        <span class="text-xs text-muted">Las competencias y RAPs se vincularán al programa sin duplicar registros.</span>
      </div>
      <div class="cluster-sm">
        <button class="btn btn-outline" id="btn-pdf-preview-cancel" onclick="closePdfPreview()">Cancelar</button>
        <button class="btn btn-success" id="btn-save-pdf" onclick="savePdfImport()"><?= icon('save') ?> Importar proyecto</button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: nueva fase ══ -->
<div class="modal-overlay" id="modal-fase">
  <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="fase-title">
    <div class="modal-header">
      <div class="modal-title" id="fase-title"><?= icon('layers') ?> Nueva fase</div>
      <button class="modal-close" onclick="closeModalFase()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" for="mf-nombre">Nombre de la fase <span class="req">*</span></label>
        <input type="text" id="mf-nombre" class="form-control" placeholder="Ej: Análisis">
      </div>
      <div class="form-group mb-0">
        <label class="form-label" for="mf-desc">Descripción</label>
        <textarea id="mf-desc" class="form-control" rows="3"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModalFase()">Cancelar</button>
      <button class="btn btn-primary" onclick="saveFase()"><?= icon('save') ?> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: nueva actividad ══ -->
<div class="modal-overlay" id="modal-actividad">
  <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="act-title">
    <div class="modal-header">
      <div class="modal-title" id="act-title"><?= icon('clipboard-list') ?> Nueva actividad</div>
      <button class="modal-close" onclick="closeModalActividad()" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ma-idfase">
      <div class="form-group">
        <label class="form-label" for="ma-nombre">Nombre de la actividad <span class="req">*</span></label>
        <input type="text" id="ma-nombre" class="form-control" placeholder="Ej: Recolección de requisitos">
      </div>
      <div class="form-group mb-0">
        <label class="form-label" for="ma-desc">Descripción</label>
        <textarea id="ma-desc" class="form-control" rows="3"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModalActividad()">Cancelar</button>
      <button class="btn btn-primary" onclick="saveActividad()"><?= icon('save') ?> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: confirmación genérica ══ -->
<div class="modal-overlay" id="modal-confirm">
  <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="modal-header">
      <div class="modal-title" id="confirm-title"></div>
      <button class="modal-close" onclick="closeModal('modal-confirm')" aria-label="Cerrar"><?= icon('x') ?></button>
    </div>
    <div class="modal-body"><p class="text-sm text-secondary" id="confirm-message"></p></div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="confirm-cancel">Cancelar</button>
      <button class="btn btn-primary" id="confirm-ok"></button>
    </div>
  </div>
</div>

<style>
  .fases-list { padding: var(--space-4); display: flex; flex-direction: column; gap: var(--space-3); max-height: 70vh; overflow-y: auto; }
  .fase-block {
    background: var(--bg-subtle); border: 1px solid var(--border);
    border-radius: var(--radius-md); padding: var(--space-4);
  }
  .fase-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
  .fase-name { font-size: 14px; font-weight: 700; color: var(--brand-text); }
  .fase-desc { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
  .act-item {
    display: flex; justify-content: space-between; align-items: center; gap: 10px;
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 9px 12px;
    cursor: pointer; transition: var(--transition); width: 100%; text-align: left; font: inherit;
  }
  .act-item:hover { border-color: var(--brand); background: var(--brand-soft); }
  .act-item.is-current { border-color: var(--brand); background: var(--brand-soft); }
  .act-name { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 7px; min-width: 0; }
  .act-name .ic { width: 14px; height: 14px; color: var(--text-muted); flex-shrink: 0; }
  .act-cta { font-size: 11px; color: var(--brand-text); font-weight: 600; white-space: nowrap; display: flex; align-items: center; gap: 4px; }
  .act-cta .ic { width: 12px; height: 12px; }

  .rap-controls { padding: 0 var(--space-4) var(--space-4); border-bottom: 1px solid var(--border); }
  .raps-list { padding: var(--space-4); max-height: 62vh; overflow-y: auto; }
  #panel-asignacion[data-locked="true"] .rap-controls,
  #panel-asignacion[data-locked="true"] .raps-list { opacity: .5; pointer-events: none; }

  .rap-comp-head {
    background: var(--brand-soft); border-left: 3px solid var(--brand);
    padding: 7px 11px; font-size: 11.5px; font-weight: 700; color: var(--brand-text);
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 8px;
  }
  .rap-item {
    display: flex; align-items: flex-start; gap: 11px;
    padding: 9px 11px; border: 1px solid var(--border);
    border-radius: var(--radius-sm); margin-bottom: 6px;
    background: var(--bg-card); transition: var(--transition);
  }
  .rap-item.is-assigned { border-color: var(--green-soft-bd); background: var(--green-soft); }
  .rap-item.is-editable { cursor: pointer; }
  .rap-item.is-editable:hover { border-color: var(--green); }
  .rap-item input { margin-top: 3px; flex-shrink: 0; }
  .rap-code { font-size: 11px; font-family: ui-monospace, Consolas, monospace; color: var(--text-muted); }
  .rap-item.is-assigned .rap-code { color: var(--success); font-weight: 700; }
  .rap-desc { font-size: 12.5px; color: var(--text-primary); line-height: 1.4; }
  .rap-item.is-dimmed { opacity: .62; }

  #btn-edit-mode.is-editing { border-color: var(--warning-solid); color: var(--warning); background: var(--warning-soft); }

  /* Vista previa del PDF */
  .pdf-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--border); flex-shrink: 0; }
  .pdf-fase { border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--space-3); }
  .pdf-fase-head { background: var(--green-soft); padding: 10px 14px; border-bottom: 1px solid var(--border); font-weight: 800; color: var(--success); font-size: 13px; }
  .pdf-fase-body { padding: var(--space-3); display: flex; flex-direction: column; gap: 10px; }
  .pdf-act { padding: 11px; border: 1px dashed var(--border); border-radius: var(--radius-sm); background: var(--bg-subtle); }
  .pdf-act-title { font-size: 12.5px; font-weight: 700; color: var(--brand-text); margin-bottom: 7px; }
  .pdf-comp { margin-left: 12px; margin-bottom: 7px; }
  .pdf-comp-title { font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: flex; align-items: center; gap: 5px; }
  .pdf-comp-title .ic { width: 12px; height: 12px; }
  .pdf-rap { font-size: 11px; background: var(--bg-card); border: 1px solid var(--border); padding: 5px 9px; border-radius: var(--radius-sm); margin-left: 12px; margin-bottom: 4px; }
  .pdf-rap-cod { font-family: ui-monospace, Consolas, monospace; color: var(--text-muted); margin-right: 6px; }
</style>

<script>
const API = '../api/proyecto.php';
let currentProgram = '', currentActividad = '';
let allFases = [], allRaps = [], allProgramas = [];
let currentRapFilter = 'all';
let isEditMode = false;
// Un elemento por PDF cargado.
// status: invalid (sin estructura) · pending · importing · done · failed
let pdfQueue = [];
let pdfImporting = false, pdfReading = false;
let pdfPreviewMode = 'single'; // 'batch' cuando la vista previa se abre desde la lista

/* ================================================================
   CARGA DE PROGRAMAS Y FASES
   ================================================================ */
/** Etiqueta de un programa en el desplegable, con lo que ya tiene cargado. */
function opcionPrograma(p) {
  const cola = p.fases > 0
    ? `${p.fases} fase${p.fases === 1 ? '' : 's'} · ${p.actividades} actividad${p.actividades === 1 ? '' : 'es'}`
    : 'sin proyecto cargado';
  return `<option value="${esc(p.id_programa)}">${esc(p.codigo)} — ${esc(p.nombre)} · ${esc(cola)}</option>`;
}

/**
 * Qué programa abrir al entrar.
 *
 * Aterrizar en una tarjeta que sólo dice "selecciona un programa" obliga a
 * dar un clic antes de ver nada, y sin saber cuál de los cinco tiene el
 * proyecto cargado. Se prioriza lo que pide la URL, luego lo último que
 * abrió este usuario, y si no, el primer programa que ya tenga fases.
 */
function programaPreferido() {
  const existe = v => allProgramas.some(p => String(p.id_programa) === v);
  const conFases = v => allProgramas.some(p => String(p.id_programa) === v && p.fases > 0);

  const pedido = new URLSearchParams(location.search).get('programa');
  if (pedido && existe(pedido)) return pedido;

  const ultimo = recordado('fases-programa');
  if (conFases(ultimo)) return ultimo;

  return String((allProgramas.find(p => p.fases > 0) ?? allProgramas[0])?.id_programa ?? '');
}

/** Pide los programas y rehace el desplegable (los recuentos cambian al importar). */
async function cargarProgramas() {
  const sel = document.getElementById('sel-programa');
  try {
    allProgramas = await fetch(API + '?action=programas').then(r => r.json());
  } catch (e) {
    sel.innerHTML = '<option value="">No se pudieron cargar los programas</option>';
    showToast('Error al cargar los programas.', 'danger');
    return false;
  }

  const cargados   = allProgramas.filter(p => p.fases > 0);
  const pendientes = allProgramas.filter(p => p.fases === 0);

  sel.innerHTML = '<option value="">Selecciona un programa…</option>'
    + (cargados.length ? `<optgroup label="Con proyecto formativo">${cargados.map(opcionPrograma).join('')}</optgroup>` : '')
    + (pendientes.length ? `<optgroup label="Sin proyecto cargado">${pendientes.map(opcionPrograma).join('')}</optgroup>` : '');
  return true;
}

/** Tras importar: refresca los recuentos y abre el programa indicado. */
async function mostrarProgramaImportado(idPrograma) {
  if (!await cargarProgramas()) return;
  const sel = document.getElementById('sel-programa');
  sel.value = String(idPrograma);
  loadFases();
}

async function init() {
  if (!await cargarProgramas()) return;
  const sel = document.getElementById('sel-programa');

  const inicial = programaPreferido();
  if (inicial) {
    sel.value = inicial;
    loadFases();
  }
}

async function loadFases() {
  currentProgram = document.getElementById('sel-programa').value;
  const hint = document.getElementById('prog-hint');

  if (!currentProgram) {
    document.getElementById('panel-fases').hidden = true;
    document.getElementById('state-idle').hidden = false;
    document.getElementById('btn-borrar-estructura').hidden = true;
    hint.textContent = 'Elige un programa para ver o construir su proyecto formativo';
    return;
  }

  document.getElementById('state-idle').hidden = true;
  document.getElementById('panel-fases').hidden = false;
  recordar('fases-programa', currentProgram);

  // Al cambiar de programa la actividad abierta ya no pertenece a lo que se
  // está viendo: se suelta para que renderFases() abra la primera de la nueva.
  currentActividad = '';
  document.getElementById('panel-asignacion').dataset.locked = 'true';

  try {
    allFases = await fetch(`${API}?action=fases&id_programa=${encodeURIComponent(currentProgram)}`).then(r => r.json());
  } catch (e) { allFases = []; }

  document.getElementById('btn-borrar-estructura').hidden = allFases.length === 0;
  hint.textContent = `${allFases.length} fase${allFases.length === 1 ? '' : 's'} configurada${allFases.length === 1 ? '' : 's'}`;
  renderFases();
}

async function renderFases() {
  const container = document.getElementById('lista-fases');

  if (!allFases.length) {
    container.innerHTML = `<div class="empty-state">
      <div class="empty-icon">${ic('layers')}</div>
      <div class="empty-title">Sin fases creadas</div>
      <p>Importa el PDF del proyecto formativo o crea la primera fase manualmente.</p>
    </div>`;
    return;
  }

  container.innerHTML = '<div class="text-center" style="padding:20px"><span class="spinner"></span></div>';

  // Las actividades se piden por fase; se arma todo antes de pintar para evitar parpadeos
  const bloques = await Promise.all(allFases.map(async f => {
    let acts = [];
    try { acts = await fetch(`${API}?action=actividades&id_fase=${f.id_fase}`).then(r => r.json()); } catch (e) {}

    const actsHtml = acts.length === 0
      ? '<p class="text-xs text-muted" style="margin-top:10px">Sin actividades</p>'
      : `<div class="stack-sm" style="margin-top:11px">${acts.map(a => `
          <button type="button" class="act-item" data-act="${a.id_actividad}"
                  onclick="selectActividad(${a.id_actividad}, '${esc(a.nombre_actividad).replace(/'/g, "\\'")}')">
            <span class="act-name">${ic('clipboard-list')}<span class="truncate">${esc(a.nombre_actividad)}</span></span>
            <span class="act-cta">Configurar ${ic('chevron-right')}</span>
          </button>`).join('')}</div>`;

    return `<div class="fase-block">
      <div class="fase-head">
        <div class="flex-1">
          <div class="fase-name">${esc(f.nombre_fase)}</div>
          ${f.descripcion ? `<div class="fase-desc">${esc(f.descripcion)}</div>` : ''}
        </div>
        <button class="btn btn-outline btn-sm" onclick="openModalActividad(${f.id_fase})">${ic('plus')} Actividad</button>
      </div>
      ${actsHtml}
    </div>`;
  }));

  container.innerHTML = bloques.join('');

  // El panel de RAPs arrancaba bloqueado hasta que se hiciera clic en una
  // actividad, así que media pantalla quedaba en gris al entrar. Se abre la
  // primera; se dispara el propio onclick para no duplicar el escapado del
  // nombre que ya hace la plantilla del botón.
  if (!currentActividad) {
    container.querySelector('.act-item')?.click();
  }
}

/* ================================================================
   ASIGNACIÓN DE RAPs
   ================================================================ */
async function selectActividad(id_actividad, nombre) {
  currentActividad = id_actividad;

  document.getElementById('panel-asignacion').dataset.locked = 'false';
  document.getElementById('titulo-actividad').textContent = nombre;
  document.getElementById('subtitulo-asignacion').textContent = 'Marca los RAPs que corresponden a esta actividad';
  document.querySelectorAll('.act-item').forEach(el =>
    el.classList.toggle('is-current', el.dataset.act == id_actividad));

  document.getElementById('lista-raps').innerHTML = '<div class="text-center" style="padding:36px"><span class="spinner"></span></div>';

  allRaps = await fetch(`${API}?action=raps_por_programa&id_programa=${currentProgram}&id_actividad=${id_actividad}`).then(r => r.json());

  // Si ya hay RAPs asignados, mostrarlos de entrada
  setRapFilter(allRaps.some(r => r.asignado) ? 'assigned' : 'all');

  isEditMode = false;
  updateEditModeUI();
}

function toggleEditMode() {
  isEditMode = !isEditMode;
  updateEditModeUI();
  filterRAPs();

  if (isEditMode) {
    showConfirm(
      'Modo edición activado',
      'La asignación manual está pensada para casos excepcionales (por ejemplo, RAPs transversales). Evita alterar la estructura oficial extraída del PDF sin justificación.',
      'Entendido', 'btn-primary', () => {}
    );
  }
}

function updateEditModeUI() {
  const btn = document.getElementById('btn-edit-mode');
  btn.innerHTML = isEditMode ? ic('lock-open') + ' Modo edición' : ic('lock') + ' Modo lectura';
  btn.classList.toggle('is-editing', isEditMode);
}

function setRapFilter(filter) {
  currentRapFilter = filter;
  document.getElementById('btn-filter-all').classList.toggle('active', filter === 'all');
  document.getElementById('btn-filter-assigned').classList.toggle('active', filter === 'assigned');
  filterRAPs();
}

function filterRAPs() {
  const q = document.getElementById('search-rap').value.toLowerCase().trim();
  const container = document.getElementById('lista-raps');

  let filtered = allRaps.filter(r =>
    `${r.competencia} ${r.codigo} ${r.descripcion}`.toLowerCase().includes(q));
  if (currentRapFilter === 'assigned') filtered = filtered.filter(r => r.asignado);

  if (!filtered.length) {
    container.innerHTML = `<div class="empty-state">
      <div class="empty-icon">${ic('search')}</div>
      <p>${currentRapFilter === 'assigned'
        ? 'Esta actividad todavía no tiene RAPs asignados.'
        : 'Ningún RAP coincide con la búsqueda.'}</p>
    </div>`;
    return;
  }

  const grouped = filtered.reduce((acc, r) => { (acc[r.competencia] ??= []).push(r); return acc; }, {});

  container.innerHTML = Object.entries(grouped).map(([comp, raps]) => `
    <div class="mb-4">
      <div class="rap-comp-head">${esc(comp)}</div>
      ${raps.map(r => `
        <label class="rap-item ${r.asignado ? 'is-assigned' : ''} ${isEditMode ? 'is-editable' : ''} ${!isEditMode && !r.asignado ? 'is-dimmed' : ''}">
          <input type="checkbox" ${r.asignado ? 'checked' : ''} ${isEditMode ? '' : 'disabled'}
                 onchange="toggleRap(${r.id_resultado}, this.checked)">
          <span>
            <span class="rap-code">${esc(r.codigo)}</span>
            <span class="rap-desc" style="display:block">${esc(r.descripcion)}</span>
          </span>
        </label>`).join('')}
    </div>`).join('');
}

async function toggleRap(id_resultado, asignar) {
  try {
    await fetch(`${API}?action=asignar_rap`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_actividad: currentActividad, id_resultado, asignar })
    });
    const rap = allRaps.find(r => r.id_resultado === id_resultado);
    if (rap) rap.asignado = asignar ? 1 : 0;
    // En la vista "Asignados" se deja ver la animación del check antes de que desaparezca
    if (currentRapFilter === 'assigned') setTimeout(filterRAPs, 300); else filterRAPs();
  } catch (e) { showToast('No se pudo actualizar la asignación.', 'danger'); }
}

/* ================================================================
   EXTRACCIÓN DE PDF (PDF.js)
   ================================================================ */
const pdfFileKey = f => `${f.name}|${f.size}|${f.lastModified}`;
const esPdf      = f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name);

function programaPorCodigo(codigo) {
  return codigo ? allProgramas.find(p => String(p.codigo).trim() === String(codigo)) : null;
}
function programaPorId(id) {
  return allProgramas.find(p => String(p.id_programa) === String(id));
}

/** Muestra (o quita) el aviso de lectura en el modal de carga y en la lista. */
function setPdfProgress(texto) {
  pdfReading = !!texto;
  ['pdf-loader', 'pdf-batch-loader'].forEach(id => {
    const el = document.getElementById(id);
    el.hidden = !texto;
    if (texto) el.querySelector('.pdf-loader-text').textContent = texto;
  });
  // Desde la vista previa de un solo PDF el aviso va en el propio botón
  const add = document.getElementById('btn-pdf-preview-add');
  add.disabled  = pdfReading;
  add.innerHTML = pdfReading ? '<span class="spinner spinner-sm"></span> Leyendo…' : ic('plus') + ' Agregar más PDF';
  document.getElementById('btn-pdf-add').disabled = pdfReading || pdfImporting;
}

// Los PDF nuevos se suman a la lista; si no hay lista, arranca una
async function handlePDFFiles(fileList) {
  const input = document.getElementById('pdf-file');
  const all   = Array.from(fileList || []);
  input.value = ''; // permite volver a elegir el mismo archivo
  if (!all.length || pdfImporting) return;
  if (pdfReading) return showToast('Espera a que terminen de leerse los PDF anteriores.', 'info');

  const pdfs = all.filter(esPdf);
  if (pdfs.length < all.length) {
    const n = all.length - pdfs.length;
    showToast(n === 1 ? 'Se ignoró un archivo que no es PDF.' : `Se ignoraron ${n} archivos que no son PDF.`, 'warning');
  }

  const yaEstan = new Set(pdfQueue.map(it => it.key));
  const files   = pdfs.filter(f => !yaEstan.has(pdfFileKey(f)));
  if (files.length < pdfs.length) {
    const n = pdfs.length - files.length;
    showToast(n === 1 ? 'Ese PDF ya estaba en la lista.' : `${n} PDF ya estaban en la lista.`, 'info');
  }
  if (!files.length) return;

  // Si se agregan PDF desde la vista previa de uno solo, conservar el programa ya elegido ahí
  const manual = document.getElementById('pdf-manual-prog');
  if (pdfQueue.length === 1 && pdfPreviewMode === 'single' && manual && !pdfQueue[0].idPrograma) {
    pdfQueue[0].idPrograma = manual.value;
  }

  // Se leen en serie: PDF.js con muchos documentos a la vez dispara la memoria
  for (const [i, file] of files.entries()) {
    setPdfProgress(files.length > 1
      ? `Leyendo e interpretando documento ${i + 1} de ${files.length}…`
      : 'Leyendo e interpretando el documento…');
    pdfQueue.push(await extractPdf(file));
  }
  setPdfProgress(null);

  if (pdfQueue.length === 1) {
    const item = pdfQueue[0];
    if (!item.data) {
      showToast(item.error, 'danger', 6000);
      pdfQueue = [];
      return;
    }
    showPdfPreview(item, 'single');
  } else {
    closeModal('modal-pdf-import');
    closeModal('modal-pdf-preview');
    showPdfBatch();
  }
}

async function extractPdf(file) {
  const item = { key: pdfFileKey(file), name: file.name, data: null, code: null, idPrograma: '', counts: null, status: 'invalid', error: null };
  try {
    const pdf = await pdfjsLib.getDocument(new Uint8Array(await file.arrayBuffer())).promise;
    let fullText = '';
    for (let i = 1; i <= pdf.numPages; i++) {
      const page = await pdf.getPage(i);
      const textContent = await page.getTextContent();
      fullText += textContent.items.map(it => it.str).join(' ') + ' \n';
    }
    pdf.destroy();

    // Se guarda el texto crudo en el servidor para poder depurar la extracción
    fetch('../api/save_debug.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ text: fullText })
    }).catch(() => {});

    const { data, programCode } = parseProjectTextV2(fullText);
    const prog = programaPorCodigo(programCode);
    Object.assign(item, {
      data, code: programCode, counts: contarEstructura(data),
      idPrograma: prog ? String(prog.id_programa) : '',
    });

    if (!item.counts.fases || (!item.counts.acts && !item.counts.raps)) {
      item.error = 'No se pudo extraer una estructura válida.';
    } else {
      item.status = 'pending';
    }
  } catch (e) {
    item.error = 'Error procesando el PDF: ' + e.message;
  }
  return item;
}

function contarEstructura(data) {
  let acts = 0, raps = 0;
  data.forEach(f => (f.actividades || []).forEach(a => {
    acts++;
    (a.competencias || []).forEach(c => raps += (c.raps || []).length);
  }));
  return { fases: data.length, acts, raps };
}

function parseProjectTextV2(text) {
  let n = text.replace(/\s+/g, ' ');
  // Normalizar tildes y caracteres especiales (crucial en PDFs con tildes separadas)
  n = n.normalize('NFC');

  let fullText = n; // copia para búsquedas globales

  // Extraer código del programa ANTES de recortar el texto
  let progMatch = fullText.match(/(\d{5,10})\s+C[oó]digo\s+del\s+Programa\s+SOFIA/i);
  const programCode = progMatch ? progMatch[1] : null;

  // Delimitar la búsqueda a la sección de estructura (secciones 3 a 3.5 aprox.)
  let startSection3 = n.search(/3\.\s*(?:Fases|Planeación)/i);
  let endSection3 = n.search(/3\.5\s+Organización/i);
  if (startSection3 !== -1 && endSection3 !== -1) {
    n = n.substring(startSection3, endSection3);
  }

  let data = [];

  const FASES_SENA = {
    'ANÁLISIS': 'ANALISIS', 'ANALISIS': 'ANALISIS',
    'PLANEACIÓN': 'PLANEACION', 'PLANEACION': 'PLANEACION',
    'EJECUCIÓN': 'EJECUCION', 'EJECUCION': 'EJECUCION',
    'EVALUACIÓN': 'EVALUACION', 'EVALUACION': 'EVALUACION'
  };

  let compRegex = /(\d{9,12})[\s\-\.]+/g;
  let anchors = [...n.matchAll(compRegex)];

  let dataDict = {};
  let lastKnownFase = 'ANALISIS';
  let lastKnownAct = 'Actividad General';

  anchors.forEach((anchor, index) => {
    let compCode = anchor[1];
    let anchorIdx = anchor.index;

    let startSearch = (index === 0) ? 0 : (anchors[index - 1].index + anchors[index - 1][0].length);
    let segment = n.substring(startSearch, anchorIdx);

    // RAPs: estrictamente 6 a 8 dígitos
    let rapMatches = [...segment.matchAll(/(\b\d{6,8}\b)[\s\-\.]+/g)];

    let nextAnchor = anchors[index + 1];
    let endComp = nextAnchor ? nextAnchor.index : anchorIdx + 800;
    let compDesc = n.substring(anchorIdx + anchor[0].length, endComp).trim();

    // Limpieza de la competencia: cortar ante el primer indicio de fase, actividad o fecha
    let phaseMarkers = Object.keys(FASES_SENA).join('|');
    let stopRegex = new RegExp('(?:^|\\s)(?:' + phaseMarkers + '|Actividad)\\s+\\d+[.\\-\\s]', 'i');
    let stopIdx = compDesc.search(stopRegex);
    if (stopIdx !== -1 && stopIdx > 5) {
      compDesc = compDesc.substring(0, stopIdx).trim();
    }

    // También cortar si aparece otro código (RAP o competencia)
    let nextMarker = compDesc.search(/\d{6,10}[\s\-\.]+/);
    if (nextMarker !== -1) compDesc = compDesc.substring(0, nextMarker).trim();

    rapMatches.forEach(rm => {
      let rapCode = rm[1];
      if (rapCode === compCode) return;

      let rapPosInN = startSearch + rm.index;
      let headerWindow = n.substring(Math.max(0, rapPosInN - 1500), rapPosInN).trim();
      let foundFase = null;

      // Buscar la fase/actividad más cercana hacia atrás (la última coincidencia)
      Object.keys(FASES_SENA).forEach(f => {
        let fRegex = new RegExp('(?:^|\\s|\\d+\\.)(' + f + ')(?=[\\s\\d\\.\\-]|$)', 'gi');
        let matches = [...headerWindow.matchAll(fRegex)];
        if (matches.length > 0) {
          let lastMatch = matches[matches.length - 1];
          let fIdx = lastMatch.index;
          if (!foundFase || fIdx > foundFase.idx) {
            foundFase = { name: FASES_SENA[f], idx: fIdx, original: lastMatch[1] };
          }
        }
      });

      if (foundFase) {
        lastKnownFase = foundFase.name;
        let actCandidate = headerWindow.substring(foundFase.idx + foundFase.original.length).trim();

        // Cortar antes del RAP actual o de cualquier otro código sospechoso
        let nextCodeIdx = actCandidate.search(/\d{6,10}/);
        if (nextCodeIdx !== -1) actCandidate = actCandidate.substring(0, nextCodeIdx).trim();

        let numMatch = actCandidate.match(/^(\d+)[.\-\s]*/);
        let actNum = numMatch ? numMatch[1] : null;
        actCandidate = actCandidate.replace(/^[\s.\-:,0-9]+/, '').trim();

        if (actNum) {
          lastKnownAct = `Actividad número ${actNum}: ${actCandidate}`;
        } else if (actCandidate.length > 10) {
          lastKnownAct = actCandidate;
        }
      }

      let rapSegment = segment.substring(rm.index + rm[0].length);
      let nextMarkerInRap = rapSegment.search(/\d{6,10}/);
      let rapDesc = (nextMarkerInRap !== -1) ? rapSegment.substring(0, nextMarkerInRap).trim() : rapSegment.trim();

      // Limpiar ruido en la descripción del RAP
      rapDesc = rapDesc.replace(/^\d+[\s\-\.]*/, '').trim();
      let phaseHeaderRegex = new RegExp('\\b(?:' + Object.keys(FASES_SENA).join('|') + '|Actividad)\\s+\\d+[.\\-\\s]', 'i');
      let phaseIdx = rapDesc.search(phaseHeaderRegex);
      if (phaseIdx !== -1) rapDesc = rapDesc.substring(0, phaseIdx).trim();

      if (rapDesc.length > 10 && !rapDesc.match(/^\d+$/)) {
        registrarDato(lastKnownFase, lastKnownAct, compCode, compDesc, rapCode, rapDesc);
      }
    });
  });

  // Barrido final: sólo si NO se detectó ninguna actividad de etapa práctica
  let yaTienePractica = false;
  Object.values(dataDict).forEach(f => {
    if (Object.values(f.actividades).some(a => a.competencias['999999999'])) yaTienePractica = true;
  });

  if (!yaTienePractica && fullText.toUpperCase().includes('ETAPA PRACTICA')) {
    registrarDato('EVALUACION', 'Actividad número 9: DESARROLLAR LA ETAPA PRODUCTIVA', '999999999',
      'RESULTADOS DE APRENDIZAJE ETAPA PRACTICA', '202634',
      'APLICAR EN LA RESOLUCIÓN DE PROBLEMAS REALES DEL SECTOR PRODUCTIVO');
  }

  const FASE_DISPLAY = { 'ANALISIS': 'ANÁLISIS', 'PLANEACION': 'PLANEACIÓN', 'EJECUCION': 'EJECUCIÓN', 'EVALUACION': 'EVALUACIÓN' };

  ['ANALISIS', 'PLANEACION', 'EJECUCION', 'EVALUACION'].forEach(fKey => {
    if (dataDict[fKey]) {
      let f = { nombre: 'Fase de ' + FASE_DISPLAY[fKey], actividades: [] };
      Object.values(dataDict[fKey].actividades).forEach(act => {
        f.actividades.push({ nombre: act.nombre, competencias: Object.values(act.competencias) });
      });
      data.push(f);
    }
  });

  function registrarDato(fase, act, cCode, cDesc, rCode, rDesc) {
    if (!dataDict[fase]) dataDict[fase] = { nombre: fase, actividades: {} };
    let actNumMatch = act.match(/Actividad número (\d+)/i);
    let aKey = actNumMatch ? ('act_' + actNumMatch[1]) : act.substring(0, 30).replace(/[^a-z0-9]/gi, '_');

    if (!dataDict[fase].actividades[aKey]) dataDict[fase].actividades[aKey] = { nombre: act, competencias: {} };
    let actRef = dataDict[fase].actividades[aKey];
    if (act.length > actRef.nombre.length) actRef.nombre = act;

    if (!actRef.competencias[cCode]) {
      actRef.competencias[cCode] = { codigo: cCode, nombre: cDesc, raps: [] };
    } else {
      // Un nombre más corto pero razonable suele estar más limpio (sin encabezados pegados)
      if (cDesc.length < actRef.competencias[cCode].nombre.length && cDesc.length > 20) {
        actRef.competencias[cCode].nombre = cDesc;
      } else if (cDesc.length > actRef.competencias[cCode].nombre.length && actRef.competencias[cCode].nombre.length < 20) {
        actRef.competencias[cCode].nombre = cDesc;
      }
    }

    if (!actRef.competencias[cCode].raps.find(r => r.codigo === rCode)) {
      actRef.competencias[cCode].raps.push({ codigo: rCode, descripcion: rDesc });
    }
  }

  return { data, programCode };
}

/** Opciones de programa para los selectores de la importación. */
function opcionesProgramas(seleccionado) {
  return '<option value="">Elige el programa…</option>' + allProgramas.map(p =>
    `<option value="${esc(p.id_programa)}" ${String(p.id_programa) === String(seleccionado) ? 'selected' : ''}>${esc(p.codigo)} — ${esc(p.nombre)}</option>`
  ).join('');
}

/**
 * Vista previa de la estructura de un PDF.
 * mode 'single': un solo PDF, se importa desde aquí.
 * mode 'batch' : consulta desde la lista; el programa se elige en la tabla.
 */
function showPdfPreview(item, mode) {
  pdfPreviewMode = mode;
  const batch   = mode === 'batch';
  const detect  = programaPorCodigo(item.code);
  const counts  = item.counts || { fases: 0, acts: 0, raps: 0 };
  const valida  = item.status !== 'invalid';

  let html = detect
    ? `<div class="alert alert-success mb-4">${ic('check-circle')}<div>
         <strong>Programa detectado: ${esc(detect.codigo)} — ${esc(detect.nombre)}</strong>
         <div class="text-xs">Se vinculará automáticamente por el código del PDF.</div>
       </div></div>`
    : item.code
      ? `<div class="alert alert-warning mb-4">${ic('alert-triangle')}<div>
           <strong>El PDF indica el código ${esc(item.code)}, pero ese programa no está registrado</strong>
           <div class="text-xs">${batch ? 'Elige el programa en la lista' : 'Elige abajo a qué programa pertenece'}, o importa primero el reporte de Sofia Plus de una de sus fichas.</div>
         </div></div>`
      : `<div class="alert alert-warning mb-4">${ic('alert-triangle')}<div>
           <strong>No se detectó un código de programa claro en el PDF</strong>
           <div class="text-xs">${batch ? 'Elige el programa en la lista' : 'Selecciona el programa abajo'} antes de confirmar la importación.</div>
         </div></div>`;

  if (!valida) {
    html = `<div class="empty-state">
      <div class="empty-icon">${ic('alert-triangle')}</div>
      <div class="empty-title">No se pudo extraer una estructura válida</div>
      <p>Intenta con otro documento o construye las fases manualmente.</p>
    </div>`;
  } else {
    // Sin coincidencia por código se pide el programa aquí mismo, antes de la estructura
    if (!detect && !batch) {
      html += `<div class="form-group mb-4">
        <label class="form-label" for="pdf-manual-prog">Programa al que pertenece esta estructura <span class="req">*</span></label>
        <select id="pdf-manual-prog" class="form-control">${opcionesProgramas(item.idPrograma || currentProgram)}</select>
      </div>`;
    }

    item.data.forEach((fase, fIdx) => {
      html += `<div class="pdf-fase">
        <div class="pdf-fase-head">${fIdx + 1}. ${esc(fase.nombre)}</div>
        <div class="pdf-fase-body">`;
      (fase.actividades || []).forEach((act, aIdx) => {
        html += `<div class="pdf-act">
          <div class="pdf-act-title">Actividad ${fIdx + 1}.${aIdx + 1}: ${esc(act.nombre)}</div>`;
        (act.competencias || []).forEach(comp => {
          html += `<div class="pdf-comp">
            <div class="pdf-comp-title">${ic('book')} ${esc(comp.codigo)} — ${esc(comp.nombre)}</div>`;
          (comp.raps || []).forEach(rap => {
            html += `<div class="pdf-rap"><span class="pdf-rap-cod">${esc(rap.codigo)}</span>${esc(rap.descripcion)}</div>`;
          });
          html += `</div>`;
        });
        html += `</div>`;
      });
      html += `</div></div>`;
    });
  }

  document.getElementById('pdf-kpi-fases').textContent = counts.fases;
  document.getElementById('pdf-kpi-acts').textContent  = counts.acts;
  document.getElementById('pdf-kpi-raps').textContent  = counts.raps;
  document.getElementById('pdf-prog-subtitle').textContent = batch
    ? item.name
    : 'Verifica la estructura extraída antes de guardarla';

  document.getElementById('btn-save-pdf').hidden          = batch || !valida;
  document.getElementById('btn-pdf-preview-add').hidden   = batch;
  document.getElementById('btn-pdf-preview-cancel').textContent = batch ? 'Volver a la lista' : 'Cancelar';

  document.getElementById('pdf-preview-content').innerHTML = html;
  document.getElementById('pdf-preview-content').scrollTop = 0;
  closeModal('modal-pdf-import');
  closeModal('modal-pdf-batch');
  openModal('modal-pdf-preview');
}

function closePdfPreview() {
  closeModal('modal-pdf-preview');
  if (pdfPreviewMode === 'batch') {
    openModal('modal-pdf-batch');
  } else if (!pdfImporting) {
    pdfQueue = [];
  }
}

/** Envía un PDF ya interpretado. El programa va explícito: el backend lo prioriza sobre el código. */
async function importPdfItem(item) {
  try {
    const resp = await fetch(`${API}?action=importar_pdf`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_programa: item.idPrograma, codigo_programa: item.code, proyecto: item.data })
    });
    const r = await resp.json().catch(() => ({ error: `Respuesta inválida del servidor (HTTP ${resp.status})` }));
    if (r.success) { item.status = 'done';   item.error = null; }
    else           { item.status = 'failed'; item.error = r.error || 'Error desconocido.'; }
  } catch (e) {
    item.status = 'failed';
    item.error  = 'Ocurrió un error en la conexión.';
  }
}

async function savePdfImport() {
  const item = pdfQueue[0];
  if (!item || pdfImporting) return;

  // Antes se enviaba el programa abierto en pantalla aunque el PDF trajera su
  // código, y como el backend prioriza id_programa el proyecto terminaba en
  // el programa equivocado. Ahora manda la coincidencia por código.
  const detect = programaPorCodigo(item.code);
  item.idPrograma = detect ? String(detect.id_programa) : (document.getElementById('pdf-manual-prog')?.value || '');
  if (!item.idPrograma) return showToast('Debes seleccionar un programa para asociar el proyecto formativo.', 'warning');

  const btn = document.getElementById('btn-save-pdf');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Guardando…';
  pdfImporting = true;

  await importPdfItem(item);

  pdfImporting = false;
  btn.disabled = false;
  btn.innerHTML = ic('save') + ' Importar proyecto';

  if (item.status === 'done') {
    showToast('Proyecto formativo importado y estructurado con éxito.', 'success');
    closePdfPreview();
    mostrarProgramaImportado(item.idPrograma);
  } else {
    showToast('Error: ' + item.error, 'danger', 6000);
  }
}

/* ── Varios PDF: tabla con el programa y el estado de cada uno ── */
function showPdfBatch() {
  document.getElementById('pdf-batch-result').innerHTML = '';
  renderPdfBatch();
  openModal('modal-pdf-batch');
}

function renderPdfBatch() {
  // Dos PDF al mismo programa se combinan en una sola estructura
  const porPrograma = {};
  pdfQueue.forEach(it => {
    if (it.status !== 'invalid' && it.idPrograma) porPrograma[it.idPrograma] = (porPrograma[it.idPrograma] || 0) + 1;
  });

  const cuenta   = fn => pdfQueue.filter(fn).length;
  const sinProg  = cuenta(it => (it.status === 'pending' || it.status === 'failed') && !it.idPrograma);
  const partes   = [`${pdfQueue.length} PDF`];
  if (cuenta(it => it.status === 'invalid')) partes.push(`${cuenta(it => it.status === 'invalid')} sin estructura`);
  if (sinProg)                               partes.push(`${sinProg} sin programa`);
  if (cuenta(it => it.status === 'done'))    partes.push(`${cuenta(it => it.status === 'done')} importados`);
  if (cuenta(it => it.status === 'failed'))  partes.push(`${cuenta(it => it.status === 'failed')} con error`);
  document.getElementById('pdf-batch-sub').textContent = partes.join(' · ');

  document.getElementById('pdf-batch-tbody').innerHTML = pdfQueue.map((it, i) => {
    const prog     = programaPorId(it.idPrograma);
    const detect   = programaPorCodigo(it.code);
    const editable = !pdfImporting && (it.status === 'pending' || it.status === 'failed');

    let programa;
    if (it.status === 'invalid') {
      programa = '<span class="text-muted">—</span>';
    } else if (!editable) {
      programa = prog ? `<span class="mono">${esc(prog.codigo)}</span> ${esc(prog.nombre)}` : '—';
    } else {
      const nota = !it.idPrograma
        ? `<div class="text-xs text-warning">${it.code ? `El código ${esc(it.code)} no está registrado: elígelo` : 'No se detectó el código: elígelo'}</div>`
        : detect && String(detect.id_programa) === it.idPrograma
          ? '<div class="text-xs text-success">Detectado por el código del PDF</div>'
          : '<div class="text-xs text-muted">Elegido a mano</div>';
      const yaTiene = prog && prog.fases > 0
        ? `<div class="text-xs text-muted">Ya tiene ${prog.fases} fase${prog.fases === 1 ? '' : 's'}: se combinará</div>`
        : '';
      programa = `<select class="form-control" style="font-size:12px;padding:5px 8px" onchange="setPdfPrograma(${i}, this.value)"
                    aria-label="Programa de ${esc(it.name)}">${opcionesProgramas(it.idPrograma)}</select>${nota}${yaTiene}`;
    }

    const estructura = it.counts && it.status !== 'invalid'
      ? `${it.counts.fases} fases · ${it.counts.acts} act. · ${it.counts.raps} RAPs`
      : '—';

    let estado;
    switch (it.status) {
      case 'invalid':   estado = `<span class="badge badge-danger">Sin estructura</span><div class="text-xs text-danger">${esc(it.error)}</div>`; break;
      case 'importing': estado = `<span class="badge badge-info"><span class="spinner spinner-sm"></span> Importando…</span>`; break;
      case 'done':      estado = `<span class="badge badge-success">Importado</span>`; break;
      case 'failed':    estado = `<span class="badge badge-danger">Error</span><div class="text-xs text-danger">${esc(it.error)}</div>`; break;
      default:
        estado = !it.idPrograma
          ? '<span class="badge badge-warning">Falta programa</span>'
          : porPrograma[it.idPrograma] > 1
            ? '<span class="badge badge-warning" title="Otro PDF va al mismo programa; las estructuras se combinarán">Programa repetido</span>'
            : '<span class="badge badge-muted">Listo</span>';
    }

    const ver    = it.data ? `<button class="btn btn-ghost btn-sm" onclick="showPdfPreview(pdfQueue[${i}], 'batch')">${ic('eye')} Ver</button>` : '';
    const quitar = !pdfImporting && it.status !== 'done'
      ? `<button class="btn btn-ghost btn-sm" onclick="removePdfItem(${i})" aria-label="Quitar ${esc(it.name)}">${ic('x')}</button>`
      : '';

    return `<tr>
      <td class="truncate" style="max-width:170px" title="${esc(it.name)}">${esc(it.name)}</td>
      <td>${programa}</td>
      <td class="text-secondary" style="white-space:nowrap">${estructura}</td>
      <td>${estado}</td>
      <td class="col-actions">${ver}${quitar}</td>
    </tr>`;
  }).join('');

  updatePdfBatchFooter();
}

function setPdfPrograma(i, idPrograma) {
  if (pdfImporting || !pdfQueue[i]) return;
  pdfQueue[i].idPrograma = idPrograma;
  renderPdfBatch();
}

function removePdfItem(i) {
  if (pdfImporting) return;
  pdfQueue.splice(i, 1);
  if (!pdfQueue.length) return cancelPdfBatch();
  renderPdfBatch();
}

function updatePdfBatchFooter() {
  const btn    = document.getElementById('btn-pdf-batch-import');
  const cancel = document.getElementById('btn-pdf-batch-cancel');
  const cola   = pdfQueue.filter(it => (it.status === 'pending' || it.status === 'failed') && it.idPrograma);
  const done   = pdfQueue.some(it => it.status === 'done');

  document.getElementById('btn-pdf-add').disabled = pdfImporting || pdfReading;
  cancel.disabled    = pdfImporting;
  cancel.textContent = done ? 'Cerrar' : 'Cancelar';
  if (pdfImporting) return;

  const hayFallidos = cola.some(it => it.status === 'failed') && !cola.some(it => it.status === 'pending');
  btn.hidden    = !pdfQueue.some(it => it.status === 'pending' || it.status === 'failed');
  btn.disabled  = cola.length === 0;
  btn.innerHTML = ic('upload-cloud') + (hayFallidos
    ? ` Reintentar ${cola.length} con error`
    : ` Importar ${cola.length} ${cola.length === 1 ? 'proyecto' : 'proyectos'}`);
}

async function doPdfBatchImport() {
  const cola = pdfQueue.filter(it => (it.status === 'pending' || it.status === 'failed') && it.idPrograma);
  if (!cola.length) return showToast('Elige el programa de al menos un PDF para importarlo.', 'warning');

  const btn = document.getElementById('btn-pdf-batch-import');
  const res = document.getElementById('pdf-batch-result');
  res.innerHTML = '';
  pdfImporting = true;
  btn.disabled = true;

  // En serie: cada proyecto es una transacción en el servidor
  for (const [i, item] of cola.entries()) {
    btn.innerHTML = `<span class="spinner spinner-sm"></span> Importando ${i + 1} de ${cola.length}…`;
    item.status = 'importing';
    renderPdfBatch();
    await importPdfItem(item);
  }

  pdfImporting = false;
  renderPdfBatch();

  const ok       = cola.filter(it => it.status === 'done');
  const fallidos = cola.length - ok.length;
  const pendSin  = pdfQueue.filter(it => it.status === 'pending' && !it.idPrograma).length;
  res.innerHTML = `<div class="alert ${fallidos ? 'alert-warning' : 'alert-success'}">${ic(fallidos ? 'alert-triangle' : 'check-circle')}<div>
      <strong>${ok.length} de ${cola.length} proyectos importados</strong>${fallidos ? ` — ${fallidos} con error (puedes reintentarlos)` : ''}
      ${pendSin ? `<br>${pendSin} PDF siguen sin programa: elígelo para importarlos.` : ''}
    </div></div>`;
  showToast(`${ok.length} de ${cola.length} proyectos importados.`, fallidos ? 'warning' : 'success');

  if (ok.length) {
    // Si el programa abierto recibió un proyecto se queda; si no, se abre el primero importado
    const abierto = ok.some(it => it.idPrograma === String(currentProgram));
    await mostrarProgramaImportado(abierto ? currentProgram : ok[0].idPrograma);
    renderPdfBatch(); // los recuentos de fases de cada programa cambiaron
  }
}

function cancelPdfBatch() {
  if (pdfImporting) return;
  closeModal('modal-pdf-batch');
  pdfQueue = [];
}

/* ================================================================
   FASES Y ACTIVIDADES MANUALES
   ================================================================ */
function openModalFase() { openModal('modal-fase'); }
function closeModalFase() {
  closeModal('modal-fase');
  document.getElementById('mf-nombre').value = '';
  document.getElementById('mf-desc').value = '';
}

async function saveFase() {
  const nombre = document.getElementById('mf-nombre').value.trim();
  if (!nombre) return showToast('El nombre de la fase es obligatorio.', 'warning');
  try {
    await fetch(`${API}?action=crear_fase`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_programa: currentProgram, nombre_fase: nombre, descripcion: document.getElementById('mf-desc').value.trim() })
    });
    showToast('Fase creada.', 'success');
    closeModalFase();
    loadFases();
  } catch (e) { showToast('No se pudo crear la fase.', 'danger'); }
}

function openModalActividad(id_fase) {
  document.getElementById('ma-idfase').value = id_fase;
  openModal('modal-actividad');
}
function closeModalActividad() {
  closeModal('modal-actividad');
  document.getElementById('ma-nombre').value = '';
  document.getElementById('ma-desc').value = '';
}

async function saveActividad() {
  const nombre = document.getElementById('ma-nombre').value.trim();
  if (!nombre) return showToast('El nombre de la actividad es obligatorio.', 'warning');
  try {
    await fetch(`${API}?action=crear_actividad`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_fase: document.getElementById('ma-idfase').value, nombre_actividad: nombre, descripcion: document.getElementById('ma-desc').value.trim() })
    });
    showToast('Actividad creada.', 'success');
    closeModalActividad();
    loadFases();
  } catch (e) { showToast('No se pudo crear la actividad.', 'danger'); }
}

/* ================================================================
   CONFIRMACIÓN REUTILIZABLE
   ================================================================ */
function showConfirm(title, message, confirmText, btnClass, onConfirm) {
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-message').innerHTML = message;

  const ok = document.getElementById('confirm-ok');
  ok.className = 'btn ' + btnClass;
  ok.textContent = confirmText;

  // Reemplazar los nodos elimina los listeners de la invocación anterior
  const okNew = ok.cloneNode(true);
  ok.replaceWith(okNew);
  okNew.onclick = () => { closeModal('modal-confirm'); onConfirm(); };
  document.getElementById('confirm-cancel').onclick = () => closeModal('modal-confirm');

  openModal('modal-confirm');
}

async function borrarEstructura() {
  if (!currentProgram) return;

  showConfirm(
    'Borrar la estructura del proyecto',
    'Se eliminarán todas las fases, actividades y asignaciones de RAPs de este programa. Esta acción no se puede deshacer.',
    'Sí, borrar', 'btn-danger',
    async () => {
      try {
        const data = await fetch(`${API}?action=borrar_estructura`, {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id_programa: currentProgram })
        }).then(r => r.json());

        if (data.success) {
          showToast('Estructura borrada correctamente.', 'success');
          loadFases();
          document.getElementById('panel-asignacion').dataset.locked = 'true';
          document.getElementById('titulo-actividad').textContent = 'Resultados de Aprendizaje';
          document.getElementById('subtitulo-asignacion').textContent = 'Selecciona una actividad para asignar RAPs';
          document.getElementById('lista-raps').innerHTML = `<div class="empty-state">
            <div class="empty-icon">${ic('target')}</div>
            <p>Selecciona una actividad en el panel izquierdo.</p>
          </div>`;
        } else {
          showToast('Error al borrar la estructura: ' + esc(data.error || 'error desconocido'), 'danger');
        }
      } catch (err) {
        showToast('Error de conexión al borrar la estructura.', 'danger');
      }
    }
  );
}

/* Arrastrar y soltar PDF: en la zona de carga, en la vista previa y sobre la lista */
const pdfDz = document.getElementById('pdf-dropzone');
pdfDz.addEventListener('dragover', e => { e.preventDefault(); pdfDz.classList.add('dragover'); });
pdfDz.addEventListener('dragleave', () => pdfDz.classList.remove('dragover'));
pdfDz.addEventListener('drop', e => { e.preventDefault(); pdfDz.classList.remove('dragover'); handlePDFFiles(e.dataTransfer.files); });

['pdf-batch-body', 'pdf-preview-content'].forEach(id => {
  const zona = document.getElementById(id);
  // preventDefault siempre: sin él el navegador abre el PDF soltado en la pestaña
  zona.addEventListener('dragover', e => {
    e.preventDefault();
    const permitido = !pdfImporting && (id === 'pdf-batch-body' || pdfPreviewMode === 'single');
    zona.classList.toggle('is-dragover', permitido);
  });
  zona.addEventListener('dragleave', e => { if (!zona.contains(e.relatedTarget)) zona.classList.remove('is-dragover'); });
  zona.addEventListener('drop', e => {
    e.preventDefault();
    zona.classList.remove('is-dragover');
    if (id === 'pdf-preview-content' && pdfPreviewMode === 'batch') return;
    handlePDFFiles(e.dataTransfer.files);
  });
});

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
