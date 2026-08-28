<?php
// Plantilla de configuración para DESARROLLO LOCAL (XAMPP/Laragon).
// Renombra este archivo a 'config.env.php' y coloca tus credenciales reales.
// 'config.env.php' está ignorado por Git y excluido de la imagen Docker.
//
// En Docker / Dokploy NO se usa este archivo: la configuración llega
// por variables de entorno (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET).

define('DB_HOST', 'localhost');
define('DB_NAME', 'juicios_evaluativos');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');
define('DB_CHARSET', 'utf8mb4');
