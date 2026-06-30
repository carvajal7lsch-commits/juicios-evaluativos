<?php
// ================================================================
// INDEX — Redirige directamente al dashboard (auth desactivada)
// ================================================================
header('Location: pages/dashboard.php');
exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión — Juicios Evaluativos SENA</title>
  <meta name="description" content="Sistema de Juicios Evaluativos SENA — Acceso al sistema" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: #0d1117;
      color: #e6edf3;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .bg-gradient {
      position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 20%, rgba(0,48,130,.35) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 80%, rgba(0,166,80,.2)  0%, transparent 55%),
        #0d1117;
    }
    .bg-grid {
      position: fixed; inset: 0; z-index: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    .orb { position: fixed; border-radius: 50%; filter: blur(80px); z-index: 0; animation: float 8s ease-in-out infinite; }
    .orb-1 { width: 400px; height: 400px; background: rgba(0,48,130,.3); top:-100px; left:-100px; }
    .orb-2 { width: 300px; height: 300px; background: rgba(0,166,80,.2); bottom:-80px; right:-80px; animation-delay: 3s; }
    @keyframes float { 0%,100% { transform: translate(0,0); } 50% { transform: translate(20px,-20px); } }
    .login-container { position: relative; z-index: 10; width: 420px; animation: fadeInUp .5s ease both; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    .login-logo { text-align: center; margin-bottom: 32px; }
    .login-logo .logo-badge {
      display: inline-flex; align-items: center; justify-content: center;
      width: 64px; height: 64px;
      background: linear-gradient(135deg, #003082, #00A650);
      border-radius: 18px; font-size: 28px; font-weight: 800; color: #fff;
      margin-bottom: 14px; box-shadow: 0 8px 32px rgba(0,48,130,.4);
    }
    .login-logo h1 { font-size: 22px; font-weight: 800; }
    .login-logo p  { font-size: 13px; color: #8b949e; margin-top: 4px; }
    .login-card {
      background: rgba(22,27,34,.85); backdrop-filter: blur(20px);
      border: 1px solid #30363d; border-radius: 20px; padding: 32px;
    }
    .login-card h2 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
    .login-card .subtitle { font-size: 13px; color: #8b949e; margin-bottom: 28px; }
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; }
    .form-control {
      width: 100%; background: #21262d; border: 1px solid #30363d;
      border-radius: 10px; color: #e6edf3; font-family: 'Inter', sans-serif;
      font-size: 14px; padding: 11px 14px; outline: none; transition: all .2s;
    }
    .form-control:focus { border-color: #1a4db5; box-shadow: 0 0 0 3px rgba(26,77,181,.2); }
    .form-control::placeholder { color: #484f58; }
    .btn-login {
      width: 100%; background: linear-gradient(135deg, #003082, #1a4db5);
      color: #fff; border: none; border-radius: 10px; padding: 13px;
      font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif;
      cursor: pointer; transition: all .2s; margin-top: 8px;
    }
    .btn-login:hover { background: linear-gradient(135deg, #1a4db5, #2563eb); box-shadow: 0 4px 20px rgba(26,77,181,.4); transform: translateY(-1px); }
    .alert-error {
      background: rgba(218,54,51,.12); border: 1px solid rgba(218,54,51,.35);
      color: #ff7b72; border-radius: 10px; padding: 12px 16px;
      font-size: 13px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
    }
    .login-footer { text-align: center; margin-top: 20px; font-size: 12px; color: #484f58; }
    .sena-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(0,48,130,.15); border: 1px solid rgba(0,48,130,.3);
      color: #79c0ff; border-radius: 20px; padding: 4px 12px;
      font-size: 11px; font-weight: 600; margin-bottom: 8px;
    }
  </style>
</head>
<body>
<div class="bg-gradient"></div>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="login-container">
  <div class="login-logo">
    <div class="logo-badge">S</div>
    <h1>Juicios Evaluativos</h1>
    <p>Sistema de Seguimiento y Evaluación SENA</p>
  </div>

  <div class="login-card">
    <div class="sena-badge">🎓 SENA — Formación Profesional</div>
    <h2>Bienvenido</h2>
    <p class="subtitle">Ingresa tus credenciales para continuar</p>

    <?php if ($error): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
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
      <button type="submit" class="btn-login" id="btn-login">Iniciar Sesión →</button>
    </form>
  </div>

  <div class="login-footer">
    <p>Servicio Nacional de Aprendizaje — SENA</p>
    <p style="margin-top:4px;">Sistema desarrollado para la gestión de formación profesional</p>
  </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
  const btn = document.getElementById('btn-login');
  btn.disabled = true;
  btn.textContent = 'Verificando...';
});
</script>
</body>
</html>
