<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Resumen general de formación y juicios evaluativos';
$activePage   = 'dashboard';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════════════
     IMPORTADOR DE REPORTE SOFIA PLUS
     ══════════════════════════════════════════════════════════════ -->
<div class="card fade-in" style="margin-bottom:24px;border-color:rgba(26,77,181,.4);background:linear-gradient(135deg,rgba(26,77,181,.07),rgba(0,166,80,.05));">
  <div class="card-header">
    <div>
      <div class="card-title" style="font-size:16px;">🚀 Importar Reporte Sofia Plus</div>
      <div class="card-subtitle">Arrastra el Excel exportado de Sofia Plus — carga aprendices, juicios, competencias y resultados automáticamente</div>
    </div>
    <div id="sf-status-badge" style="display:none;"></div>
  </div>

  <div id="sf-dropzone" style="border:2px dashed rgba(26,77,181,.5);border-radius:14px;padding:36px 24px;text-align:center;cursor:pointer;transition:all .2s;background:rgba(26,77,181,.04);"
       onclick="document.getElementById('sf-file').click()"
       ondragover="event.preventDefault();this.style.borderColor='var(--sena-green)';this.style.background='rgba(0,166,80,.08)'"
       ondragleave="this.style.borderColor='rgba(26,77,181,.5)';this.style.background='rgba(26,77,181,.04)'"
       ondrop="event.preventDefault();this.style.borderColor='rgba(26,77,181,.5)';this.style.background='rgba(26,77,181,.04)';handleSofiaFile(event.dataTransfer.files[0])">
    <input type="file" id="sf-file" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleSofiaFile(this.files[0])" />
    <div style="font-size:36px;margin-bottom:10px;">📊</div>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px;">Arrastra el reporte .xlsx de Sofia Plus aquí</div>
    <div style="font-size:12px;color:var(--text-secondary);">o haz clic para seleccionar — .xlsx / .xls / .csv</div>
  </div>

  <!-- Preview del encabezado detectado -->
  <div id="sf-preview" style="display:none;margin-top:20px;">
    <div style="background:var(--bg-input);border-radius:var(--radius-md);padding:16px;margin-bottom:16px;">
      <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:10px;letter-spacing:.05em;">📋 DATOS DETECTADOS DEL ENCABEZADO</div>
      <div class="form-row" style="gap:12px;">
        <div><div style="font-size:11px;color:var(--text-muted);">Ficha</div><div style="font-size:15px;font-weight:700;color:var(--sena-blue-lt);" id="sf-ficha">—</div></div>
        <div><div style="font-size:11px;color:var(--text-muted);">Programa</div><div style="font-size:13px;font-weight:600;" id="sf-programa">—</div></div>
        <div><div style="font-size:11px;color:var(--text-muted);">Código</div><div style="font-size:13px;font-family:monospace;" id="sf-codigo">—</div></div>
        <div><div style="font-size:11px;color:var(--text-muted);">Estado</div><div style="font-size:13px;" id="sf-estado-ficha">—</div></div>
        <div><div style="font-size:11px;color:var(--text-muted);">Periodo</div><div style="font-size:12px;" id="sf-periodo">—</div></div>
      </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
      <div style="background:rgba(56,139,253,.1);border:1px solid rgba(56,139,253,.3);border-radius:var(--radius-md);padding:12px 18px;text-align:center;flex:1;min-width:100px;">
        <div style="font-size:22px;font-weight:800;color:var(--info);" id="sf-n-aprendices">0</div>
        <div style="font-size:11px;color:var(--text-secondary);">Aprendices</div>
      </div>
      <div style="background:rgba(0,166,80,.1);border:1px solid rgba(0,166,80,.3);border-radius:var(--radius-md);padding:12px 18px;text-align:center;flex:1;min-width:100px;">
        <div style="font-size:22px;font-weight:800;color:var(--success);" id="sf-n-juicios">0</div>
        <div style="font-size:11px;color:var(--text-secondary);">Juicios con valor</div>
      </div>
      <div style="background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.3);border-radius:var(--radius-md);padding:12px 18px;text-align:center;flex:1;min-width:100px;">
        <div style="font-size:22px;font-weight:800;color:var(--warning);" id="sf-n-comp">0</div>
        <div style="font-size:11px;color:var(--text-secondary);">Competencias</div>
      </div>
      <div style="background:rgba(218,54,51,.1);border:1px solid rgba(218,54,51,.3);border-radius:var(--radius-md);padding:12px 18px;text-align:center;flex:1;min-width:100px;">
        <div style="font-size:22px;font-weight:800;color:var(--danger);" id="sf-n-res">0</div>
        <div style="font-size:11px;color:var(--text-secondary);">Resultados</div>
      </div>
    </div>

    <div id="sf-preview-tabla" style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:16px;">
      <table class="table" style="font-size:12px;">
        <thead><tr><th>Tipo Doc</th><th>Documento</th><th>Nombre</th><th>Apellidos</th><th>Estado</th><th>Competencia</th><th>Resultado</th><th>Juicio</th></tr></thead>
        <tbody id="sf-tbody-preview"></tbody>
      </table>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn-outline" onclick="cancelSofiaImport()">Cancelar</button>
      <button class="btn btn-success" id="btn-sf-import" onclick="doSofiaImport()" style="padding:10px 24px;font-size:14px;">
        🚀 Importar todo al sistema
      </button>
    </div>
  </div>

  <div id="sf-result" style="margin-top:16px;"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     FILTROS GLOBALES DEL DASHBOARD
     ══════════════════════════════════════════════════════════════ -->
<div class="card fade-in" style="margin-bottom:24px; display:flex; gap:16px; align-items:flex-end;">
  <div style="flex:1;">
    <label class="form-label" style="font-size:12px;color:var(--text-secondary);">Filtrar por Programa de Formación</label>
    <select id="global-prog" class="form-control" onchange="applyGlobalFilters()">
      <option value="">Todos los programas</option>
    </select>
  </div>
  <div style="flex:1;">
    <label class="form-label" style="font-size:12px;color:var(--text-secondary);">Filtrar por Ficha</label>
    <select id="global-ficha" class="form-control" onchange="applyGlobalFilters()">
      <option value="">Todas las fichas</option>
    </select>
  </div>
  <div>
    <button class="btn btn-outline" onclick="clearGlobalFilters()">Limpiar Filtros</button>
  </div>
</div>

<div class="kpi-grid fade-in" id="kpi-grid">
  <div class="kpi-card" style="--kpi-color: #1a4db5;">
    <div class="kpi-icon">👥</div>
    <div class="kpi-value" id="kpi-aprendices">—</div>
    <div class="kpi-label">Total Aprendices</div>
    <div class="kpi-trend"><span id="kpi-activos" class="trend-up">— activos</span></div>
  </div>
  <div class="kpi-card" style="--kpi-color: #00A650;">
    <div class="kpi-icon">✅</div>
    <div class="kpi-value" id="kpi-aprobados">—</div>
    <div class="kpi-label">Juicios Aprobados</div>
    <div class="kpi-trend"><span id="kpi-pct-aprob" class="trend-up">—%</span> de aprobación</div>
  </div>
  <div class="kpi-card" style="--kpi-color: #d29922;">
    <div class="kpi-icon">⏳</div>
    <div class="kpi-value" id="kpi-pendientes">—</div>
    <div class="kpi-label">Juicios Pendientes</div>
    <div class="kpi-trend">Por evaluar</div>
  </div>
  <div class="kpi-card" style="--kpi-color: #da3633;">
    <div class="kpi-icon">❌</div>
    <div class="kpi-value" id="kpi-noaprobados">—</div>
    <div class="kpi-label">No Aprobados</div>
    <div class="kpi-trend">Requieren intervención</div>
  </div>
  <div class="kpi-card" style="--kpi-color: #388bfd;">
    <div class="kpi-icon">📋</div>
    <div class="kpi-value" id="kpi-fichas">—</div>
    <div class="kpi-label">Fichas Activas</div>
    <div class="kpi-trend"><span id="kpi-competencias">—</span> competencias</div>
  </div>
</div>

<div class="card fade-in fade-in-delay-1" style="margin-bottom:20px; position:relative; z-index:100;">
  <div class="card-header">
    <div>
      <div class="card-title">🔎 Filtros Avanzados</div>
      <div class="card-subtitle">Filtra la tabla de aprendices por múltiples criterios</div>
    </div>
    <button class="btn btn-outline btn-sm" onclick="clearFilters()">Limpiar filtros</button>
  </div>
  <div class="form-row">
    <div class="form-group" style="margin-bottom:0; position:relative; flex: 2;">
      <label class="form-label">Buscar aprendiz</label>
      <input type="text" id="f-search" class="form-control" placeholder="Nombre, apellido o documento..." oninput="applyFilters()" onblur="setTimeout(()=>document.getElementById('search-dropdown').style.display='none', 200)" onfocus="if(this.value.trim()) document.getElementById('search-dropdown').style.display='block'" />
      
      <!-- Dropdown emergente -->
      <div id="search-dropdown" style="display:none; position:absolute; top:100%; left:0; width:100%; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-md); box-shadow:0 10px 30px rgba(0,0,0,0.6); z-index:1000; margin-top:8px; max-height:300px; overflow-y:auto; overflow-x:hidden;">
      </div>
    </div>
    <div class="form-group" style="margin-bottom:0; flex: 1;">
      <label class="form-label">Nivel de Avance</label>
      <select id="f-avance" class="form-control" onchange="applyFilters()">
        <option value="">Todos los niveles</option>
        <option value="alto">Excelente (80% - 100%)</option>
        <option value="medio">En proceso (50% - 79%)</option>
        <option value="bajo">Crítico (0% - 49%)</option>
      </select>
    </div>
  </div>
</div>

<div class="grid-2 fade-in fade-in-delay-2" style="margin-bottom:20px;">
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">📊 Juicios por Tipo</div><div class="card-subtitle">Distribución del estado de evaluaciones</div></div>
    </div>
    <div class="chart-container" style="height:260px;"><canvas id="chart-tipos"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">📈 % Aprobación por Competencia</div><div class="card-subtitle">Barras horizontales por competencia</div></div>
    </div>
    <div class="chart-container" style="height:260px;overflow-y:auto;"><canvas id="chart-competencias"></canvas></div>
  </div>
</div>

<div class="card fade-in fade-in-delay-3">
  <div class="card-header">
    <div><div class="card-title">👥 Seguimiento por Aprendiz</div><div class="card-subtitle" id="tabla-count">Cargando...</div></div>
    <a href="fichas.php" class="btn btn-outline btn-sm">Ver todas las fichas →</a>
  </div>
  <div class="table-wrap">
    <table class="table" id="tabla-aprendices">
      <thead>
        <tr><th>Aprendiz</th><th>Documento</th><th>Ficha</th><th>Programa</th><th>Estado</th><th>Avance</th><th>Aprobados</th><th></th></tr>
      </thead>
      <tbody id="tabla-body">
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-secondary);"><span class="spinner"></span> Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card fade-in fade-in-delay-4" style="margin-top:20px;">
  <div class="card-header">
    <div><div class="card-title">🏫 Aprendices por Ficha de Formación</div><div class="card-subtitle">Total y activos por grupo</div></div>
  </div>
  <div class="chart-container" style="height:300px;"><canvas id="chart-fichas"></canvas></div>
</div>

<script>
const API = '../api/dashboard.php';
let chartTipos, chartComp, chartFichas;
let globalParams = '';
let allGlobalFichas = [];

async function init() {
  await loadGlobalFiltersData();
  await refreshDashboard();
  // loadFichasFilter() ya no es necesario, lo hace renderGlobalFichasOptions
}

async function loadGlobalFiltersData() {
  const d = await fetch(API + '?action=get_filtros_globales').then(r=>r.json());
  const selP = document.getElementById('global-prog');
  
  allGlobalFichas = d.fichas;
  
  d.programas.forEach(p => { const o=document.createElement('option'); o.value=p.id_programa; o.textContent=p.nombre; selP.appendChild(o); });
  renderGlobalFichasOptions();
}

function renderGlobalFichasOptions(progId = '') {
  const selGlobal = document.getElementById('global-ficha');
  const currentGlobalVal = selGlobal.value;

  selGlobal.innerHTML = '<option value="">Todas las fichas</option>';
  
  const filtered = progId ? allGlobalFichas.filter(f => f.id_programa == progId) : allGlobalFichas;
  
  let foundGlobal = false;

  filtered.forEach(f => { 
    const txt = f.ficha + ' — ' + f.programa?.substring(0,30);
    // Para el filtro global
    const oG = document.createElement('option'); oG.value = f.ficha; oG.textContent = txt; 
    selGlobal.appendChild(oG); 
    if (f.ficha == currentGlobalVal) foundGlobal = true;
  });
  
  // Keep selection if it is still valid
  if (foundGlobal) selGlobal.value = currentGlobalVal;
}

function applyGlobalFilters() {
  const prog = document.getElementById('global-prog').value;
  
  // Re-render fichas when programa changes to limit options
  renderGlobalFichasOptions(prog);
  
  const ficha = document.getElementById('global-ficha').value;
  const search = document.getElementById('f-search') ? document.getElementById('f-search').value.trim() : '';
  
  globalParams = `&global_programa=${prog}&global_ficha=${ficha}&global_search=${encodeURIComponent(search)}`;
  refreshDashboard();
}

function selectApprentice(doc) {
  document.getElementById('f-search').value = doc;
  document.getElementById('search-dropdown').style.display = 'none';
  applyGlobalFilters();
}

function clearGlobalFilters() {
  document.getElementById('global-prog').value = '';
  document.getElementById('global-ficha').value = '';
  if (document.getElementById('f-search')) document.getElementById('f-search').value = '';
  applyGlobalFilters();
}

async function refreshDashboard() {
  await Promise.all([loadKPIs(), loadCharts(), loadTabla()]);
}

async function loadKPIs() {
  const d = await fetch(API + '?action=kpis' + globalParams).then(r=>r.json());
  document.getElementById('kpi-aprendices').textContent  = d.total_aprendices.toLocaleString();
  document.getElementById('kpi-activos').textContent     = d.activos.toLocaleString() + ' activos';
  document.getElementById('kpi-aprobados').textContent   = d.aprobados.toLocaleString();
  document.getElementById('kpi-pct-aprob').textContent   = d.pct_aprobacion + '%';
  document.getElementById('kpi-pendientes').textContent  = d.pendientes.toLocaleString();
  document.getElementById('kpi-noaprobados').textContent = d.no_aprobados.toLocaleString();
  document.getElementById('kpi-fichas').textContent      = d.total_fichas;
  document.getElementById('kpi-competencias').textContent= d.total_competencias;
}

const COLORS = {
  'Aprobado':'#00A650', 'APROBADO':'#00A650',
  'No Aprobado':'#da3633', 'NO APROBADO':'#da3633',
  'Pendiente':'#d29922', 'POR EVALUAR':'#d29922',
  'En proceso':'#388bfd', 'EN PROCESO':'#388bfd'
};

async function loadCharts() {
  const tipos = await fetch(API + '?action=juicios_por_tipo' + globalParams).then(r=>r.json());
  if (chartTipos) chartTipos.destroy();
  chartTipos = new Chart(document.getElementById('chart-tipos'), {
    type:'doughnut',
    data:{ labels:tipos.map(t=>t.nombre), datasets:[{ data:tipos.map(t=>t.total), backgroundColor:tipos.map(t=>COLORS[t.nombre]||'#888'), borderWidth:2, borderColor:'#161b22' }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'right', labels:{ color:'#e6edf3', font:{size:12} } } } }
  });

  const comp = await fetch(API + '?action=avance_competencias' + globalParams).then(r=>r.json());
  const labels = comp.map(c=>c.competencia.length>35?c.competencia.substring(0,35)+'…':c.competencia);
  const pcts   = comp.map(c=>c.total_resultados>0?Math.round(c.aprobados/c.total_resultados*100):0);
  if (chartComp) chartComp.destroy();
  chartComp = new Chart(document.getElementById('chart-competencias'), {
    type:'bar',
    data:{ labels, datasets:[{ label:'% Aprobación', data:pcts, backgroundColor:pcts.map(p=>p>=80?'rgba(0,166,80,.75)':p>=50?'rgba(210,153,34,.75)':'rgba(218,54,51,.75)'), borderRadius:6, borderSkipped:false }] },
    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ max:100, ticks:{ color:'#8b949e', callback:v=>v+'%' }, grid:{ color:'#21262d' } }, y:{ ticks:{ color:'#8b949e', font:{size:11} }, grid:{ display:false } } } }
  });

  const fichas = await fetch(API + '?action=aprendices_por_ficha' + globalParams).then(r=>r.json());
  if (chartFichas) chartFichas.destroy();
  chartFichas = new Chart(document.getElementById('chart-fichas'), {
    type:'bar',
    data:{ labels:fichas.map(f=>f.ficha+' - '+(f.programa.substring(0,25))), datasets:[{ label:'Total', data:fichas.map(f=>f.total), backgroundColor:'rgba(26,77,181,.7)', borderRadius:4 },{ label:'Activos', data:fichas.map(f=>f.activos), backgroundColor:'rgba(0,166,80,.7)', borderRadius:4 }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ color:'#e6edf3' } } }, scales:{ x:{ ticks:{ color:'#8b949e', font:{size:11} }, grid:{ color:'#21262d' } }, y:{ ticks:{ color:'#8b949e' }, grid:{ color:'#21262d' } } } }
  });
}

let lastQuery = '';

async function loadTabla() {
  const searchVal = document.getElementById('f-search').value.trim();
  const params = new URLSearchParams({ action:'tabla_aprendices', search:searchVal });
  const data = await fetch(API + '?' + params + globalParams).then(r=>r.json());
  renderTabla(data);

  // Lógica del dropdown emergente
  const dropdown = document.getElementById('search-dropdown');
  const searchInput = document.getElementById('f-search');
  
  // Solo abrir el dropdown si el usuario está activamente escribiendo en el input
  if (searchVal && document.activeElement === searchInput) {
    if (data.length > 0) {
      dropdown.innerHTML = data.slice(0, 10).map(a => `
        <a href="javascript:void(0)" onclick="selectApprentice('${a.documento}')" 
           style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border); text-decoration:none; color:inherit; transition:background 0.2s;"
           onmouseover="this.style.background='rgba(56,139,253,0.1)'" onmouseout="this.style.background='transparent'">
           <div>
             <div style="font-weight:600; font-size:14px; color:var(--text-primary);">${esc(a.nombre+' '+a.apellidos)}</div>
             <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
               <span style="font-family:monospace; color:var(--sena-blue-lt);">${esc(a.documento)}</span> • Ficha: ${esc(a.ficha)}
             </div>
           </div>
           <div><span class="badge ${a.estado==='Activo'?'badge-success':(a.estado==='Retiro Voluntario'?'badge-warning':'badge-muted')}">${esc(a.estado)}</span></div>
        </a>
      `).join('');
      if (data.length > 10) {
        dropdown.innerHTML += `<div style="padding:10px; text-align:center; font-size:12px; color:var(--text-secondary); background:rgba(0,0,0,0.2);">+ ${data.length - 10} resultados más. Revisa la tabla.</div>`;
      }
    } else {
      dropdown.innerHTML = `<div style="padding:16px; text-align:center; color:var(--text-secondary); font-size:14px;">⚠️ No se encontró ningún aprendiz para "<strong>${esc(searchVal)}</strong>"</div>`;
    }
    dropdown.style.display = 'block';
  } else {
    dropdown.style.display = 'none';
  }
}

function renderTabla(data) {
  const tbody = document.getElementById('tabla-body');
  
  // Aplicar filtro local de Avance
  const avanceFilter = document.getElementById('f-avance').value;
  let filteredData = data;
  if (avanceFilter) {
    filteredData = data.filter(a => {
      const pct = a.total_resultados > 0 ? Math.round(a.aprobados / a.total_resultados * 100) : 0;
      if (avanceFilter === 'alto') return pct >= 80;
      if (avanceFilter === 'medio') return pct >= 50 && pct < 80;
      if (avanceFilter === 'bajo') return pct < 50;
      return true;
    });
  }

  document.getElementById('tabla-count').textContent = filteredData.length + ' aprendice(s)';
  if (!filteredData.length) { tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🔍</div><p>Sin resultados</p></div></td></tr>'; return; }
  const estadoBadge = {'Activo':'badge-success','Retiro Voluntario':'badge-warning','Deserción':'badge-danger','Graduado':'badge-info','Trasladado':'badge-muted'};
  tbody.innerHTML = filteredData.map(a => {
    const pct   = a.total_resultados>0?Math.round(a.aprobados/a.total_resultados*100):0;
    const color = pct>=80?'green':pct>=50?'warn':'danger';
    const estado= estadoBadge[a.estado]||'badge-muted';
    return `<tr>
      <td><strong>${esc(a.nombre+' '+a.apellidos)}</strong></td>
      <td style="font-family:monospace;font-size:12px">${esc(a.documento)}</td>
      <td>${esc(a.ficha)}</td>
      <td style="font-size:12px;color:var(--text-secondary)">${esc(a.programa?.substring(0,40))}${a.programa?.length>40?'…':''}</td>
      <td><span class="badge ${estado}">${esc(a.estado)}</span></td>
      <td style="min-width:120px;">
        <div class="progress-bar-wrap"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
        <div class="progress-label"><span>${pct}%</span><span>${a.aprobados}/${a.total_resultados}</span></div>
      </td>
      <td><strong style="color:var(--success)">${a.aprobados}</strong></td>
      <td><a href="ficha_detalle.php?ficha=${encodeURIComponent(a.ficha)}&openAvance=${encodeURIComponent(a.documento)}" class="btn btn-outline btn-sm">Ver →</a></td>
    </tr>`;
  }).join('');
}

let filterTimer;
function applyFilters() { 
  clearTimeout(filterTimer); 
  filterTimer=setTimeout(() => {
    loadTabla();
    applyGlobalFilters(); // Trigger global filters as well when advanced filters change
  }, 350); 
}
function clearFilters() { 
  document.getElementById('f-search').value=''; 
  document.getElementById('f-avance').value=''; 
  loadTabla();
  applyGlobalFilters();
}
function esc(str){return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

Chart.defaults.color='#8b949e'; Chart.defaults.font.family='Inter';

// ════════════════════════════════════════════════════════════════
// IMPORTADOR SOFIA PLUS
// ════════════════════════════════════════════════════════════════
let sfParsedMeta = {};
let sfParsedRows = [];

function handleSofiaFile(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const wb = XLSX.read(e.target.result, { type: 'binary', cellDates: true });
    const ws = wb.Sheets[wb.SheetNames[0]];
    const allRows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', blankrows: true });
    parseSofiaReport(allRows);
  };
  reader.readAsBinaryString(file);
}

function parseSofiaReport(allRows) {
  // ── 1. Buscar la fila de encabezados de datos ─────────────────
  //    La fila de encabezados contiene "Tipo de Documento" o "tipo"
  let headerRowIdx = -1;
  for (let i = 0; i < Math.min(allRows.length, 30); i++) {
    const row = allRows[i].map(c => String(c).toLowerCase().trim());
    if (row.some(c => c.includes('tipo') && (c.includes('documento') || c.includes('doc'))) ||
        row.some(c => c.includes('número') || c.includes('numero') || c === 'número de documento')) {
      headerRowIdx = i;
      break;
    }
  }

  if (headerRowIdx === -1) {
    alert('⚠️ No se pudo detectar la fila de encabezados de datos.\nVerifica que el archivo sea el reporte correcto de Sofia Plus.');
    return;
  }

  // ── 2. Extraer metadatos del encabezado (filas 0..headerRowIdx-1) ──
  const meta = {};
  for (let i = 0; i < headerRowIdx; i++) {
    const row = allRows[i];
    if (!row || !row.length) continue;

    for (let j = 0; j < row.length; j++) {
      // Normalizamos: a minúsculas, quitamos acentos y dos puntos
      const cellText = String(row[j] || '').trim().toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/:/g, '');

      if (!cellText) continue;

      let nextVal = '';
      for (let k = j + 1; k < row.length; k++) {
        if (String(row[k] || '').trim() !== '') {
          // Si el valor es una fecha de SheetJS, la formateamos
          if (row[k] instanceof Date) {
            nextVal = row[k].toISOString().split('T')[0]; // YYYY-MM-DD
          } else {
            nextVal = String(row[k] || '').trim();
          }
          break;
        }
      }

      if (!nextVal) continue;

      if (cellText.includes('ficha') && cellText.includes('caracterizacion') && !cellText.includes('estado')) meta.ficha = nextVal;
      else if (cellText === 'codigo' || cellText === 'cogigo') meta.codigo_programa = nextVal; // Sofia Plus escribe "Cógigo"
      else if (cellText === 'version') meta.version = nextVal;
      else if (cellText.includes('denominacion')) meta.nombre_programa = nextVal;
      else if (cellText.includes('estado') && cellText.includes('ficha')) meta.estado_ficha = nextVal;
      else if (cellText.includes('fecha') && cellText.includes('inicio')) meta.fecha_inicio = nextVal;
      else if (cellText.includes('fecha') && cellText.includes('fin')) meta.fecha_fin = nextVal;
      else if (cellText.includes('modalidad')) meta.modalidad = nextVal;
    }
  }

  console.log("Metadatos extraídos:", meta);

  // Fallback ficha: buscar en toda la sección de cabecera si no se detectó
  if (!meta.ficha) {
    for (let i = 0; i < headerRowIdx; i++) {
      allRows[i].forEach(c => {
        const s = String(c).trim();
        if (/^\d{6,8}$/.test(s)) meta.ficha = s;
      });
    }
  }

  // ── 3. Leer encabezados de columnas y filas de datos ─────────────
  const colHeaders = allRows[headerRowIdx].map(c => String(c).trim().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, ''));

  // Mapa de columnas por palabras clave
  const findCol = (...keys) => colHeaders.findIndex(h => keys.some(k => h.includes(k)));

  const cols = {
    tipo_doc  : findCol('tipo de documento', 'tipo documento', 'tipo doc'),
    num_doc   : findCol('número de documento', 'numero de documento', 'num doc', 'número doc'),
    nombre    : findCol('nombre'),
    apellidos : findCol('apellidos', 'apellido'),
    estado    : findCol('estado'),
    competencia: findCol('competencia'),
    resultado : findCol('resultado de aprendizaje', 'resultado aprendizaje', 'resultado'),
    juicio    : findCol('juicio de evaluaci', 'juicio evaluacion', 'juicio'),
    fecha_j   : findCol('fecha y hora', 'fecha juicio', 'fecha'),
    funcionario: findCol('funcionario', 'instructor'),
  };

  // Ajuste: nombre puede coincidir con "nombre del programa" — evitar eso
  if (cols.nombre >= 0 && colHeaders[cols.nombre].includes('programa')) {
    cols.nombre = colHeaders.findIndex((h, i) => h === 'nombre' || (h.includes('nombre') && !h.includes('programa')));
  }

  const dataRows = allRows.slice(headerRowIdx + 1).filter(r =>
    r.some(c => String(c).trim() !== '') &&
    String(r[cols.num_doc] ?? '').trim() !== ''
  );

  sfParsedMeta = meta;
  sfParsedRows = dataRows.map(r => {
    const get = idx => {
      if (idx < 0) return '';
      const val = r[idx];
      if (val instanceof Date) {
        // Formatear fecha de JS a YYYY-MM-DD HH:mm:ss
        const y = val.getFullYear();
        const m = String(val.getMonth() + 1).padStart(2, '0');
        const d = String(val.getDate()).padStart(2, '0');
        const hh = String(val.getHours()).padStart(2, '0');
        const mm = String(val.getMinutes()).padStart(2, '0');
        const ss = String(val.getSeconds()).padStart(2, '0');
        return `${y}-${m}-${d} ${hh}:${mm}:${ss}`;
      }
      return String(val ?? '').trim();
    };
    return {
      tipo_documento        : get(cols.tipo_doc),
      numero_documento      : get(cols.num_doc),
      nombre                : get(cols.nombre),
      apellidos             : get(cols.apellidos),
      estado                : get(cols.estado),
      competencia           : get(cols.competencia),
      resultado_aprendizaje : get(cols.resultado),
      juicio_evaluacion     : get(cols.juicio),
      fecha_juicio          : get(cols.fecha_j),
      funcionario           : get(cols.funcionario),
    };
  });

  // ── 4. Mostrar preview ────────────────────────────────────────
  showSofiaPreview();
}

function showSofiaPreview() {
  const meta = sfParsedMeta;
  const rows = sfParsedRows;

  document.getElementById('sf-ficha').textContent       = meta.ficha || '⚠️ No detectada';
  document.getElementById('sf-programa').textContent    = meta.nombre_programa || '—';
  document.getElementById('sf-codigo').textContent      = (meta.codigo_programa || '—') + (meta.version ? ' v'+meta.version : '');
  document.getElementById('sf-estado-ficha').textContent= meta.estado_ficha || '—';
  document.getElementById('sf-periodo').textContent     = (meta.fecha_inicio||'—') + ' → ' + (meta.fecha_fin||'—');

  const uniqAprendices = new Set(rows.map(r=>r.numero_documento).filter(Boolean));
  const conJuicio      = rows.filter(r => r.juicio_evaluacion && r.juicio_evaluacion !== '');
  const uniqComp       = new Set(rows.map(r=>r.competencia).filter(Boolean));
  const uniqRes        = new Set(rows.map(r=>r.resultado_aprendizaje).filter(Boolean));

  document.getElementById('sf-n-aprendices').textContent = uniqAprendices.size;
  document.getElementById('sf-n-juicios').textContent    = conJuicio.length;
  document.getElementById('sf-n-comp').textContent       = uniqComp.size;
  document.getElementById('sf-n-res').textContent        = uniqRes.size;

  const judgeColor = {'Aprobado':'badge-success','No Aprobado':'badge-danger','Aprobacion':'badge-success'};
  document.getElementById('sf-tbody-preview').innerHTML = rows.slice(0,50).map(r => `
    <tr>
      <td style="white-space:nowrap">${esc(r.tipo_documento.substring(0,12))}</td>
      <td style="font-family:monospace">${esc(r.numero_documento)}</td>
      <td>${esc(r.nombre)}</td>
      <td>${esc(r.apellidos)}</td>
      <td><span class="badge badge-muted" style="font-size:10px;">${esc(r.estado)}</span></td>
      <td style="color:var(--text-secondary);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.competencia.substring(0,30))}</td>
      <td style="color:var(--text-secondary);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.resultado_aprendizaje.substring(0,30))}</td>
      <td>${r.juicio_evaluacion ? `<span class="badge ${judgeColor[r.juicio_evaluacion]||'badge-info'}" style="font-size:10px;">${esc(r.juicio_evaluacion)}</span>` : '<span style="color:var(--text-muted);font-size:10px;">—</span>'}</td>
    </tr>
  `).join('');

  document.getElementById('sf-preview').style.display = 'block';
  document.getElementById('sf-dropzone').style.display = 'none';
}

async function doSofiaImport() {
  if (!sfParsedMeta.ficha) {
    alert('⚠️ No se detectó la ficha en el encabezado. Verifica el archivo.');
    return;
  }
  const btn = document.getElementById('btn-sf-import');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:6px;"></span> Importando...';

  const r = await fetch('../api/importar_reporte.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ficha_meta: sfParsedMeta, rows: sfParsedRows })
  }).then(r => r.json());

  const res = document.getElementById('sf-result');
  if (r.error) {
    res.innerHTML = `<div class="alert alert-danger">❌ Error: ${esc(r.error)}</div>`;
  } else {
    res.innerHTML = `
      <div class="alert alert-success">
        ✅ <strong>Importación completada</strong> — Ficha <strong>${esc(sfParsedMeta.ficha)}</strong> (${r.ficha})<br>
        👥 Aprendices: ${r.aprendices.insertados} nuevos · ${r.aprendices.actualizados} actualizados<br>
        📚 Competencias: ${r.competencias.insertadas} nuevas · ${r.competencias.ya_existian} ya existían<br>
        🎯 Resultados: ${r.resultados.insertados} nuevos<br>
        📝 Juicios: ${r.juicios.insertados} insertados · ${r.juicios.actualizados} actualizados · ${r.juicios.sin_juicio} sin valor
        ${r.errores?.length ? '<br>⚠️ ' + r.errores.slice(0,3).join(' | ') : ''}
      </div>`;
    cancelSofiaImport();
    // Refrescar KPIs y tabla
    init();
  }
  btn.disabled = false;
  btn.innerHTML = '🚀 Importar todo al sistema';
}

function cancelSofiaImport() {
  document.getElementById('sf-preview').style.display = 'none';
  document.getElementById('sf-dropzone').style.display = 'block';
  document.getElementById('sf-file').value = '';
  sfParsedMeta = {}; sfParsedRows = [];
}

init();

</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
