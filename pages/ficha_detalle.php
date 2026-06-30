<?php
define('ROOT_PATH', dirname(__DIR__));
$ficha = $_GET['ficha'] ?? '';
if (!$ficha) { header('Location: fichas.php'); exit; }

$pageTitle    = 'Detalle de Ficha: ' . htmlspecialchars($ficha);
$pageSubtitle = 'Panel de control centralizado para la ficha de formación';
$activePage   = 'fichas'; // Keep the sidebar highlight on Fichas
require_once ROOT_PATH . '/includes/header.php';
?>

<style>
  .hero-ficha {
    background: linear-gradient(135deg, rgba(26,77,181,0.08), rgba(0,166,80,0.05));
    border: 1px solid rgba(26,77,181,0.2);
    border-radius: var(--radius-lg);
    padding: 32px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 24px;
  }
  .hf-title { font-size: 28px; font-weight: 800; color: var(--sena-blue); letter-spacing: -0.02em; margin-bottom: 4px; font-family: monospace;}
  .hf-subtitle { font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;}
  .hf-meta { display: flex; gap: 16px; font-size: 13px; color: var(--text-secondary); flex-wrap: wrap; }
  .hf-meta div { display: flex; align-items: center; gap: 6px; }
  
  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    padding: 16px 24px;
    border-radius: var(--radius-md);
    min-width: 140px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .stat-val { font-size: 24px; font-weight: 800; color: var(--text-primary); }
  .stat-lbl { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-top: 4px; }

  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 50px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    user-select: none;
    color: var(--text-secondary);
    gap: 6px;
  }
  .status-pill:hover {
    border-color: var(--sena-blue);
    background: rgba(26,77,181,0.05);
  }
  .status-pill.active {
    background: var(--sena-blue);
    color: white;
    border-color: var(--sena-blue);
    box-shadow: 0 4px 10px rgba(26,77,181,0.3);
  }
</style>

<!-- HEADER HERO -->
<div class="hero-ficha fade-in" id="hero-meta" style="display:none;">
  <div>
    <div style="display:flex; align-items:center; gap:12px;">
      <div class="hf-title" id="hf-ficha"></div>
      <span class="badge" id="hf-estado" style="font-size:12px; padding:6px 12px;"></span>
    </div>
    <div class="hf-subtitle" id="hf-programa"></div>
    <div class="hf-meta">
      <div>📅 <span id="hf-fechas"></span></div>
      <div>📍 <span id="hf-modalidad"></span></div>
    </div>
  </div>
  
  <div style="display:flex; gap:16px; flex-wrap:wrap;">
    <div class="stat-card">
      <div class="stat-val" id="stat-aprendices" style="color:var(--sena-blue-lt);">0</div>
      <div class="stat-lbl">Aprendices</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" id="stat-competencias" style="color:var(--warning);">0</div>
      <div class="stat-lbl">Competencias</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" id="stat-resultados" style="color:var(--info);">0</div>
      <div class="stat-lbl">RAPs Totales</div>
    </div>
  </div>
</div>

<div class="tabs fade-in">
  <button id="tab-btn-aprendices" class="tab active" onclick="switchTab('tab-aprendices',this)">👥 Aprendices</button>
  <button id="tab-btn-competencias" class="tab" onclick="switchTab('tab-competencias',this)">📚 Programa y Resultados</button>
  <button id="tab-btn-juicios" class="tab" onclick="switchTab('tab-juicios',this)">📝 Juicios Evaluativos</button>
</div>

<!-- TAB APRENDICES -->
<div id="tab-aprendices" class="tab-content active fade-in">
  <div class="card">
    <div class="card-header" style="padding-bottom:16px; flex-wrap:wrap; gap:16px;">
      <div class="card-title" style="font-size:16px; min-width: 150px;">Listado de Aprendices</div>
      <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; flex:1;">
        <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Estados:</span>
        <div id="filter-pills-aprendices" style="display:flex; gap:8px; flex-wrap:wrap;">
          <!-- Se cargan dinámicamente -->
        </div>
      </div>
      <div style="width:250px; position:relative;">
        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);">🔍</span>
        <input type="text" id="search-aprendices" class="form-control" placeholder="Buscar por nombre o doc..." style="padding-left:36px;" oninput="filterAprendices()" />
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Nombre completo</th><th>Documento</th><th>Estado</th><th style="width:180px;">Avance Individual</th><th>Acciones</th></tr></thead>
        <tbody id="tbody-aprendices"><tr><td colspan="5" style="text-align:center;padding:32px;"><span class="spinner"></span></td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<!-- TAB COMPETENCIAS -->
<div id="tab-competencias" class="tab-content fade-in">
  <div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px;">
      <div class="card-title" style="font-size:16px;">Competencias y Resultados</div>
      <div style="width:350px; position:relative;">
        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);">🔍</span>
        <input type="text" id="search-competencias" class="form-control" placeholder="Buscar por competencia o código de resultado..." style="padding-left:36px;" oninput="filterCompetencias()" />
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Competencia / Resultado</th><th>Código</th><th style="width:250px;">Avance de la Ficha (Activos)</th></tr></thead>
        <tbody id="tbody-competencias"><tr><td colspan="3" style="text-align:center;padding:32px;"><span class="spinner"></span></td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<!-- TAB JUICIOS -->
<div id="tab-juicios" class="tab-content fade-in">
  <!-- 1. KPIs de Gestión -->
  <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:16px; margin-bottom:24px;">
    <div class="kpi-card" style="--kpi-color: var(--danger); background: rgba(218,54,51,0.05);">
      <div class="kpi-icon">⚠️</div>
      <div class="kpi-value" id="radar-riesgo-count">0</div>
      <div class="kpi-label">Aprendices en Riesgo Crítico</div>
      <div style="font-size:10px; color:var(--text-muted); margin-top:4px;">Progreso menor al 30%</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--warning); background: rgba(210,153,34,0.05);">
      <div class="kpi-icon">🕵️‍♂️</div>
      <div class="kpi-value" id="radar-gap-count">0</div>
      <div class="kpi-label">Inconsistencias Detectadas</div>
      <div style="font-size:10px; color:var(--text-muted); margin-top:4px;">RAPs con registros incompletos</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--sena-blue); background: rgba(26,77,181,0.05);">
      <div class="kpi-icon">📝</div>
      <div class="kpi-value" id="radar-total-count">0</div>
      <div class="kpi-label">Total Registros Históricos</div>
      <div style="font-size:10px; color:var(--text-muted); margin-top:4px;">Juicios en la base de datos</div>
    </div>
  </div>

  <div class="grid-2" style="grid-template-columns: 1fr 400px; gap:24px; align-items: flex-start;">
    <!-- Consultas Rápidas y Buscador -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">🔎 Consultas Rápidas y Explorador</div>
          <div class="card-subtitle">Usa los filtros directos para encontrar información específica sin saturarte</div>
        </div>
        <button class="btn btn-success btn-sm" onclick="openRegistroModal()">✏️ Registro Manual</button>
      </div>
      
      <!-- Filtros de Acción Rápida -->
      <div style="padding:20px; border-bottom:1px solid var(--border); background:rgba(0,0,0,0.02);">
        <div style="font-size:11px; font-weight:800; color:var(--text-muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">¿Qué deseas localizar ahora?</div>
        <div id="quick-filter-pills" style="display:flex; flex-wrap:wrap; gap:10px;">
          <button class="btn btn-outline q-pill" data-filter="No Aprobado" style="border-color:var(--danger); color:var(--danger);" onclick="setQuickFilter('No Aprobado', this)">🔴 Ver 'No Aprobados'</button>
          <button class="btn btn-outline q-pill" data-filter="Por evaluar" style="border-color:var(--warning); color:var(--warning);" onclick="setQuickFilter('Por evaluar', this)">⏳ Por Evaluar</button>
          <button class="btn btn-outline q-pill" data-filter="Reciente" style="border-color:var(--sena-blue); color:var(--sena-blue);" onclick="setQuickFilter('Reciente', this)">📅 Registros Recientes</button>
          <button class="btn btn-outline q-pill" data-filter="all" style="border-color:var(--text-muted); color:var(--text-muted);" onclick="setQuickFilter('all', this)">🌐 Ver Todo</button>
        </div>
      </div>

      <!-- Buscador -->
      <div style="padding:16px; border-bottom:1px solid var(--border);">
        <div style="position:relative;">
          <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);">🔍</span>
          <input type="text" id="search-juicios" class="form-control" placeholder="Escribe el nombre de un aprendiz o un RAP para empezar..." style="padding-left:36px; height:45px; font-size:15px;" oninput="filterJuicios()">
        </div>
      </div>

      <!-- Área de Resultados (Cards en lugar de Tabla) -->
      <div id="juicios-results-container" style="padding:20px; display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:16px; min-height:300px; max-height:600px; overflow-y:auto; background:var(--bg-body);">
        <div style="grid-column:1/-1; text-align:center; padding:60px;">
          <div style="font-size:40px; margin-bottom:16px;">🔍</div>
          <div style="font-size:16px; font-weight:600; color:var(--text-secondary);">Empieza a buscar o selecciona un filtro arriba</div>
          <div style="font-size:12px; color:var(--text-muted); margin-top:8px;">La información aparecerá aquí de forma organizada</div>
        </div>
      </div>
    </div>

    <!-- Alertas de Gestión -->
    <div style="display:flex; flex-direction:column; gap:24px;">
      <div class="card" style="border-top: 3px solid var(--danger);">
        <div class="card-header"><div class="card-title" style="font-size:14px;">🚨 Aprendices que requieren atención</div></div>
        <div id="radar-list-riesgo" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
          <!-- Se llena vía JS -->
        </div>
      </div>
      
      <div class="card" style="border-top: 3px solid var(--warning);">
        <div class="card-header"><div class="card-title" style="font-size:14px;">🕵️‍♂️ RAPs con "Olvidos" del Instructor</div></div>
        <div id="radar-list-gaps" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
          <!-- Se llena vía JS -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AVANCE POR APRENDIZ (ELIMINADO Y MOVIDO A PAGINA INDEPENDIENTE) -->


<!-- MODAL REGISTRO MANUAL -->
<div id="modal-registro" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="card" style="width:600px; max-width:90%; animation:fadeInUp 0.3s ease;">
    <div class="card-header" style="border-bottom:1px solid var(--border); margin-bottom:16px; padding-bottom:16px;">
      <div class="card-title">✏️ Registrar nuevo juicio evaluativo</div>
      <button class="btn" style="background:none; border:none; font-size:20px; cursor:pointer;" onclick="closeRegistroModal()">✕</button>
    </div>
    <form id="form-juicio" onsubmit="saveJuicio(event)">
      <div class="form-group">
        <label class="form-label">Aprendiz de esta ficha <span class="req">*</span></label>
        <select id="jf-aprendiz" class="form-control" required onchange="loadResultadosParaAprendiz()">
          <option value="">Selecciona un aprendiz...</option>
        </select>
      </div>
      <div id="resultados-section" style="display:none;">
        <div class="form-group">
          <label class="form-label">Resultado de aprendizaje <span class="req">*</span></label>
          <select name="id_resultado" id="jf-resultado" class="form-control" required onchange="checkResultadoStatus()">
            <option value="">Selecciona un resultado...</option>
          </select>
          <div id="resultado-status" style="margin-top:6px;"></div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo de juicio <span class="req">*</span></label>
            <select name="estado" id="jf-tipo" class="form-control" required></select>
          </div>
          <div class="form-group">
            <label class="form-label">Funcionario evaluador <span class="req">*</span></label>
            <select name="documento_funcionario" id="jf-funcionario" class="form-control" required></select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Observaciones</label>
          <textarea name="observaciones" class="form-control" rows="3" placeholder="Opcional..."></textarea>
        </div>
        <div id="juicio-result"></div>
        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
          <button type="button" class="btn btn-outline" onclick="closeRegistroModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-save-juicio">💾 Guardar Juicio</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- MODAL IMPORTACIÓN -->
<div id="modal-import" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="card" style="width:720px; max-width:90%; animation:fadeInUp 0.3s ease;">
    <div class="card-header" style="border-bottom:1px solid var(--border); margin-bottom:16px; padding-bottom:16px;">
      <div><div class="card-title">⬆️ Importación Masiva de Juicios</div><div class="card-subtitle">Exclusivo para la ficha actual</div></div>
      <button class="btn" style="background:none; border:none; font-size:20px; cursor:pointer;" onclick="closeImportModal()">✕</button>
    </div>
    
    <div class="dropzone" id="dz-juicios" onclick="document.getElementById('file-juicios').click()" style="margin-bottom:16px;">
      <input type="file" id="file-juicios" accept=".csv" style="display:none;" onchange="handleFileJuicios(this.files[0])" />
      <div class="dropzone-icon">📂</div>
      <div class="dropzone-title">Arrastra el CSV de juicios aquí</div>
      <div class="dropzone-sub">El archivo debe contener la columna documento_aprendiz y codigo_resultado</div>
    </div>
    
    <div id="jpreview-section" style="display:none;">
      <div class="card-header" style="margin-bottom:12px; background:var(--bg-body); padding:12px; border-radius:8px;">
        <div><div class="card-title" style="font-size:14px;">Vista previa</div><div class="card-subtitle" id="jpreview-count"></div></div>
        <button class="btn btn-success btn-sm" id="btn-jimport" onclick="doJuiciosImport()">⬆️ Confirmar Importación</button>
      </div>
      <div class="table-wrap" style="max-height:240px; overflow-y:auto;">
        <table class="table">
          <thead><tr><th>#</th><th>Doc. Aprendiz</th><th>Cód. Resultado</th><th>Juicio</th></tr></thead>
          <tbody id="jpreview-body"></tbody>
        </table>
      </div>
    </div>
    <div id="jimport-result" style="margin-top:16px;"></div>
  </div>
</div>

<script>
const FICHA = new URLSearchParams(window.location.search).get('ficha');
const API = '../api/ficha_detalle.php?ficha=' + encodeURIComponent(FICHA);

// Mover modales al body para asegurar que cubran toda la pantalla (ejecutado sincronamente)
const mAv = document.getElementById('modal-avance');
if (mAv) document.body.appendChild(mAv);

const mReg = document.getElementById('modal-registro');
if (mReg) document.body.appendChild(mReg);

const mImp = document.getElementById('modal-import');
if (mImp) document.body.appendChild(mImp);

function switchTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}

let allJuiciosData = [];
let allAprendicesData = [];
let allCompetenciasData = [];
let selectedStates = [];

async function init() {
  await Promise.all([
    loadMeta(),
    loadAprendices(),
    loadCompetencias(),
    loadJuicios()
  ]);

  // Auto-abrir modal de avance si viene de la URL
  const urlParams = new URLSearchParams(window.location.search);
  const autoOpenDoc = urlParams.get('openAvance');
  if (autoOpenDoc) {
    const a = allAprendicesData.find(ap => ap.documento === autoOpenDoc);
    if (a) openAvanceModal(a.documento, a.nombre + ' ' + a.apellidos);
  }
}

async function loadMeta() {
  const meta = await fetch(API + '&action=meta').then(r => r.json());
  if (meta.error) { alert(meta.error); window.location.href = 'fichas.php'; return; }
  
  document.getElementById('hf-ficha').textContent = meta.ficha;
  document.getElementById('hf-programa').textContent = meta.codigo_programa + ' — ' + meta.programa;
  document.getElementById('hf-fechas').textContent = meta.fecha_inicio + ' a ' + meta.fecha_fin;
  document.getElementById('hf-modalidad').textContent = meta.modalidad;
  
  const b = document.getElementById('hf-estado');
  b.textContent = meta.estado;
  b.className = 'badge ' + (meta.estado === 'En Ejecución' ? 'badge-success' : 'badge-info');

  document.getElementById('stat-aprendices').textContent = meta.total_aprendices;
  document.getElementById('stat-competencias').textContent = meta.total_competencias;
  document.getElementById('stat-resultados').textContent = meta.total_resultados ?? 0;
  
  document.getElementById('hero-meta').style.display = 'flex';
}



async function loadAprendices() {
  allAprendicesData = await fetch(API + '&action=aprendices').then(r => r.json());
  
  const estadosSet = new Set();
  allAprendicesData.forEach(a => {
    const raw = (a.estado || 'Desconocido').trim().toLowerCase();
    a.estado_normalized = raw.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    estadosSet.add(a.estado_normalized);
  });

  const container = document.getElementById('filter-pills-aprendices');
  container.innerHTML = '';
  
  // Opción "Todos" simplificada o simplemente desactivar filtros
  Array.from(estadosSet).sort().forEach(e => {
    const pill = document.createElement('div');
    pill.className = 'status-pill';
    pill.innerHTML = `<span>${e}</span>`;
    pill.onclick = () => toggleStatusFilter(e, pill);
    container.appendChild(pill);
  });

  renderAprendices(allAprendicesData);
}

function toggleStatusFilter(state, el) {
  const idx = selectedStates.indexOf(state);
  if (idx > -1) {
    selectedStates.splice(idx, 1);
    el.classList.remove('active');
  } else {
    selectedStates.push(state);
    el.classList.add('active');
  }
  filterAprendices();
}

function renderAprendices(data) {
  const tb = document.getElementById('tbody-aprendices');
  if (!data.length) { tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">Sin resultados</td></tr>'; return; }
  
  const eb = {'Activo':'badge-success','Retiro Voluntario':'badge-warning','Deserción':'badge-danger'};
  tb.innerHTML = data.map(a => {
    const aprob = parseInt(a.raps_aprobados || 0);
    const total = parseInt(a.total_raps || 0);
    const pct = total > 0 ? Math.round((aprob / total) * 100) : 0;
    const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';
    
    return `
      <tr>
        <td><strong>${esc(a.nombre)} ${esc(a.apellidos)}</strong></td>
        <td style="font-family:monospace; font-size:12px;">${esc(a.documento)}</td>
        <td><span class="badge ${eb[a.estado_normalized]||'badge-muted'}">${esc(a.estado_normalized)}</span></td>
        <td>
          <div style="display:flex; align-items:center; gap:10px;">
            <div class="progress-bar-wrap" style="flex:1; height:6px;">
              <div class="progress-bar ${color}" style="width:${pct}%"></div>
            </div>
            <span style="font-size:11px; font-weight:700; min-width:35px;">${pct}%</span>
          </div>
          <div style="font-size:9px; color:var(--text-muted); margin-top:2px;">${aprob} de ${total} RAPs</div>
        </td>
        <td><a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(FICHA)}&documento=${encodeURIComponent(a.documento)}" class="btn btn-outline btn-sm">📈 Avance</a></td>
      </tr>
    `;
  }).join('');
}

function filterAprendices() {
  const q = document.getElementById('search-aprendices').value.toLowerCase().trim();
  
  const filtered = allAprendicesData.filter(a => {
    const matchSearch = (a.nombre + ' ' + a.apellidos).toLowerCase().includes(q) || a.documento.toLowerCase().includes(q);
    const matchEstado = selectedStates.length === 0 || selectedStates.includes(a.estado_normalized);
    return matchSearch && matchEstado;
  });
  renderAprendices(filtered);
}

async function loadCompetencias() {
  allCompetenciasData = await fetch(API + '&action=competencias').then(r => r.json());
  renderCompetencias(allCompetenciasData);
}

function renderCompetencias(data) {
  const container = document.getElementById('tbody-competencias');
  if (!data.length) { container.innerHTML = '<tr><td colspan="3" style="text-align:center;">Sin resultados</td></tr>'; return; }

  // 1. Agrupar por competencia
  const grouped = data.reduce((acc, r) => {
    if (!acc[r.competencia]) acc[r.competencia] = { 
      name: r.competencia, 
      code: r.cod_comp, 
      hrs: r.duracion_horas, 
      raps: [] 
    };
    acc[r.competencia].raps.push(r);
    return acc;
  }, {});

  // 2. Renderizar con porcentajes por bloque
  container.innerHTML = Object.values(grouped).map(c => {
    // Calcular promedio de la competencia
    const totalRaps = c.raps.length;
    const avgPct = Math.round(c.raps.reduce((sum, r) => {
        const total = parseInt(r.total_activos || 0);
        return sum + (total > 0 ? (parseInt(r.aprobados_count || 0) / total) : 0);
    }, 0) / totalRaps * 100);
    
    const compHeader = `
      <tr style="background:rgba(26,77,181,0.04);">
        <td colspan="2" style="padding:16px; border-bottom:2px solid var(--sena-blue);">
          <div style="font-size:11px; font-weight:800; color:var(--sena-blue-lt); margin-bottom:2px;">COMPETENCIA: ${esc(c.code)}</div>
          <div style="font-size:15px; font-weight:800; color:var(--sena-blue);">${esc(c.name)}</div>
          <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">⏱️ Duración: ${c.hrs} horas | 📚 ${totalRaps} Resultados</div>
        </td>
        <td style="padding:16px; border-bottom:2px solid var(--sena-blue); text-align:right; vertical-align:middle;">
          <div style="font-size:20px; font-weight:900; color:var(--sena-blue);">${avgPct}%</div>
          <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Avance Grupal</div>
        </td>
      </tr>
    `;

    const rapsRows = c.raps.map(r => {
      const aprob = parseInt(r.aprobados_count || 0);
      const total = parseInt(r.total_activos || 0);
      const pct = total > 0 ? Math.round((aprob / total) * 100) : 0;
      const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';

      return `
        <tr>
          <td style="padding-left:32px; vertical-align:top; border-bottom:1px solid var(--border);">
            <div style="font-weight:600; font-size:13px; color:var(--text-primary);">${esc(r.resultado)}</div>
            ${r.faltantes && r.faltantes.length > 0 ? `
              <div onclick="quickSearchApprentice('', '${esc(r.cod_res)}')" style="margin-top:10px; background:rgba(218,54,51,0.05); border:1px solid rgba(218,54,51,0.2); border-radius:8px; padding:10px; cursor:pointer;">
                <div style="font-size:10px; font-weight:900; color:var(--danger); margin-bottom:6px; letter-spacing:0.5px;">🚨 ATENCIÓN: FALTAN POR CALIFICAR</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                  ${r.faltantes.map(f => `<span style="background:var(--bg-card); border:1px solid var(--border); padding:3px 8px; border-radius:6px; font-size:10px; color:var(--text-primary); font-weight:700;">${esc(f)}</span>`).join('')}
                </div>
              </div>
            ` : ''}
          </td>
          <td style="font-size:11px; color:var(--text-muted); font-family:monospace; vertical-align:top; padding-top:14px; border-bottom:1px solid var(--border);">${esc(r.cod_res)}</td>
          <td style="vertical-align:top; padding-top:14px; border-bottom:1px solid var(--border);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
              <div class="progress-bar-wrap" style="flex:1; height:6px; background:rgba(0,0,0,0.05);">
                <div class="progress-bar ${color}" style="width:${pct}%"></div>
              </div>
              <span style="font-size:12px; font-weight:800; min-width:35px; text-align:right;">${pct}%</span>
            </div>
            <div style="font-size:10px; color:var(--text-muted); text-align:right;">${aprob} de ${total} aprobados</div>
          </td>
        </tr>
      `;
    }).join('');

    return compHeader + rapsRows;
  }).join('');
}

function filterCompetencias() {
  const q = document.getElementById('search-competencias').value.toLowerCase().trim();
  const filtered = allCompetenciasData.filter(r => 
    r.competencia.toLowerCase().includes(q) || 
    (r.cod_res && r.cod_res.toLowerCase().includes(q)) || 
    (r.resultado && r.resultado.toLowerCase().includes(q))
  );
  renderCompetencias(filtered);
}

async function loadJuicios() {
  // 1. Cargar historial completo para el buscador
  allJuiciosData = await fetch(API + '&action=juicios').then(r => r.json());
  renderJuicios(allJuiciosData);
  
  // 2. Cargar auditoría para el radar
  const auditData = await fetch(API + '&action=audit').then(r => r.json());
  renderRadar(auditData);
}

function renderRadar(auditData) {
  // KPIs
  document.getElementById('radar-total-count').textContent = allJuiciosData.length;
  
  // Inconsistencias (Gaps)
  const gaps = auditData.filter(r => r.total_aprobados > 0 && r.total_aprobados < r.total_grupo);
  document.getElementById('radar-gap-count').textContent = gaps.length;
  
  const gapList = document.getElementById('radar-list-gaps');
  if (gaps.length === 0) {
    gapList.innerHTML = '<div style="font-size:12px; color:var(--text-muted); text-align:center;">✅ No se detectan inconsistencias grupales</div>';
  } else {
    gapList.innerHTML = gaps.slice(0, 5).map(g => `
      <div onclick="quickSearchApprentice('', '${esc(g.codigo)}')" style="background:rgba(210,153,34,0.05); border:1px solid rgba(210,153,34,0.2); border-radius:8px; padding:12px; cursor:pointer; transition:transform 0.1s;">
        <div style="font-size:11px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">${esc(g.codigo)}</div>
        <div style="font-size:10px; color:var(--warning); font-weight:800;">Faltan: ${g.total_grupo - g.total_aprobados} aprendices</div>
        <div style="font-size:9px; color:var(--text-secondary); margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
          ${g.faltantes.slice(0,5).map(f => `<span style="color:var(--text-primary); font-weight:700;">${esc(f)}</span>`).join(', ')}
        </div>
      </div>
    `).join('');
  }

  // Aprendices en Riesgo (Calculado desde allAprendicesData si ya cargó)
  if (allAprendicesData && allAprendicesData.length > 0) {
    const enRiesgo = allAprendicesData.filter(a => {
      const pct = a.total_raps > 0 ? (a.raps_aprobados / a.total_raps) * 100 : 0;
      return pct < 30 && a.estado_normalized === 'Activo';
    });
    
    document.getElementById('radar-riesgo-count').textContent = enRiesgo.length;
    const riesgoList = document.getElementById('radar-list-riesgo');
    
    if (enRiesgo.length === 0) {
      riesgoList.innerHTML = '<div style="font-size:12px; color:var(--text-muted); text-align:center;">✅ No hay aprendices en riesgo crítico</div>';
    } else {
      riesgoList.innerHTML = enRiesgo.slice(0, 5).map(a => {
        const pct = Math.round((a.raps_aprobados / a.total_raps) * 100);
        return `
          <div style="background:rgba(218,54,51,0.05); border:1px solid rgba(218,54,51,0.2); border-radius:8px; padding:10px; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <div style="font-size:12px; font-weight:700; color:var(--text-primary);">${esc(a.nombre + ' ' + a.apellidos)}</div>
              <div style="font-size:10px; color:var(--danger); font-weight:600;">Progreso: ${pct}%</div>
            </div>
            <a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(FICHA)}&documento=${encodeURIComponent(a.documento)}" class="btn btn-outline btn-sm" style="padding:4px 8px; font-size:10px;">Ver →</a>
          </div>
        `;
      }).join('');
    }
  }
}

let currentQuickFilter = null;

function setQuickFilter(f, el) {
  // Manejo visual de botones
  document.querySelectorAll('.q-pill').forEach(btn => {
    btn.style.background = '';
    btn.style.fontWeight = 'normal';
    btn.style.borderColor = 'var(--border)';
  });
  if (el) {
    el.style.background = 'rgba(26,77,181,0.1)';
    el.style.fontWeight = '700';
    el.style.borderColor = 'var(--sena-blue)';
  }

  if (f === 'all') {
    currentQuickFilter = 'all';
    document.getElementById('search-juicios').value = ''; // Limpiar búsqueda al ver todo
  } else {
    currentQuickFilter = f;
  }
  filterJuicios();
}

function quickSearchApprentice(name, rapCode = '') {
  // Cambiar a la pestaña de Juicios usando el botón correspondiente
  const btn = document.getElementById('tab-btn-juicios');
  if (btn) switchTab('tab-juicios', btn);
  
  // Limpiar filtros y poner el nombre + RAP en el buscador
  setQuickFilter('all', document.querySelector('.q-pill[data-filter="all"]'));
  const searchInput = document.getElementById('search-juicios');
  if (searchInput) {
    searchInput.value = (name + ' ' + rapCode).trim();
    // Ejecutar búsqueda
    filterJuicios();
    // Scroll suave al buscador
    searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function filterJuicios() {
  const q = (document.getElementById('search-juicios').value || '').toLowerCase().trim();
  const container = document.getElementById('juicios-results-container');
  
  if (!allJuiciosData) return;

  // Si no hay búsqueda ni filtro, mostrar estado inicial (opcional: o mostrar todo)
  if (!q && !currentQuickFilter) {
    container.innerHTML = `
      <div style="grid-column:1/-1; text-align:center; padding:60px;">
        <div style="font-size:40px; margin-bottom:16px;">🔍</div>
        <div style="font-size:16px; font-weight:600; color:var(--text-secondary);">Empieza a buscar o selecciona un filtro arriba</div>
        <div style="font-size:12px; color:var(--text-muted); margin-top:8px;">La información aparecerá aquí de forma organizada</div>
      </div>
    `;
    return;
  }

  const filtered = allJuiciosData.filter(j => {
    const nom = (j.aprendiz || '').toLowerCase();
    const doc = (j.documento_aprendiz || '').toLowerCase();
    const cod = (j.cod_resultado || '').toLowerCase();
    const res = (j.resultado || '').toLowerCase();
    const est = (j.estado || '').toLowerCase();

    const matchSearch = nom.includes(q) || doc.includes(q) || cod.includes(q) || res.includes(q);
    
    let matchFilter = true;
    if (currentQuickFilter === 'No Aprobado') {
      matchFilter = est.includes('no aprobado');
    } else if (currentQuickFilter === 'Por evaluar') {
      // Un juicio es por evaluar si dice "Por evaluar", "Pendiente", si está vacío o nulo
      matchFilter = est.includes('por evaluar') || est.includes('pendiente') || est === '' || est === 'null' || !j.estado;
    } else if (currentQuickFilter === 'Reciente') {
      if (!j.fecha_registro) return false;
      const date = new Date(j.fecha_registro);
      const limit = new Date(); limit.setDate(limit.getDate() - 30); // Extendemos a 30 días
      matchFilter = date >= limit;
    }
    
    return matchSearch && matchFilter;
  });

  console.log(`Filtrando: q="${q}", filter="${currentQuickFilter}", resultados=${filtered.length}`);
  renderJuicios(filtered);
}

function renderJuicios(data) {
  const container = document.getElementById('juicios-results-container');
  const q = (document.getElementById('search-juicios').value || '').trim();

  if (!data.length) { 
    container.innerHTML = `
      <div style="grid-column:1/-1; text-align:center; padding:60px;">
        <div style="font-size:40px; margin-bottom:16px;">🔍</div>
        <div style="font-size:16px; font-weight:600; color:var(--text-secondary);">No hay registros históricos para esta búsqueda</div>
        ${q ? `<div style="margin-top:12px; background:rgba(218,54,51,0.05); padding:10px; border-radius:8px; border:1px dashed var(--danger); color:var(--danger); font-size:12px;">
            Confirmado: No existe registro para los criterios: "<strong>${esc(q)}</strong>"
        </div>` : ''}
      </div>
    `;
    return; 
  }
  
  // Normalizamos el mapeo para evitar errores de mayúsculas
  const config = {
    'aprobado': { badge: '#28a745', icon: '✅', label: 'Aprobado' },
    'no aprobado': { badge: '#dc3545', icon: '❌', label: 'No Aprobado' },
    'por evaluar': { badge: '#ffc107', icon: '⏳', label: 'Por evaluar' },
    'pendiente': { badge: '#ffc107', icon: '⏳', label: 'Por evaluar' }
  };
  
  container.innerHTML = data.slice(0, 500).map(j => {
    const rawEstado = (j.estado || 'Por evaluar').trim().toLowerCase();
    const c = config[rawEstado] || config['por evaluar'];
    const textColor = (c.badge === '#ffc107') ? '#000' : '#fff';
    
    return `
      <div class="card" style="padding:16px; border:1px solid var(--border); background:var(--bg-card); border-left: 5px solid ${c.badge}; transition:transform 0.2s; cursor:default;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
          <div>
            <div style="font-size:13px; font-weight:700; color:var(--text-primary);">${esc(j.aprendiz)}</div>
            <div style="font-size:11px; color:var(--text-muted); font-family:monospace;">ID: ${esc(j.documento_aprendiz)}</div>
          </div>
          <span style="background:${c.badge}; color:${textColor}; padding:5px 12px; border-radius:14px; font-size:10px; font-weight:900; white-space:nowrap; display:flex; align-items:center; gap:6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            ${c.icon} ${esc(c.label)}
          </span>
        </div>
        
        <div style="background:rgba(0,0,0,0.02); border-radius:6px; padding:10px; margin-bottom:12px; border:1px solid rgba(0,0,0,0.03);">
          <div style="font-size:10px; font-weight:800; color:var(--sena-blue-lt); text-transform:uppercase; letter-spacing:0.5px;">${esc(j.cod_resultado)}</div>
          <div style="font-size:12px; line-height:1.4; color:var(--text-secondary);">${esc(j.resultado.substring(0,120))}...</div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div style="font-size:11px; color:var(--text-muted);">
            📅 ${j.fecha_registro ? j.fecha_registro.substring(0,16) : 'Sin fecha'}
          </div>
          <div style="font-size:10px; color:var(--text-muted); font-style:italic;">
            Por: ${esc(j.funcionario || 'SENA')}
          </div>
        </div>
      </div>
    `;
  }).join('') + (data.length > 500 ? `<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--text-muted); font-size:12px;">Mostrando los primeros 500 de ${data.length} resultados.</div>` : '');
}



// ── LÓGICA DE MODAL DE AVANCE (ELIMINADA) ──


let csvJuicios = [];

function openRegistroModal() {
  document.getElementById('modal-registro').style.display = 'flex';
  const sel = document.getElementById('jf-aprendiz');
  sel.innerHTML = '<option value="">Selecciona un aprendiz...</option>';
  allAprendicesData.forEach(a => {
    const o = document.createElement('option');
    o.value = a.documento;
    o.textContent = a.nombre + ' ' + a.apellidos + ' — ' + a.documento;
    sel.appendChild(o);
  });
  if (document.getElementById('jf-tipo').options.length === 0) loadFormSelects();
}

function closeRegistroModal() {
  document.getElementById('modal-registro').style.display = 'none';
  document.getElementById('resultados-section').style.display = 'none';
  document.getElementById('form-juicio').reset();
}

async function loadFormSelects() {
  const [tipos, funcionarios] = await Promise.all([
    fetch('../api/juicios.php?action=tipos').then(r => r.json()),
    fetch('../api/juicios.php?action=funcionarios').then(r => r.json())
  ]);
  const selT = document.getElementById('jf-tipo');
  tipos.forEach(t => { const o = document.createElement('option'); o.value = t.nombre; o.textContent = t.nombre; selT.appendChild(o); });
  const selF = document.getElementById('jf-funcionario');
  if(!funcionarios.length){ const o = document.createElement('option'); o.value = '00000000'; o.textContent = '(Sin funcionario)'; selF.appendChild(o); }
  funcionarios.forEach(f => { const o = document.createElement('option'); o.value = f.documento; o.textContent = f.nombre_completo; selF.appendChild(o); });
}

async function loadResultadosParaAprendiz() {
  const doc = document.getElementById('jf-aprendiz').value;
  if (!doc) { document.getElementById('resultados-section').style.display = 'none'; return; }
  
  document.getElementById('resultados-section').style.display = 'block';
  const data = await fetch('../api/juicios.php?action=resultados_by_aprendiz&documento=' + encodeURIComponent(doc)).then(r => r.json());
  const sel = document.getElementById('jf-resultado');
  sel.innerHTML = '<option value="">Selecciona un resultado...</option>';
  let lastComp = '';
  data.forEach(r => {
    if (r.competencia !== lastComp) { const og = document.createElement('optgroup'); og.label = '📚 ' + r.competencia; sel.appendChild(og); lastComp = r.competencia; }
    const o = document.createElement('option'); o.value = r.id_resultado; o.textContent = r.codigo + ' — ' + r.descripcion.substring(0,60); o.dataset.juicio = r.juicio_actual || ''; sel.appendChild(o);
  });
}

function checkResultadoStatus() {
  const sel = document.getElementById('jf-resultado');
  const opt = sel.options[sel.selectedIndex];
  const juicio = opt?.dataset?.juicio;
  const div = document.getElementById('resultado-status');
  if (juicio) div.innerHTML = `<div class="alert alert-warning">⚠️ Ya existe juicio <strong>${esc(juicio)}</strong>. Al guardar, se actualizará.</div>`;
  else div.innerHTML = '';
}

async function saveJuicio(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  data.documento_aprendiz = document.getElementById('jf-aprendiz').value;
  const btn = document.getElementById('btn-save-juicio'); btn.disabled = true;
  
  const r = await fetch('../api/juicios.php?action=save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) }).then(r => r.json());
  const res = document.getElementById('juicio-result');
  if (r.ok) {
    res.innerHTML = '<div class="alert alert-success" style="margin-bottom:12px;">✅ Juicio guardado.</div>';
    loadJuicios(); loadMeta(); // Actualizar KPIs y tabla
    setTimeout(closeRegistroModal, 1500);
  } else res.innerHTML = `<div class="alert alert-danger" style="margin-bottom:12px;">❌ ${esc(r.error)}</div>`;
  btn.disabled = false; setTimeout(() => res.innerHTML = '', 5000);
}

// ── IMPORTACIÓN MASIVA CSV ──
function openImportModal() { document.getElementById('modal-import').style.display = 'flex'; }
function closeImportModal() { document.getElementById('modal-import').style.display = 'none'; cancelJuiciosImport(); }

function handleFileJuicios(file) {
  if (!file) return;
  // Cargar PapaParse dinámicamente si no existe
  if (typeof Papa === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js';
    script.onload = () => parseJuiciosCSV(file);
    document.head.appendChild(script);
  } else {
    parseJuiciosCSV(file);
  }
}

function parseJuiciosCSV(file) {
  Papa.parse(file, {
    header: true, skipEmptyLines: true,
    complete: r => { csvJuicios = r.data; showJuiciosPreview(r.data); }
  });
}

function showJuiciosPreview(rows) {
  document.getElementById('jpreview-section').style.display = 'block';
  document.getElementById('jpreview-count').textContent = rows.length + ' filas detectadas';
  document.getElementById('jpreview-body').innerHTML = rows.slice(0, 50).map((r, i) => `
    <tr><td>${i+1}</td><td style="font-family:monospace;font-size:12px">${esc(r.documento_aprendiz||r.DOCUMENTO_APRENDIZ||'')}</td>
    <td>${esc(r.codigo_resultado||r.CODIGO_RESULTADO||'')}</td>
    <td><span class="badge badge-info">${esc(r.tipo_juicio||r.TIPO_JUICIO||'Aprobado')}</span></td></tr>
  `).join('');
}

async function doJuiciosImport() {
  const btn = document.getElementById('btn-jimport'); btn.disabled = true; btn.textContent = 'Importando...';
  const r = await fetch('../api/juicios.php?action=bulk', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({rows:csvJuicios}) }).then(r => r.json());
  const res = document.getElementById('jimport-result');
  if (r.error) res.innerHTML = `<div class="alert alert-danger">❌ ${esc(r.error)}</div>`;
  else {
    res.innerHTML = `<div class="alert alert-success">✅ ${r.insertados} insertados, ${r.actualizados} actualizados.</div>`;
    loadJuicios(); loadMeta(); // Actualizar datos
    setTimeout(closeImportModal, 2500);
  }
  btn.disabled = false; btn.textContent = '⬆️ Confirmar Importación';
}

function cancelJuiciosImport() {
  document.getElementById('jpreview-section').style.display = 'none';
  document.getElementById('file-juicios').value = '';
  csvJuicios = [];
  document.getElementById('jimport-result').innerHTML = '';
}

function esc(str) { return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
