<?php
/**
 * Reparación manual del esquema del proyecto formativo.
 *
 * Ya no hace falta en el flujo normal: `sql/schema.sql` crea las tablas en
 * bases nuevas, `sql/migracion_proyecto_formativo.sql` las añade a las
 * existentes y los endpoints del módulo llaman a ensureProyectoSchema() en
 * cada arranque. Este script se conserva como botón de emergencia y para
 * poder ver el resultado de la comprobación.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/proyecto.php';

header('Content-Type: text/plain; charset=utf-8');

$db = getDB();

// programa_resultado no forma parte del módulo, pero vivía en este script
// desde antes de que existiera su propia migración: se mantiene aquí.
$db->exec("CREATE TABLE IF NOT EXISTS `programa_resultado` (
    `id_programa`   INTEGER UNSIGNED NOT NULL,
    `id_resultado`  INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id_programa`, `id_resultado`),
    CONSTRAINT `fk_pr_programa` FOREIGN KEY(`id_programa`)
        REFERENCES `Programa`(`id_programa`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_pr_resultado` FOREIGN KEY(`id_resultado`)
        REFERENCES `Resultados_aprendizaje`(`id_resultado`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (ensureProyectoSchema($db)) {
    echo "Esquema del proyecto formativo verificado correctamente.\n";
    exit;
}

http_response_code(500);
echo "No se pudo crear la estructura del proyecto formativo.\n";
echo "Revisa el log del servidor: probablemente el usuario de base de datos no puede ejecutar DDL.\n";
