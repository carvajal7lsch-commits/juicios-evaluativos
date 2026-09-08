<?php
// Ruta base del proyecto (ajustar según directorio de XAMPP)
defined('BASE_URL')   || define('BASE_URL',   '');
defined('ROOT_PATH')  || define('ROOT_PATH',  dirname(__DIR__));

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/assets/icons.php';

/* ----------------------------------------------------------------
 * Variables que cada página puede definir ANTES de incluir este archivo:
 *   $pageTitle    string  Título mostrado en el topbar
 *   $pageSubtitle string  Descripción corta bajo el título
 *   $activePage   string  Clave del ítem activo del sidebar
 *   $pageActions  string  HTML de los botones primarios → van al topbar
 *   $crumbs       array   [['title'=>..,'url'=>..], ...] migas de pan
 * ---------------------------------------------------------------- */
$activePage   = $activePage   ?? '';
$pageActions  = $pageActions  ?? '';
$pageTitle    = $pageTitle    ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';

// ── Definición del menú lateral ──
$navGroups = [
    'Principal' => [
        ['key' => 'dashboard',  'label' => 'Dashboard',              'icon' => 'dashboard',   'url' => '/pages/dashboard.php'],
    ],
    'Gestión' => [
        ['key' => 'programas',  'label' => 'Programas de Formación', 'icon' => 'book',        'url' => '/pages/programas.php'],
        ['key' => 'fichas',     'label' => 'Fichas de Formación',    'icon' => 'school',      'url' => '/pages/fichas.php'],
        ['key' => 'aprendices', 'label' => 'Seguimiento Aprendiz',   'icon' => 'user-search', 'url' => '/pages/aprendices.php'],
    ],
    'Proyecto Formativo' => [
        ['key' => 'fases',           'label' => 'Fases y Actividades',  'icon' => 'layers', 'url' => '/pages/fases.php'],
        ['key' => 'fases_dashboard', 'label' => 'Avance por Fases',   'icon' => 'target', 'url' => '/pages/fases_dashboard.php'],
    ],
];

// ── Migas de pan: la página puede definir $crumbs; si no, se derivan ──
if (!isset($crumbs)) {
    $home      = ['title' => 'Inicio', 'url' => BASE_URL . '/pages/dashboard.php'];
    $selfFile  = basename($_SERVER['PHP_SELF']);
    $sectionMap = [
        'dashboard'       => null,
        'programas'       => ['Programas de Formación', '/pages/programas.php'],
        'fichas'          => ['Fichas de Formación',    '/pages/fichas.php'],
        'aprendices'      => ['Seguimiento Aprendiz',   '/pages/aprendices.php'],
        'fases'           => ['Fases y Actividades',    '/pages/fases.php'],
        'fases_dashboard' => ['Avance por Fases',     '/pages/fases_dashboard.php'],
    ];

    if ($selfFile === 'ficha_detalle.php') {
        $crumbs = [$home, ['title' => 'Fichas de Formación', 'url' => BASE_URL . '/pages/fichas.php'], ['title' => 'Detalle de Ficha', 'url' => '']];
    } elseif ($selfFile === 'aprendiz_seguimiento.php') {
        $crumbs = [$home, ['title' => 'Seguimiento Aprendiz', 'url' => BASE_URL . '/pages/aprendices.php'], ['title' => 'Detalle Individual', 'url' => '']];
    } elseif ($activePage === 'dashboard') {
        $crumbs = [['title' => 'Inicio', 'url' => '']];
    } else {
        $section = $sectionMap[$activePage] ?? null;
        $crumbs  = $section ? [$home, ['title' => $section[0], 'url' => '']] : [$home, ['title' => $pageTitle, 'url' => '']];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — SENA</title>
  <meta name="description" content="Sistema de gestión de juicios evaluativos para aprendices SENA" />
  <meta name="color-scheme" content="light dark" />
  <link rel="icon" href="<?= BASE_URL ?>/assets/favicon.svg" type="image/svg+xml" />

  <!-- Tema: se aplica antes del primer pintado para evitar el parpadeo -->
  <script>
    (function () {
      try {
        var t = localStorage.getItem('ui-theme');
        if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t;
        if (localStorage.getItem('ui-sidebar') === 'collapsed') document.documentElement.dataset.sidebar = 'collapsed';
      } catch (e) {}
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css" />

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

  <script>
  /* ================================================================
     UI CORE — helpers compartidos por todas las páginas.
     Van en el <head> para estar disponibles antes de que se ejecute
     el <script> de cada página. La inicialización (tema, sidebar,
     Chart.js) vive en footer.php, cuando ya existe el DOM.
     ================================================================ */

  /** Icono del sprite SVG. Equivalente JS del helper icon() de PHP. */
  function ic(name, cls) {
    return '<svg class="ic ' + (cls || '') + '" aria-hidden="true" focusable="false">'
         + '<use href="#i-' + name + '"></use></svg>';
  }

  /** Escapa HTML. Obligatorio para todo lo que venga de la API. */
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /** Lee un token del design system: siempre el valor del tema activo. */
  function token(name) {
    return getComputedStyle(document.documentElement).getPropertyValue('--' + name).trim();
  }

  /* ── Preferencias recordadas ──
     Guardar el último filtro elegido evita que cada visita empiece en un
     panel vacío. localStorage revienta en modo privado y con las cookies
     de sitio bloqueadas, así que ninguna de las dos puede lanzar: la página
     debe seguir funcionando sin memoria. */

  /** Última elección guardada para `clave`, o '' si no hay ninguna. */
  function recordado(clave) {
    try { return localStorage.getItem('pref-' + clave) || ''; } catch (e) { return ''; }
  }

  /** Guarda la elección actual para `clave`. */
  function recordar(clave, valor) {
    try { localStorage.setItem('pref-' + clave, valor); } catch (e) {}
  }

  /* ── Estados de aprendiz (espejo de includes/estados.php) ──
     Sofia Plus exporta 'EN FORMACION' en mayúsculas; la app escribía
     'Activo'. Comparar contra un literal dejaba fuera a la mayoría, así que
     "activo" se define por exclusión de los estados terminales. */
  const ESTADOS_INACTIVOS = [
    'RETIRO VOLUNTARIO', 'RETIRADO', 'DESERCION', 'DESERCIÓN',
    'CANCELADO', 'CANCELAMIENTO', 'TRASLADADO', 'APLAZADO'
  ];

  /** ¿El aprendiz sigue en formación? */
  function esActivo(estado) {
    return !ESTADOS_INACTIVOS.includes(String(estado ?? '').trim().toUpperCase());
  }

  /** Clase de badge para un estado de aprendiz, sin depender de mayúsculas. */
  function badgeEstado(estado) {
    const e = String(estado ?? '').trim().toUpperCase();
    if (!ESTADOS_INACTIVOS.includes(e)) return 'badge-success';
    if (e.includes('DESERC')) return 'badge-danger';
    if (e.includes('RETIR') || e.includes('APLAZADO')) return 'badge-warning';
    return 'badge-muted';
  }

  /** Clase de badge para el estado de una ficha. */
  function badgeFicha(estado) {
    const e = String(estado ?? '').trim().toUpperCase();
    if (e.includes('EJECUCION') || e.includes('EJECUCIÓN')) return 'badge-success';
    if (e.includes('TERMINAD'))   return 'badge-info';
    if (e.includes('SUSPEND'))    return 'badge-warning';
    return 'badge-muted';
  }

  /** Notificación efímera en la esquina inferior derecha. */
  function showToast(message, type = 'info', ms = 4200) {
    const stack = document.getElementById('toast-stack');
    if (!stack) return;
    const icons = { success: 'check-circle', danger: 'x-circle', warning: 'alert-triangle', info: 'info' };
    const el = document.createElement('div');
    el.className = 'toast is-' + type;
    el.innerHTML = ic(icons[type] || 'info') + '<div class="flex-1">' + message + '</div>';
    stack.appendChild(el);
    setTimeout(() => {
      el.classList.add('leaving');
      el.addEventListener('animationend', () => el.remove(), { once: true });
    }, ms);
  }

  /** Abre un .modal-overlay por id y lleva el foco a su primer control. */
  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
    m.querySelector('input:not([type=hidden]), select, textarea, button')?.focus();
  }

  function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('open');
    if (!document.querySelector('.modal-overlay.open')) document.body.style.overflow = '';
  }
  </script>
</head>
<body>
<?php render_icon_sprite(); ?>
<div class="app-wrapper" id="app-wrapper">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><?= icon('graduation-cap') ?></div>
    <div class="logo-text">
      <strong>SENA Evaluación</strong>
      <span>Juicios Evaluativos</span>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Navegación principal">
    <?php foreach ($navGroups as $groupTitle => $items): ?>
      <p class="nav-group-title"><?= htmlspecialchars($groupTitle) ?></p>
      <?php foreach ($items as $it): ?>
        <a href="<?= BASE_URL . $it['url'] ?>"
           class="nav-item <?= $activePage === $it['key'] ? 'active' : '' ?>"
           data-label="<?= htmlspecialchars($it['label']) ?>"
           <?= $activePage === $it['key'] ? 'aria-current="page"' : '' ?>>
          <?= icon($it['icon']) ?><span><?= htmlspecialchars($it['label']) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">SE</div>
      <div class="user-info">
        <strong>SENA</strong>
        <span>Juicios Evaluativos</span>
      </div>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<!-- ══ MAIN ══ -->
<main class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <button class="icon-btn btn-menu" id="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
        <?= icon('panel-left') ?>
      </button>
      <button class="icon-btn btn-sidebar-collapse" id="sidebar-collapse" type="button"
              aria-label="Contraer o expandir el menú lateral" title="Contraer menú (Ctrl+B)">
        <?= icon('panel-left') ?>
      </button>
      <div class="topbar-heading">
        <div class="page-title"><?= htmlspecialchars($pageTitle) ?></div>
        <?php if ($pageSubtitle !== ''): ?>
          <div class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="topbar-actions" id="topbar-actions">
      <?= $pageActions ?>
      <div class="theme-switch" role="group" aria-label="Tema de la interfaz">
        <button type="button" data-theme-set="light"  aria-pressed="false" title="Tema claro"><?= icon('sun') ?></button>
        <button type="button" data-theme-set="dark"   aria-pressed="false" title="Tema oscuro"><?= icon('moon') ?></button>
        <button type="button" data-theme-set="system" aria-pressed="false" title="Seguir al sistema"><?= icon('monitor') ?></button>
      </div>
    </div>
  </header>

  <div class="page-body">
    <?php if (!empty($crumbs) && count($crumbs) > 1): ?>
    <nav class="breadcrumb" aria-label="Ruta de navegación">
      <?php foreach ($crumbs as $index => $c): ?>
        <?php if ($index === count($crumbs) - 1): ?>
          <span class="breadcrumb-current" aria-current="page"><?= htmlspecialchars($c['title']) ?></span>
        <?php else: ?>
          <?php if (!empty($c['url'])): ?>
            <a href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars($c['title']) ?></a>
          <?php else: ?>
            <span><?= htmlspecialchars($c['title']) ?></span>
          <?php endif; ?>
          <?= icon('chevron-right') ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
