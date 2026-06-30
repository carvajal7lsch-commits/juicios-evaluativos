<?php
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'fichas') {
        $stmt = $db->query("SELECT f.ficha, p.nombre AS programa FROM formacion f JOIN Programa p ON f.id_programa = p.id_programa ORDER BY f.ficha");
        jsonResponse($stmt->fetchAll());
    }

    if ($action === 'estadisticas_fases') {
        $ficha = $_GET['ficha'] ?? '';
        if (!$ficha) jsonResponse([]);

        // Obtener ID del programa
        $stmtProg = $db->prepare('SELECT id_programa FROM formacion WHERE ficha = ?');
        $stmtProg->execute([$ficha]);
        $id_prog = $stmtProg->fetchColumn();
        
        if (!$id_prog) jsonResponse([]);

        // Primero, obtener las fases y saber cuántos RAPs tiene cada una
        $sqlRapsPorFase = "
            SELECT f.id_fase, f.nombre_fase, COUNT(DISTINCT ar.id_resultado) AS total_raps
            FROM fases_proyecto f
            LEFT JOIN actividades_proyecto ap ON f.id_fase = ap.id_fase
            LEFT JOIN actividad_resultado ar ON ap.id_actividad = ar.id_actividad
            WHERE f.id_programa = ?
            GROUP BY f.id_fase, f.nombre_fase
        ";
        $stmtRapsFase = $db->prepare($sqlRapsPorFase);
        $stmtRapsFase->execute([$id_prog]);
        $fasesInfo = $stmtRapsFase->fetchAll();
        
        $fasesMap = [];
        foreach($fasesInfo as $fi) {
            $fasesMap[$fi['id_fase']] = [
                'nombre_fase' => $fi['nombre_fase'],
                'total_raps' => (int)$fi['total_raps'],
                'aprendices_aprobados' => 0,
                'aprendices_pendientes' => 0,
                'total_aprendices' => 0
            ];
        }

        // Si no hay fases o RAPs, retornamos el map vacío
        $totalRapsAllFases = array_sum(array_column($fasesMap, 'total_raps'));
        if ($totalRapsAllFases === 0 && count($fasesMap) == 0) {
            jsonResponse([]);
        }

        // Calcular cuántos RAPs ha aprobado cada aprendiz por fase
        $sqlAprendizFase = "
            SELECT 
                f.id_fase,
                a.documento,
                COUNT(DISTINCT CASE WHEN LOWER(je.estado) = 'aprobado' THEN je.id_resultado ELSE NULL END) AS raps_aprobados
            FROM fases_proyecto f
            JOIN actividades_proyecto ap ON f.id_fase = ap.id_fase
            JOIN actividad_resultado ar ON ap.id_actividad = ar.id_actividad
            JOIN Aprendiz a ON a.ficha = ? AND a.estado = 'Activo'
            LEFT JOIN juicios_evaluativos je ON je.documento_aprendiz = a.documento AND je.id_resultado = ar.id_resultado
            WHERE f.id_programa = ?
            GROUP BY f.id_fase, a.documento
        ";
        
        $stmtAF = $db->prepare($sqlAprendizFase);
        $stmtAF->execute([$ficha, $id_prog]);
        $resultados = $stmtAF->fetchAll();
        
        foreach ($resultados as $r) {
            $idFase = $r['id_fase'];
            if (!isset($fasesMap[$idFase])) continue;
            
            $fasesMap[$idFase]['total_aprendices']++;
            
            if ($fasesMap[$idFase]['total_raps'] > 0 && $r['raps_aprobados'] >= $fasesMap[$idFase]['total_raps']) {
                $fasesMap[$idFase]['aprendices_aprobados']++;
            } else {
                $fasesMap[$idFase]['aprendices_pendientes']++;
            }
        }
        
        // Si hay aprendices sin RAPs registrados pero la consulta no los trajo (por el JOIN):
        // Debemos asegurarnos de contar a todos los activos
        $stmtActivos = $db->prepare("SELECT COUNT(*) FROM Aprendiz WHERE ficha = ? AND estado = 'Activo'");
        $stmtActivos->execute([$ficha]);
        $totalActivos = (int)$stmtActivos->fetchColumn();
        
        // Calcular los porcentajes y ajustar totales
        $finalData = [];
        foreach ($fasesMap as $id => $data) {
            // Ajustar pendientes para aquellos aprendices que no aparecieron en el JOIN
            if ($data['total_aprendices'] < $totalActivos) {
                $faltantes = $totalActivos - $data['total_aprendices'];
                $data['aprendices_pendientes'] += $faltantes;
                $data['total_aprendices'] = $totalActivos;
            }
            
            $pct = $data['total_aprendices'] > 0 
                ? round(($data['aprendices_aprobados'] / $data['total_aprendices']) * 100) 
                : 0;
                
            $data['pct_cumplimiento'] = $pct;
            $data['id_fase'] = $id;
            $finalData[] = $data;
        }

        jsonResponse($finalData);
    }
}

jsonResponse(['error' => 'Acción no válida'], 400);
