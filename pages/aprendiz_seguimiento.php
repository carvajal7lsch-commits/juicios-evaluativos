<?php
define('ROOT_PATH', dirname(__DIR__));
$ficha = $_GET['ficha'] ?? '';
$documento = $_GET['documento'] ?? '';

if (!$ficha || !$documento) { header('Location: fichas.php'); exit; }

$pageTitle    = 'Seguimiento de Aprendiz';
$pageSubtitle = 'Detalle individual de juicios y avance de competencias';
$activePage   = 'fichas';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="fade-in" style="margin-bottom: 24px;">
    <nav style="margin-bottom:16px;">
        <a href="ficha_detalle.php?ficha=<?php echo urlencode($ficha); ?>" style="color:var(--sena-blue); text-decoration:none; font-weight:600; font-size:14px;">
            ← Volver a la Ficha <?php echo htmlspecialchars($ficha); ?>
        </a>
    </nav>

    <!-- Info Card -->
    <div class="card" style="margin-bottom:24px; border-left: 4px solid var(--sena-blue);">
        <div class="card-header">
            <div>
                <div class="card-title" id="ap-nombre-completo" style="font-size:20px;">Cargando aprendiz...</div>
                <div class="card-subtitle" id="ap-documento">Doc: <?php echo htmlspecialchars($documento); ?></div>
            </div>
            <div id="ap-estado-badge"></div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="kpi-card" style="--kpi-color:var(--sena-green); cursor:pointer;" onclick="setFilter('Aprobado')">
            <div class="kpi-icon">✅</div>
            <div class="kpi-value" id="av-aprobados">0</div>
            <div class="kpi-label">Resultados Aprobados</div>
        </div>
        <div class="kpi-card" style="--kpi-color:var(--danger); cursor:pointer;" onclick="setFilter('No Aprobado')">
            <div class="kpi-icon">❌</div>
            <div class="kpi-value" id="av-noaprobados">0</div>
            <div class="kpi-label">No Aprobados</div>
        </div>
        <div class="kpi-card" style="--kpi-color:var(--warning); cursor:pointer;" onclick="setFilter('Por evaluar')">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-value" id="av-pendientes-k">0</div>
            <div class="kpi-label">Por evaluar</div>
        </div>
        <div class="kpi-card" style="--kpi-color:var(--info); cursor:pointer;" onclick="setFilter('all')">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-value" id="av-pct-global">0%</div>
            <div class="kpi-label">Avance Global (Ver todos)</div>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns: 1fr 350px; gap:24px;">
        <!-- Left Column: Table -->
        <div class="card">
            <div class="card-header" style="flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="card-title">📋 Detalle de Resultados de Aprendizaje</div>
                    <div class="card-subtitle">Listado completo de juicios registrados</div>
                </div>
                <div style="display:flex; gap:8px;">
                    <div style="position:relative; width:200px;">
                        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted);">🔍</span>
                        <input type="text" id="av-search-input" class="form-control" placeholder="Buscar RAP o Código..." style="padding-left:32px;" oninput="renderTable()">
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Competencia / Resultado</th>
                            <th style="width:120px;">Estado</th>
                            <th style="width:180px;">Registro</th>
                        </tr>
                    </thead>
                    <tbody id="av-detalle-body">
                        <tr><td colspan="3" style="text-align:center; padding:40px;"><span class="spinner"></span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Stats & Charts -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            <div class="card">
                <div class="card-header"><div class="card-title" style="font-size:14px;">📊 Desempeño por Competencia</div></div>
                <div style="height:300px; padding:16px;"><canvas id="chart-avance-comp"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title" style="font-size:14px;">📊 Resumen Visual</div></div>
                <div id="av-comp-list" style="padding:16px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const FICHA = <?php echo json_encode($ficha); ?>;
const DOC = <?php echo json_encode($documento); ?>;
const API = '../api/competencias.php';

let rawData = [];
let filteredData = [];
let currentFilter = 'all';
let chartAvanceComp = null;

async function init() {
    try {
        const [compData, detalle, aprendices] = await Promise.all([
            fetch(API + '?action=avance_aprendiz&documento=' + encodeURIComponent(DOC)).then(r => r.json()),
            fetch(API + '?action=detalle_aprendiz&documento=' + encodeURIComponent(DOC)).then(r => r.json()),
            fetch('../api/ficha_detalle.php?action=aprendices&ficha=' + encodeURIComponent(FICHA)).then(r => r.json())
        ]);

        const aprendiz = aprendices.find(a => a.documento === DOC);
        if (aprendiz) {
            document.getElementById('ap-nombre-completo').textContent = aprendiz.nombre + ' ' + aprendiz.apellidos;
            const eb = {'Activo':'badge-success','Retiro Voluntario':'badge-warning','Deserción':'badge-danger'};
            document.getElementById('ap-estado-badge').innerHTML = `<span class="badge ${eb[aprendiz.estado]||'badge-muted'}">${aprendiz.estado}</span>`;
        }

        rawData = detalle;
        updateKPIs(detalle);
        renderTable();
        renderCharts(compData);
        renderCompList(compData);
    } catch (e) {
        console.error(e);
    }
}

function updateKPIs(data) {
    const aprobados = data.filter(d => (d.juicio || '').trim().toLowerCase() === 'aprobado').length;
    const noAprob = data.filter(d => (d.juicio || '').trim().toLowerCase() === 'no aprobado').length;
    
    // "Por evaluar" son los que están vacíos, nulos o explícitamente dicen "por evaluar"
    const pendientes = data.filter(d => {
        const j = (d.juicio || '').trim().toLowerCase();
        return j === '' || j === 'por evaluar' || j === 'null';
    }).length;

    const totalR = data.length;
    const pctGlobal = totalR > 0 ? Math.round(aprobados / totalR * 100) : 0;

    document.getElementById('av-aprobados').textContent = aprobados;
    document.getElementById('av-noaprobados').textContent = noAprob;
    document.getElementById('av-pendientes-k').textContent = pendientes;
    document.getElementById('av-pct-global').textContent = pctGlobal + '%';
}

function setFilter(f) {
    currentFilter = f;
    renderTable();
    
    // Highlight active card
    document.querySelectorAll('.kpi-card').forEach(c => c.style.boxShadow = 'none');
    const labels = {'Aprobado':0, 'No Aprobado':1, 'Por evaluar':2, 'all':3};
    const cards = document.querySelectorAll('.kpi-card');
    if(cards[labels[f]]) cards[labels[f]].style.boxShadow = '0 0 0 2px var(--sena-blue)';
}

function renderTable() {
    const q = document.getElementById('av-search-input').value.toLowerCase().trim();
    const tb = document.getElementById('av-detalle-body');
    
    filteredData = rawData.filter(d => {
        const matchSearch = d.codigo.toLowerCase().includes(q) || d.descripcion.toLowerCase().includes(q) || d.competencia.toLowerCase().includes(q);
        let matchType = true;
        if (currentFilter !== 'all') {
            const juic = (d.juicio || 'Pendiente').trim().toLowerCase();
            matchType = juic === currentFilter.toLowerCase();
        }
        return matchSearch && matchType;
    });

    if (!filteredData.length) {
        tb.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:40px; color:var(--text-muted);">No se encontraron resultados</td></tr>';
        return;
    }

    let lastComp = '';
    tb.innerHTML = filteredData.map(d => {
        let compHeader = '';
        if (d.competencia !== lastComp) {
            compHeader = `<tr><td colspan="3" style="background:rgba(26,77,181,0.03); font-weight:700; color:var(--sena-blue); font-size:12px; padding:12px 16px;">📚 ${esc(d.competencia)}</td></tr>`;
            lastComp = d.competencia;
        }

        const juic = (d.juicio || 'Por evaluar').trim();
        let statusClass = 'badge-muted';
        const jLower = juic.toLowerCase();
        
        if(jLower === 'aprobado') statusClass = 'badge-success';
        else if(jLower === 'no aprobado') statusClass = 'badge-danger';
        else if(jLower === 'por evaluar' || jLower === '' || jLower === 'null') statusClass = 'badge-warning';

        return compHeader + `
            <tr>
                <td style="padding-left:32px;">
                    <div style="font-size:11px; color:var(--text-muted); font-family:monospace;">${esc(d.codigo)}</div>
                    <div style="font-size:13px; font-weight:500; line-height:1.4;">${esc(d.descripcion)}</div>
                </td>
                <td style="vertical-align:middle;">
                    <span class="badge ${statusClass}">${esc(d.juicio || 'Por evaluar')}</span>
                </td>
                <td>
                    ${d.fecha_registro ? `
                        <div style="font-size:12px; font-weight:600;">${d.fecha_registro.substring(0,16)}</div>
                        <div style="font-size:10px; color:var(--text-secondary); text-transform:uppercase;">Por: ${esc(d.funcionario || 'SENA')}</div>
                    ` : '<span style="color:var(--text-muted); font-style:italic; font-size:12px;">Sin registro</span>'}
                </td>
            </tr>
        `;
    }).join('');
}

function renderCharts(compData) {
    const labels = compData.map(c => c.competencia.substring(0, 20) + '...');
    const pcts = compData.map(c => c.total_resultados > 0 ? Math.round(c.aprobados / c.total_resultados * 100) : 0);

    if(chartAvanceComp) chartAvanceComp.destroy();
    chartAvanceComp = new Chart(document.getElementById('chart-avance-comp'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: '% Aprobado',
                data: pcts,
                backgroundColor: 'rgba(0,166,80,0.6)',
                borderColor: 'var(--sena-green)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: { 
                x: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                y: { ticks: { font: { size: 10 } } }
            },
            plugins: { legend: { display: false } }
        }
    });
}

function renderCompList(compData) {
    document.getElementById('av-comp-list').innerHTML = compData.map(c => {
        const pct = c.total_resultados > 0 ? Math.round(c.aprobados / c.total_resultados * 100) : 0;
        const color = pct >= 80 ? 'green' : pct >= 50 ? 'warn' : 'danger';
        return `
            <div style="margin-bottom:12px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="font-size:11px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;">${esc(c.competencia)}</span>
                    <span style="font-size:10px; color:var(--text-secondary);">${c.aprobados}/${c.total_resultados}</span>
                </div>
                <div class="progress-bar-wrap" style="height:6px;"><div class="progress-bar ${color}" style="width:${pct}%"></div></div>
            </div>
        `;
    }).join('');
}

function esc(str) { return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
