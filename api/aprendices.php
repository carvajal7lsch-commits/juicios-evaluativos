<?php
// ================================================================
// API — Aprendices (CRUD + carga masiva)
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET ──────────────────────────────────────────────────────────
if ($method === 'GET') {
  switch ($action) {
    case 'list':
      $stmt = $db->query('
        SELECT a.documento, a.nombre, a.apellidos, a.ficha,
               a.tipo_doc, a.estado,
               f.id_programa, p.nombre AS programa
        FROM Aprendiz a
        JOIN formacion f ON a.ficha = f.ficha
        JOIN Programa p ON f.id_programa = p.id_programa
        ORDER BY a.apellidos, a.nombre
      ');
      echo json_encode($stmt->fetchAll());
      break;

    case 'tipos_doc':
      echo json_encode([
        ['nombre' => 'Cédula de Ciudadanía'],
        ['nombre' => 'Tarjeta de Identidad'],
        ['nombre' => 'Cédula de Extranjería'],
        ['nombre' => 'Pasaporte'],
        ['nombre' => 'NUIP'],
      ]);
      break;

    case 'estados':
      echo json_encode([
        ['nombre' => 'Activo'],
        ['nombre' => 'Retiro Voluntario'],
        ['nombre' => 'Deserción'],
        ['nombre' => 'Trasladado'],
        ['nombre' => 'Graduado'],
        ['nombre' => 'Cancelado'],
      ]);
      break;

    case 'fichas':
      echo json_encode($db->query('
        SELECT f.ficha, p.nombre AS programa, f.estado
        FROM formacion f
        JOIN Programa p ON f.id_programa = p.id_programa
        ORDER BY f.ficha
      ')->fetchAll());
      break;

    default:
      http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
  }
  exit;
}

// ── POST ─────────────────────────────────────────────────────────
if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  switch ($action) {
    case 'save':
      $required = ['documento','tipo_doc','nombre','apellidos','estado','ficha'];
      foreach ($required as $f) {
        if (empty($body[$f])) { http_response_code(422); echo json_encode(['error' => "Campo '$f' es requerido"]); exit; }
      }
      $stmt = $db->prepare('
        INSERT INTO Aprendiz (documento, tipo_doc, nombre, apellidos, estado, ficha)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          tipo_doc=VALUES(tipo_doc), nombre=VALUES(nombre), apellidos=VALUES(apellidos),
          estado=VALUES(estado), ficha=VALUES(ficha)
      ');
      $stmt->execute([
        $body['documento'], $body['tipo_doc'], strtoupper($body['nombre']),
        strtoupper($body['apellidos']), $body['estado'], $body['ficha']
      ]);
      echo json_encode(['ok' => true, 'documento' => $body['documento']]);
      break;

    case 'bulk':
      $rows    = $body['rows'] ?? [];
      $results = ['insertados' => 0, 'actualizados' => 0, 'errores' => []];

      $db->beginTransaction();
      try {
        $stmt = $db->prepare('
          INSERT INTO Aprendiz (documento, tipo_doc, nombre, apellidos, estado, ficha)
          VALUES (?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE
            tipo_doc=VALUES(tipo_doc), nombre=VALUES(nombre), apellidos=VALUES(apellidos),
            estado=VALUES(estado), ficha=VALUES(ficha)
        ');

        foreach ($rows as $i => $row) {
          $doc     = trim($row['documento'] ?? $row['DOCUMENTO'] ?? $row['Documento'] ?? '');
          $nombre  = trim($row['nombre']    ?? $row['NOMBRE']    ?? $row['Nombre'] ?? '');
          $apells  = trim($row['apellidos'] ?? $row['APELLIDOS'] ?? $row['Apellidos'] ?? '');
          $ficha   = trim($row['ficha']     ?? $row['FICHA']     ?? $row['Ficha'] ?? '');
          $tipoDoc = trim($row['tipo_documento'] ?? $row['TIPO_DOCUMENTO'] ?? $row['Tipo Documento'] ?? 'Cédula de Ciudadanía');
          $estado  = trim($row['estado']    ?? $row['ESTADO']    ?? $row['Estado'] ?? 'Activo');

          if (!$doc || !$nombre || !$ficha) {
            $results['errores'][] = "Fila " . ($i+1) . ": faltan campos obligatorios";
            continue;
          }

          $fichaExists = $db->prepare('SELECT 1 FROM formacion WHERE ficha=? LIMIT 1');
          $fichaExists->execute([$ficha]);
          if (!$fichaExists->fetchColumn()) {
            $results['errores'][] = "Fila " . ($i+1) . ": ficha '$ficha' no existe en el sistema";
            continue;
          }

          $prevStmt = $db->prepare('SELECT 1 FROM Aprendiz WHERE documento=? LIMIT 1');
          $prevStmt->execute([$doc]);
          $isUpdate = (bool)$prevStmt->fetchColumn();

          $stmt->execute([$doc, $tipoDoc, strtoupper($nombre), strtoupper($apells), $estado, $ficha]);

          if ($isUpdate) $results['actualizados']++;
          else           $results['insertados']++;
        }
        $db->commit();
      } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
      }
      echo json_encode($results);
      break;

    default:
      http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
  }
  exit;
}

// ── DELETE ───────────────────────────────────────────────────────
if ($method === 'DELETE') {
  $doc = $_GET['documento'] ?? '';
  if (!$doc) { http_response_code(422); echo json_encode(['error'=>'Documento requerido']); exit; }

  $check = $db->prepare('SELECT COUNT(*) FROM juicios_evaluativos WHERE documento_aprendiz=?');
  $check->execute([$doc]);
  if ($check->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error'=>'El aprendiz tiene juicios registrados, no se puede eliminar.']);
    exit;
  }
  $db->prepare('DELETE FROM Aprendiz WHERE documento=?')->execute([$doc]);
  echo json_encode(['ok'=>true]);
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Método no permitido']);
