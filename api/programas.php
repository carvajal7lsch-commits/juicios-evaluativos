<?php
// ================================================================
// API — Gestión de Programas de Formación
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $stmt = $db->query('
                SELECT p.id_programa, p.codigo, p.nombre,
                       COUNT(DISTINCT f.ficha) AS total_fichas,
                       COUNT(DISTINCT a.documento) AS total_aprendices,
                       COUNT(DISTINCT pc.id_competencia) AS total_competencias
                FROM Programa p
                LEFT JOIN formacion f ON p.id_programa = f.id_programa
                LEFT JOIN Aprendiz a ON f.ficha = a.ficha
                LEFT JOIN programa_competencia pc ON p.id_programa = pc.id_programa
                GROUP BY p.id_programa, p.codigo, p.nombre
                ORDER BY p.nombre ASC
            ');
            echo json_encode($stmt->fetchAll());
            break;

        default:
            http_response_code(400); echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}
