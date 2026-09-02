-- ================================================================
-- MIGRACIÓN: permitir fecha_registro NULL
--
-- api/importar_reporte.php inserta NULL cuando la fila del reporte de
-- Sofía Plus no trae fecha de juicio (un RAP aún sin evaluar). Con la
-- columna en NOT NULL, la importación entera falla con:
--   SQLSTATE[23000] 1048 Column 'fecha_registro' cannot be null
--
-- Se conserva el DEFAULT CURRENT_TIMESTAMP: api/juicios.php omite la
-- columna al insertar y sigue recibiendo la fecha actual.
-- ================================================================

USE `juicios_evaluativos`;

ALTER TABLE `juicios_evaluativos`
    MODIFY `fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
