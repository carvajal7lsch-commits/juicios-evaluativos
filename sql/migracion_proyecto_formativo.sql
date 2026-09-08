-- ================================================================
-- MIGRACIÓN: proyecto formativo (fases, actividades y cobertura)
--
-- `schema.sql` borraba `fases_proyecto`, `actividades_proyecto` y
-- `actividad_resultado` como restos de un esquema viejo y no las volvía
-- a crear, así que cualquier base inicializada solo con ese script deja
-- el módulo de proyecto formativo muerto:
--   SQLSTATE[42S02] 1146 Table 'juicios_evaluativos.fases_proyecto'
--   doesn't exist
--
-- Esta migración las crea sobre bases ya existentes y añade la columna
-- `orden`, que permite mostrar las fases en su secuencia pedagógica
-- (Análisis → Planeación → Ejecución → Evaluación) en vez de por el id
-- que les tocó al importarlas del PDF.
--
-- Es idempotente: se puede volver a ejecutar sin efectos secundarios.
-- ================================================================

USE `juicios_evaluativos`;

CREATE TABLE IF NOT EXISTS `fases_proyecto` (
    `id_fase`       INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_programa`   INTEGER UNSIGNED NOT NULL,
    `nombre_fase`   VARCHAR(255) NOT NULL,
    `descripcion`   VARCHAR(500),
    `orden`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(`id_fase`),
    KEY `idx_fase_programa` (`id_programa`, `orden`),
    CONSTRAINT `fk_fase_programa`
        FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `actividades_proyecto` (
    `id_actividad`      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_fase`           INTEGER UNSIGNED NOT NULL,
    `nombre_actividad`  VARCHAR(255) NOT NULL,
    `descripcion`       VARCHAR(500),
    `orden`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(`id_actividad`),
    KEY `idx_actividad_fase` (`id_fase`, `orden`),
    CONSTRAINT `fk_actividad_fase`
        FOREIGN KEY(`id_fase`) REFERENCES `fases_proyecto`(`id_fase`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `actividad_resultado` (
    `id_actividad`  INTEGER UNSIGNED NOT NULL,
    `id_resultado`  INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id_actividad`, `id_resultado`),
    KEY `idx_ar_resultado` (`id_resultado`),
    CONSTRAINT `fk_ar_actividad`
        FOREIGN KEY(`id_actividad`) REFERENCES `actividades_proyecto`(`id_actividad`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_ar_resultado`
        FOREIGN KEY(`id_resultado`) REFERENCES `Resultados_aprendizaje`(`id_resultado`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bases creadas antes de que existiera `orden` (por api/migrate.php)
ALTER TABLE `fases_proyecto`
    ADD COLUMN IF NOT EXISTS `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `actividades_proyecto`
    ADD COLUMN IF NOT EXISTS `orden` SMALLINT UNSIGNED NOT NULL DEFAULT 0;

-- Orden pedagógico de las fases ya importadas. La colación utf8mb4_unicode_ci
-- ignora las tildes, así que 'ANÁLISIS' entra por '%analisis%'.
UPDATE `fases_proyecto` SET `orden` = 1 WHERE `orden` = 0 AND `nombre_fase` LIKE '%analisis%';
UPDATE `fases_proyecto` SET `orden` = 2 WHERE `orden` = 0 AND `nombre_fase` LIKE '%planeacion%';
UPDATE `fases_proyecto` SET `orden` = 3 WHERE `orden` = 0 AND `nombre_fase` LIKE '%ejecucion%';
UPDATE `fases_proyecto` SET `orden` = 4 WHERE `orden` = 0 AND `nombre_fase` LIKE '%evaluacion%';
