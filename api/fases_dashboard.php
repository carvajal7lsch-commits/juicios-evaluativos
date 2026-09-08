<?php
/**
 * ================================================================
 * AVANCE POR FASES DEL PROYECTO FORMATIVO
 * ----------------------------------------------------------------
 * La versión anterior contaba a un aprendiz como "aprobado en la fase"
 * sólo si tenía el 100% de sus RAPs. Como nadie cierra una fase hasta el
 * final del programa, las cuatro fases marcaban 0% durante casi toda la
 * formación y el tablero no distinguía a quien llevaba 6 de 10 RAPs de
 * quien no había empezado.
 *
 * Aquí el avance es continuo (RAPs aprobados sobre RAPs evaluables) y el
 * aviso de riesgo es RELATIVO AL GRUPO, no a un calendario: se marca
 * rezagado a quien está por debajo de la mitad del avance mediano de su
 * propia ficha. Así una fase que el grupo entero aún no ha tocado no
 * genera alarmas falsas, y una fase donde 16 aprendices van al 60% y 5 al
 * 10% señala exactamente a esos 5.
 * ================================================================
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/estados.php';
require_once __DIR__ . '/../includes/proyecto.php';

$action = $_GET['action'] ?? '';
$db = getDB();

// Igual que api/proyecto.php: si la base viene de un schema.sql que borraba
// las tablas del proyecto formativo, se reconstruyen en el primer acceso.
if (!ensureProyectoSchema($db)) {
    jsonResponse(['error' => 'La estructura del proyecto formativo no está disponible en la base de datos. Revisa el log del servidor.'], 500);
}

/** Programa al que pertenece una ficha, o null si no existe. */
function programaDeFicha(PDO $db, string $ficha): ?int {
    $stmt = $db->prepare('SELECT id_programa FROM formacion WHERE ficha = ?');
    $stmt->execute([$ficha]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

/** Aprendices en formación de una ficha, indexados por documento. */
function aprendicesActivos(PDO $db, string $ficha): array {
    $sql = 'SELECT documento, nombre, apellidos FROM Aprendiz a
            WHERE a.ficha = ? AND ' . sqlAprendizActivo() . '
            ORDER BY apellidos, nombre';
    $stmt = $db->prepare($sql);
    $stmt->execute([$ficha]);

    $out = [];
    foreach ($stmt->fetchAll() as $a) {
        $out[$a['documento']] = $a;
    }
    return $out;
}

/** Mediana de una lista de números. Devuelve 0.0 si viene vacía. */
function mediana(array $valores): float {
    if (!$valores) {
        return 0.0;
    }
    sort($valores);
    $n = count($valores);
    $medio = intdiv($n, 2);
    return $n % 2 ? (float) $valores[$medio] : ($valores[$medio - 1] + $valores[$medio]) / 2;
}

/**
 * Umbral de rezago de un grupo: la mitad del avance mediano.
 *
 * Es deliberadamente relativo. Un umbral fijo ("menos del 50%") marcaría a
 * la ficha entera en marzo y a nadie en noviembre; éste sólo señala a quien
 * se está descolgando de su propio grupo. Si la mediana es 0 (fase que
 * nadie ha empezado) el umbral es 0 y no se marca a nadie.
 */
function umbralRezago(array $pcts): float {
    return mediana($pcts) / 2;
}

/** Reparte los avances en las cinco clases del gráfico apilado. */
function distribucion(array $pcts): array {
    $d = ['sin_iniciar' => 0, 'inicial' => 0, 'medio' => 0, 'avanzado' => 0, 'completo' => 0];
    foreach ($pcts as $p) {
        if ($p <= 0) {
            $d['sin_iniciar']++;
        } elseif ($p < 50) {
            $d['inicial']++;
        } elseif ($p < 80) {
            $d['medio']++;
        } elseif ($p < 100) {
            $d['avanzado']++;
        } else {
            $d['completo']++;
        }
    }
    return $d;
}

/**
 * RAPs evaluables de cada fase de un programa.
 *
 * Se aplana a pares (fase, RAP) DISTINTOS antes de cruzar con nada más: un
 * mismo RAP puede colgar de varias actividades de la fase, y contarlo dos
 * veces inflaría el denominador del avance.
 *
 * @return array<int, list<int>> id_fase => lista de id_resultado
 */
function rapsPorFase(PDO $db, int $idPrograma): array {
    $sql = 'SELECT DISTINCT f.id_fase, ar.id_resultado
            FROM fases_proyecto f
            JOIN actividades_proyecto ap ON ap.id_fase = f.id_fase
            JOIN actividad_resultado ar ON ar.id_actividad = ap.id_actividad
            WHERE f.id_programa = ?';
    $stmt = $db->prepare($sql);
    $stmt->execute([$idPrograma]);

    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $out[(int) $r['id_fase']][] = (int) $r['id_resultado'];
    }
    return $out;
}

/**
 * RAPs aprobados por cada aprendiz de la ficha en cada fase.
 *
 * @return array<int, array<string, int>> id_fase => documento => aprobados
 */
function aprobadosPorFase(PDO $db, int $idPrograma, string $ficha): array {
    $sql = "SELECT fr.id_fase, a.documento, COUNT(DISTINCT je.id_resultado) AS aprobados
            FROM (SELECT DISTINCT f.id_fase, ar.id_resultado
                  FROM fases_proyecto f
                  JOIN actividades_proyecto ap ON ap.id_fase = f.id_fase
                  JOIN actividad_resultado ar ON ar.id_actividad = ap.id_actividad
                  WHERE f.id_programa = ?) fr
            JOIN Aprendiz a ON a.ficha = ? AND " . sqlAprendizActivo() . "
            JOIN juicios_evaluativos je
                 ON je.documento_aprendiz = a.documento
                AND je.id_resultado = fr.id_resultado
                AND UPPER(TRIM(je.estado)) = 'APROBADO'
            GROUP BY fr.id_fase, a.documento";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idPrograma, $ficha]);

    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $out[(int) $r['id_fase']][$r['documento']] = (int) $r['aprobados'];
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // La ficha llega con el recuento de aprendices activos y de fases del
    // programa: la página necesita saber cuál abrir sola, y abrir una ficha
    // cuyo programa no tiene proyecto formativo es caer en un panel vacío.
    if ($action === 'fichas') {
        $sql = 'SELECT f.ficha, p.id_programa, p.nombre AS programa,
                       (SELECT COUNT(*) FROM Aprendiz a
                        WHERE a.ficha = f.ficha AND ' . sqlAprendizActivo() . ') AS aprendices,
                       (SELECT COUNT(*) FROM fases_proyecto fp
                        WHERE fp.id_programa = p.id_programa) AS fases
                FROM formacion f
                JOIN Programa p ON f.id_programa = p.id_programa
                ORDER BY f.ficha';

        $fichas = [];
        foreach ($db->query($sql)->fetchAll() as $f) {
            $f['aprendices'] = (int) $f['aprendices'];
            $f['fases']      = (int) $f['fases'];
            $fichas[] = $f;
        }
        jsonResponse($fichas);
    }

    // ── Panorama: una tarjeta por fase ──────────────────────────────
    if ($action === 'estadisticas_fases') {
        $ficha = $_GET['ficha'] ?? '';
        $idPrograma = $ficha !== '' ? programaDeFicha($db, $ficha) : null;
        if ($idPrograma === null) {
            jsonResponse(['fases' => [], 'total_aprendices' => 0]);
        }

        $aprendices = aprendicesActivos($db, $ficha);
        $totalAprendices = count($aprendices);

        $stmtFases = $db->prepare('SELECT id_fase, nombre_fase, descripcion
                                   FROM fases_proyecto WHERE id_programa = ?
                                   ORDER BY ' . sqlOrdenFases());
        $stmtFases->execute([$idPrograma]);
        $fases = $stmtFases->fetchAll();

        $rapsFase  = rapsPorFase($db, $idPrograma);
        $aprobados = aprobadosPorFase($db, $idPrograma, $ficha);

        $salida = [];
        foreach ($fases as $f) {
            $idFase    = (int) $f['id_fase'];
            $totalRaps = count($rapsFase[$idFase] ?? []);

            // Cada aprendiz activo puntúa, aunque no tenga ningún juicio:
            // los ausentes de la consulta son justamente los que van a 0.
            $pcts = [];
            foreach (array_keys($aprendices) as $doc) {
                $ok = $aprobados[$idFase][$doc] ?? 0;
                $pcts[] = $totalRaps > 0 ? ($ok / $totalRaps) * 100 : 0;
            }

            $umbral = umbralRezago($pcts);
            $rezagados = $umbral > 0 ? count(array_filter($pcts, static fn($p) => $p < $umbral)) : 0;

            $salida[] = [
                'id_fase'          => $idFase,
                'nombre_fase'      => $f['nombre_fase'],
                'total_raps'       => $totalRaps,
                'total_aprendices' => $totalAprendices,
                // Avance del grupo = media de los avances individuales.
                'pct_avance'       => $pcts ? (int) round(array_sum($pcts) / count($pcts)) : 0,
                'pct_mediana'      => (int) round(mediana($pcts)),
                'umbral_rezago'    => (int) round($umbral),
                'rezagados'        => $rezagados,
                'distribucion'     => distribucion($pcts),
            ];
        }

        jsonResponse(['fases' => $salida, 'total_aprendices' => $totalAprendices]);
    }

    // ── Detalle de una fase: quién va atrás y qué RAP la frena ──────
    if ($action === 'detalle_fase') {
        $ficha  = $_GET['ficha'] ?? '';
        $idFase = (int) ($_GET['id_fase'] ?? 0);
        if ($ficha === '' || $idFase <= 0) {
            jsonResponse(['error' => 'Falta la ficha o la fase.'], 400);
        }

        $stmtFase = $db->prepare('SELECT id_fase, nombre_fase FROM fases_proyecto WHERE id_fase = ?');
        $stmtFase->execute([$idFase]);
        $fase = $stmtFase->fetch();
        if (!$fase) {
            jsonResponse(['error' => 'La fase no existe.'], 404);
        }

        $aprendices = aprendicesActivos($db, $ficha);
        $totalAprendices = count($aprendices);

        // RAPs de la fase, con cuántos aprendices de ESTA ficha los aprobaron.
        $sqlRaps = "SELECT r.id_resultado, r.codigo, r.descripcion, c.nombre AS competencia,
                           COUNT(DISTINCT je.documento_aprendiz) AS aprobados
                    FROM actividades_proyecto ap
                    JOIN actividad_resultado ar ON ar.id_actividad = ap.id_actividad
                    JOIN Resultados_aprendizaje r ON r.id_resultado = ar.id_resultado
                    JOIN Competencias c ON c.id_competencia = r.id_competencia
                    LEFT JOIN juicios_evaluativos je
                         ON je.id_resultado = r.id_resultado
                        AND UPPER(TRIM(je.estado)) = 'APROBADO'
                        AND je.documento_aprendiz IN (
                            SELECT a.documento FROM Aprendiz a
                            WHERE a.ficha = ? AND " . sqlAprendizActivo() . ")
                    WHERE ap.id_fase = ?
                    GROUP BY r.id_resultado, r.codigo, r.descripcion, c.nombre";
        $stmtRaps = $db->prepare($sqlRaps);
        $stmtRaps->execute([$ficha, $idFase]);

        $raps = [];
        foreach ($stmtRaps->fetchAll() as $r) {
            $ok = (int) $r['aprobados'];
            $raps[] = [
                'id_resultado' => (int) $r['id_resultado'],
                'codigo'       => $r['codigo'],
                'descripcion'  => $r['descripcion'],
                'competencia'  => $r['competencia'],
                'aprobados'    => $ok,
                'pct'          => $totalAprendices > 0 ? (int) round(($ok / $totalAprendices) * 100) : 0,
            ];
        }
        // Los que frenan la fase, primero.
        usort($raps, static fn($a, $b) => [$a['pct'], $a['codigo']] <=> [$b['pct'], $b['codigo']]);
        $totalRaps = count($raps);

        // Avance de cada aprendiz dentro de la fase.
        $sqlAp = "SELECT je.documento_aprendiz, COUNT(DISTINCT je.id_resultado) AS aprobados
                  FROM juicios_evaluativos je
                  WHERE UPPER(TRIM(je.estado)) = 'APROBADO'
                    AND je.id_resultado IN (
                        SELECT ar.id_resultado FROM actividades_proyecto ap
                        JOIN actividad_resultado ar ON ar.id_actividad = ap.id_actividad
                        WHERE ap.id_fase = ?)
                  GROUP BY je.documento_aprendiz";
        $stmtAp = $db->prepare($sqlAp);
        $stmtAp->execute([$idFase]);

        $porDocumento = [];
        foreach ($stmtAp->fetchAll() as $r) {
            $porDocumento[$r['documento_aprendiz']] = (int) $r['aprobados'];
        }

        $filas = [];
        $pcts  = [];
        foreach ($aprendices as $doc => $a) {
            $ok  = $porDocumento[$doc] ?? 0;
            $pct = $totalRaps > 0 ? (int) round(($ok / $totalRaps) * 100) : 0;
            $pcts[] = $pct;
            $filas[] = [
                'documento'  => $doc,
                'nombre'     => trim($a['nombre'] . ' ' . $a['apellidos']),
                'aprobados'  => $ok,
                'pendientes' => max(0, $totalRaps - $ok),
                'pct'        => $pct,
            ];
        }

        $umbral = umbralRezago($pcts);
        foreach ($filas as &$fila) {
            $fila['rezagado'] = $umbral > 0 && $fila['pct'] < $umbral;
        }
        unset($fila);

        // Menor avance primero: la lista se lee de arriba abajo como
        // "a quién hay que citar".
        usort($filas, static fn($a, $b) => [$a['pct'], $a['nombre']] <=> [$b['pct'], $b['nombre']]);

        jsonResponse([
            'fase' => [
                'id_fase'          => (int) $fase['id_fase'],
                'nombre_fase'      => $fase['nombre_fase'],
                'total_raps'       => $totalRaps,
                'total_aprendices' => $totalAprendices,
                'umbral_rezago'    => (int) round($umbral),
                'pct_mediana'      => (int) round(mediana($pcts)),
            ],
            'aprendices' => $filas,
            'raps'       => $raps,
        ]);
    }
}

jsonResponse(['error' => 'Acción no válida'], 400);
