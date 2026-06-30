<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Dashboard de Fases del Proyecto';
$pageSubtitle = 'Medición de cumplimiento por fases del proyecto formativo';
$activePage   = 'fases_dashboard';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="card fade-in" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <div class="card-title">🏫 Selección de Ficha</div>
            <div class="card-subtitle">Visualiza el cumplimiento de las fases para una ficha específica</div>
        </div>
    </div>
    <div style="padding: 16px;">
        <select id="sel-ficha" class="form-control" style="max-width: 500px;" onchange="loadDashboard()">
            <option value="">Cargando fichas...</option>
        </select>
    </div>
</div>

<div id="dashboard-container" class="fade-in" style="display: none;">
    <div class="kpi-grid" id="fases-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <!-- Se llena dinámicamente con JS -->
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">📊 Porcentaje de Cumplimiento por Fase</div>
                <div class="card-subtitle">Comparativa del avance de la ficha en cada fase del proyecto</div>
            </div>
        </div>
        <div style="height: 350px; padding: 20px;">
            <canvas id="chart-fases"></canvas>
        </div>
    </div>
</div>

<div id="empty-state" class="fade-in" style="display: none; text-align: center; padding: 60px;">
    <div style="font-size: 40px; margin-bottom: 16px;">🗂️</div>
    <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">Aún no hay fases configuradas o evaluadas</div>
    <div style="font-size: 14px; color: var(--text-muted); margin-top: 8px;">
        El programa asociado a esta ficha no tiene fases con resultados de aprendizaje asignados,<br>
        o no hay aprendices activos en esta ficha.
    </div>
    <a href="fases.php" class="btn btn-primary" style="margin-top: 20px;">Ir a Gestión de Fases</a>
</div>

<script>
const API = '../api/fases_dashboard.php';
let chartFases = null;

async function init() {
    const fichas = await fetch(API + '?action=fichas').then(r => r.json());
    const sel = document.getElementById('sel-ficha');
    sel.innerHTML = '<option value="">Selecciona una ficha...</option>';
    fichas.forEach(f => {
        sel.innerHTML += `<option value="${f.ficha}">${f.ficha} - ${esc(f.programa.substring(0,40))}...</option>`;
    });
}

async function loadDashboard() {
    const ficha = document.getElementById('sel-ficha').value;
    if (!ficha) {
        document.getElementById('dashboard-container').style.display = 'none';
        document.getElementById('empty-state').style.display = 'none';
        return;
    }
    
    const data = await fetch(`${API}?action=estadisticas_fases&ficha=${ficha}`).then(r => r.json());
    
    if (!data || data.length === 0) {
        document.getElementById('dashboard-container').style.display = 'none';
        document.getElementById('empty-state').style.display = 'block';
        return;
    }
    
    document.getElementById('empty-state').style.display = 'none';
    document.getElementById('dashboard-container').style.display = 'block';
    
    renderKPIs(data);
    renderChart(data);
}

function renderKPIs(data) {
    const container = document.getElementById('fases-kpi-grid');
    container.innerHTML = data.map((f, idx) => {
        const isCompleted = f.pct_cumplimiento === 100;
        const colorClass = f.pct_cumplimiento >= 80 ? 'var(--sena-green)' : (f.pct_cumplimiento >= 50 ? 'var(--warning)' : 'var(--danger)');
        const bgClass = f.pct_cumplimiento >= 80 ? 'rgba(0,166,80,0.05)' : (f.pct_cumplimiento >= 50 ? 'rgba(210,153,34,0.05)' : 'rgba(218,54,51,0.05)');
        
        return `
            <div class="card" style="border-top: 4px solid ${colorClass}; background: ${bgClass}; padding: 20px;">
                <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Fase ${idx + 1}</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 16px;">${esc(f.nombre_fase)}</div>
                
                <div style="display: flex; align-items: flex-end; gap: 12px; margin-bottom: 16px;">
                    <div style="font-size: 36px; font-weight: 900; color: ${colorClass}; line-height: 1;">${f.pct_cumplimiento}%</div>
                    <div style="font-size: 12px; color: var(--text-secondary); padding-bottom: 4px;">Cumplimiento</div>
                </div>
                
                <div class="progress-bar-wrap" style="height: 8px; margin-bottom: 16px; background: rgba(0,0,0,0.05);">
                    <div class="progress-bar" style="width: ${f.pct_cumplimiento}%; background: ${colorClass};"></div>
                </div>
                
                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: var(--sena-green);">✅</span>
                        <span style="font-weight: 600;">${f.aprendices_aprobados} Aprobados</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: var(--warning);">⏳</span>
                        <span style="font-weight: 600; color: var(--text-secondary);">${f.aprendices_pendientes} Pendientes</span>
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 10px; color: var(--text-muted); text-align: right;">
                    ${f.total_raps} RAPs a evaluar
                </div>
            </div>
        `;
    }).join('');
}

function renderChart(data) {
    const labels = data.map(f => f.nombre_fase);
    const pcts = data.map(f => f.pct_cumplimiento);
    const bgColors = pcts.map(p => p >= 80 ? 'rgba(0,166,80,0.7)' : (p >= 50 ? 'rgba(210,153,34,0.7)' : 'rgba(218,54,51,0.7)'));
    const borderColors = pcts.map(p => p >= 80 ? 'var(--sena-green)' : (p >= 50 ? 'var(--warning)' : 'var(--danger)'));

    if (chartFases) chartFases.destroy();
    
    const ctx = document.getElementById('chart-fases').getContext('2d');
    chartFases = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '% Cumplimiento',
                data: pcts,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + '%'; },
                        color: '#8b949e'
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                x: {
                    ticks: { color: '#8b949e', font: { size: 12, weight: 600 } },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '% de aprendices aprobados';
                        }
                    }
                }
            }
        }
    });
}

function esc(str){return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

init();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
