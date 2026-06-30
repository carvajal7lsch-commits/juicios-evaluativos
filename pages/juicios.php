<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Juicios Evaluativos';
$pageSubtitle = 'Registro e importación de juicios evaluativos por resultado de aprendizaje';
$activePage   = 'juicios';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="tabs fade-in">
  <button class="tab active" onclick="switchTab('tab-lista',this)">📋 Juicios Registrados</button>
  <button class="tab" onclick="switchTab('tab-registro',this)">✏️ Registrar Juicio</button>
  <button class="tab" onclick="switchTab('tab-import',this)">⬆️ Importación Masiva</button>
</div>

<!-- LISTA -->
<div id="tab-lista" class="tab-content active fade-in">
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Juicios evaluativos registrados</div><div class="card-subtitle" id="juicios-count">Cargando...</div></div>
    </div>
    <div class="filters-bar" style="margin-bottom:16px;">
      <div class="filter-search">
        <span class="search-icon">🔍</span>
        <input type="text" id="j-search" class="form-control" placeholder="Buscar aprendiz, competencia..." oninput="filterJuicios()" />
      </div>
      <select id="j-tipo" class="form-control" style="width:auto;" onchange="filterJuicios()">
        <option value="">Todos los tipos</option>
        <option>Aprobado</option><option>No Aprobado</option><option>Pendiente</option><option>En proceso</option>
      </select>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Aprendiz</th><th>Competencia</th><th>Resultado</th><th>Juicio</th><th>Funcionario</th><th>Fecha</th><th></th></tr></thead>
        <tbody id="tbody-juicios"><tr><td colspan="7" style="text-align:center;padding:32px;"><span class="spinner"></span></td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<!-- REGISTRO -->
<div id="tab-registro" class="tab-content fade-in">
  <div class="card" style="max-width:720px;">
    <div class="card-header"><div class="card-title">✏️ Registrar nuevo juicio evaluativo</div></div>
    <form id="form-juicio" onsubmit="saveJuicio(event)">
      <div class="form-group">
        <label class="form-label">Aprendiz <span class="req">*</span></label>
        <input type="text" id="jf-search-aprendiz" class="form-control" placeholder="Buscar por nombre o documento..." oninput="buscarAprendiz()" autocomplete="off" />
        <div id="aprendiz-results" style="position:relative;"></div>
        <input type="hidden" name="documento_aprendiz" id="jf-doc-aprendiz" />
        <div id="aprendiz-selected" style="margin-top:8px;display:none;">
          <span class="badge badge-info" id="aprendiz-badge"></span>
          <button type="button" onclick="clearAprendiz()" style="background:none;border:none;color:var(--danger);cursor:pointer;margin-left:6px;">✕</button>
        </div>
      </div>
      <div id="resultados-section" style="display:none;">
        <div class="form-group">
          <label class="form-label">Resultado de aprendizaje <span class="req">*</span></label>
          <select name="id_resultado" id="jf-resultado" class="form-control" required onchange="checkResultadoStatus()"><option value="">Selecciona un resultado...</option></select>
          <div id="resultado-status" style="margin-top:6px;"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Tipo de juicio <span class="req">*</span></label><select name="estado" id="jf-tipo" class="form-control" required></select></div>
          <div class="form-group"><label class="form-label">Funcionario evaluador <span class="req">*</span></label><select name="documento_funcionario" id="jf-funcionario" class="form-control" required></select></div>
        </div>
        <div class="form-group"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="3" placeholder="Descripción del desempeño..."></textarea></div>
        <div id="juicio-result"></div>
        <button type="submit" class="btn btn-primary" id="btn-save-juicio">💾 Guardar juicio</button>
      </div>
    </form>
  </div>
</div>

<!-- IMPORTACIÓN -->
<div id="tab-import" class="tab-content fade-in">
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">⬆️ Importación masiva de juicios</div></div>
      <button onclick="downloadTemplateJuicios()" class="btn btn-outline btn-sm">📥 Descargar plantilla</button>
    </div>
    <div class="alert alert-info" style="margin-bottom:16px;">
      ℹ️ Columnas: <strong>documento_aprendiz, codigo_resultado, tipo_juicio</strong>. Opcional: documento_funcionario, observaciones.
    </div>
    <div class="dropzone" id="dz-juicios" onclick="document.getElementById('file-juicios').click()">
      <input type="file" id="file-juicios" accept=".csv" style="display:none;" onchange="handleFileJuicios(this.files[0])" />
      <div class="dropzone-icon">📂</div>
      <div class="dropzone-title">Arrastra el CSV de juicios aquí</div>
      <div class="dropzone-sub">o haz clic para seleccionar</div>
    </div>
    <div id="jpreview-section" style="display:none;margin-top:24px;">
      <div class="card-header" style="margin-bottom:12px;">
        <div><div class="card-title">Vista previa</div><div class="card-subtitle" id="jpreview-count"></div></div>
        <div style="display:flex;gap:8px;">
          <button class="btn btn-outline btn-sm" onclick="cancelJuiciosImport()">Cancelar</button>
          <button class="btn btn-success" id="btn-jimport" onclick="doJuiciosImport()">⬆️ Importar <span id="jimport-count">0</span></button>
        </div>
      </div>
      <div class="table-wrap" style="max-height:300px;overflow-y:auto;">
        <table class="table">
          <thead><tr><th>#</th><th>Doc. Aprendiz</th><th>Cód. Resultado</th><th>Tipo Juicio</th><th>Funcionario</th></tr></thead>
          <tbody id="jpreview-body"></tbody>
        </table>
      </div>
    </div>
    <div id="jimport-result" style="margin-top:16px;"></div>
  </div>
</div>

<script>
const API_J='../api/juicios.php';
let allJuicios=[],csvJuicios=[],searchTimer;

function switchTab(id,btn){document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));document.getElementById(id).classList.add('active');btn.classList.add('active');}

async function init(){await Promise.all([loadJuicios(),loadFormSelects()]);}

async function loadJuicios(){
  const data=await fetch(API_J+'?action=list').then(r=>r.json());
  allJuicios=data;renderJuicios(data);
}

function renderJuicios(data){
  document.getElementById('juicios-count').textContent=data.length+' juicio(s)';
  const tbody=document.getElementById('tbody-juicios');
  if(!data.length){tbody.innerHTML='<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📝</div><p>Sin juicios registrados</p></div></td></tr>';return;}
  const col={'Aprobado':'badge-success','No Aprobado':'badge-danger','Pendiente':'badge-warning','En proceso':'badge-info'};
  tbody.innerHTML=data.map(j=>`<tr>
    <td><strong>${esc(j.aprendiz)}</strong><br><small style="color:var(--text-secondary);font-family:monospace;">${esc(j.documento_aprendiz)}</small></td>
    <td style="font-size:12px;max-width:160px;">${esc(j.competencia?.substring(0,50))}</td>
    <td style="font-size:12px;"><span style="color:var(--text-secondary)">${esc(j.cod_resultado)}</span><br>${esc(j.resultado?.substring(0,60))}</td>
    <td><span class="badge ${col[j.tipo_juicio]||'badge-muted'}">${esc(j.tipo_juicio)}</span></td>
    <td style="font-size:12px;">${esc(j.funcionario)}</td>
    <td style="font-size:12px;color:var(--text-secondary);">${j.fecha_registro?j.fecha_registro.substring(0,10):'—'}</td>
    <td><button class="btn btn-danger btn-sm" onclick="deleteJuicio(${j.id_juicio})">🗑</button></td>
  </tr>`).join('');
}

function filterJuicios(){
  const q=document.getElementById('j-search').value.toLowerCase();
  const t=document.getElementById('j-tipo').value;
  renderJuicios(allJuicios.filter(j=>(!q||(j.aprendiz+j.competencia+j.resultado+j.documento_aprendiz).toLowerCase().includes(q))&&(!t||j.tipo_juicio===t)));
}

async function deleteJuicio(id){if(!confirm('¿Eliminar este juicio?'))return;await fetch(API_J+'?id='+id,{method:'DELETE'});loadJuicios();}

async function loadFormSelects(){
  const [tipos,funcionarios]=await Promise.all([fetch(API_J+'?action=tipos').then(r=>r.json()),fetch(API_J+'?action=funcionarios').then(r=>r.json())]);
  const selT=document.getElementById('jf-tipo');tipos.forEach(t=>{const o=document.createElement('option');o.value=t.nombre;o.textContent=t.nombre;selT.appendChild(o);});
  const selF=document.getElementById('jf-funcionario');
  if(!funcionarios.length){const o=document.createElement('option');o.value='00000000';o.textContent='(Sin funcionario)';selF.appendChild(o);}
  funcionarios.forEach(f=>{const o=document.createElement('option');o.value=f.documento;o.textContent=f.nombre_completo;selF.appendChild(o);});
}

async function buscarAprendiz(){
  clearTimeout(searchTimer);
  searchTimer=setTimeout(async()=>{
    const q=document.getElementById('jf-search-aprendiz').value.trim();
    if(q.length<2){document.getElementById('aprendiz-results').innerHTML='';return;}
    const data=await fetch('../api/aprendices.php?action=list').then(r=>r.json());
    const found=data.filter(a=>(a.nombre+' '+a.apellidos+' '+a.documento).toLowerCase().includes(q.toLowerCase())).slice(0,6);
    const div=document.getElementById('aprendiz-results');
    if(!found.length){div.innerHTML='';return;}
    div.innerHTML=`<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);position:absolute;width:100%;z-index:50;margin-top:4px;overflow:hidden;">
      ${found.map(a=>`<div style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);"
        onmousedown="selectAprendiz('${esc(a.documento)}','${esc(a.nombre+' '+a.apellidos)}')"
        onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background=''">
        <strong>${esc(a.nombre+' '+a.apellidos)}</strong> <span style="color:var(--text-secondary);font-family:monospace;">${esc(a.documento)}</span>
        <br><small style="color:var(--text-muted)">${esc(a.ficha)} — ${esc(a.programa?.substring(0,35))}</small>
      </div>`).join('')}
    </div>`;
  },300);
}

async function selectAprendiz(doc,nombre){
  document.getElementById('jf-doc-aprendiz').value=doc;
  document.getElementById('jf-search-aprendiz').value='';
  document.getElementById('aprendiz-results').innerHTML='';
  document.getElementById('aprendiz-badge').textContent=nombre+' — '+doc;
  document.getElementById('aprendiz-selected').style.display='block';
  document.getElementById('resultados-section').style.display='block';
  const data=await fetch(API_J+'?action=resultados_by_aprendiz&documento='+encodeURIComponent(doc)).then(r=>r.json());
  const sel=document.getElementById('jf-resultado');sel.innerHTML='<option value="">Selecciona un resultado...</option>';
  let lastComp='';
  data.forEach(r=>{
    if(r.competencia!==lastComp){const og=document.createElement('optgroup');og.label='📚 '+r.competencia;sel.appendChild(og);lastComp=r.competencia;}
    const o=document.createElement('option');o.value=r.id_resultado;o.textContent=r.codigo+' — '+r.descripcion.substring(0,60);o.dataset.juicio=r.juicio_actual||'';sel.appendChild(o);
  });
}

function clearAprendiz(){document.getElementById('jf-doc-aprendiz').value='';document.getElementById('aprendiz-selected').style.display='none';document.getElementById('resultados-section').style.display='none';document.getElementById('jf-search-aprendiz').value='';}

function checkResultadoStatus(){
  const sel=document.getElementById('jf-resultado');const opt=sel.options[sel.selectedIndex];const juicio=opt?.dataset?.juicio;
  const div=document.getElementById('resultado-status');
  if(juicio)div.innerHTML=`<div class="alert alert-warning">⚠️ Ya existe juicio <strong>${esc(juicio)}</strong>. Al guardar, se actualizará.</div>`;
  else div.innerHTML='';
}

async function saveJuicio(e){
  e.preventDefault();const data=Object.fromEntries(new FormData(e.target));
  if(!data.documento_aprendiz){alert('Selecciona un aprendiz');return;}
  const btn=document.getElementById('btn-save-juicio');btn.disabled=true;
  const r=await fetch(API_J+'?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(r=>r.json());
  const res=document.getElementById('juicio-result');
  if(r.ok){res.innerHTML='<div class="alert alert-success" style="margin-bottom:12px;">✅ Juicio guardado.</div>';loadJuicios();clearAprendiz();document.getElementById('jf-resultado').innerHTML='<option value="">Selecciona un resultado...</option>';document.getElementById('resultado-status').innerHTML='';}
  else res.innerHTML=`<div class="alert alert-danger" style="margin-bottom:12px;">❌ ${esc(r.error)}</div>`;
  btn.disabled=false;setTimeout(()=>res.innerHTML='',5000);
}

const dzJ=document.getElementById('dz-juicios');
dzJ.addEventListener('dragover',e=>{e.preventDefault();dzJ.classList.add('dragover');});
dzJ.addEventListener('dragleave',()=>dzJ.classList.remove('dragover'));
dzJ.addEventListener('drop',e=>{e.preventDefault();dzJ.classList.remove('dragover');handleFileJuicios(e.dataTransfer.files[0]);});

function handleFileJuicios(file){if(!file)return;Papa.parse(file,{header:true,skipEmptyLines:true,complete:r=>{csvJuicios=r.data;showJuiciosPreview(r.data);}});}

function showJuiciosPreview(rows){
  document.getElementById('jpreview-section').style.display='block';
  document.getElementById('jpreview-count').textContent=rows.length+' filas';
  document.getElementById('jimport-count').textContent=rows.length;
  document.getElementById('jpreview-body').innerHTML=rows.slice(0,50).map((r,i)=>`
    <tr><td>${i+1}</td><td style="font-family:monospace;font-size:12px">${esc(r.documento_aprendiz||r.DOCUMENTO_APRENDIZ||'')}</td>
    <td>${esc(r.codigo_resultado||r.CODIGO_RESULTADO||'')}</td>
    <td><span class="badge badge-info">${esc(r.tipo_juicio||r.TIPO_JUICIO||'Aprobado')}</span></td>
    <td style="font-size:12px">${esc(r.documento_funcionario||'—')}</td></tr>
  `).join('');
}

async function doJuiciosImport(){
  const btn=document.getElementById('btn-jimport');btn.disabled=true;btn.textContent='Importando...';
  const r=await fetch(API_J+'?action=bulk',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({rows:csvJuicios})}).then(r=>r.json());
  const res=document.getElementById('jimport-result');
  if(r.error)res.innerHTML=`<div class="alert alert-danger">❌ ${esc(r.error)}</div>`;
  else{res.innerHTML=`<div class="alert alert-success">✅ ${r.insertados} insertados, ${r.actualizados} actualizados.</div>`;cancelJuiciosImport();loadJuicios();}
  btn.disabled=false;
}

function cancelJuiciosImport(){document.getElementById('jpreview-section').style.display='none';document.getElementById('file-juicios').value='';csvJuicios=[];}

function downloadTemplateJuicios(){
  const csv='documento_aprendiz,codigo_resultado,tipo_juicio,documento_funcionario,observaciones\n1234567890,240201500-N-001,Aprobado,87654321,Demuestra competencia técnica';
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='plantilla_juicios.csv';a.click();
}

function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
