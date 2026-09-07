<?php
// ================================================================
// INDEX — Redirige directamente al dashboard (auth desactivada)
// ================================================================
header('Location: pages/dashboard.php');
exit;

// ----------------------------------------------------------------
// Pantalla de login (inactiva mientras la autenticación esté apagada).
// Se mantiene alineada con el design system: mismos tokens, mismo
// sistema de temas claro/oscuro y misma iconografía SVG.
// ----------------------------------------------------------------
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/assets/icons.php';
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar sesión — Juicios Evaluativos SENA</title>
  <meta name="description" content="Sistema de Juicios Evaluativos SENA — Acceso al sistema" />
  <meta name="color-scheme" content="light dark" />
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml" />

  <script>
    (function () {
      try {
        var t = localStorage.getItem('ui-theme');
        if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t;
      } catch (e) {}
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="assets/css/main.css" />

  <style>
    body { display: flex; align-items: center; justify-content: center; padding: var(--space-5); position: relative; overflow-x: hidden; }
    .bg-decor {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 70% 55% at 18% 18%, var(--brand-soft) 0%, transparent 62%),
        radial-gradient(ellipse 55% 45% at 82% 82%, var(--green-soft) 0%, transparent 58%);
    }
    .login-container { position: relative; z-index: 1; width: 100%; max-width: 400px; }
    .login-logo { text-align: center; margin-bottom: var(--space-6); }
    .logo-badge {
      display: inline-flex; align-items: center; justify-content: center;
      width: 58px; height: 58px; border-radius: 16px;
      background: linear-gradient(135deg, #003082, #00A650);
      color: #fff; margin-bottom: var(--space-3); box-shadow: var(--shadow-md);
    }
    .logo-badge .ic { width: 30px; height: 30px; stroke-width: 1.8; }
    .login-logo h1 { font-size: 21px; font-weight: 800; letter-spacing: -.02em; }
    .login-logo p { font-size: 13px; color: var(--text-secondary); margin-top: 3px; }
    .login-card { padding: var(--space-6); box-shadow: var(--shadow-lg); }
    .login-card h2 { font-size: 16px; font-weight: 700; }
    .login-card .subtitle { font-size: 13px; color: var(--text-secondary); margin-bottom: var(--space-5); margin-top: 3px; }
    .btn-login { width: 100%; justify-content: center; padding: 11px; font-size: 14.5px; margin-top: var(--space-2); }
    .login-footer { text-align: center; margin-top: var(--space-5); font-size: 11.5px; color: var(--text-muted); line-height: 1.6; }
    .theme-switch { position: fixed; top: 18px; right: 18px; z-index: 2; }
  </style>
</head>
<body>
<?php render_icon_sprite(); ?>
<div class="bg-decor"></div>

<div class="theme-switch" role="group" aria-label="Tema de la interfaz">
  <button type="button" data-theme-set="light"  aria-pressed="false" title="Tema claro"><?= icon('sun') ?></button>
  <button type="button" data-theme-set="dark"   aria-pressed="false" title="Tema oscuro"><?= icon('moon') ?></button>
  <button type="button" data-theme-set="system" aria-pressed="false" title="Seguir al sistema"><?= icon('monitor') ?></button>
</div>

<div class="login-container fade-in">
  <div class="login-logo">
    <div class="logo-badge"><?= icon('graduation-cap') ?></div>
    <h1>Juicios Evaluativos</h1>
    <p>Sistema de seguimiento y evaluación SENA</p>
  </div>

  <div class="card login-card">
    <span class="badge badge-brand mb-3"><?= icon('graduation-cap') ?> SENA — Formación Profesional</span>
    <h2>Bienvenido</h2>
    <p class="subtitle">Ingresa tus credenciales para continuar</p>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><?= icon('alert-triangle') ?><div><?= htmlspecialchars($error) ?></div></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="correo@sena.edu.co"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email" />
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required autocomplete="current-password" />
      </div>
      <button type="submit" class="btn btn-primary btn-login" id="btn-login">
        Iniciar sesión <?= icon('arrow-right') ?>
      </button>
    </form>
  </div>

  <div class="login-footer">
    <p>Servicio Nacional de Aprendizaje — SENA</p>
    <p>Sistema para la gestión de la formación profesional</p>
  </div>
</div>

<script>
  // Selector de tema (versión reducida del de footer.php, que aquí no se carga)
  (function () {
    var KEY = 'ui-theme';
    function stored() { try { return localStorage.getItem(KEY) || 'system'; } catch (e) { return 'system'; } }
    function sync(theme) {
      document.querySelectorAll('[data-theme-set]').forEach(function (b) {
        b.setAttribute('aria-pressed', String(b.dataset.themeSet === theme));
      });
    }
    document.querySelectorAll('[data-theme-set]').forEach(function (b) {
      b.addEventListener('click', function () {
        var t = b.dataset.themeSet;
        if (t === 'system') delete document.documentElement.dataset.theme;
        else document.documentElement.dataset.theme = t;
        try { localStorage.setItem(KEY, t); } catch (e) {}
        sync(t);
      });
    });
    sync(stored());
  })();

  document.querySelector('form').addEventListener('submit', function () {
    var btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.textContent = 'Verificando…';
  });
</script>
</body>
</html>
