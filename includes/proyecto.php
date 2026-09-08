<?php
/**
 * Auto-reparación del esquema del proyecto formativo.
 *
 * `sql/schema.sql` llegó a borrar estas tres tablas sin volver a crearlas,
 * así que toda base inicializada con aquella versión deja el módulo de
 * fases inservible:
 *   SQLSTATE[42S02] 1146 Table 'juicios_evaluativos.fases_proyecto' doesn't exist
 *
 * El esquema y la migración ya están corregidos, pero una base en
 * producción no vuelve a pasar por `docker-entrypoint-initdb.d`: su
 * volumen ya no está vacío. Antes había que abrir `api/migrate.php` a mano
 * en el navegador para arreglarlo, cosa que nadie recuerda hacer.
 *
 * Esta función cierra ese hueco: los endpoints del proyecto formativo la
 * llaman al arrancar y la estructura aparece sola en el primer acceso.
 * El coste normal es una consulta a `information_schema`; el DDL solo se
 * ejecuta cuando de verdad falta algo.
 */

/** Sentencias que crean la estructura. Espejo de `sql/migracion_proyecto_formativo.sql`. */
function proyectoSchemaDDL(): array {
    return [
        "CREATE TABLE IF NOT EXISTS `fases_proyecto` (
            `id_fase`       INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_programa`   INTEGER UNSIGNED NOT NULL,
            `nombre_fase`   VARCHAR(255) NOT NULL,
            `descripcion`   VARCHAR(500),
            `orden`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY(`id_fase`),
            KEY `idx_fase_programa` (`id_programa`, `orden`),
            CONSTRAINT `fk_fase_programa` FOREIGN KEY(`id_programa`)
                REFERENCES `Programa`(`id_programa`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `actividades_proyecto` (
            `id_actividad`      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_fase`           INTEGER UNSIGNED NOT NULL,
            `nombre_actividad`  VARCHAR(255) NOT NULL,
            `descripcion`       VARCHAR(500),
            `orden`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY(`id_actividad`),
            KEY `idx_actividad_fase` (`id_fase`, `orden`),
            CONSTRAINT `fk_actividad_fase` FOREIGN KEY(`id_fase`)
                REFERENCES `fases_proyecto`(`id_fase`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `actividad_resultado` (
            `id_actividad`  INTEGER UNSIGNED NOT NULL,
            `id_resultado`  INTEGER UNSIGNED NOT NULL,
            PRIMARY KEY(`id_actividad`, `id_resultado`),
            KEY `idx_ar_resultado` (`id_resultado`),
            CONSTRAINT `fk_ar_actividad` FOREIGN KEY(`id_actividad`)
                REFERENCES `actividades_proyecto`(`id_actividad`) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT `fk_ar_resultado` FOREIGN KEY(`id_resultado`)
                REFERENCES `Resultados_aprendizaje`(`id_resultado`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Bases creadas por la versión antigua de api/migrate.php, sin `orden`.
        "ALTER TABLE `fases_proyecto`
            ADD COLUMN IF NOT EXISTS `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0",
        "ALTER TABLE `actividades_proyecto`
            ADD COLUMN IF NOT EXISTS `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0",
    ];
}

/**
 * Garantiza que existan las tablas del proyecto formativo.
 *
 * @return bool true si la estructura está lista; false si faltaba y no se
 *              pudo crear (por ejemplo, si el usuario de BD no tiene DDL).
 */
function ensureProyectoSchema(PDO $db): bool {
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $sql = "SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND (   (table_name = 'fases_proyecto'       AND column_name IN ('id_fase', 'orden'))
                   OR (table_name = 'actividades_proyecto' AND column_name IN ('id_actividad', 'orden'))
                   OR (table_name = 'actividad_resultado'  AND column_name = 'id_actividad') )";

    try {
        // 5 columnas = las tres tablas presentes y ambas con `orden`.
        if ((int) $db->query($sql)->fetchColumn() === 5) {
            return $listo = true;
        }

        foreach (proyectoSchemaDDL() as $ddl) {
            $db->exec($ddl);
        }
        backfillOrdenFases($db);
        error_log('proyecto_schema: estructura del proyecto formativo creada automáticamente.');
        return $listo = true;
    } catch (PDOException $e) {
        error_log('proyecto_schema: no se pudo reparar el esquema: ' . $e->getMessage());
        return $listo = false;
    }
}

/**
 * Rellena `orden` en las fases que aún lo tienen en 0.
 *
 * Las fases creadas antes de que existiera la columna se quedarían todas
 * en 0 y el dashboard las seguiría mostrando por id. Solo toca filas sin
 * orden, así que un orden puesto a mano nunca se pisa.
 */
function backfillOrdenFases(PDO $db): void {
    $fases = $db->query('SELECT id_fase, nombre_fase FROM fases_proyecto WHERE orden = 0')->fetchAll();
    if (!$fases) {
        return;
    }
    $upd = $db->prepare('UPDATE fases_proyecto SET orden = ? WHERE id_fase = ?');
    foreach ($fases as $f) {
        $orden = ordenFaseSena($f['nombre_fase']);
        if ($orden > 0) {
            $upd->execute([$orden, $f['id_fase']]);
        }
    }
}

/**
 * Orden pedagógico de una fase a partir de su nombre.
 *
 * El PDF del proyecto formativo no siempre lista las fases en secuencia, y
 * el `id_fase` solo refleja el orden en que se importaron. Los cuatro
 * nombres del ciclo SENA sí son estables, así que se deducen de ahí; una
 * fase con nombre libre devuelve 0 y queda al final, ordenada por id.
 */
function ordenFaseSena(string $nombre): int {
    $n = mb_strtolower($nombre, 'UTF-8');
    // Sin tildes: el PDF alterna entre "PLANEACIÓN" y "PLANEACION".
    $n = strtr($n, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

    foreach (['analisis' => 1, 'planeacion' => 2, 'ejecucion' => 3, 'evaluacion' => 4] as $clave => $orden) {
        if (str_contains($n, $clave)) {
            return $orden;
        }
    }
    return 0;
}

/** Fragmento SQL para listar fases en orden pedagógico (las sin orden, al final). */
function sqlOrdenFases(string $alias = ''): string {
    $col = $alias !== '' ? "$alias." : '';
    return "IF({$col}orden = 0, 65535, {$col}orden), {$col}id_fase";
}
