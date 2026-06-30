<?php
// ================================================================
// API — Competencias y Resultados
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
      echo json_encode($db->query('
        SELECT c.id_competencia, c.codigo, c.nombre, c.duracion_horas,
               COUNT(r.id_resultado) AS total_resultados
        FROM Competencias c
        LEFT JOIN Resultados_aprendizaje r ON r.id_competencia = c.id_competencia
        GROUP BY c.id_competencia ORDER BY c.codigo
      ')->fetchAll());
      break;

    case 'resultados':
      $idComp = $_GET['id_competencia'] ?? '';
      if ($idComp) {
        $stmt = $db->prepare('SELECT id_resultado, codigo, descripcion FROM Resultados_aprendizaje WHERE id_competencia=? ORDER BY codigo');
        $stmt->execute([$idComp]);
        echo json_encode($stmt->fetchAll());
      } else {
        echo json_encode($db->query('
          SELECT r.id_resultado, r.codigo, r.descripcion, c.nombre AS competencia
          FROM Resultados_aprendizaje r
          JOIN Competencias c ON r.id_competencia = c.id_competencia
          ORDER BY c.nombre, r.codigo
        ')->fetchAll());
      }
      break;

    case 'avance_aprendiz':
      $doc = $_GET['documento'] ?? '';
      if (!$doc) { echo json_encode([]); break; }
      $stmt = $db->prepare('
        SELECT c.id_competencia, c.nombre AS competencia, c.duracion_horas,
               COUNT(DISTINCT r.id_resultado) AS total_resultados,
               COUNT(DISTINCT CASE WHEN LOWER(TRIM(j.estado))="aprobado" THEN j.id_juicio END) AS aprobados,
               COUNT(DISTINCT CASE WHEN j.id_juicio IS NOT NULL THEN j.id_juicio END) AS evaluados
        FROM Aprendiz a
        JOIN formacion f ON a.ficha = f.ficha
        JOIN programa_resultado pr ON f.id_programa = pr.id_programa
        JOIN Resultados_aprendizaje r ON pr.id_resultado = r.id_resultado
        JOIN Competencias c ON r.id_competencia = c.id_competencia
        LEFT JOIN juicios_evaluativos j ON j.documento_aprendiz = a.documento AND j.id_resultado = r.id_resultado
        WHERE a.documento = ?
        GROUP BY c.id_competencia, c.nombre, c.duracion_horas
        ORDER BY c.nombre
      ');
      $stmt->execute([$doc]);
      echo json_encode($stmt->fetchAll());
      break;

    case 'detalle_aprendiz':
      $doc = $_GET['documento'] ?? '';
      if (!$doc) { echo json_encode([]); break; }
      $stmt = $db->prepare('
        SELECT DISTINCT c.nombre AS competencia, r.id_resultado, r.codigo, r.descripcion,
               j.estado AS juicio, j.fecha_registro,
               fu.nombre_completo AS funcionario
        FROM Aprendiz a
        JOIN formacion f ON a.ficha = f.ficha
        JOIN programa_resultado pr ON f.id_programa = pr.id_programa
        JOIN Resultados_aprendizaje r ON pr.id_resultado = r.id_resultado
        JOIN Competencias c ON r.id_competencia = c.id_competencia
        LEFT JOIN juicios_evaluativos j ON j.documento_aprendiz = a.documento AND j.id_resultado = r.id_resultado
        LEFT JOIN Funcionarios fu ON j.documento_funcionario = fu.documento
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
    case 'save_competencia':
      if (empty($body['codigo']) || empty($body['nombre'])) { http_response_code(422); echo json_encode(['error'=>'Campos requeridos']); exit; }
      $stmt = $db->prepare('INSERT INTO Competencias (codigo,nombre,duracion_horas) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),duracion_horas=VALUES(duracion_horas)');
      $stmt->execute([$body['codigo'],$body['nombre'],$body['duracion_horas']??0]);
      echo json_encode(['ok'=>true,'id'=>$db->lastInsertId()]);
      break;

    case 'save_resultado':
      if (empty($body['id_competencia'])||empty($body['codigo'])||empty($body['descripcion'])) { http_response_code(422); echo json_encode(['error'=>'Campos requeridos']); exit; }
      $stmt = $db->prepare('INSERT INTO Resultados_aprendizaje (id_competencia,codigo,descripcion) VALUES (?,?,?) ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion)');
      $stmt->execute([$body['id_competencia'],$body['codigo'],$body['descripcion']]);
      echo json_encode(['ok'=>true]);
      break;

    default:
      http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
  }
}
