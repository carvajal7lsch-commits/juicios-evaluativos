<?php
// ================================================================
// API — Dashboard KPIs (con filtros globales)
// ================================================================
header('Content-Type: application/json; charset=utf-8');

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/estados.php';
require_once ROOT_PATH . '/includes/formacion.php';

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

// ----------------------------------------------------------------
// Filtro que sólo toca `formacion`. Las consultas analíticas de abajo
// usan LEFT JOIN sobre Aprendiz: meter una condición sobre `a.` en el
// WHERE las convertiría en INNER JOIN y perderíamos las fichas sin
// aprendices o los RAPs sin calificar, que es justo lo que queremos ver.
// ----------------------------------------------------------------
$whereFicha  = ['1=1'];
$paramsFicha = [];
if ($g_ficha) { $whereFicha[] = 'f.ficha = ?';       $paramsFicha[] = $g_ficha; }
if ($g_prog)  { $whereFicha[] = 'f.id_programa = ?'; $paramsFicha[] = $g_prog; }
$whereFichaStr = implode(' AND ', $whereFicha);

switch ($action) {

  case 'kpis':
    // Aprendices
    $stmt = $db->prepare("SELECT COUNT(a.documento) FROM Aprendiz a JOIN formacion f ON a.ficha = f.ficha WHERE $whereStr");
    $stmt->execute($paramsGlobal);
    $totalAprendices = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(a.documento) FROM Aprendiz a JOIN formacion f ON a.ficha = f.ficha WHERE " . sqlAprendizActivo() . " AND $whereStr");
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
      JOIN Competencias c ON c.id_competencia = pc.id_competencia
      JOIN Resultados_aprendizaje r ON r.id_competencia = c.id_competencia
      WHERE $whereStr AND " . sqlEsCompetenciaLectiva() . "
    ");
    $stmt->execute($paramsGlobal);
    $totalPosibles = $stmt->fetchColumn();

    // Trabajo realmente pendiente = todo lo que aún no está aprobado.
    // Incluye tanto los juicios marcados 'Por evaluar' como los pares
    // aprendiz-RAP que ni siquiera tienen fila. El cálculo anterior
    // (posibles − juicios) sólo contaba los segundos y dejaba fuera
    // los miles de registros explícitamente marcados por evaluar.
    $pendientes = max(0, $totalPosibles - $totalAprobados);

    echo json_encode([
      'total_aprendices'   => (int)$totalAprendices,
      'activos'            => (int)$totalActivos,
      'total_juicios'      => (int)$totalJuicios,
      'total_posibles'     => (int)$totalPosibles,
      'aprobados'          => (int)$totalAprobados,
      'no_aprobados'       => (int)$totalNoAprobados,
      'pendientes'         => (int)$pendientes,
      'total_fichas'       => (int)$totalFichas,
      'total_competencias' => (int)$totalCompetencias,
      'pct_aprobacion'     => $totalJuicios > 0 ? round($totalAprobados / $totalJuicios * 100, 1) : 0,
    ]);
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

  // ================================================================
  // DIAGRAMA DE RITMO — calendario consumido vs. avance real
  // Una fila por ficha. No usa fecha_registro: sólo el calendario de
  // la ficha y el conteo de RAPs aprobados, así que cubre el 100% de
  // los datos aunque la mayoría de juicios no tengan fecha.
  // ================================================================
  case 'ritmo_fichas':
    $stmt = $db->prepare("
      SELECT f.ficha,
             f.estado          AS estado_ficha,
             f.fecha_inicio,
             f.fecha_fin,
             p.nombre          AS programa,
             COUNT(DISTINCT CASE WHEN " . sqlAprendizActivo() . " THEN a.documento END) AS activos,
             COUNT(DISTINCT r.id_resultado)                                     AS raps_programa,
             COUNT(DISTINCT CASE WHEN j.estado = 'Aprobado'
                                 THEN CONCAT(j.documento_aprendiz, '-', j.id_resultado) END) AS aprobados
      FROM formacion f
      JOIN Programa p                  ON p.id_programa    = f.id_programa
      LEFT JOIN Aprendiz a             ON a.ficha          = f.ficha AND " . sqlAprendizActivo() . "
      LEFT JOIN programa_competencia pc ON pc.id_programa  = f.id_programa
      -- Sólo competencias de etapa lectiva: los RAPs de la etapa productiva
      -- se evalúan al final y contarlos ahora hunde el avance artificialmente.
      LEFT JOIN Competencias c         ON c.id_competencia = pc.id_competencia
                                      AND " . sqlEsCompetenciaLectiva() . "
      LEFT JOIN Resultados_aprendizaje r ON r.id_competencia = c.id_competencia
      LEFT JOIN juicios_evaluativos j  ON j.documento_aprendiz = a.documento
                                      AND j.id_resultado       = r.id_resultado
      WHERE $whereFichaStr
      GROUP BY f.ficha, f.estado, f.fecha_inicio, f.fecha_fin, p.nombre
      HAVING activos > 0 AND raps_programa > 0
      ORDER BY f.ficha
    ");
    $stmt->execute($paramsFicha);

    $hoy  = new DateTime('today');
    $out  = [];
    foreach ($stmt->fetchAll() as $r) {
        $ini = new DateTime($r['fecha_inicio']);
        $finFicha = new DateTime($r['fecha_fin']);

        // El horizonte es el cierre de la LECTIVA, no el de la ficha: los
        // juicios lectivos deben estar puestos antes de la etapa productiva.
        $finLectiva = finEtapaLectiva($r['fecha_fin']);

        $duracion = (int)$ini->diff($finLectiva)->days;
        if ($duracion <= 0 || $finLectiva <= $ini) continue; // fechas inconsistentes

        $transcurrido = (int)$ini->diff($hoy)->days;
        if ($hoy < $ini) $transcurrido = 0;

        $esperado = (int)$r['activos'] * (int)$r['raps_programa'];
        if ($esperado <= 0) continue;

        $pctCalendario = max(0, min(100, round($transcurrido / $duracion * 100, 1)));
        $pctAvance     = round((int)$r['aprobados'] / $esperado * 100, 1);
        $enProductiva  = $hoy > $finLectiva;

        $out[] = [
            'ficha'            => $r['ficha'],
            'programa'         => $r['programa'],
            'estado_ficha'     => $r['estado_ficha'],
            'fecha_inicio'     => $r['fecha_inicio'],
            'fecha_fin_ficha'  => $r['fecha_fin'],
            'fecha_fin_lectiva'=> $finLectiva->format('Y-m-d'),
            'activos'          => (int)$r['activos'],
            'raps_programa'    => (int)$r['raps_programa'],
            'aprobados'        => (int)$r['aprobados'],
            'esperado'         => $esperado,
            'pct_calendario'   => $pctCalendario,
            'pct_avance'       => $pctAvance,
            // Positivo = adelantada respecto a lo esperado; negativo = atrasada.
            'desvio'           => round($pctAvance - $pctCalendario, 1),
            // Días hasta el cierre de la LECTIVA (0 si ya está en productiva)
            'dias_restantes'   => $enProductiva ? 0 : (int)$hoy->diff($finLectiva)->days,
            'en_productiva'    => $enProductiva,
            'dias_fin_ficha'   => $hoy > $finFicha ? 0 : (int)$hoy->diff($finFicha)->days,
        ];
    }
    echo json_encode($out);
    break;

  // ================================================================
  // MAPA DE CALOR — adaptativo
  //   Con ficha seleccionada  -> Aprendiz x Competencia (vista de aula)
  //   Sin ficha seleccionada  -> Ficha x Competencia    (vista institucional)
  // Las columnas rojas señalan la competencia, no al aprendiz.
  // ================================================================
  case 'heatmap':
    $porAprendiz = $g_ficha !== '';

    if ($porAprendiz) {
        $sql = "
          SELECT a.documento                       AS fila_id,
                 CONCAT(a.nombre, ' ', a.apellidos) AS fila,
                 c.id_competencia,
                 c.codigo                          AS cod_comp,
                 c.nombre                          AS competencia,
                 COUNT(DISTINCT r.id_resultado)    AS total,
                 COUNT(DISTINCT CASE WHEN j.estado = 'Aprobado' THEN r.id_resultado END) AS aprobados
          FROM Aprendiz a
          JOIN formacion f                  ON f.ficha          = a.ficha
          JOIN programa_competencia pc      ON pc.id_programa   = f.id_programa
          JOIN Competencias c               ON c.id_competencia = pc.id_competencia
          JOIN Resultados_aprendizaje r     ON r.id_competencia = c.id_competencia
          LEFT JOIN juicios_evaluativos j   ON j.documento_aprendiz = a.documento
                                           AND j.id_resultado       = r.id_resultado
          WHERE $whereFichaStr AND " . sqlAprendizActivo() . "
            AND " . sqlEsCompetenciaLectiva() . "
          GROUP BY a.documento, fila, c.id_competencia, c.codigo, c.nombre
          ORDER BY a.apellidos, a.nombre, c.codigo
        ";
    } else {
        $sql = "
          SELECT f.ficha                        AS fila_id,
                 f.ficha                        AS fila,
                 c.id_competencia,
                 c.codigo                       AS cod_comp,
                 c.nombre                       AS competencia,
                 COUNT(DISTINCT CONCAT(a.documento, '-', r.id_resultado)) AS total,
                 COUNT(DISTINCT CASE WHEN j.estado = 'Aprobado'
                                     THEN CONCAT(a.documento, '-', r.id_resultado) END) AS aprobados
          FROM formacion f
          JOIN programa_competencia pc      ON pc.id_programa   = f.id_programa
          JOIN Competencias c               ON c.id_competencia = pc.id_competencia
          JOIN Resultados_aprendizaje r     ON r.id_competencia = c.id_competencia
          JOIN Aprendiz a                   ON a.ficha = f.ficha AND " . sqlAprendizActivo() . "
          LEFT JOIN juicios_evaluativos j   ON j.documento_aprendiz = a.documento
                                           AND j.id_resultado       = r.id_resultado
          WHERE $whereFichaStr AND " . sqlEsCompetenciaLectiva() . "
          GROUP BY f.ficha, c.id_competencia, c.codigo, c.nombre
          ORDER BY f.ficha, c.codigo
        ";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($paramsFicha);

    $filas = []; $cols = []; $celdas = [];
    foreach ($stmt->fetchAll() as $r) {
        $filas[$r['fila_id']] = $r['fila'];
        $cols[$r['id_competencia']] = ['codigo' => $r['cod_comp'], 'nombre' => $r['competencia']];
        $celdas[] = [
            'fila'      => $r['fila_id'],
            'col'       => (int)$r['id_competencia'],
            'total'     => (int)$r['total'],
            'aprobados' => (int)$r['aprobados'],
            'pct'       => (int)$r['total'] > 0 ? round((int)$r['aprobados'] / (int)$r['total'] * 100) : 0,
        ];
    }

    echo json_encode([
        'modo'    => $porAprendiz ? 'aprendiz' : 'ficha',
        'filas'   => array_map(fn($id, $n) => ['id' => (string)$id, 'nombre' => $n], array_keys($filas), $filas),
        'columnas'=> array_map(fn($id, $c) => ['id' => (int)$id] + $c, array_keys($cols), $cols),
        'celdas'  => $celdas,
    ]);
    break;

  // ================================================================
  // RAPs ATASCADOS — los que más se quedan sin evaluar
  // En esta base no existe 'No Aprobado': el cuello de botella real
  // no es reprobar, es no llegar a calificar.
  // ================================================================
  case 'raps_atascados':
    // Se agrupa por FICHA + RAP, nunca sólo por RAP: cada ficha tiene su
    // propio cronograma, así que mezclarlas haría pasar por "incompleto" un
    // RAP que una cohorte de 2023 ya cerró y otra de 2025 aún no ha visto.
    // La agregación va en subconsulta porque MySQL no admite un alias de
    // función de grupo dentro de una expresión del ORDER BY.
    // El resultado se agrupa por FICHA + COMPETENCIA, no por RAP suelto.
    // Un instructor califica la competencia entera de una sentada, así que
    // todos sus RAPs quedan con cifras idénticas: listarlos uno por uno
    // repetía el mismo hecho cuatro veces con códigos ilegibles.
    $stmt = $db->prepare("
      SELECT ficha,
             programa,
             competencia,
             cod_comp,
             COUNT(*)                  AS raps,          -- RAPs a medio calificar
             MAX(total)                AS aprendices,    -- tamaño del grupo activo
             MIN(aprobados)            AS aprob_min,
             MAX(aprobados)            AS aprob_max,
             SUM(total - aprobados)    AS faltantes      -- juicios por registrar
      FROM (
        SELECT f.ficha,
               p.nombre                        AS programa,
               c.nombre                        AS competencia,
               c.codigo                        AS cod_comp,
               r.id_resultado,
               COUNT(DISTINCT a.documento)     AS total,
               COUNT(DISTINCT CASE WHEN j.estado = 'Aprobado' THEN a.documento END) AS aprobados
        FROM formacion f
        JOIN Programa p                 ON p.id_programa    = f.id_programa
        JOIN programa_competencia pc    ON pc.id_programa   = f.id_programa
        JOIN Competencias c             ON c.id_competencia = pc.id_competencia
        JOIN Resultados_aprendizaje r   ON r.id_competencia = c.id_competencia
        JOIN Aprendiz a                 ON a.ficha = f.ficha AND " . sqlAprendizActivo() . "
        LEFT JOIN juicios_evaluativos j ON j.documento_aprendiz = a.documento
                                       AND j.id_resultado       = r.id_resultado
        WHERE $whereFichaStr AND " . sqlEsCompetenciaLectiva() . "
        GROUP BY f.ficha, p.nombre, c.nombre, c.codigo, r.id_resultado
        -- Sólo calificaciones INCOMPLETAS dentro de una misma ficha: con
        -- aprobados = 0 el RAP no se ha empezado (normal si va más adelante
        -- en el cronograma) y con aprobados = total ya está cerrado. El caso
        -- accionable es el de en medio: se calificó a parte del grupo.
        HAVING total >= 5 AND aprobados > 0 AND aprobados < total
      ) AS agg
      GROUP BY ficha, programa, competencia, cod_comp
      ORDER BY faltantes DESC
    ");
    $stmt->execute($paramsFicha);

    // La respuesta va agrupada POR FICHA. Sin agrupar, la lista mezclaba
    // competencias de programas distintos y no se entendía de qué grupo
    // hablaba cada fila.
    $porFicha = [];
    foreach ($stmt->fetchAll() as $r) {
        $ficha = $r['ficha'];
        $porFicha[$ficha] ??= [
            'ficha'     => $ficha,
            'programa'  => $r['programa'],
            'faltantes' => 0,
            'items'     => [],
        ];

        $aprendices = (int)$r['aprendices'];
        $aprobMin   = (int)$r['aprob_min'];
        $aprobMax   = (int)$r['aprob_max'];

        // Tope por ficha: la lista es un plan de trabajo, no un inventario
        if (count($porFicha[$ficha]['items']) < 5) {
            $porFicha[$ficha]['items'][] = [
                'competencia' => $r['competencia'],
                'cod_comp'    => $r['cod_comp'],
                'raps'        => (int)$r['raps'],
                'aprendices'  => $aprendices,
                'aprob_min'   => $aprobMin,
                'aprob_max'   => $aprobMax,
                // Los RAPs de una competencia suelen calificarse a la vez; si
                // no, se muestra el rango en vez de fingir precisión.
                'uniforme'    => $aprobMin === $aprobMax,
                'faltantes'   => (int)$r['faltantes'],
                'pct_hecho'   => $aprendices > 0 ? round($aprobMin / $aprendices * 100) : 0,
            ];
        }
        $porFicha[$ficha]['faltantes'] += (int)$r['faltantes'];
    }

    // Primero la ficha con más trabajo acumulado por registrar
    $porFicha = array_values($porFicha);
    usort($porFicha, fn($a, $b) => $b['faltantes'] <=> $a['faltantes']);
    echo json_encode($porFicha);
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
