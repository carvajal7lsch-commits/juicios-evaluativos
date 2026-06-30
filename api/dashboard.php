<?php
// ================================================================
// API — Dashboard KPIs (con filtros globales)
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

$db     = getDB();
$action = $_GET['action'] ?? 'kpis';

$g_ficha = $_GET['global_ficha'] ?? '';
$g_prog  = $_GET['global_programa'] ?? '';
$g_search= $_GET['global_search'] ?? '';

$whereGlobal = ['1=1'];
$paramsGlobal = [];

if ($g_ficha) {
    $whereGlobal[] = "f.ficha = ?";
    $paramsGlobal[] = $g_ficha;
}
if ($g_prog) {
    $whereGlobal[] = "f.id_programa = ?";
    $paramsGlobal[] = $g_prog;
}
if ($g_search) {
    $whereGlobal[] = "(a.documento LIKE ? OR a.nombre LIKE ? OR a.apellidos LIKE ?)";
    $paramsGlobal[] = "%$g_search%";
    $paramsGlobal[] = "%$g_search%";
    $paramsGlobal[] = "%$g_search%";
}

$whereStr = implode(' AND ', $whereGlobal);

switch ($action) {

  case 'kpis':
    // Aprendices
    $stmt = $db->prepare("SELECT COUNT(a.documento) FROM Aprendiz a JOIN formacion f ON a.ficha = f.ficha WHERE $whereStr");
    $stmt->execute($paramsGlobal);
    $totalAprendices = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(a.documento) FROM Aprendiz a JOIN formacion f ON a.ficha = f.ficha WHERE a.estado='Activo' AND $whereStr");
    $stmt->execute($paramsGlobal);
    $totalActivos = $stmt->fetchColumn();

    // Juicios
    $stmt = $db->prepare("SELECT COUNT(j.id_juicio) FROM juicios_evaluativos j JOIN Aprendiz a ON j.documento_aprendiz = a.documento JOIN formacion f ON a.ficha = f.ficha WHERE $whereStr");
    $stmt->execute($paramsGlobal);
    $totalJuicios = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(j.id_juicio) FROM juicios_evaluativos j JOIN Aprendiz a ON j.documento_aprendiz = a.documento JOIN formacion f ON a.ficha = f.ficha WHERE j.estado='Aprobado' AND $whereStr");
    $stmt->execute($paramsGlobal);
    $totalAprobados = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(j.id_juicio) FROM juicios_evaluativos j JOIN Aprendiz a ON j.documento_aprendiz = a.documento JOIN formacion f ON a.ficha = f.ficha WHERE j.estado='No Aprobado' AND $whereStr");
    $stmt->execute($paramsGlobal);
    $totalNoAprobados = $stmt->fetchColumn();

    // Fichas
    $stmt = $db->prepare("SELECT COUNT(DISTINCT f.ficha) FROM formacion f LEFT JOIN Aprendiz a ON f.ficha = a.ficha WHERE $whereStr");
    $stmt->execute($paramsGlobal);
    $totalFichas = $stmt->fetchColumn();

    // Competencias
    if ($g_ficha || $g_prog || $g_search) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT pc.id_competencia) FROM programa_competencia pc JOIN formacion f ON pc.id_programa = f.id_programa LEFT JOIN Aprendiz a ON f.ficha = a.ficha WHERE $whereStr");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM Competencias");
    }
    $stmt->execute($paramsGlobal);
    $totalCompetencias = $stmt->fetchColumn();

    // Pendientes (Total Posibles - Total Juicios)
    $stmt = $db->prepare("
      SELECT COUNT(*) FROM Aprendiz a
      JOIN formacion f ON a.ficha = f.ficha
      JOIN programa_competencia pc ON f.id_programa = pc.id_programa
      JOIN Resultados_aprendizaje r ON pc.id_competencia = r.id_competencia
      WHERE $whereStr
    ");
    $stmt->execute($paramsGlobal);
    $totalPosibles = $stmt->fetchColumn();

    $pendientes = max(0, $totalPosibles - $totalJuicios);

    echo json_encode([
      'total_aprendices'   => (int)$totalAprendices,
      'activos'            => (int)$totalActivos,
      'total_juicios'      => (int)$totalJuicios,
      'aprobados'          => (int)$totalAprobados,
      'no_aprobados'       => (int)$totalNoAprobados,
      'pendientes'         => (int)$pendientes,
      'total_fichas'       => (int)$totalFichas,
      'total_competencias' => (int)$totalCompetencias,
      'pct_aprobacion'     => $totalJuicios > 0 ? round($totalAprobados / $totalJuicios * 100, 1) : 0,
    ]);
    break;

  case 'aprendices_por_ficha':
    $stmt = $db->prepare("
      SELECT f.ficha, p.nombre AS programa, COUNT(a.documento) AS total,
             SUM(CASE WHEN a.estado='Activo' THEN 1 ELSE 0 END) AS activos
      FROM formacion f
      JOIN Programa p ON f.id_programa = p.id_programa
      LEFT JOIN Aprendiz a ON a.ficha = f.ficha
      WHERE $whereStr
      GROUP BY f.ficha, p.nombre
      ORDER BY total DESC
    ");
    $stmt->execute($paramsGlobal);
    echo json_encode($stmt->fetchAll());
    break;

  case 'juicios_por_tipo':
    $stmt = $db->prepare("
      SELECT j.estado AS nombre, COUNT(j.id_juicio) AS total
      FROM juicios_evaluativos j
      JOIN Aprendiz a ON j.documento_aprendiz = a.documento
      JOIN formacion f ON a.ficha = f.ficha
      WHERE $whereStr
      GROUP BY j.estado
    ");
    $stmt->execute($paramsGlobal);
    echo json_encode($stmt->fetchAll());
    break;

  case 'avance_competencias':
    $stmt = $db->prepare("
      SELECT c.nombre AS competencia,
             COUNT(DISTINCT r.id_resultado) AS total_resultados,
             COUNT(DISTINCT CASE WHEN j.estado='Aprobado' THEN j.id_juicio END) AS aprobados
      FROM formacion f
      JOIN programa_competencia pc ON f.id_programa = pc.id_programa
      JOIN Competencias c ON pc.id_competencia = c.id_competencia
      JOIN Resultados_aprendizaje r ON r.id_competencia = c.id_competencia
      LEFT JOIN Aprendiz a ON a.ficha = f.ficha
      LEFT JOIN juicios_evaluativos j ON j.id_resultado = r.id_resultado AND j.documento_aprendiz = a.documento
      WHERE $whereStr
      GROUP BY c.id_competencia, c.nombre
      ORDER BY c.nombre
    ");
    $stmt->execute($paramsGlobal);
    echo json_encode($stmt->fetchAll());
    break;

  case 'tabla_aprendices':
    $search = '%' . ($_GET['search'] ?? '') . '%';

    $where  = $whereGlobal;
    $params = $paramsGlobal;

    if ($_GET['search'] ?? '') {
      $where[]  = '(a.nombre LIKE ? OR a.apellidos LIKE ? OR a.documento LIKE ?)';
      $params[] = $search; $params[] = $search; $params[] = $search;
    }

    $whereSQL = implode(' AND ', $where);
    $stmt = $db->prepare("
      SELECT a.documento, a.nombre, a.apellidos, a.ficha,
             a.estado, p.nombre AS programa,
             COUNT(DISTINCT ra.id_resultado) AS total_resultados,
             COUNT(DISTINCT CASE WHEN j.estado='Aprobado' THEN j.id_juicio END) AS aprobados,
             COUNT(DISTINCT j.id_juicio) AS evaluados
      FROM Aprendiz a
      JOIN formacion f ON a.ficha = f.ficha
      JOIN Programa p ON f.id_programa = p.id_programa
      LEFT JOIN programa_competencia pc ON pc.id_programa = f.id_programa
      LEFT JOIN Resultados_aprendizaje ra ON ra.id_competencia = pc.id_competencia
      LEFT JOIN juicios_evaluativos j ON j.documento_aprendiz = a.documento AND j.id_resultado = ra.id_resultado
      WHERE $whereSQL
      GROUP BY a.documento, a.nombre, a.apellidos, a.ficha, a.estado, p.nombre
      ORDER BY a.apellidos, a.nombre
      LIMIT 100
    ");
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    break;

  case 'get_filtros_globales':
    // Helper para poblar los selects en el frontend
    $programas = $db->query('SELECT id_programa, nombre FROM Programa ORDER BY nombre')->fetchAll();
    $fichas    = $db->query('SELECT f.ficha, p.id_programa, p.nombre AS programa FROM formacion f JOIN Programa p ON f.id_programa = p.id_programa ORDER BY f.ficha')->fetchAll();
    echo json_encode(['programas' => $programas, 'fichas' => $fichas]);
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
}
