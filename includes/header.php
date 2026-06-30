<?php
// Ruta base del proyecto (ajustar según directorio de XAMPP)
defined('BASE_URL')   || define('BASE_URL',   '');
defined('ROOT_PATH')  || define('ROOT_PATH',  dirname(__DIR__));

require_once ROOT_PATH . '/config/database.php';

// Página activa para el sidebar
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Juicios Evaluativos') ?> — SENA</title>
  <meta name="description" content="Sistema de gestión de juicios evaluativos para aprendices SENA" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>
<div class="app-wrapper">

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">S</div>
    <div class="logo-text">
      <strong>SENA Evaluación</strong>
      <span>Juicios Evaluativos</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <p class="nav-group-title">Principal</p>
    <a href="<?= BASE_URL ?>/pages/dashboard.php"
       class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <p class="nav-group-title">Gestión</p>
    <a href="<?= BASE_URL ?>/pages/programas.php"
       class="nav-item <?= $activePage === 'programas' ? 'active' : '' ?>">
      <span class="nav-icon">📚</span> Programas de Formación
    </a>
    <a href="<?= BASE_URL ?>/pages/fichas.php"
       class="nav-item <?= $activePage === 'fichas' ? 'active' : '' ?>">
      <span class="nav-icon">🏫</span> Fichas de Formación
    </a>
    <a href="<?= BASE_URL ?>/pages/aprendices.php"
       class="nav-item <?= $activePage === 'aprendices' ? 'active' : '' ?>">
      <span class="nav-icon">👤</span> Seguimiento Aprendiz
    </a>

    <p class="nav-group-title">Proyecto Formativo</p>
    <a href="<?= BASE_URL ?>/pages/fases.php"
       class="nav-item <?= $activePage === 'fases' ? 'active' : '' ?>">
      <span class="nav-icon">🗂️</span> Fases y Actividades
    </a>
    <a href="<?= BASE_URL ?>/pages/fases_dashboard.php"
       class="nav-item <?= $activePage === 'fases_dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">🎯</span> Dashboard de Fases
    </a>

    <?php /* 
    <?php if ($user['rol'] === 'admin'): ?>
    <p class="nav-group-title">Configuración</p>
    <a href="<?= BASE_URL ?>/pages/configuracion.php"
       class="nav-item <?= $activePage === 'configuracion' ? 'active' : '' ?>">
      <span class="nav-icon">⚙️</span> Configuración
    </a>
    <?php endif; ?>
    */ ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user" style="justify-content: center;">
      <div class="user-info" style="text-align: center;">
        <strong>SENA</strong>
        <span>Juicios Evaluativos</span>
      </div>
    </div>
  </div>
</aside>

<!-- ── MAIN ── -->
<main class="main-content">
  <header class="topbar">
    <div>
      <?php
      // Lógica de Breadcrumbs (Migas de Pan)
      $breadcrumbMap = [
          'dashboard'  => 'Inicio',
          'fichas'     => 'Fichas de Formación',
          'aprendices' => 'Seguimiento Aprendiz',
          'programas'  => 'Programas de Formación'
      ];
      
      $bParent = $breadcrumbMap[$activePage ?? 'dashboard'] ?? 'Inicio';
      $bParentUrl = BASE_URL . '/pages/dashboard.php';
      $crumbs = [];
      
      if (basename($_SERVER['PHP_SELF']) === 'ficha_detalle.php') {
          $crumbs[] = ['title' => 'Inicio', 'url' => $bParentUrl];
          $crumbs[] = ['title' => 'Fichas de Formación', 'url' => BASE_URL . '/pages/fichas.php'];
          $crumbs[] = ['title' => 'Detalle de Ficha', 'url' => ''];
      } elseif (basename($_SERVER['PHP_SELF']) === 'aprendiz_seguimiento.php') {
          $crumbs[] = ['title' => 'Inicio', 'url' => $bParentUrl];
          $crumbs[] = ['title' => 'Seguimiento Aprendiz', 'url' => BASE_URL . '/pages/aprendices.php'];
          $crumbs[] = ['title' => 'Detalle Individual', 'url' => ''];
      } else {
          $crumbs[] = ['title' => $bParent, 'url' => $bParentUrl];
          $crumbs[] = ['title' => $pageTitle ?? 'Dashboard', 'url' => ''];
      }
      ?>

      <div class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
      <?php if (!empty($pageSubtitle)): ?>
      <div class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></div>
      <?php endif; ?>
    </div>
    <div class="topbar-actions" id="topbar-actions">
      <!-- Los botones de acción de cada página se inyectan aquí -->
    </div>
  </header>

  <div class="page-body">
    <?php if (isset($crumbs)): ?>
    <style>
      .breadcrumb-pill {
          display: inline-flex;
          align-items: center;
          background: var(--bg-card);
          border-radius: var(--radius-md);
          padding: 6px 14px 6px 10px;
          box-shadow: var(--shadow-sm);
          border: 1px solid var(--border);
          border-left: 4px solid var(--sena-green);
          font-size: 13px;
          font-weight: 500;
          margin-bottom: 20px;
      }
      .breadcrumb-pill a { color: var(--sena-green); text-decoration: none; transition: 0.2s; font-weight: 600;}
      .breadcrumb-pill a:hover { opacity: 0.8; text-shadow: 0 0 8px rgba(0,166,80,0.4); }
      .breadcrumb-chevron { color: var(--text-muted); margin: 0 10px; font-size: 11px; }
      .breadcrumb-current { color: var(--text-secondary); font-weight: 400; }
    </style>
    <div class="breadcrumb-pill fade-in">
      <?php foreach ($crumbs as $index => $c): ?>
          <?php if ($index === count($crumbs) - 1): ?>
              <span class="breadcrumb-current"><?= htmlspecialchars($c['title']) ?></span>
          <?php else: ?>
              <?= $c['url'] ? '<a href="'.$c['url'].'">' : '<a href="#" style="cursor:default;">' ?>
                  <?= htmlspecialchars($c['title']) ?>
              </a>
              <span class="breadcrumb-chevron">❯</span>
          <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
