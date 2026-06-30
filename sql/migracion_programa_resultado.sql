-- ================================================================
-- MIGRACIÓN: Vincular RAPs directamente a Programas
-- Esto evita que los RAPs se "sumen" entre programas que comparten competencias
-- ================================================================

USE `juicios_evaluativos`;

-- 1. Crear la tabla de relación Programa -> Resultado
CREATE TABLE IF NOT EXISTS `programa_resultado` (
    `id_programa`   INTEGER UNSIGNED NOT NULL,
    `id_resultado`  INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id_programa`, `id_resultado`),
    CONSTRAINT `fk_pr_programa`
        FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_pr_resultado`
        FOREIGN KEY(`id_resultado`) REFERENCES `Resultados_aprendizaje`(`id_resultado`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Migrar datos existentes: Vincular cada programa con los RAPs de sus competencias actuales
INSERT IGNORE INTO `programa_resultado` (id_programa, id_resultado)
SELECT pc.id_programa, ra.id_resultado
FROM programa_competencia pc
JOIN Resultados_aprendizaje ra ON pc.id_competencia = ra.id_competencia;

-- 3. (Opcional) Limpiar duplicados reales en Resultados_aprendizaje si los hubiera
-- Pero por ahora, con la tabla de relación, las queries ya no mostrarán RAPs de otros programas.
