  </div><!-- /.page-body -->
</main><!-- /.main-content -->

</div><!-- /.app-wrapper -->

<div class="toast-stack" id="toast-stack" role="status" aria-live="polite"></div>

<script>
/* ================================================================
   TEMA CLARO / OSCURO / SISTEMA
   Los helpers ic() / esc() / token() / showToast() / openModal()
   se definen en header.php, antes del script de cada página.
   ================================================================ */
const ThemeManager = (() => {
  const KEY = 'ui-theme';
  const mql = window.matchMedia('(prefers-color-scheme: dark)');

  function stored() {
    try { return localStorage.getItem(KEY) || 'system'; } catch (e) { return 'system'; }
  }

  /** Tema efectivo que se está pintando ahora: 'light' | 'dark' */
  function resolved() {
    const t = stored();
    return t === 'system' ? (mql.matches ? 'dark' : 'light') : t;
  }

  function apply(theme) {
    if (theme === 'system') {
      delete document.documentElement.dataset.theme;
    } else {
      document.documentElement.dataset.theme = theme;
    }
    try { localStorage.setItem(KEY, theme); } catch (e) {}
    syncButtons(theme);
    document.dispatchEvent(new CustomEvent('themechange', { detail: { theme, resolved: resolved() } }));
  }

  function syncButtons(theme) {
    document.querySelectorAll('[data-theme-set]').forEach(b => {
      b.setAttribute('aria-pressed', String(b.dataset.themeSet === theme));
    });
  }

  function init() {
    syncButtons(stored());
    document.querySelectorAll('[data-theme-set]').forEach(b => {
      b.addEventListener('click', () => apply(b.dataset.themeSet));
    });
    // Si está en "sistema", seguir los cambios del SO en vivo
    mql.addEventListener('change', () => {
      if (stored() === 'system') {
        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: 'system', resolved: resolved() } }));
      }
    });
  }

  return { init, apply, resolved, stored };
})();

/* ================================================================
   SIDEBAR: colapsar en escritorio, off-canvas en móvil
   ================================================================ */
const SidebarManager = (() => {
  const KEY = 'ui-sidebar';
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const wrapper  = document.getElementById('app-wrapper');

  function toggleCollapse() {
    const collapsed = document.documentElement.dataset.sidebar === 'collapsed';
    if (collapsed) delete document.documentElement.dataset.sidebar;
    else document.documentElement.dataset.sidebar = 'collapsed';
    try { localStorage.setItem(KEY, collapsed ? 'expanded' : 'collapsed'); } catch (e) {}
    // Las gráficas necesitan recalcular su ancho tras la transición
    setTimeout(() => window.dispatchEvent(new Event('resize')), 220);
  }

  function openMobile(open) {
    sidebar.classList.toggle('open', open);
    wrapper.classList.toggle('nav-open', open);
    document.getElementById('menu-toggle')?.setAttribute('aria-expanded', String(open));
  }

  function init() {
    document.getElementById('sidebar-collapse')?.addEventListener('click', toggleCollapse);
    document.getElementById('menu-toggle')?.addEventListener('click', () => openMobile(!sidebar.classList.contains('open')));
    backdrop?.addEventListener('click', () => openMobile(false));

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') openMobile(false);
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b' && !e.shiftKey) {
        e.preventDefault(); toggleCollapse();
      }
    });
  }

  return { init, toggleCollapse };
})();

/* ── Cierre de modales: clic en el fondo o tecla Escape ── */
document.addEventListener('click', (e) => {
  if (e.target.classList?.contains('modal-overlay')) closeModal(e.target.id);
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});

/* ================================================================
   CHART.JS — valores por defecto ligados al tema activo
   Las páginas no deben fijar colores de ejes/leyenda: los heredan
   de aquí, y así cambian solos al alternar claro/oscuro.
   ================================================================ */
function applyChartDefaults() {
  if (typeof Chart === 'undefined') return;
  const tick = token('chart-tick');
  const grid = token('chart-grid');
  const card = token('bg-card');
  const text = token('text-primary');
  const border = token('border');

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.font.size = 11;
  Chart.defaults.color = tick;
  Chart.defaults.borderColor = grid;
  Chart.defaults.plugins.legend.labels.color = tick;
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.boxHeight = 10;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.padding = 14;
  Chart.defaults.plugins.tooltip.backgroundColor = card;
  Chart.defaults.plugins.tooltip.titleColor = text;
  Chart.defaults.plugins.tooltip.bodyColor = tick;
  Chart.defaults.plugins.tooltip.borderColor = border;
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  Chart.defaults.plugins.tooltip.displayColors = true;
  Chart.defaults.plugins.tooltip.boxPadding = 4;
  Chart.defaults.maintainAspectRatio = false;
}

/** Paleta categórica para series de datos, coherente con el tema. */
function chartPalette() {
  return [
    token('brand-text'), token('green-text') || token('green'),
    token('warning-solid'), token('danger-solid'), token('info-solid'),
    '#8b5cf6', '#ec4899', '#14b8a6'
  ];
}

/* Al cambiar el tema: refrescar defaults y repintar cada gráfica viva */
document.addEventListener('themechange', () => {
  applyChartDefaults();
  if (typeof Chart !== 'undefined' && Chart.instances) {
    Object.values(Chart.instances).forEach(c => { try { c.update('none'); } catch (e) {} });
  }
});

/* ================================================================
   ARRANQUE
   ================================================================ */
ThemeManager.init();
SidebarManager.init();
applyChartDefaults();

document.querySelectorAll('.fade-in').forEach((el, i) => {
  if (!el.style.animationDelay) el.style.animationDelay = Math.min(i * 0.035, 0.2) + 's';
});
</script>
</body>
</html>
