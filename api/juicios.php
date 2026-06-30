<?php
// ================================================================
// API — Juicios Evaluativos
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
  switch ($action) {
    case 'list':
      $stmt = $db->query('
        SELECT j.id_juicio, j.documento_aprendiz, j.fecha_registro, j.estado,
               CONCAT(a.nombre," ",a.apellidos) AS aprendiz,
               r.codigo AS cod_resultado, r.descripcion AS resultado,
               c.nombre AS competencia,
               fu.nombre_completo AS funcionario
        FROM juicios_evaluativos j
        JOIN Aprendiz a ON j.documento_aprendiz = a.documento
        JOIN Resultados_aprendizaje r ON j.id_resultado = r.id_resultado
        JOIN Competencias c ON r.id_competencia = c.id_competencia
        LEFT JOIN Funcionarios fu ON j.documento_funcionario = fu.documento
        ORDER BY j.fecha_registro DESC
        LIMIT 500
      ');
      echo json_encode($stmt->fetchAll());
      break;

    case 'tipos':
      $rows = $db->query('SELECT DISTINCT estado FROM juicios_evaluativos ORDER BY estado')->fetchAll(PDO::FETCH_COLUMN);
      if (empty($rows)) $rows = ['Aprobado', 'No Aprobado', 'Pendiente', 'En proceso'];
      echo json_encode(array_map(fn($e) => ['nombre' => $e], $rows));
      break;

    case 'funcionarios':
      echo json_encode($db->query('
        SELECT documento, nombre_completo FROM Funcionarios ORDER BY nombre_completo
      ')->fetchAll());
      break;

    case 'search_aprendices':
      $q = $_GET['q'] ?? '';
      if (strlen($q) < 2) { echo json_encode([]); break; }
      $stmt = $db->prepare('
        SELECT a.documento, a.nombre, a.apellidos, a.estado, a.ficha, p.nombre AS programa
        FROM Aprendiz a
        JOIN formacion f ON a.ficha = f.ficha
        JOIN Programa p ON f.id_programa = p.id_programa
        WHERE a.documento LIKE ? OR CONCAT(a.nombre, " ", a.apellidos) LIKE ?
        LIMIT 20
      ');
      $stmt->execute(["%$q%", "%$q%"]);
      echo json_encode($stmt->fetchAll());
      break;

    case 'resultados_by_aprendiz':
      $doc = $_GET['documento'] ?? '';
      if (!$doc) { echo json_encode([]); break; }
      $stmt = $db->prepare('
        SELECT r.id_resultado, r.codigo, r.descripcion, c.nombre AS competencia,
               j.id_juicio, j.estado AS juicio_actual
        FROM Aprendiz a
        JOIN formacion f ON a.ficha = f.ficha
        JOIN programa_competencia pc ON pc.id_programa = f.id_programa
        JOIN Resultados_aprendizaje r ON r.id_competencia = pc.id_competencia
        JOIN Competencias c ON c.id_competencia = r.id_competencia
        LEFT JOIN juicios_evaluativos j ON j.documento_aprendiz = a.documento AND j.id_resultado = r.id_resultado
        WHERE a.documento = ?
        ORDER BY c.nombre, r.codigo
      ');
      $stmt->execute([$doc]);
      echo json_encode($stmt->fetchAll());
      break;

    default:
      http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
  }
  exit;
}

if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  switch ($action) {
    case 'save':
      $required = ['documento_aprendiz','id_resultado','documento_funcionario','estado'];
      foreach ($required as $f) if (empty($body[$f])) { http_response_code(422); echo json_encode(['error'=>"$f requerido"]); exit; }

      $stmt = $db->prepare('
        INSERT INTO juicios_evaluativos (documento_aprendiz, id_resultado, documento_funcionario, estado)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          estado=VALUES(estado),
          documento_funcionario=VALUES(documento_funcionario),
          fecha_registro=CURRENT_TIMESTAMP
      ');
      $stmt->execute([$body['documento_aprendiz'], $body['id_resultado'], $body['documento_funcionario'], $body['estado']]);
      echo json_encode(['ok' => true]);
      break;

    case 'bulk':
      $rows    = $body['rows'] ?? [];
      $results = ['insertados'=>0,'actualizados'=>0,'errores'=>[]];

      $resultsMap = [];
      foreach ($db->query('SELECT id_resultado, LOWER(codigo) AS codigo FROM Resultados_aprendizaje')->fetchAll() as $r)
        $resultsMap[$r['codigo']] = $r['id_resultado'];

      $db->beginTransaction();
      try {
        $stmt = $db->prepare('
          INSERT INTO juicios_evaluativos (documento_aprendiz, id_resultado, documento_funcionario, estado)
          VALUES (?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE estado=VALUES(estado), documento_funcionario=VALUES(documento_funcionario), fecha_registro=CURRENT_TIMESTAMP
        ');

        foreach ($rows as $i => $row) {
          $docAprendiz    = trim($row['documento_aprendiz']    ?? $row['DOCUMENTO_APRENDIZ']   ?? '');
          $codResultado   = strtolower(trim($row['codigo_resultado'] ?? $row['CODIGO_RESULTADO'] ?? ''));
          $docFuncionario = trim($row['documento_funcionario']  ?? $row['DOCUMENTO_FUNCIONARIO'] ?? '');
          $estado         = trim($row['estado'] ?? $row['tipo_juicio'] ?? $row['TIPO_JUICIO'] ?? 'Pendiente');

          if (!$docAprendiz || !$codResultado) {
            $results['errores'][] = "Fila ".($i+1).": faltan datos obligatorios";
            continue;
          }

          $idResultado = $resultsMap[$codResultado] ?? null;
          if (!$idResultado) { $results['errores'][] = "Fila ".($i+1).": código resultado '$codResultado' no encontrado"; continue; }

          $aCheck = $db->prepare('SELECT 1 FROM Aprendiz WHERE documento=? LIMIT 1');
          $aCheck->execute([$docAprendiz]);
          if (!$aCheck->fetchColumn()) { $results['errores'][] = "Fila ".($i+1).": aprendiz '$docAprendiz' no registrado"; continue; }

          $prevStmt = $db->prepare('SELECT 1 FROM juicios_evaluativos WHERE documento_aprendiz=? AND id_resultado=? LIMIT 1');
          $prevStmt->execute([$docAprendiz, $idResultado]);
          $isUpdate = (bool)$prevStmt->fetchColumn();

          $stmt->execute([$docAprendiz, $idResultado, $docFuncionario ?: '00000000', $estado]);
          if ($isUpdate) $results['actualizados']++; else $results['insertados']++;
        }
        $db->commit();
      } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error'=>$e->getMessage()]);
        exit;
      }
      echo json_encode($results);
      break;

    default:
      http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
  }
  exit;
}

if ($method === 'DELETE') {
  $id = $_GET['id'] ?? '';
  if (!$id) { http_response_code(422); echo json_encode(['error'=>'id requerido']); exit; }
  $db->prepare('DELETE FROM juicios_evaluativos WHERE id_juicio=?')->execute([$id]);
  echo json_encode(['ok'=>true]);
  exit;
}
