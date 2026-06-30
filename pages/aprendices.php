<?php
define('ROOT_PATH', dirname(__DIR__));
$pageTitle    = 'Buscador de Aprendices';
$pageSubtitle = 'Busca aprendices por nombre o documento para ver su seguimiento';
$activePage   = 'aprendices';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="fade-in" style="max-width: 800px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 24px; padding: 32px; text-align: center; background: linear-gradient(135deg, var(--bg-card), rgba(0,166,80,0.02));">
        <div style="font-size: 40px; margin-bottom: 16px;">👤</div>
        <h2 style="margin-bottom: 8px; color: var(--sena-blue);">Seguimiento de Aprendiz</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">Ingresa el nombre o documento del aprendiz para consultar su historial de juicios evaluativos.</p>
        
        <div style="position: relative;">
            <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted);">🔍</span>
            <input type="text" id="global-search-aprendiz" class="form-control" placeholder="Ej: Juan Perez o 10203040..." 
                   style="height: 56px; padding-left: 50px; font-size: 16px; border-radius: 12px; box-shadow: var(--shadow-md);"
                   oninput="searchAprendices(this.value)">
        </div>
    </div>

    <div id="search-results" style="display: grid; gap: 12px;">
        <!-- Los resultados aparecerán aquí -->
    </div>
    
    <div id="search-placeholder" style="text-align: center; padding: 48px; color: var(--text-muted);">
        <p>Comienza a escribir para buscar...</p>
    </div>
</div>

<script>
let searchTimeout = null;

async function searchAprendices(q) {
    const resultsDiv = document.getElementById('search-results');
    const placeholder = document.getElementById('search-placeholder');
    
    if (q.length < 2) {
        resultsDiv.innerHTML = '';
        placeholder.style.display = 'block';
        return;
    }

    placeholder.style.display = 'none';
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch('../api/juicios.php?action=search_aprendices&q=' + encodeURIComponent(q)).then(r => r.json());
            
            if (res.length === 0) {
                resultsDiv.innerHTML = `<div class="card" style="padding: 24px; text-align: center; color: var(--text-secondary);">No se encontraron aprendices con "${q}"</div>`;
                return;
            }

            resultsDiv.innerHTML = res.map(a => `
                <a href="aprendiz_seguimiento.php?ficha=${encodeURIComponent(a.ficha)}&documento=${encodeURIComponent(a.documento)}" 
                   class="card" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid var(--sena-green);">
                    <div>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 16px;">${esc(a.nombre)} ${esc(a.apellidos)}</div>
                        <div style="font-size: 13px; color: var(--text-secondary);">Doc: ${esc(a.documento)} • Ficha: ${esc(a.ficha)}</div>
                        <div style="font-size: 12px; color: var(--sena-blue); margin-top: 4px;">${esc(a.programa)}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="badge ${a.estado === 'Activo' ? 'badge-success' : 'badge-muted'}">${esc(a.estado)}</span>
                        <span style="font-size: 20px; color: var(--text-muted);">❯</span>
                    </div>
                </a>
            `).join('');
            
        } catch (e) {
            console.error(e);
        }
    }, 300);
}

function esc(str) { return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
