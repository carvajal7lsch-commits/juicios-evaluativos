<?php
// ================================================================
// API — Importar Reporte Sofia Plus
// Procesa el Excel completo y distribuye datos a todas las tablas
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db   = getDB();
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['ficha_meta']) || empty($body['rows'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$meta = $body['ficha_meta'];
$rows = $body['rows'];

$results = [
    'programa'     => null,
    'ficha'        => null,
    'aprendices'   => ['insertados'=>0,'actualizados'=>0],
    'competencias' => ['insertadas'=>0,'ya_existian'=>0],
    'resultados'   => ['insertados'=>0,'ya_existian'=>0],
    'juicios'      => ['insertados'=>0,'actualizados'=>0,'sin_juicio'=>0],
    'errores'      => [],
];

// ──────────────────────────────────────────────────────────────────
// 1. PROGRAMA
// ──────────────────────────────────────────────────────────────────
$codigoPrograma = trim($meta['codigo_programa'] ?? 'SIN_CODIGO');
$nombrePrograma = trim($meta['nombre_programa'] ?? 'Programa Sofia Plus');

$stmtProg = $db->prepare('SELECT id_programa FROM Programa WHERE codigo=? LIMIT 1');
$stmtProg->execute([$codigoPrograma]);
$idPrograma = $stmtProg->fetchColumn();

if (!$idPrograma) {
    $db->prepare('INSERT INTO Programa (codigo, nombre) VALUES (?,?)')->execute([$codigoPrograma, $nombrePrograma]);
    $idPrograma = $db->lastInsertId();
    $results['programa'] = 'creado';
} else {
    $db->prepare('UPDATE Programa SET nombre=? WHERE id_programa=?')->execute([$nombrePrograma, $idPrograma]);
    $results['programa'] = 'actualizado';
}

// ──────────────────────────────────────────────────────────────────
// 2. FICHA / FORMACIÓN
// ──────────────────────────────────────────────────────────────────
$ficha         = trim($meta['ficha'] ?? '');
$estadoFicha   = trim($meta['estado_ficha'] ?? 'En Ejecución');
$modalidad     = trim($meta['modalidad']    ?? 'Presencial');
$fechaInicio   = trim($meta['fecha_inicio'] ?? date('Y-m-d'));
$fechaFin      = trim($meta['fecha_fin']    ?? date('Y-m-d', strtotime('+2 years')));

// Normalización SENA: 'En Formación' → 'En Ejecución'
if (mb_strtolower($estadoFicha, 'UTF-8') === 'en formación' ||
    mb_strtolower($estadoFicha, 'UTF-8') === 'en formacion') {
    $estadoFicha = 'En Ejecución';
}

// Convertir fechas DD/MM/YYYY → YYYY-MM-DD si aplica
foreach (['fecha_inicio', 'fecha_fin'] as $fk) {
    $raw = trim($meta[$fk] ?? '');
    if (preg_match('#(\d{1,2})[/-](\d{1,2})[/-](\d{4})#', $raw, $m)) {
        $raw = "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    if ($fk === 'fecha_inicio') $fechaInicio = $raw ?: date('Y-m-d');
    else                        $fechaFin    = $raw ?: date('Y-m-d', strtotime('+2 years'));
}

if (!$ficha) { http_response_code(422); echo json_encode(['error'=>'No se pudo detectar la ficha en el encabezado']); exit; }

$stmtFicha = $db->prepare('SELECT ficha FROM formacion WHERE ficha=? LIMIT 1');
$stmtFicha->execute([$ficha]);
if (!$stmtFicha->fetchColumn()) {
    $db->prepare('INSERT INTO formacion (ficha,id_programa,estado,fecha_inicio,fecha_fin,modalidad) VALUES (?,?,?,?,?,?)')->execute([$ficha,$idPrograma,$estadoFicha,$fechaInicio,$fechaFin,$modalidad]);
    $results['ficha'] = 'creada';
} else {
    $db->prepare('UPDATE formacion SET id_programa=?,estado=?,modalidad=?,fecha_inicio=?,fecha_fin=? WHERE ficha=?')->execute([$idPrograma,$estadoFicha,$modalidad,$fechaInicio,$fechaFin,$ficha]);
    $results['ficha'] = 'actualizada';
}

// ──────────────────────────────────────────────────────────────────
// 3. PROCESAR FILAS
// ──────────────────────────────────────────────────────────────────
$db->beginTransaction();
try {

    $cacheComp  = []; // codigo → id_competencia
    $cacheRes   = []; // codigo → id_resultado
    $cacheApren = []; // documento → bool ya procesado

    foreach ($rows as $i => $row) {
        $tipoDocNom  = trim($row['tipo_documento']       ?? $row['Tipo de Documento']       ?? 'Cédula de Ciudadanía');
        $documento   = trim($row['numero_documento']      ?? $row['Número de Documento']      ?? '');
        $nombre      = strtoupper(trim($row['nombre']     ?? $row['Nombre']     ?? ''));
        $apellidos   = strtoupper(trim($row['apellidos']  ?? $row['Apellidos']  ?? ''));
        $estadoAp    = trim($row['estado']                ?? $row['Estado']                ?? 'Activo');
        $competencia = trim($row['competencia']           ?? $row['Competencia']           ?? '');
        $resultado   = trim($row['resultado_aprendizaje'] ?? $row['Resultado de Aprendizaje'] ?? '');
        $juicioNom   = trim($row['juicio_evaluacion']     ?? $row['Juicio de Evaluación']   ?? $row['Juicio de Evaluacion'] ?? '');
        $fechaJuicio = trim($row['fecha_juicio'] ?? $row['Fecha y Hora de Juicio Evaluativo'] ?? $row['Fecha Registro'] ?? $row['Fecha y Hora de Registro'] ?? '');
        $funcionario = trim($row['funcionario']           ?? $row['Funcionario que Resuelve el Juicio Evaluativo'] ?? '');

        if (!$documento || !$nombre) { $results['errores'][] = "Fila ".($i+1).": sin documento o nombre"; continue; }

        // ── Aprendiz (solo 1 vez por documento) ───────────────────
        if (!isset($cacheApren[$documento])) {
            $chk = $db->prepare('SELECT 1 FROM Aprendiz WHERE documento=? LIMIT 1');
            $chk->execute([$documento]);
            $exists = (bool)$chk->fetchColumn();

            $db->prepare('INSERT INTO Aprendiz (documento,tipo_doc,nombre,apellidos,estado,ficha) VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE tipo_doc=VALUES(tipo_doc),nombre=VALUES(nombre),apellidos=VALUES(apellidos),estado=VALUES(estado),ficha=VALUES(ficha)')
               ->execute([$documento,$tipoDocNom,$nombre,$apellidos,$estadoAp ?: 'Activo',$ficha]);

            if ($exists) $results['aprendices']['actualizados']++;
            else         $results['aprendices']['insertados']++;
            $cacheApren[$documento] = true;
        }

        // ── Competencia ───────────────────────────────────────────
        if (!$competencia) continue;

        $codigoComp = $competencia;
        $nombreComp = $competencia;
        if (preg_match('/^(\S+)\s*[-–]\s*(.+)/', $competencia, $mc)) {
            $codigoComp = trim($mc[1]);
            $nombreComp = trim($mc[2]);
        }

        if (!isset($cacheComp[$codigoComp])) {
            $chkC = $db->prepare('SELECT id_competencia FROM Competencias WHERE codigo=? LIMIT 1');
            $chkC->execute([$codigoComp]);
            $idComp = $chkC->fetchColumn();
            if (!$idComp) {
                $db->prepare('INSERT INTO Competencias (codigo,nombre,duracion_horas) VALUES (?,?,0)')->execute([$codigoComp,$nombreComp]);
                $idComp = $db->lastInsertId();
                $results['competencias']['insertadas']++;
            } else {
                $results['competencias']['ya_existian']++;
            }
            $db->prepare('INSERT IGNORE INTO programa_competencia (id_programa,id_competencia) VALUES (?,?)')->execute([$idPrograma,$idComp]);
            $cacheComp[$codigoComp] = (int)$idComp;
        }
        $idCompetencia = $cacheComp[$codigoComp];

        // ── Resultado de aprendizaje ──────────────────────────────
        if (!$resultado) continue;

        $codigoRes = $resultado;
        $descRes   = $resultado;
        if (preg_match('/^(\S+)\s*[-–]\s*(.+)/', $resultado, $mr)) {
            $codigoRes = trim($mr[1]);
            $descRes   = trim($mr[2]);
        }

        if (!isset($cacheRes[$codigoRes])) {
            $chkR = $db->prepare('SELECT id_resultado FROM Resultados_aprendizaje WHERE codigo=? LIMIT 1');
            $chkR->execute([$codigoRes]);
            $idRes = $chkR->fetchColumn();
            if (!$idRes) {
                $db->prepare('INSERT INTO Resultados_aprendizaje (id_competencia,codigo,descripcion) VALUES (?,?,?)')->execute([$idCompetencia,$codigoRes,$descRes]);
                $idRes = $db->lastInsertId();
                $results['resultados']['insertados']++;
            } else {
                $results['resultados']['ya_existian']++;
            }
            $cacheRes[$codigoRes] = (int)$idRes;
        }
        $idResultado = $cacheRes[$codigoRes];
        $db->prepare('INSERT IGNORE INTO programa_resultado (id_programa,id_resultado) VALUES (?,?)')->execute([$idPrograma,$idResultado]);

        // ── Juicio evaluativo ─────────────────────────────────────
        if (!$juicioNom) { $results['juicios']['sin_juicio']++; continue; }

        // Funcionario: buscar por nombre o insertar placeholder
        $docFuncionario = '00000000';
        if ($funcionario) {
            $tipoDocF = 'Cédula de Ciudadanía';
            $numDocF = null;
            $nombresApellidosF = trim($funcionario);
            
            // Formato de Sofia Plus suele ser: "CC 78759532 - GUSTAVO ADOLFO JURIS TORREGROSA"
            // o a veces sin tipo de doc: "1117523028 - OSCAR CAMILO CASTRO MOPAN"
            if (preg_match('/^(?:([A-Za-z]+)\s*[-–]?\s*)?(\d+)\s*[-–]\s*(.+)$/u', $funcionario, $m)) {
                $tipoDocAbr = strtoupper(trim($m[1]));
                if ($tipoDocAbr === 'CC') $tipoDocF = 'Cédula de Ciudadanía';
                elseif ($tipoDocAbr === 'TI') $tipoDocF = 'Tarjeta de Identidad';
                elseif ($tipoDocAbr === 'CE') $tipoDocF = 'Cédula de Extranjería';
                elseif ($tipoDocAbr === 'PEP') $tipoDocF = 'PEP';
                
                $numDocF = $m[2];
                $nombresApellidosF = trim($m[3]);
            }
            
            // Dividir nombre y apellidos (aproximación)
            $partes = explode(' ', $nombresApellidosF);
            $nomF = $nombresApellidosF;
            $apF = '';
            
            if (count($partes) >= 3) {
                // Asumimos los primeros dos son nombres, el resto apellidos
                $nomF = $partes[0] . ' ' . $partes[1];
                $apF = implode(' ', array_slice($partes, 2));
            } elseif (count($partes) == 2) {
                $nomF = $partes[0];
                $apF = $partes[1];
            }
            
            if ($numDocF) {
                // Si encontramos un documento real, insertamos o actualizamos (por si antes estaba mal o si cambió nombre)
                $db->prepare('INSERT INTO Funcionarios (documento,tipo_doc,nombre_completo) VALUES (?,?,?)
                              ON DUPLICATE KEY UPDATE tipo_doc=VALUES(tipo_doc), nombre_completo=VALUES(nombre_completo)')->execute([$numDocF, $tipoDocF, trim($nomF . ' ' . $apF)]);
                $docFuncionario = $numDocF;
            } else {
                // No se pudo extraer el documento, buscar por nombre o crear con ID Ficticio
                $stmtF  = $db->prepare("SELECT documento FROM Funcionarios WHERE LOWER(nombre_completo) LIKE ? LIMIT 1");
                $stmtF->execute(['%'.strtolower($nombresApellidosF).'%']);
                $docF   = $stmtF->fetchColumn();
                
                if ($docF) {
                    $docFuncionario = $docF;
                } else {
                    $docPh = 'F' . substr(md5($funcionario), 0, 9);
                    $db->prepare('INSERT IGNORE INTO Funcionarios (documento,tipo_doc,nombre_completo) VALUES (?,?,?)')->execute([$docPh, $tipoDocF, trim($nomF . ' ' . $apF)]);
                    $docFuncionario = $docPh;
                }
            }
        }

        // Convertir fecha de Excel (puede venir como string DD/MM/YYYY o como número serie de Excel)
        $fechaSQL = null;
        if ($fechaJuicio) {
            if (is_numeric($fechaJuicio) && $fechaJuicio > 30000) {
                // Es un número de serie de Excel
                $unixDate = ($fechaJuicio - 25569) * 86400;
                $fechaSQL = gmdate("Y-m-d H:i:s", $unixDate);
            } elseif (preg_match('#(\d{4})[/-](\d{1,2})[/-](\d{1,2})#', $fechaJuicio, $mf)) {
                // Formato YYYY-MM-DD
                $fechaSQL = "{$mf[1]}-{$mf[2]}-{$mf[3]}";
                if (preg_match('/(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?/', $fechaJuicio, $mh)) {
                    $hours = str_pad($mh[1], 2, "0", STR_PAD_LEFT);
                    $mins  = str_pad($mh[2], 2, "0", STR_PAD_LEFT);
                    $secs  = str_pad($mh[3] ?? "0", 2, "0", STR_PAD_LEFT);
                    $fechaSQL .= " {$hours}:{$mins}:{$secs}";
                } else {
                    $fechaSQL .= " 00:00:00";
                }
            } elseif (preg_match('#(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})#', $fechaJuicio, $mf)) {
                $day   = str_pad($mf[1], 2, "0", STR_PAD_LEFT);
                $month = str_pad($mf[2], 2, "0", STR_PAD_LEFT);
                $year  = $mf[3];
                if (strlen($year) == 2) $year = "20" . $year;
                
                $fechaSQL = "{$year}-{$month}-{$day}";
                
                // Extraer hora si existe (HH:MM:SS o HH:MM)
                if (preg_match('/(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?/', $fechaJuicio, $mh)) {
                    $hours = str_pad($mh[1], 2, "0", STR_PAD_LEFT);
                    $mins  = str_pad($mh[2], 2, "0", STR_PAD_LEFT);
                    $secs  = str_pad($mh[3] ?? "0", 2, "0", STR_PAD_LEFT);
                    $fechaSQL .= " {$hours}:{$mins}:{$secs}";
                } else {
                    $fechaSQL .= " 00:00:00";
                }
            }
        }

        $prevJ = $db->prepare('SELECT 1 FROM juicios_evaluativos WHERE documento_aprendiz=? AND id_resultado=? LIMIT 1');
        $prevJ->execute([$documento,$idResultado]);
        $isUpdate = (bool)$prevJ->fetchColumn();

        // Si no hay fecha, guardamos NULL explícitamente en lugar de CURRENT_TIMESTAMP
        $stmtJ = $db->prepare('INSERT INTO juicios_evaluativos (documento_aprendiz,id_resultado,documento_funcionario,estado,fecha_registro)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE estado=VALUES(estado),documento_funcionario=VALUES(documento_funcionario),fecha_registro=VALUES(fecha_registro)');
        $stmtJ->execute([$documento,$idResultado,$docFuncionario,$juicioNom,$fechaSQL]);

        if ($isUpdate) $results['juicios']['actualizados']++;
        else           $results['juicios']['insertados']++;
    }

    $db->commit();
    $results['ok'] = true;
    echo json_encode($results);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
