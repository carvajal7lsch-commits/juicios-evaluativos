<?php
// ================================================================
// Healthcheck — usado por Docker/Dokploy para saber si el
// contenedor está vivo y con la base de datos accesible.
// ================================================================
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config/database.php';
    getDB()->query('SELECT 1');
    echo json_encode(['status' => 'ok', 'db' => 'up']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'db' => 'down']);
}
