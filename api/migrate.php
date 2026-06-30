<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDB();
    
    $queries = [
        "CREATE TABLE IF NOT EXISTS `programa_resultado` (
            `id_programa`   INTEGER UNSIGNED NOT NULL,
            `id_resultado`  INTEGER UNSIGNED NOT NULL,
            PRIMARY KEY(`id_programa`, `id_resultado`),
            CONSTRAINT `fk_pr_programa` FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT `fk_pr_resultado` FOREIGN KEY(`id_resultado`) REFERENCES `Resultados_aprendizaje`(`id_resultado`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `fases_proyecto` (
            `id_fase`       INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_programa`   INTEGER UNSIGNED NOT NULL,
            `nombre_fase`   VARCHAR(255) NOT NULL,
            `descripcion`   VARCHAR(500),
            PRIMARY KEY(`id_fase`),
            CONSTRAINT `fk_fase_programa` FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `actividades_proyecto` (
            `id_actividad`      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_fase`           INTEGER UNSIGNED NOT NULL,
            `nombre_actividad`  VARCHAR(255) NOT NULL,
            `descripcion`       VARCHAR(500),
            PRIMARY KEY(`id_actividad`),
            CONSTRAINT `fk_actividad_fase` FOREIGN KEY(`id_fase`) REFERENCES `fases_proyecto`(`id_fase`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `actividad_resultado` (
            `id_actividad`  INTEGER UNSIGNED NOT NULL,
            `id_resultado`  INTEGER UNSIGNED NOT NULL,
            PRIMARY KEY(`id_actividad`, `id_resultado`),
            CONSTRAINT `fk_ar_actividad` FOREIGN KEY(`id_actividad`) REFERENCES `actividades_proyecto`(`id_actividad`) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT `fk_ar_resultado` FOREIGN KEY(`id_resultado`) REFERENCES `Resultados_aprendizaje`(`id_resultado`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach($queries as $q) {
        $db->exec($q);
    }
    
    echo "Migración completada exitosamente.\n";
} catch(Exception $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
