<?php
// ================================================================
// API — Gestión de Fichas de Formación
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ==========================================
// LECTURA DE DATOS (GET)
// ==========================================
if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $programa = $_GET['programa'] ?? '';
            $where = ['1=1'];
            $params = [];
            if ($programa) {
                $where[] = 'f.id_programa = ?';
                $params[] = $programa;
            }
            $whereSQL = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT f.ficha, f.fecha_inicio, f.fecha_fin, f.modalidad,
                       f.estado, p.nombre AS programa,
                       COUNT(a.documento) AS total_aprendices
                FROM formacion f
                JOIN Programa p ON f.id_programa = p.id_programa
                LEFT JOIN Aprendiz a ON a.ficha = f.ficha
                WHERE $whereSQL
                GROUP BY f.ficha, p.nombre, f.estado, f.fecha_inicio, f.fecha_fin, f.modalidad
                ORDER BY f.fecha_inicio DESC
            ");
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_estados':
            $stmt = $db->query('SELECT DISTINCT estado FROM formacion ORDER BY estado ASC');
            echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'get_programas':
            $stmt = $db->query('SELECT id_programa, nombre FROM Programa ORDER BY nombre ASC');
            echo json_encode($stmt->fetchAll());
            break;

        default:
            http_response_code(400); echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}

// ==========================================
// BORRADO MASIVO (DELETE)
// ==========================================
if ($method === 'DELETE') {
    $ficha = $_GET['ficha'] ?? '';
    if (!$ficha) {
        http_response_code(400); echo json_encode(['error' => 'Número de ficha requerido']); exit;
    }

    try {
        $db->beginTransaction();

        // 0. Obtener el programa asociado antes de borrar la ficha
        $stmtProg = $db->prepare('SELECT id_programa FROM formacion WHERE ficha = ?');
        $stmtProg->execute([$ficha]);
        $idPrograma = $stmtProg->fetchColumn();

        // 1. Borrar todos los juicios de los aprendices asociados a esta ficha
        $stmtJuicios = $db->prepare('
            DELETE j FROM juicios_evaluativos j
            JOIN Aprendiz a ON j.documento_aprendiz = a.documento
            WHERE a.ficha = ?
        ');
        $stmtJuicios->execute([$ficha]);
        $juiciosBorrados = $stmtJuicios->rowCount();

        // 2. Borrar todos los aprendices asociados a esta ficha
        $stmtAprendices = $db->prepare('DELETE FROM Aprendiz WHERE ficha = ?');
        $stmtAprendices->execute([$ficha]);
        $aprendicesBorrados = $stmtAprendices->rowCount();

        // 3. Borrar la ficha (formacion)
        $stmtFicha = $db->prepare('DELETE FROM formacion WHERE ficha = ?');
        $stmtFicha->execute([$ficha]);

        if ($stmtFicha->rowCount() === 0) {
            $db->rollBack();
            http_response_code(404); echo json_encode(['error' => 'La ficha no existe']); exit;
        }

        // 4. Limpieza de catálogos huérfanos
        if ($idPrograma) {
            $stmtCount = $db->prepare('SELECT COUNT(*) FROM formacion WHERE id_programa = ?');
            $stmtCount->execute([$idPrograma]);
            if ($stmtCount->fetchColumn() == 0) {
                $db->prepare('DELETE FROM Programa WHERE id_programa = ?')->execute([$idPrograma]);
            }
        }

        // Limpiar Resultados huérfanos
        $db->exec('
            DELETE r FROM Resultados_aprendizaje r
            LEFT JOIN programa_competencia pc ON r.id_competencia = pc.id_competencia
            LEFT JOIN juicios_evaluativos j ON r.id_resultado = j.id_resultado
            WHERE pc.id_competencia IS NULL AND j.id_juicio IS NULL
        ');

        // Limpiar Competencias huérfanas
        $db->exec('
            DELETE c FROM Competencias c
            LEFT JOIN programa_competencia pc ON c.id_competencia = pc.id_competencia
            LEFT JOIN Resultados_aprendizaje r ON c.id_competencia = r.id_competencia
            WHERE pc.id_competencia IS NULL AND r.id_resultado IS NULL
        ');

        $db->commit();
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Ficha y datos asociados eliminados exitosamente',
            'detalles' => [
                'aprendices_borrados' => $aprendicesBorrados,
                'juicios_borrados'    => $juiciosBorrados
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// BORRADO MASIVO MULTIPLE (POST action=bulk_delete)
// ==========================================
if ($method === 'POST' && $action === 'bulk_delete') {
    $body = json_decode(file_get_contents('php://input'), true);
    $fichas = $body['fichas'] ?? [];
    
    if (empty($fichas) || !is_array($fichas)) {
        http_response_code(400); echo json_encode(['error' => 'No se enviaron fichas para eliminar']); exit;
    }

    try {
        $db->beginTransaction();
        $totalBorradas = 0;

        foreach ($fichas as $ficha) {
            $stmtProg = $db->prepare('SELECT id_programa FROM formacion WHERE ficha = ?');
            $stmtProg->execute([$ficha]);
            $idPrograma = $stmtProg->fetchColumn();

            if (!$idPrograma) continue;

            $db->prepare('
                DELETE j FROM juicios_evaluativos j
                JOIN Aprendiz a ON j.documento_aprendiz = a.documento
                WHERE a.ficha = ?
            ')->execute([$ficha]);

            $db->prepare('DELETE FROM Aprendiz WHERE ficha = ?')->execute([$ficha]);
            
            $stmtFicha = $db->prepare('DELETE FROM formacion WHERE ficha = ?');
            $stmtFicha->execute([$ficha]);
            if ($stmtFicha->rowCount() > 0) {
                $totalBorradas++;
            }

            if ($idPrograma) {
                $stmtCount = $db->prepare('SELECT COUNT(*) FROM formacion WHERE id_programa = ?');
                $stmtCount->execute([$idPrograma]);
                if ($stmtCount->fetchColumn() == 0) {
                    $db->prepare('DELETE FROM Programa WHERE id_programa = ?')->execute([$idPrograma]);
                }
            }
        }

        // Limpiar huérfanos globalmente
        $db->exec('
            DELETE r FROM Resultados_aprendizaje r
            LEFT JOIN programa_competencia pc ON r.id_competencia = pc.id_competencia
            LEFT JOIN juicios_evaluativos j ON r.id_resultado = j.id_resultado
            WHERE pc.id_competencia IS NULL AND j.id_juicio IS NULL
        ');
        $db->exec('
            DELETE c FROM Competencias c
            LEFT JOIN programa_competencia pc ON c.id_competencia = pc.id_competencia
            LEFT JOIN Resultados_aprendizaje r ON c.id_competencia = r.id_competencia
            WHERE pc.id_competencia IS NULL AND r.id_resultado IS NULL
        ');

        $db->commit();
        echo json_encode([
            'ok' => true,
            'mensaje' => "$totalBorradas fichas eliminadas exitosamente"
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}
