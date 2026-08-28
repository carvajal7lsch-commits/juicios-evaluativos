<?php
// ================================================================
// Conexión a la base de datos — PDO
//
// Orden de resolución de la configuración:
//   1. config/config.env.php  (desarrollo local, ignorado por Git)
//   2. Variables de entorno   (Docker / Dokploy)
// Lo que no defina el archivo local se completa con el entorno.
// ================================================================

$envPath = __DIR__ . '/config.env.php';
if (file_exists($envPath)) {
    require_once $envPath;
}

// --- Relleno desde variables de entorno -------------------------
$envDefaults = [
    'DB_HOST'    => 'localhost',
    'DB_NAME'    => 'juicios_evaluativos',
    'DB_USER'    => 'root',
    'DB_PASS'    => '',
    'DB_CHARSET' => 'utf8mb4',
];

foreach ($envDefaults as $key => $default) {
    if (defined($key)) {
        continue;
    }
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $default;
    }
    define($key, $value);
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos']));
        }
    }
    return $pdo;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
