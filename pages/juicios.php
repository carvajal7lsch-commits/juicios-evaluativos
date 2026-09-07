<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Juicios Evaluativos';
$pageSubtitle = 'Registro e importación de juicios por resultado de aprendizaje';
$activePage   = 'juicios';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="tabs mb-5" role="tablist">
  <button class="tab active" role="tab" onclick="switchTab('tab-lista',this)"><?= icon('clipboard-list') ?> Juicios Registrados</button>
  <button class="tab" role="tab" onclick="switchTab('tab-registro',this)"><?= icon('pencil') ?> Registrar Juicio</button>
  <button class="tab" role="tab" onclick="switchTab('tab-import',this)"><?= icon('upload') ?> Importación Masiva</button>
</div>

<!-- ══ LISTA ══ -->
<div id="tab-lista" class="tab-content active">
  <div class="toolbar mb-4">
    <div class="search-field">
      <?= icon('search') ?>
      <input type="text" id="j-search" class="form-control" placeholder="Buscar aprendiz, competencia o resultado…"
             oninput="filterJuicios()" aria-label="Buscar juicio" />
    </div>
    <select id="j-tipo" class="form-control" style="width:180px" onchange="filterJuicios()" aria-label="Filtrar por tipo de juicio">
      <option value="">Todos los tipos</option>
      <option>Aprobado</option><option>No Aprobado</option><option>Pendiente</option><option>En proceso</option>
    </select>
    <span class="toolbar-sep" aria-hidden="true"></span>
    <span class="text-sm text-secondary" id="juicios-count">Cargando…</span>
  </div>

  <div class="card card-flush">
    <div class="table-wrap is-scrollable">
      <table class="table">
        <thead>
          <tr><th>Aprendiz</th><th>Competencia</th><th>Resultado</th><th>Juicio</th><th>Funcionario</th><th>Fecha</th><th class="col-actions"></th></tr>
        </thead>
        <tbody id="tbody-juicios"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══ REGISTRO ══ -->
<div id="tab-registro" class="tab-content">
  <div class="card" style="max-width:740px">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('pencil') ?> Registrar nuevo juicio evaluativo</div>
        <div class="card-subtitle">Busca el aprendiz y selecciona el resultado de aprendizaje a evaluar</div>
      </div>
    </div>

    <form id="form-juicio" onsubmit="saveJuicio(event)">
      <div class="form-group">
        <label class="form-label" for="jf-search-aprendiz">Aprendiz <span class="req">*</span></label>
        <div class="search-field" style="min-width:0">
          <?= icon('search') ?>
          <input type="text" id="jf-search-aprendiz" class="form-control"
                 placeholder="Buscar por nombre o documento…" oninput="buscarAprendiz()" autocomplete="off" />
        </div>
        <div id="aprendiz-results" style="position:relative"></div>
        <input type="hidden" name="documento_aprendiz" id="jf-doc-aprendiz" />
        <div id="aprendiz-selected" class="cluster-sm" style="margin-top:8px" hidden>
          <span class="badge badge-brand" id="aprendiz-badge"></span>
          <button type="button" class="icon-btn" style="width:26px;height:26px" onclick="clearAprendiz()"
                  title="Quitar aprendiz" aria-label="Quitar aprendiz seleccionado"><?= icon('x') ?></button>
        </div>
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
        <div class="form-group">
          <label class="form-label" for="jf-obs">Observaciones</label>
          <textarea name="observaciones" id="jf-obs" class="form-control" rows="3" placeholder="Descripción del desempeño…"></textarea>
        </div>
        <div id="juicio-result" class="mb-3"></div>
        <button type="submit" class="btn btn-primary" id="btn-save-juicio"><?= icon('save') ?> Guardar juicio</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ IMPORTACIÓN ══ -->
<div id="tab-import" class="tab-content">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title"><?= icon('upload') ?> Importación masiva de juicios</div>
        <div class="card-subtitle">Carga un CSV con los juicios a registrar o actualizar</div>
      </div>
      <button onclick="downloadTemplateJuicios()" class="btn btn-outline btn-sm"><?= icon('download') ?> Descargar plantilla</button>
    </div>

    <div class="alert alert-info mb-4">
      <?= icon('info') ?>
      <div>Columnas obligatorias: <strong>documento_aprendiz, codigo_resultado, tipo_juicio</strong>.
      Opcionales: documento_funcionario, observaciones.</div>
    </div>

    <div class="dropzone" id="dz-juicios" onclick="document.getElementById('file-juicios').click()">
      <input type="file" id="file-juicios" accept=".csv" hidden onchange="handleFileJuicios(this.files[0])" />
      <div class="dropzone-icon"><?= icon('folder-open') ?></div>
      <div class="dropzone-title">Arrastra el CSV de juicios aquí</div>
      <div class="dropzone-sub">o haz clic para seleccionarlo</div>
    </div>

    <div id="jpreview-section" style="margin-top:20px" hidden>
      <div class="cluster-between mb-3">
        <div>
          <div class="section-title">Vista previa</div>
          <div class="section-sub" id="jpreview-count"></div>
        </div>
        <div class="cluster-sm">
          <button class="btn btn-outline btn-sm" onclick="cancelJuiciosImport()"><?= icon('x') ?> Cancelar</button>
          <button class="btn btn-success btn-sm" id="btn-jimport" onclick="doJuiciosImport()">
            <?= icon('upload') ?> Importar <span id="jimport-count">0</span>
          </button>
        </div>
      </div>
      <div class="table-wrap" style="max-height:320px">
        <table class="table table-compact">
          <thead><tr><th>#</th><th>Doc. Aprendiz</th><th>Cód. Resultado</th><th>Tipo Juicio</th><th>Funcionario</th></tr></thead>
          <tbody id="jpreview-body"></tbody>
        </table>
      </div>
    </div>

    <div id="jimport-result" style="margin-top:16px"></div>
  </div>
</div>

<style>
  .ap-suggest {
    position: absolute; width: 100%; z-index: 50; margin-top: 4px;
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;
  }
  .ap-suggest button {
    display: block; width: 100%; text-align: left; padding: 9px 13px;
    background: none; border: none; border-bottom: 1px solid var(--border);
    cursor: pointer; font-size: 13px; color: var(--text-primary);
  }
  .ap-suggest button:last-child { border-bottom: none; }
  .ap-suggest button:hover, .ap-suggest button:focus-visible { background: var(--bg-card-hover); }
</style>

<script>
const API_J = '../api/juicios.php';
let allJuicios = [], csvJuicios = [], searchTimer;

function switchTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
  btn.setAttribute('aria-selected', 'true');
}

async function init() { await Promise.all([loadJuicios(), loadFormSelects()]); }

async function loadJuicios() {
  document.getElementById('tbody-juicios').innerHTML =
    Array.from({ length: 5 }, () => '<tr><td colspan="7"><span class="skeleton skeleton-row"></span></td></tr>').join('');
  try {
    allJuicios = await fetch(API_J + '?action=list').then(r => r.json());
    renderJuicios(allJuicios);
  } catch (e) { showToast('No se pudieron cargar los juicios.', 'danger'); }
}

const TIPO_BADGE = {
  'Aprobado': 'badge-success', 'No Aprobado': 'badge-danger',
  'Pendiente': 'badge-warning', 'En proceso': 'badge-info'
};

function renderJuicios(data) {
  document.getElementById('juicios-count').textContent =
    `${data.length} juicio${data.length === 1 ? '' : 's'}` + (data.length !== allJuicios.length ? ` de ${allJuicios.length}` : '');

  const tbody = document.getElementById('tbody-juicios');
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
      <div class="empty-icon">${ic(allJuicios.length ? 'search' : 'file-pen')}</div>
      <div class="empty-title">${allJuicios.length ? 'Sin resultados' : 'Sin juicios registrados'}</div>
      <p>${allJuicios.length ? 'Ningún juicio coincide con los filtros.' : 'Registra un juicio o importa un CSV para empezar.'}</p>
    </div></td></tr>`;
    return;
  }

  tbody.innerHTML = data.map(j => `<tr>
    <td>
      <div class="fw-600">${esc(j.aprendiz)}</div>
      <div class="text-xs text-secondary mono">${esc(j.documento_aprendiz)}</div>
    </td>
    <td class="text-sm text-secondary truncate" style="max-width:180px" title="${esc(j.competencia)}">${esc(j.competencia)}</td>
    <td style="max-width:260px">
      <div class="text-xs text-muted mono">${esc(j.cod_resultado)}</div>
      <div class="text-sm truncate" title="${esc(j.resultado)}">${esc(j.resultado)}</div>
    </td>
    <td><span class="badge ${TIPO_BADGE[j.tipo_juicio] || 'badge-muted'}">${esc(j.tipo_juicio)}</span></td>
    <td class="text-sm">${esc(j.funcionario)}</td>
    <td class="text-sm text-secondary mono">${j.fecha_registro ? esc(j.fecha_registro.substring(0, 10)) : '—'}</td>
    <td class="col-actions">
      <button class="btn btn-danger-soft btn-sm btn-icon" onclick="deleteJuicio(${j.id_juicio})"
              title="Eliminar juicio" aria-label="Eliminar juicio">${ic('trash')}</button>
    </td>
  </tr>`).join('');
}

function filterJuicios() {
  const q = document.getElementById('j-search').value.toLowerCase();
  const t = document.getElementById('j-tipo').value;
  renderJuicios(allJuicios.filter(j =>
    (!q || `${j.aprendiz}${j.competencia}${j.resultado}${j.documento_aprendiz}`.toLowerCase().includes(q)) &&
    (!t || j.tipo_juicio === t)
  ));
}

async function deleteJuicio(id) {
  if (!confirm('¿Eliminar este juicio evaluativo?')) return;
  try {
    await fetch(API_J + '?id=' + id, { method: 'DELETE' });
    showToast('Juicio eliminado.', 'success');
    loadJuicios();
  } catch (e) { showToast('No se pudo eliminar el juicio.', 'danger'); }
}

async function loadFormSelects() {
  try {
    const [tipos, funcionarios] = await Promise.all([
      fetch(API_J + '?action=tipos').then(r => r.json()),
      fetch(API_J + '?action=funcionarios').then(r => r.json())
    ]);
    document.getElementById('jf-tipo').innerHTML =
      tipos.map(t => `<option value="${esc(t.nombre)}">${esc(t.nombre)}</option>`).join('');
    document.getElementById('jf-funcionario').innerHTML =
      (funcionarios.length ? '' : '<option value="00000000">(Sin funcionario)</option>') +
      funcionarios.map(f => `<option value="${esc(f.documento)}">${esc(f.nombre_completo)}</option>`).join('');
  } catch (e) {}
}

function buscarAprendiz() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    const q = document.getElementById('jf-search-aprendiz').value.trim();
    const div = document.getElementById('aprendiz-results');
    if (q.length < 2) { div.innerHTML = ''; return; }

    const data = await fetch('../api/aprendices.php?action=list').then(r => r.json());
    const found = data.filter(a => `${a.nombre} ${a.apellidos} ${a.documento}`.toLowerCase().includes(q.toLowerCase())).slice(0, 6);
    if (!found.length) { div.innerHTML = ''; return; }

    div.innerHTML = `<div class="ap-suggest">${found.map(a => `
      <button type="button" onmousedown="selectAprendiz('${esc(a.documento)}','${esc(a.nombre + ' ' + a.apellidos)}')">
        <span class="fw-600">${esc(a.nombre + ' ' + a.apellidos)}</span>
        <span class="mono text-secondary text-xs"> ${esc(a.documento)}</span>
        <div class="text-xs text-muted">${esc(a.ficha)} — ${esc((a.programa || '').substring(0, 40))}</div>
      </button>`).join('')}</div>`;
  }, 300);
}

async function selectAprendiz(doc, nombre) {
  document.getElementById('jf-doc-aprendiz').value = doc;
  document.getElementById('jf-search-aprendiz').value = '';
  document.getElementById('aprendiz-results').innerHTML = '';
  document.getElementById('aprendiz-badge').textContent = nombre + ' — ' + doc;
  document.getElementById('aprendiz-selected').hidden = false;
  document.getElementById('resultados-section').hidden = false;

  const data = await fetch(API_J + '?action=resultados_by_aprendiz&documento=' + encodeURIComponent(doc)).then(r => r.json());
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

function clearAprendiz() {
  document.getElementById('jf-doc-aprendiz').value = '';
  document.getElementById('jf-search-aprendiz').value = '';
  document.getElementById('aprendiz-selected').hidden = true;
  document.getElementById('resultados-section').hidden = true;
}

function checkResultadoStatus() {
  const sel = document.getElementById('jf-resultado');
  const juicio = sel.options[sel.selectedIndex]?.dataset?.juicio;
  document.getElementById('resultado-status').innerHTML = juicio
    ? `<div class="alert alert-warning">${ic('alert-triangle')}<div>Ya existe un juicio <strong>${esc(juicio)}</strong> para este resultado. Al guardar, se actualizará.</div></div>`
    : '';
}

async function saveJuicio(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  if (!data.documento_aprendiz) { showToast('Selecciona un aprendiz primero.', 'warning'); return; }

  const btn = document.getElementById('btn-save-juicio');
  const res = document.getElementById('juicio-result');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Guardando…';

  try {
    const r = await fetch(API_J + '?action=save', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    }).then(r => r.json());

    if (r.ok) {
      res.innerHTML = `<div class="alert alert-success">${ic('check-circle')}<div>Juicio guardado correctamente.</div></div>`;
      showToast('Juicio guardado.', 'success');
      loadJuicios();
      clearAprendiz();
      document.getElementById('jf-resultado').innerHTML = '<option value="">Selecciona un resultado…</option>';
      document.getElementById('resultado-status').innerHTML = '';
    } else {
      res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>${esc(r.error)}</div></div>`;
    }
  } catch (err) {
    res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error de red al guardar.</div></div>`;
  }

  btn.disabled = false;
  btn.innerHTML = ic('save') + ' Guardar juicio';
  setTimeout(() => res.innerHTML = '', 5000);
}

/* ── Importación CSV ── */
const dzJ = document.getElementById('dz-juicios');
dzJ.addEventListener('dragover', e => { e.preventDefault(); dzJ.classList.add('dragover'); });
dzJ.addEventListener('dragleave', () => dzJ.classList.remove('dragover'));
dzJ.addEventListener('drop', e => { e.preventDefault(); dzJ.classList.remove('dragover'); handleFileJuicios(e.dataTransfer.files[0]); });

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
  document.getElementById('jimport-count').textContent = rows.length;
  document.getElementById('jpreview-body').innerHTML = rows.slice(0, 50).map((r, i) => `
    <tr>
      <td class="text-muted">${i + 1}</td>
      <td class="mono text-sm">${esc(r.documento_aprendiz || r.DOCUMENTO_APRENDIZ || '')}</td>
      <td class="mono text-sm">${esc(r.codigo_resultado || r.CODIGO_RESULTADO || '')}</td>
      <td><span class="badge badge-info">${esc(r.tipo_juicio || r.TIPO_JUICIO || 'Aprobado')}</span></td>
      <td class="text-sm text-secondary">${esc(r.documento_funcionario || '—')}</td>
    </tr>`).join('');
}

async function doJuiciosImport() {
  const btn = document.getElementById('btn-jimport');
  const res = document.getElementById('jimport-result');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-sm"></span> Importando…';

  try {
    const r = await fetch(API_J + '?action=bulk', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ rows: csvJuicios })
    }).then(r => r.json());

    if (r.error) {
      res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>${esc(r.error)}</div></div>`;
    } else {
      res.innerHTML = `<div class="alert alert-success">${ic('check-circle')}<div>${r.insertados} insertados, ${r.actualizados} actualizados.</div></div>`;
      showToast('Importación completada.', 'success');
      cancelJuiciosImport();
      loadJuicios();
    }
  } catch (e) {
    res.innerHTML = `<div class="alert alert-danger">${ic('x-circle')}<div>Error de red durante la importación.</div></div>`;
  }

  btn.disabled = false;
  btn.innerHTML = ic('upload') + ' Importar <span id="jimport-count">' + csvJuicios.length + '</span>';
}

function cancelJuiciosImport() {
  document.getElementById('jpreview-section').hidden = true;
  document.getElementById('file-juicios').value = '';
  csvJuicios = [];
}

function downloadTemplateJuicios() {
  const csv = 'documento_aprendiz,codigo_resultado,tipo_juicio,documento_funcionario,observaciones\n'
            + '1234567890,240201500-N-001,Aprobado,87654321,Demuestra competencia técnica';
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
  a.download = 'plantilla_juicios.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
