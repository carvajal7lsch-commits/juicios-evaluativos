<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Gestión de Fases y Actividades';
$pageSubtitle = 'Configuración del Proyecto Formativo y asignación de RAPs';
$activePage   = 'fases';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- Cargar PDF.js para lectura de documentos en el cliente -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>

<!-- Dropzone Global -->
<div id="pdf-import-zone" class="card fade-in" style="margin-bottom: 24px; border-color: rgba(0,166,80,0.4); background: linear-gradient(135deg, rgba(0,166,80,0.05), rgba(26,77,181,0.05));">
    <div class="card-header">
        <div>
            <div class="card-title" style="color: var(--sena-green);">⚡ Importación Automática (PDF)</div>
            <div class="card-subtitle">Arrastra el documento PDF del Proyecto Formativo. El sistema detectará automáticamente el Programa, Fases, Actividades y RAPs.</div>
        </div>
    </div>
    <div id="pdf-dropzone" style="border: 2px dashed rgba(0,166,80,0.5); border-radius: 14px; padding: 36px 24px; text-align: center; cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.5); margin: 16px;"
         onclick="document.getElementById('pdf-file').click()"
         ondragover="event.preventDefault(); this.style.borderColor='var(--sena-blue)'; this.style.background='rgba(26,77,181,0.05)';"
         ondragleave="this.style.borderColor='rgba(0,166,80,0.5)'; this.style.background='rgba(255,255,255,0.5)';"
         ondrop="event.preventDefault(); this.style.borderColor='rgba(0,166,80,0.5)'; this.style.background='rgba(255,255,255,0.5)'; handlePDFFile(event.dataTransfer.files[0]);">
        <input type="file" id="pdf-file" accept=".pdf" style="display:none;" onchange="handlePDFFile(this.files[0])" />
        <div style="font-size: 36px; margin-bottom: 10px;">📄</div>
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">Arrastra aquí el PDF del Proyecto Formativo</div>
        <div style="font-size: 12px; color: var(--text-secondary);">No necesitas seleccionar el programa primero, el algoritmo lo detectará por ti.</div>
        
        <div id="pdf-loader" style="display:none; margin-top: 16px;">
            <span class="spinner" style="border-color: var(--sena-green); border-right-color: transparent;"></span>
            <div style="font-size: 12px; font-weight: 600; color: var(--sena-green); margin-top: 8px;">Leyendo e interpretando documento...</div>
        </div>
    </div>
</div>

<div class="card fade-in" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div class="card-title">📚 Gestión Manual y Visualización</div>
            <div class="card-subtitle">Selecciona el programa para ver sus fases y gestionar la asignación manual</div>
        </div>
        <button id="btn-borrar-estructura" class="btn btn-sm" style="background: #dc3545; color: white; border: none; display: none;" onclick="borrarEstructura()">🗑️ Borrar Estructura Actual</button>
    </div>
    <div style="padding: 16px;">
        <select id="sel-programa" class="form-control" style="max-width: 500px;" onchange="loadFases()">
            <option value="">Cargando programas...</option>
        </select>
    </div>
</div>

<div class="grid-2 fade-in" style="gap: 24px; align-items: start; display: none;" id="panel-fases">
    <!-- Panel Izquierdo: Fases y Actividades -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">🗂️ Fases del Proyecto</div>
                <div class="card-subtitle">Organiza el proyecto en fases y sus respectivas actividades</div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openModalFase()">+ Nueva Fase</button>
        </div>
        <div id="lista-fases" style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
            <!-- Fases renderizadas aquí -->
        </div>
    </div>

    <!-- Panel Derecho: Asignación de RAPs -->
    <div class="card" id="panel-asignacion" style="opacity: 0.5; pointer-events: none;">
        <div class="card-header">
            <div>
                <div class="card-title" id="titulo-actividad">🎯 Resultados de Aprendizaje</div>
                <div class="card-subtitle">Selecciona una actividad para asignar RAPs</div>
            </div>
        </div>
        <div style="padding: 16px;">
            <div style="position: relative; margin-bottom: 12px;">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);">🔍</span>
                <input type="text" id="search-rap" class="form-control" placeholder="Buscar RAP o Competencia..." style="padding-left:36px;" oninput="filterRAPs()">
            </div>
            <div style="margin-bottom: 16px; display: flex; gap: 8px; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 8px;">
                    <button id="btn-filter-all" class="btn btn-sm btn-primary" onclick="setRapFilter('all')">Todos los RAPs</button>
                    <button id="btn-filter-assigned" class="btn btn-sm btn-outline" onclick="setRapFilter('assigned')">Asignados a esta Actividad</button>
                </div>
                <button id="btn-edit-mode" class="btn btn-sm btn-outline" style="border-color: #ff9800; color: #ff9800;" onclick="toggleEditMode()">🔒 Modo Lectura</button>
            </div>
            <div id="lista-raps" style="max-height: 500px; overflow-y: auto;">
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    Selecciona una actividad en el panel izquierdo
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales Manuales... -->
<!-- Modal Fase -->
<div id="modal-fase" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="card" style="width:400px; animation:fadeInUp 0.3s ease;">
        <div class="card-header">
            <div class="card-title">Nueva Fase</div>
            <button class="btn" style="background:none; border:none; font-size:20px; cursor:pointer;" onclick="closeModalFase()">✕</button>
        </div>
        <div style="padding: 20px;">
            <div class="form-group">
                <label class="form-label">Nombre de la Fase <span class="req">*</span></label>
                <input type="text" id="mf-nombre" class="form-control" placeholder="Ej: Análisis">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="mf-desc" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <button class="btn btn-outline" onclick="closeModalFase()">Cancelar</button>
                <button class="btn btn-primary" onclick="saveFase()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Actividad -->
<div id="modal-actividad" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="card" style="width:400px; animation:fadeInUp 0.3s ease;">
        <div class="card-header">
            <div class="card-title">Nueva Actividad</div>
            <button class="btn" style="background:none; border:none; font-size:20px; cursor:pointer;" onclick="closeModalActividad()">✕</button>
        </div>
        <div style="padding: 20px;">
            <input type="hidden" id="ma-idfase">
            <div class="form-group">
                <label class="form-label">Nombre de la Actividad <span class="req">*</span></label>
                <input type="text" id="ma-nombre" class="form-control" placeholder="Ej: Recolección de requisitos">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="ma-desc" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <button class="btn btn-outline" onclick="closeModalActividad()">Cancelar</button>
                <button class="btn btn-primary" onclick="saveActividad()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview de Importación PDF -->
<div id="modal-pdf-preview" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="card" style="width:800px; max-width:95%; height:90vh; display:flex; flex-direction:column; animation:fadeInUp 0.3s ease;">
        <div class="card-header" style="border-bottom: 1px solid var(--border);">
            <div>
                <div class="card-title" style="color: var(--sena-green);">✅ Documento Analizado con Éxito</div>
                <div class="card-subtitle" id="pdf-prog-subtitle">Verifica la estructura extraída antes de guardarla</div>
            </div>
            <button class="btn" style="background:none; border:none; font-size:20px; cursor:pointer;" onclick="closePdfPreview()">✕</button>
        </div>
        
        <div style="padding: 16px; background: rgba(0,0,0,0.02); display: flex; gap: 16px; border-bottom: 1px solid var(--border);">
            <div class="kpi-card" style="flex:1; padding:10px; margin:0; --kpi-color: var(--sena-blue); box-shadow:none; border:1px solid var(--border);">
                <div class="kpi-value" id="pdf-kpi-fases" style="font-size:20px;">0</div>
                <div class="kpi-label">Fases detectadas</div>
            </div>
            <div class="kpi-card" style="flex:1; padding:10px; margin:0; --kpi-color: var(--sena-green); box-shadow:none; border:1px solid var(--border);">
                <div class="kpi-value" id="pdf-kpi-acts" style="font-size:20px;">0</div>
                <div class="kpi-label">Actividades</div>
            </div>
            <div class="kpi-card" style="flex:1; padding:10px; margin:0; --kpi-color: var(--warning); box-shadow:none; border:1px solid var(--border);">
                <div class="kpi-value" id="pdf-kpi-raps" style="font-size:20px;">0</div>
                <div class="kpi-label">RAPs identificados</div>
            </div>
        </div>

        <div id="pdf-preview-content" style="flex:1; overflow-y:auto; padding: 20px; display: flex; flex-direction: column; gap: 16px;">
            <!-- Render dinámico -->
        </div>

        <div style="padding: 16px; border-top: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--bg-card);">
            <div style="font-size: 11px; color: var(--text-muted);">
                Nota: Se vincularán las competencias y RAPs al programa automáticamente sin duplicar registros.
            </div>
            <div style="display:flex; gap:12px;">
                <button class="btn btn-outline" onclick="closePdfPreview()">Cancelar</button>
                <button class="btn btn-success" id="btn-save-pdf" onclick="savePdfImport()">💾 Importar Proyecto Formativo</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '../api/proyecto.php';
let currentProgram = '';
let currentActividad = '';
let allFases = [];
let allRaps = [];
let currentRapFilter = 'all'; // 'all' o 'assigned'
let isEditMode = false;
let extractedProjectData = [];
let extractedProgramCode = null;

async function init() {
    const progs = await fetch(API + '?action=programas').then(r => r.json());
    const sel = document.getElementById('sel-programa');
    sel.innerHTML = '<option value="">Selecciona un programa...</option>';
    progs.forEach(p => {
        sel.innerHTML += `<option value="${p.id_programa}">${p.codigo} - ${esc(p.nombre)}</option>`;
    });
}

async function loadFases() {
    currentProgram = document.getElementById('sel-programa').value;
    if (!currentProgram) {
        document.getElementById('panel-fases').style.display = 'none';
        return;
    }
    
    document.getElementById('panel-fases').style.display = 'grid';
    const [fases] = await Promise.all([
        fetch(`${API}?action=fases&id_programa=${currentProgram}`).then(r => r.json()),
    ]);
    
    allFases = fases;
    renderFases();
    
    const btnBorrar = document.getElementById('btn-borrar-estructura');
    if (allFases.length > 0) {
        btnBorrar.style.display = 'block';
    } else {
        btnBorrar.style.display = 'none';
    }
}

async function renderFases() {
    const container = document.getElementById('lista-fases');
    if (!allFases.length) {
        container.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding:20px;">No hay fases creadas. Importa un PDF o usa "Nueva Fase".</div>';
        return;
    }

    container.innerHTML = '';
    for (const f of allFases) {
        const acts = await fetch(`${API}?action=actividades&id_fase=${f.id_fase}`).then(r => r.json());
        
        const card = document.createElement('div');
        card.style = "background: rgba(0,0,0,0.02); border: 1px solid var(--border); border-radius: 8px; padding: 16px;";
        
        let actsHtml = acts.length === 0 
            ? '<div style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">Sin actividades</div>'
            : '<div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">' + acts.map(a => `
                <div onclick="selectActividad(${a.id_actividad}, '${esc(a.nombre_actividad)}')" style="background: var(--bg-card); padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.2s;" onmouseover="this.style.borderColor='var(--sena-blue)'" onmouseout="this.style.borderColor='var(--border)'">
                    <span style="font-size: 13px; font-weight: 600;">📝 ${esc(a.nombre_actividad)}</span>
                    <span style="font-size: 11px; color: var(--sena-blue);">Configurar →</span>
                </div>
            `).join('') + '</div>';

        card.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--sena-blue);">${esc(f.nombre_fase)}</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">${esc(f.descripcion)}</div>
                </div>
                <button class="btn btn-outline btn-sm" onclick="openModalActividad(${f.id_fase})">+ Actividad</button>
            </div>
            ${actsHtml}
        `;
        container.appendChild(card);
    }
}

async function selectActividad(id_actividad, nombre) {
    currentActividad = id_actividad;
    document.getElementById('panel-asignacion').style.opacity = '1';
    document.getElementById('panel-asignacion').style.pointerEvents = 'auto';
    document.getElementById('titulo-actividad').textContent = '🎯 RAPs: ' + nombre;
    
    document.getElementById('lista-raps').innerHTML = '<div style="text-align:center; padding:40px;"><span class="spinner"></span></div>';
    
    allRaps = await fetch(`${API}?action=raps_por_programa&id_programa=${currentProgram}&id_actividad=${id_actividad}`).then(r => r.json());
    
    // Auto-seleccionar 'assigned' si hay RAPs asignados, para que el usuario vea de inmediato la configuración
    const hasAssigned = allRaps.some(r => r.asignado);
    setRapFilter(hasAssigned ? 'assigned' : 'all');
    
    // Resetear modo edición al cambiar de actividad por seguridad
    isEditMode = false;
    updateEditModeUI();
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    updateEditModeUI();
    filterRAPs();
    
    if (isEditMode) {
        showCustomConfirm(
            "⚠️ Modo Edición Activado", 
            "El sistema permite la asignación manual para casos excepcionales (ej. RAPs transversales). Ten cuidado de no alterar la estructura oficial extraída del PDF sin justificación.",
            "Entendido",
            "btn-primary",
            () => {}
        );
    }
}

function updateEditModeUI() {
    const btn = document.getElementById('btn-edit-mode');
    if (isEditMode) {
        btn.innerHTML = '✏️ Modo Edición';
        btn.style.backgroundColor = '#fff3e0';
    } else {
        btn.innerHTML = '🔒 Modo Lectura';
        btn.style.backgroundColor = 'transparent';
    }
}

function setRapFilter(filter) {
    currentRapFilter = filter;
    if (filter === 'all') {
        document.getElementById('btn-filter-all').className = 'btn btn-sm btn-primary';
        document.getElementById('btn-filter-assigned').className = 'btn btn-sm btn-outline';
    } else {
        document.getElementById('btn-filter-all').className = 'btn btn-sm btn-outline';
        document.getElementById('btn-filter-assigned').className = 'btn btn-sm btn-primary';
    }
    filterRAPs();
}

function filterRAPs() {
    const q = document.getElementById('search-rap').value.toLowerCase().trim();
    const container = document.getElementById('lista-raps');
    
    let filtered = allRaps.filter(r => 
        (r.competencia.toLowerCase().includes(q) || 
        r.codigo.toLowerCase().includes(q) || 
        r.descripcion.toLowerCase().includes(q))
    );
    
    if (currentRapFilter === 'assigned') {
        filtered = filtered.filter(r => r.asignado);
    }
    
    if (!filtered.length) {
        container.innerHTML = `<div style="padding:20px; text-align:center; color:var(--text-muted);">No se encontraron RAPs con los filtros actuales.</div>`;
        return;
    }

    // Agrupar por competencia
    const grouped = filtered.reduce((acc, r) => {
        if (!acc[r.competencia]) acc[r.competencia] = [];
        acc[r.competencia].push(r);
        return acc;
    }, {});
    
    container.innerHTML = Object.entries(grouped).map(([comp, raps]) => `
        <div style="margin-bottom: 16px;">
            <div style="background: rgba(26,77,181,0.05); padding: 8px 12px; border-left: 3px solid var(--sena-blue); font-size: 12px; font-weight: 700; color: var(--sena-blue); margin-bottom: 8px;">
                ${esc(comp)}
            </div>
            ${raps.map(r => `
                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 10px; border: 1px solid ${r.asignado ? 'var(--sena-green)' : 'var(--border)'}; border-radius: 6px; margin-bottom: 6px; cursor: ${isEditMode ? 'pointer' : 'default'}; background: ${r.asignado ? 'rgba(0,166,80,0.05)' : 'var(--bg-card)'}; transition: 0.2s;" ${isEditMode ? `onmouseover="this.style.borderColor='var(--sena-green)'" onmouseout="this.style.borderColor='${r.asignado ? 'var(--sena-green)' : 'var(--border)'}'"` : ''}>
                    <input type="checkbox" style="margin-top: 4px;" ${r.asignado ? 'checked' : ''} ${!isEditMode ? 'disabled' : ''} onchange="toggleRap(${r.id_resultado}, this.checked)">
                    <div style="opacity: ${!isEditMode && !r.asignado ? '0.6' : '1'};">
                        <div style="font-size: 11px; font-family: monospace; color: ${r.asignado ? 'var(--sena-green)' : 'var(--text-muted)'}; font-weight: ${r.asignado ? 'bold' : 'normal'};">${esc(r.codigo)}</div>
                        <div style="font-size: 13px; color: var(--text-primary); line-height: 1.3; font-weight: ${r.asignado ? '500' : 'normal'};">${esc(r.descripcion)}</div>
                    </div>
                </label>
            `).join('')}
        </div>
    `).join('');
}

async function toggleRap(id_resultado, asignar) {
    await fetch(`${API}?action=asignar_rap`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_actividad: currentActividad, id_resultado, asignar }) });
    const rap = allRaps.find(r => r.id_resultado === id_resultado);
    if (rap) rap.asignado = asignar ? 1 : 0;
    
    // Solo re-filtrar si estamos en la vista de "Asignados", para que desaparezca al desmarcarlo (con una pequeña demora visual)
    if (currentRapFilter === 'assigned') {
        setTimeout(filterRAPs, 300);
    } else {
        filterRAPs(); // Re-renderizar para actualizar colores
    }
}

// ========================================================
// NUEVA LOGICA DE EXTRACCIÓN Y LECTURA DE PDF (PDF.js) MEJORADA
// ========================================================
async function handlePDFFile(file) {
    if (!file || file.type !== 'application/pdf') return showCustomToast('Por favor sube un archivo PDF válido', true);
    
    document.getElementById('pdf-loader').style.display = 'block';
    
    const reader = new FileReader();
    reader.onload = async function() {
        try {
            const typedarray = new Uint8Array(this.result);
            const pdf = await pdfjsLib.getDocument(typedarray).promise;
            let fullText = "";
            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const textContent = await page.getTextContent();
                const pageText = textContent.items.map(item => item.str).join(" ");
                fullText += pageText + " \n";
            }
            
            // GUARDAR EL TEXTO EN EL SERVIDOR PARA DEPUBAR POR LA IA
            await fetch('../api/save_debug.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: fullText })
            });

            parseProjectTextV2(fullText);
            
        } catch(e) {
            console.error(e);
            showCustomToast("Error procesando el PDF: " + e.message, true);
        } finally {
            document.getElementById('pdf-loader').style.display = 'none';
        }
    };
    reader.readAsArrayBuffer(file);
}

function parseProjectTextV2(text) {
    let n = text.replace(/\s+/g, ' ');
    // Normalizar tildes y caracteres especiales (Crucial para PDFs con tildes separadas)
    n = n.normalize("NFC");
    
    let fullText = n; // Guardar copia para búsquedas globales
    
    // Extraer código del programa ANTES de recortar el texto
    let progMatch = fullText.match(/(\d{5,10})\s+C[oó]digo\s+del\s+Programa\s+SOFIA/i);
    extractedProgramCode = progMatch ? progMatch[1] : null;

    // Delimitar búsqueda a la sección de estructura (Sección 3 a 3.5 aprox)
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
    let lastKnownFase = "ANALISIS";
    let lastKnownAct = "Actividad General";

    anchors.forEach((anchor, index) => {
        let compCode = anchor[1];
        let anchorIdx = anchor.index;
        
        let startSearch = (index === 0) ? 0 : (anchors[index-1].index + anchors[index-1][0].length);
        let segment = n.substring(startSearch, anchorIdx);

        // RAPs: Estrictamente 6 a 8 dígitos
        let rapMatches = [...segment.matchAll(/(\b\d{6,8}\b)[\s\-\.]+/g)];
        
        let nextAnchor = anchors[index + 1];
        let endComp = nextAnchor ? nextAnchor.index : anchorIdx + 800;
        let compDesc = n.substring(anchorIdx + anchor[0].length, endComp).trim();
        
        // Limpieza inteligente de Competencia: Detenerse ante el primer indicio de una Fase o Actividad o Fecha
        let phaseMarkers = Object.keys(FASES_SENA).join('|');
        let stopRegex = new RegExp("(?:^|\\s)(?:" + phaseMarkers + "|Actividad)\\s+\\d+[.\\-\\s]", "i");
        let stopIdx = compDesc.search(stopRegex);
        if (stopIdx !== -1 && stopIdx > 5) {
            compDesc = compDesc.substring(0, stopIdx).trim();
        }
        
        // También detenerse si vemos otro código (RAP o Comp)
        let nextMarker = compDesc.search(/\d{6,10}[\s\-\.]+/);
        if (nextMarker !== -1) compDesc = compDesc.substring(0, nextMarker).trim();

        rapMatches.forEach(rm => {
            let rapCode = rm[1];
            if (rapCode === compCode) return;

            let rapPosInN = startSearch + rm.index;
            let headerWindow = n.substring(Math.max(0, rapPosInN - 1500), rapPosInN).trim();
            let foundFase = null;
            
            // Buscar la fase/actividad más cercana HACIA ATRÁS (usando regex global para encontrar la última)
            Object.keys(FASES_SENA).forEach(f => {
                let fRegex = new RegExp("(?:^|\\s|\\d+\\.)(" + f + ")(?=[\\s\\d\\.\\-]|$)","gi");
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
                
                // Detener actCandidate antes del RAP actual o cualquier otro código sospechoso
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
            
            // Limpiar ruidos en rapDesc
            rapDesc = rapDesc.replace(/^\d+[\s\-\.]*/, '').trim();
            // Detener si parece el inicio de un encabezado de fase
            let phaseHeaderRegex = new RegExp("\\b(?:" + Object.keys(FASES_SENA).join('|') + "|Actividad)\\s+\\d+[.\\-\\s]", "i");
            let phaseIdx = rapDesc.search(phaseHeaderRegex);
            if (phaseIdx !== -1) rapDesc = rapDesc.substring(0, phaseIdx).trim();

            if (rapDesc.length > 10 && !rapDesc.match(/^\d+$/)) {
                registrarDato(lastKnownFase, lastKnownAct, compCode, compDesc, rapCode, rapDesc);
            }
        });
    });

    // --- BARRIDO FINAL: Solo si NO se detectó NINGUNA actividad de Etapa Práctica ---
    let yaTienePractica = false;
    Object.values(dataDict).forEach(f => {
        if (Object.values(f.actividades).some(a => a.competencias["999999999"])) yaTienePractica = true;
    });

    if (!yaTienePractica) {
        if (fullText.toUpperCase().includes("ETAPA PRACTICA")) {
            registrarDato("EVALUACION", "Actividad número 9: DESARROLLAR LA ETAPA PRODUCTIVA", "999999999", "RESULTADOS DE APRENDIZAJE ETAPA PRACTICA", "202634", "APLICAR EN LA RESOLUCIÓN DE PROBLEMAS REALES DEL SECTOR PRODUCTIVO");
        }
    }

    const FASE_DISPLAY = { 'ANALISIS': 'ANÁLISIS', 'PLANEACION': 'PLANEACIÓN', 'EJECUCION': 'EJECUCIÓN', 'EVALUACION': 'EVALUACIÓN' };

    ['ANALISIS', 'PLANEACION', 'EJECUCION', 'EVALUACION'].forEach(fKey => {
        if (dataDict[fKey]) {
            let f = { nombre: "Fase de " + FASE_DISPLAY[fKey], actividades: [] };
            Object.values(dataDict[fKey].actividades).forEach(act => {
                f.actividades.push({ nombre: act.nombre, competencias: Object.values(act.competencias) });
            });
            data.push(f);
        }
    });

    function registrarDato(fase, act, cCode, cDesc, rCode, rDesc) {
        if (!dataDict[fase]) dataDict[fase] = { nombre: fase, actividades: {} };
        let actNumMatch = act.match(/Actividad número (\d+)/i);
        let aKey = actNumMatch ? ("act_" + actNumMatch[1]) : act.substring(0, 30).replace(/[^a-z0-9]/gi, '_');

        if (!dataDict[fase].actividades[aKey]) dataDict[fase].actividades[aKey] = { nombre: act, competencias: {} };
        let actRef = dataDict[fase].actividades[aKey];
        if (act.length > actRef.nombre.length) actRef.nombre = act;
        
        if (!actRef.competencias[cCode]) {
            actRef.competencias[cCode] = { codigo: cCode, nombre: cDesc, raps: [] };
        } else {
            // Si el nuevo nombre es más corto pero razonable, probablemente esté más limpio (sin encabezados pegados)
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

    extractedProjectData = data;
    showPdfPreview();
}

function showPdfPreview() {
    let tFases = extractedProjectData.length;
    let tActs = 0;
    let tRaps = 0;
    let html = '';
    
    // Alerta de programa detectado
    let progHtml = extractedProgramCode 
        ? `<div style="background: rgba(0,166,80,0.1); border: 1px solid var(--sena-green); padding: 10px; border-radius: 6px; margin-bottom: 16px; font-weight: 700; color: var(--sena-green);">
             ✅ Código de programa detectado en el PDF: ${extractedProgramCode}
             <div style="font-size:11px; font-weight:normal; color:var(--text-secondary);">El sistema lo vinculará automáticamente. No necesitas seleccionarlo.</div>
           </div>`
        : `<div style="background: rgba(210,153,34,0.1); border: 1px solid var(--warning); padding: 10px; border-radius: 6px; margin-bottom: 16px; font-weight: 700; color: var(--warning);">
             ⚠️ No se detectó un código de programa claro en el PDF.
             <div style="font-size:11px; font-weight:normal; color:var(--text-secondary);">Por favor selecciona el programa en la lista desplegable de abajo antes de confirmar.</div>
           </div>`;
           
    html += progHtml;
    
    extractedProjectData.forEach((fase, fIdx) => {
        html += `<div style="border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); overflow: hidden; margin-bottom:12px;">
                    <div style="background: rgba(0,166,80,0.05); padding: 12px 16px; border-bottom: 1px solid var(--border); font-weight: 800; color: var(--sena-green);">
                        ${fIdx+1}. ${esc(fase.nombre)}
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">`;
        if (fase.actividades) {
            fase.actividades.forEach((act, aIdx) => {
                tActs++;
                html += `<div style="padding: 12px; border: 1px dashed var(--border); border-radius: 6px; background: rgba(0,0,0,0.02);">
                            <div style="font-size: 13px; font-weight: 700; color: var(--sena-blue); margin-bottom: 8px;">
                                Actividad ${fIdx+1}.${aIdx+1}: ${esc(act.nombre)}
                            </div>`;
                if (act.competencias) {
                    act.competencias.forEach(comp => {
                        html += `<div style="margin-left: 16px; margin-bottom: 8px;">
                                    <div style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">
                                        📚 Comp: ${esc(comp.codigo)} - ${esc(comp.nombre)}
                                    </div>
                                    <div style="margin-left: 16px; display: flex; flex-direction: column; gap: 4px;">`;
                        if (comp.raps) {
                            comp.raps.forEach(rap => {
                                tRaps++;
                                html += `<div style="font-size: 11px; background: var(--bg-card); border: 1px solid var(--border); padding: 6px 10px; border-radius: 4px;">
                                            <span style="font-family: monospace; color: var(--text-muted); margin-right: 6px;">${esc(rap.codigo)}</span>
                                            ${esc(rap.descripcion)}
                                         </div>`;
                            });
                        }
                        html += `</div></div>`;
                    });
                }
                html += `</div>`;
            });
        }
        html += `</div></div>`;
    });

    document.getElementById('pdf-kpi-fases').textContent = tFases;
    document.getElementById('pdf-kpi-acts').textContent = tActs;
    document.getElementById('pdf-kpi-raps').textContent = tRaps;
    
    if (tFases === 0 || (tActs === 0 && tRaps === 0)) {
        html = `<div style="text-align:center; padding: 40px; color: var(--danger); font-weight: 600;">⚠️ No se pudo extraer una estructura válida del PDF. Por favor intenta otro documento o realiza el registro manual.</div>`;
        document.getElementById('btn-save-pdf').style.display = 'none';
    } else {
        document.getElementById('btn-save-pdf').style.display = 'inline-block';
    }

    // Si no hay codigoPrograma y no hay uno seleccionado en el dropdown manual
    let manualSel = document.getElementById('sel-programa').value;
    if (!extractedProgramCode && !manualSel) {
        // Obligarlo a seleccionar
        html += `<div style="margin-top: 16px;">
                    <label class="form-label">Selecciona el Programa para esta estructura:</label>
                    <select id="pdf-manual-prog" class="form-control">${document.getElementById('sel-programa').innerHTML}</select>
                 </div>`;
    }

    document.getElementById('pdf-preview-content').innerHTML = html;
    document.getElementById('modal-pdf-preview').style.display = 'flex';
}

function closePdfPreview() {
    document.getElementById('modal-pdf-preview').style.display = 'none';
    document.getElementById('pdf-file').value = '';
}

async function savePdfImport() {
    let finalProgramCode = extractedProgramCode;
    let finalProgramId = document.getElementById('sel-programa').value;

    if (!finalProgramCode) {
        let manualDrop = document.getElementById('pdf-manual-prog');
        if (manualDrop) finalProgramId = manualDrop.value;
        if (!finalProgramId) {
            return showCustomToast('Debes seleccionar un programa para asociar el proyecto formativo.', true);
        }
    }

    const btn = document.getElementById('btn-save-pdf');
    btn.disabled = true;
    btn.innerHTML = 'Guardando e importando...';
    
    try {
        const response = await fetch(`${API}?action=importar_pdf`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_programa: finalProgramId,
                codigo_programa: finalProgramCode,
                proyecto: extractedProjectData
            })
        });

        const resData = await response.json();
        
        if (resData.success) {
            alert('¡Proyecto formativo importado y estructurado con éxito!');
            closePdfPreview();
            
            if (document.getElementById('sel-programa').value) {
                loadFases();
            } else if (resData.id_programa) {
                document.getElementById('sel-programa').value = resData.id_programa;
                loadFases();
            }
        } else {
            showCustomToast('Error: ' + resData.error, true);
        }
    } catch (e) {
        showCustomToast('Ocurrió un error en la conexión', true);
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '💾 Importar Proyecto Formativo';
    }
}
// Modales manuales...
function openModalFase() { document.getElementById('modal-fase').style.display = 'flex'; }
function closeModalFase() { document.getElementById('modal-fase').style.display = 'none'; document.getElementById('mf-nombre').value=''; document.getElementById('mf-desc').value=''; }
async function saveFase() {
    const nombre = document.getElementById('mf-nombre').value.trim();
    if (!nombre) return showCustomToast('El nombre es obligatorio', true);
    await fetch(`${API}?action=crear_fase`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_programa: currentProgram, nombre_fase: nombre, descripcion: document.getElementById('mf-desc').value.trim() }) });
    closeModalFase(); loadFases();
}
function openModalActividad(id_fase) { document.getElementById('ma-idfase').value = id_fase; document.getElementById('modal-actividad').style.display = 'flex'; }
function closeModalActividad() { document.getElementById('modal-actividad').style.display = 'none'; document.getElementById('ma-nombre').value=''; document.getElementById('ma-desc').value=''; }
async function saveActividad() {
    const nombre = document.getElementById('ma-nombre').value.trim();
    if (!nombre) return showCustomToast('El nombre es obligatorio', true);
    await fetch(`${API}?action=crear_actividad`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_fase: document.getElementById('ma-idfase').value, nombre_actividad: nombre, descripcion: document.getElementById('ma-desc').value.trim() }) });
    closeModalActividad(); loadFases();
}
function esc(str){return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function showCustomToast(msg, isError = false) {
    const toast = document.createElement('div');
    toast.style.cssText = `position:fixed; bottom:20px; right:20px; background:${isError ? '#dc3545' : 'var(--sena-green)'}; color:white; padding:12px 24px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:99999; transform:translateY(100px); opacity:0; transition:0.3s; font-weight:500; font-size:14px;`;
    toast.innerText = msg;
    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.transform = 'translateY(0)'; toast.style.opacity = '1'; });
    setTimeout(() => { toast.style.transform = 'translateY(100px)'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}

function showCustomConfirm(title, message, confirmText, btnClass, onConfirm) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:99999; display:flex; align-items:center; justify-content:center; opacity:0; transition:0.2s;';
    const modal = document.createElement('div');
    modal.style.cssText = 'background:var(--bg-card); padding:24px; border-radius:12px; max-width:400px; width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.1); transform:scale(0.9); transition:0.2s;';
    modal.innerHTML = `
        <h3 style="margin-top:0; color:var(--text-primary); font-size:18px;">${title}</h3>
        <p style="color:var(--text-secondary); font-size:14px; line-height:1.5; margin-bottom:24px;">${message}</p>
        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <button class="btn btn-sm btn-outline" id="btn-cancel">Cancelar</button>
            <button class="btn btn-sm ${btnClass}" id="btn-confirm">${confirmText}</button>
        </div>
    `;
    overlay.appendChild(modal); document.body.appendChild(overlay);
    requestAnimationFrame(() => { overlay.style.opacity = '1'; modal.style.transform = 'scale(1)'; });
    const close = () => { overlay.style.opacity = '0'; modal.style.transform = 'scale(0.9)'; setTimeout(() => overlay.remove(), 200); };
    modal.querySelector('#btn-cancel').onclick = close;
    modal.querySelector('#btn-confirm').onclick = () => { close(); onConfirm(); };
}

async function borrarEstructura() {
    if (!currentProgram) return;
    
    showCustomConfirm(
        "⚠️ ¡ADVERTENCIA!",
        "¿Estás seguro de que deseas BORRAR toda la estructura del Proyecto Formativo?<br><br>Esto eliminará todas las Fases, Actividades y asignaciones de RAPs actuales para este programa. Esta acción no se puede deshacer.",
        "🗑️ Sí, Borrar",
        "btn-danger",
        async () => {
            try {
                const res = await fetch(`${API}?action=borrar_estructura`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_programa: currentProgram })
                });
                
                const data = await res.json();
                if (data.success) {
                    showCustomToast("✅ Estructura borrada correctamente");
                    document.getElementById('sel-programa').value = currentProgram; // Keep selected
                    loadFases(); // Recargar la vista (quedará vacía)
                    
                    // Limpiar panel derecho
                    document.getElementById('lista-raps').innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);">Selecciona una actividad en el panel izquierdo</div>';
                    document.getElementById('titulo-actividad').textContent = '🎯 Resultados de Aprendizaje';
                    document.getElementById('panel-asignacion').style.opacity = '0.5';
                    document.getElementById('panel-asignacion').style.pointerEvents = 'none';
                } else {
                    showCustomToast("❌ Error al borrar estructura: " + (data.error || 'Error desconocido'), true);
                }
            } catch (err) {
                console.error(err);
                showCustomToast("❌ Error de conexión al borrar la estructura", true);
            }
        }
    );
}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
