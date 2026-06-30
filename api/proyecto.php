<?php
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'programas') {
        $stmt = $db->query('SELECT * FROM Programa ORDER BY nombre');
        jsonResponse($stmt->fetchAll());
    }
    
    if ($action === 'fases') {
        $id_prog = $_GET['id_programa'] ?? 0;
        $stmt = $db->prepare('SELECT * FROM fases_proyecto WHERE id_programa = ? ORDER BY id_fase');
        $stmt->execute([$id_prog]);
        jsonResponse($stmt->fetchAll());
    }
    
    if ($action === 'actividades') {
        $id_fase = $_GET['id_fase'] ?? 0;
        $stmt = $db->prepare('SELECT * FROM actividades_proyecto WHERE id_fase = ? ORDER BY id_actividad');
        $stmt->execute([$id_fase]);
        jsonResponse($stmt->fetchAll());
    }

    if ($action === 'raps_por_programa') {
        $id_prog = $_GET['id_programa'] ?? 0;
        $id_act = $_GET['id_actividad'] ?? 0;
        
        $sql = "SELECT c.nombre AS competencia, r.id_resultado, r.codigo, r.descripcion,
                (SELECT 1 FROM actividad_resultado ar WHERE ar.id_resultado = r.id_resultado AND ar.id_actividad = ?) AS asignado
                FROM programa_competencia pc
                JOIN Competencias c ON pc.id_competencia = c.id_competencia
                JOIN Resultados_aprendizaje r ON c.id_competencia = r.id_competencia
                WHERE pc.id_programa = ?
                ORDER BY c.nombre, r.codigo";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id_act, $id_prog]);
        jsonResponse($stmt->fetchAll());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'crear_fase') {
        $stmt = $db->prepare('INSERT INTO fases_proyecto (id_programa, nombre_fase, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([$body['id_programa'], $body['nombre_fase'], $body['descripcion'] ?? '']);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
    
    if ($action === 'crear_actividad') {
        $stmt = $db->prepare('INSERT INTO actividades_proyecto (id_fase, nombre_actividad, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([$body['id_fase'], $body['nombre_actividad'], $body['descripcion'] ?? '']);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }

    if ($action === 'asignar_rap') {
        $id_actividad = $body['id_actividad'] ?? 0;
        $id_resultado = $body['id_resultado'] ?? 0;
        $asignar = $body['asignar'] ?? true;
        
        if ($asignar) {
            $stmt = $db->prepare('INSERT IGNORE INTO actividad_resultado (id_actividad, id_resultado) VALUES (?, ?)');
            $stmt->execute([$id_actividad, $id_resultado]);
        } else {
            $stmt = $db->prepare('DELETE FROM actividad_resultado WHERE id_actividad = ? AND id_resultado = ?');
            $stmt->execute([$id_actividad, $id_resultado]);
        }
        jsonResponse(['success' => true]);
    }

    if ($action === 'importar_pdf') {
        $id_programa = $body['id_programa'] ?? 0;
        $codigo_programa = $body['codigo_programa'] ?? '';
        $proyecto = $body['proyecto'] ?? [];
        
        // Lookup id_programa by codigo_programa if needed
        if (!$id_programa && $codigo_programa) {
            $stmt = $db->prepare('SELECT id_programa FROM Programa WHERE codigo = ? LIMIT 1');
            $stmt->execute([$codigo_programa]);
            $id_programa = $stmt->fetchColumn();
        }

        if (!$id_programa || empty($proyecto)) {
            jsonResponse(['error' => 'No se seleccionó programa válido o datos incompletos'], 400);
        }

        try {
            $db->beginTransaction();

            foreach ($proyecto as $fase) {
                // Crear/Buscar Fase (Normalizando nombre)
                $stmtFase = $db->prepare('SELECT id_fase FROM fases_proyecto WHERE id_programa = ? AND (nombre_fase = ? OR nombre_fase LIKE ?) LIMIT 1');
                $nombreFaseNorm = trim($fase['nombre']);
                $stmtFase->execute([$id_programa, $nombreFaseNorm, "%$nombreFaseNorm%"]);
                $id_fase = $stmtFase->fetchColumn();

                if (!$id_fase) {
                    $stmtInsFase = $db->prepare('INSERT INTO fases_proyecto (id_programa, nombre_fase, descripcion) VALUES (?, ?, ?)');
                    $stmtInsFase->execute([$id_programa, $nombreFaseNorm, 'Importado automáticamente del PDF']);
                    $id_fase = $db->lastInsertId();
                }

                if (!empty($fase['actividades'])) {
                    foreach ($fase['actividades'] as $act) {
                        // Buscar Actividad ignorando prefijos de numeración
                        $nombreAct = trim($act['nombre']);
                        // Extraer el núcleo del nombre si tiene "Actividad número X: "
                        $nombreActBusqueda = preg_replace('/^Actividad\s+número\s+\d+:\s+/i', '', $nombreAct);
                        
                        $stmtAct = $db->prepare('SELECT id_actividad FROM actividades_proyecto WHERE id_fase = ? AND (nombre_actividad = ? OR nombre_actividad LIKE ? OR nombre_actividad LIKE ?) LIMIT 1');
                        $stmtAct->execute([$id_fase, $nombreAct, "%$nombreActBusqueda%", "%$nombreAct%"]);
                        $id_act = $stmtAct->fetchColumn();

                        if (!$id_act) {
                            $stmtInsAct = $db->prepare('INSERT INTO actividades_proyecto (id_fase, nombre_actividad, descripcion) VALUES (?, ?, ?)');
                            $stmtInsAct->execute([$id_fase, $nombreAct, 'Importada del PDF']);
                            $id_act = $db->lastInsertId();
                        } else {
                            // Si existe, actualizamos el nombre al más completo (el que tiene número)
                            $db->prepare('UPDATE actividades_proyecto SET nombre_actividad = ? WHERE id_actividad = ?')->execute([$nombreAct, $id_act]);
                        }

                        if (!empty($act['competencias'])) {
                            foreach ($act['competencias'] as $comp) {
                                $id_comp = null;

                                // 1. Buscar la competencia a través de los RAPs (El código RAP es estable, el de competencia puede variar entre PDF y Excel)
                                if (!empty($comp['raps'])) {
                                    foreach ($comp['raps'] as $rap) {
                                        $stmtCheck = $db->prepare('SELECT id_competencia FROM Resultados_aprendizaje WHERE codigo = ? LIMIT 1');
                                        $stmtCheck->execute([$rap['codigo']]);
                                        if ($found_comp = $stmtCheck->fetchColumn()) {
                                            $id_comp = $found_comp;
                                            break;
                                        }
                                    }
                                }

                                // 2. Si no se encontró por RAP, buscar por código exacto o nombre exacto
                                if (!$id_comp) {
                                    $stmtComp = $db->prepare('SELECT id_competencia FROM Competencias WHERE codigo = ? OR nombre = ? LIMIT 1');
                                    $stmtComp->execute([$comp['codigo'], $comp['nombre']]);
                                    $id_comp = $stmtComp->fetchColumn();
                                }

                                // 3. Si definitivamente no existe, se crea
                                if (!$id_comp) {
                                    $stmtInsComp = $db->prepare('INSERT INTO Competencias (codigo, nombre) VALUES (?, ?)');
                                    $stmtInsComp->execute([$comp['codigo'], $comp['nombre']]);
                                    $id_comp = $db->lastInsertId();
                                }
                                
                                // Vincular competencia al programa si no lo está
                                $db->prepare('INSERT IGNORE INTO programa_competencia (id_programa, id_competencia) VALUES (?, ?)')->execute([$id_programa, $id_comp]);

                                if (!empty($comp['raps'])) {
                                    foreach ($comp['raps'] as $rap) {
                                        // Verificar/Crear RAP
                                        $stmtRap = $db->prepare('SELECT id_resultado FROM Resultados_aprendizaje WHERE codigo = ? LIMIT 1');
                                        $stmtRap->execute([$rap['codigo']]);
                                        $id_rap = $stmtRap->fetchColumn();

                                        if (!$id_rap) {
                                            $stmtInsRap = $db->prepare('INSERT INTO Resultados_aprendizaje (id_competencia, codigo, descripcion) VALUES (?, ?, ?)');
                                            $stmtInsRap->execute([$id_comp, $rap['codigo'], $rap['descripcion']]);
                                            $id_rap = $db->lastInsertId();
                                        }

                                        // Vincular RAP al programa
                                        $db->prepare('INSERT IGNORE INTO programa_resultado (id_programa, id_resultado) VALUES (?, ?)')->execute([$id_programa, $id_rap]);

                                        // 3. Vincular RAP a la Actividad actual
                                        $db->prepare('INSERT IGNORE INTO actividad_resultado (id_actividad, id_resultado) VALUES (?, ?)')->execute([$id_act, $id_rap]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $db->commit();
            jsonResponse(['success' => true]);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    if ($action === 'borrar_estructura') {
        $id_programa = $body['id_programa'] ?? 0;
        if (!$id_programa) {
            jsonResponse(['error' => 'Programa no válido'], 400);
        }
        try {
            // Delete phases, which cascades to activities and assignments
            $stmt = $db->prepare('DELETE FROM fases_proyecto WHERE id_programa = ?');
            $stmt->execute([$id_programa]);
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}

jsonResponse(['error' => 'Acción no válida'], 400);
