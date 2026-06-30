<?php
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$ficha  = $_GET['ficha'] ?? '';

if (!$ficha && $method === 'GET') {
    http_response_code(400); echo json_encode(['error'=>'Se requiere el número de ficha']); exit;
}

if ($method === 'GET') {
    switch ($action) {
        case 'meta':
            $stmt = $db->prepare('
                SELECT f.ficha, f.fecha_inicio, f.fecha_fin, f.modalidad,
                       p.nombre AS programa, p.codigo AS codigo_programa, f.estado,
                       (SELECT COUNT(*) FROM Aprendiz WHERE ficha = f.ficha) as total_aprendices,
                       (SELECT COUNT(DISTINCT pc.id_competencia) FROM programa_competencia pc WHERE pc.id_programa = f.id_programa) as total_competencias,
                       (SELECT COUNT(DISTINCT pr.id_resultado) FROM programa_resultado pr
                        WHERE pr.id_programa = f.id_programa) as total_resultados
                FROM formacion f
                JOIN Programa p ON f.id_programa = p.id_programa
                WHERE f.ficha = ?
            ');
            $stmt->execute([$ficha]);
            $meta = $stmt->fetch();

            if (!$meta) { http_response_code(404); echo json_encode(['error'=>'Ficha no encontrada']); exit; }

            $stmtAvg = $db->prepare("
                SELECT ROUND(AVG(
                    (SELECT COUNT(DISTINCT j.id_resultado) FROM juicios_evaluativos j
                     JOIN programa_resultado pr ON j.id_resultado = pr.id_resultado
                     WHERE j.documento_aprendiz = a.documento
                     AND pr.id_programa = f.id_programa
                     AND LOWER(TRIM(j.estado)) = 'aprobado') * 100.0 /
                    NULLIF(
                        (SELECT COUNT(DISTINCT pr.id_resultado) FROM programa_resultado pr
                         JOIN formacion f2 ON f2.id_programa = pr.id_programa
                         WHERE f2.ficha = a.ficha), 0)
                ), 1) AS avance_promedio
                FROM Aprendiz a
                JOIN formacion f ON a.ficha = f.ficha
                WHERE a.ficha = ? AND LOWER(TRIM(a.estado)) NOT IN ('retiro voluntario', 'trasladado', 'deserción', 'cancelado', 'aplazado')
            ");
            $stmtAvg->execute([$ficha]);
            $meta['avance_promedio'] = (float)($stmtAvg->fetchColumn() ?? 0);

            echo json_encode($meta);
            break;

        case 'aprendices':
            $stmt = $db->prepare("
                SELECT a.documento, a.nombre, a.apellidos, a.tipo_doc, a.estado,
                       (SELECT COUNT(DISTINCT j.id_resultado) FROM juicios_evaluativos j
                        JOIN programa_resultado pr ON j.id_resultado = pr.id_resultado
                        WHERE j.documento_aprendiz = a.documento
                        AND pr.id_programa = f.id_programa
                        AND LOWER(TRIM(j.estado)) = 'aprobado') AS raps_aprobados,
                       (SELECT COUNT(DISTINCT pr.id_resultado) FROM programa_resultado pr
                        JOIN formacion f ON f.id_programa = pr.id_programa
                        WHERE f.ficha = a.ficha) AS total_raps
                FROM Aprendiz a
                JOIN formacion f ON a.ficha = f.ficha
                WHERE a.ficha = ?
                ORDER BY a.apellidos, a.nombre
            ");
            $stmt->execute([$ficha]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'competencias':
            $totalActivos = $db->prepare("SELECT COUNT(*) FROM Aprendiz WHERE ficha = ? AND LOWER(TRIM(estado)) NOT IN ('retiro voluntario', 'trasladado', 'deserción', 'cancelado', 'aplazado')");
            $totalActivos->execute([$ficha]);
            $nActivos = (int)$totalActivos->fetchColumn();

            $stmt = $db->prepare("
                SELECT c.codigo AS cod_comp, c.nombre AS competencia, c.duracion_horas,
                       r.id_resultado, r.codigo AS cod_res, r.descripcion AS resultado,
                       (SELECT COUNT(DISTINCT j.documento_aprendiz)
                        FROM juicios_evaluativos j
                        JOIN Aprendiz ap ON j.documento_aprendiz = ap.documento
                        WHERE j.id_resultado = r.id_resultado
                        AND LOWER(TRIM(j.estado)) = 'aprobado'
                        AND ap.ficha = f.ficha
                        AND LOWER(TRIM(ap.estado)) NOT IN ('retiro voluntario', 'trasladado', 'deserción', 'cancelado', 'aplazado')) AS aprobados_count
                FROM formacion f
                JOIN programa_resultado pr ON f.id_programa = pr.id_programa
                JOIN Resultados_aprendizaje r ON pr.id_resultado = r.id_resultado
                JOIN Competencias c ON r.id_competencia = c.id_competencia
                WHERE f.ficha = ?
                ORDER BY c.nombre, r.codigo
            ");
            $stmt->execute([$ficha]);
            $rows = $stmt->fetchAll();

            // Adjuntar total_activos y lista de faltantes a cada fila
            $activeFilter = "NOT IN ('retiro voluntario', 'trasladado', 'deserción', 'cancelado', 'aplazado')";
            foreach ($rows as &$row) {
                $row['total_activos'] = $nActivos;
                if ($row['aprobados_count'] > 0 && $row['aprobados_count'] < $nActivos) {
                    $stmtMissing = $db->prepare("
                        SELECT CONCAT(nombre, ' ', apellidos) 
                        FROM Aprendiz 
                        WHERE ficha = ? AND LOWER(TRIM(estado)) $activeFilter
                        AND documento NOT IN (
                            SELECT documento_aprendiz FROM juicios_evaluativos 
                            WHERE id_resultado = ? AND LOWER(TRIM(estado)) = 'aprobado'
                        )
                    ");
                    $stmtMissing->execute([$ficha, $row['id_resultado']]);
                    $row['faltantes'] = $stmtMissing->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $row['faltantes'] = [];
                }
            }
            echo json_encode($rows);
            break;

        case 'juicios':
            $stmt = $db->prepare("
                SELECT j.id_juicio, j.documento_aprendiz, j.fecha_registro, j.estado,
                       CONCAT(a.nombre,' ',a.apellidos) AS aprendiz,
                       r.codigo AS cod_resultado, r.descripcion AS resultado,
                       c.nombre AS competencia,
                       fu.nombre_completo AS funcionario
                FROM juicios_evaluativos j
                JOIN Aprendiz a ON j.documento_aprendiz = a.documento
                JOIN Resultados_aprendizaje r ON j.id_resultado = r.id_resultado
                JOIN Competencias c ON r.id_competencia = c.id_competencia
                LEFT JOIN Funcionarios fu ON j.documento_funcionario = fu.documento
                WHERE a.ficha = ?
                ORDER BY j.fecha_registro DESC
            ");
            $stmt->execute([$ficha]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'audit':
            // 1. Obtener total de aprendices activos en la ficha
            $activeFilter = "NOT IN ('retiro voluntario', 'trasladado', 'deserción', 'cancelado', 'aplazado')";
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM Aprendiz WHERE ficha = ? AND LOWER(TRIM(estado)) $activeFilter");
            $stmtCount->execute([$ficha]);
            $totalAprendices = (int)$stmtCount->fetchColumn();

            // 2. Obtener RAPs y sus aprobaciones
            $stmt = $db->prepare("
                SELECT r.id_resultado, r.codigo, r.descripcion, c.nombre AS competencia,
                       (SELECT COUNT(*) 
                        FROM juicios_evaluativos j 
                        JOIN Aprendiz a ON j.documento_aprendiz = a.documento
                        WHERE j.id_resultado = r.id_resultado 
                        AND a.ficha = f.ficha 
                        AND LOWER(TRIM(j.estado)) = 'aprobado'
                        AND LOWER(TRIM(a.estado)) $activeFilter) AS total_aprobados
                FROM formacion f
                JOIN programa_resultado pr ON f.id_programa = pr.id_programa
                JOIN Resultados_aprendizaje r ON pr.id_resultado = r.id_resultado
                JOIN Competencias c ON r.id_competencia = c.id_competencia
                WHERE f.ficha = ?
                ORDER BY c.nombre, r.codigo
            ");
            $stmt->execute([$ficha]);
            $raps = $stmt->fetchAll();

            // 3. Identificar quiénes faltan por cada RAP (solo si hay al menos 1 aprobado pero no todos)
            // Para no saturar, el frontend pedirá detalles si es necesario o podemos enviarlos aquí si son pocos
            foreach ($raps as &$rap) {
                $rap['total_grupo'] = $totalAprendices;
                if ($rap['total_aprobados'] > 0 && $rap['total_aprobados'] < $totalAprendices) {
                    // Buscar aprendices que NO tienen este RAP aprobado
                    $stmtMissing = $db->prepare("
                        SELECT CONCAT(nombre, ' ', apellidos) AS nombre
                        FROM Aprendiz a
                        WHERE a.ficha = ? 
                        AND LOWER(TRIM(a.estado)) $activeFilter
                        AND a.documento NOT IN (
                            SELECT j.documento_aprendiz 
                            FROM juicios_evaluativos j 
                            WHERE j.id_resultado = ? 
                            AND LOWER(TRIM(j.estado)) = 'aprobado'
                        )
                    ");
                    $stmtMissing->execute([$ficha, $rap['id_resultado']]);
                    $rap['faltantes'] = $stmtMissing->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $rap['faltantes'] = [];
                }
            }
            echo json_encode($raps);
            break;

        default:
            http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
    }
    exit;
}
