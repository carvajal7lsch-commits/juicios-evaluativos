-- ================================================================
-- SISTEMA DE JUICIOS EVALUATIVOS - SENA
-- Schema simplificado (estados y tipos como VARCHAR directo)
-- ================================================================

CREATE DATABASE IF NOT EXISTS `juicios_evaluativos`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `juicios_evaluativos`;

-- ================================================================
-- LIMPIEZA (orden inverso por dependencias FK)
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `juicios_evaluativos`;
DROP TABLE IF EXISTS `Aprendiz`;
DROP TABLE IF EXISTS `Funcionarios`;
DROP TABLE IF EXISTS `programa_competencia`;
DROP TABLE IF EXISTS `Resultados_aprendizaje`;
DROP TABLE IF EXISTS `Competencias`;
DROP TABLE IF EXISTS `formacion`;
DROP TABLE IF EXISTS `Programa`;

DROP TABLE IF EXISTS `actividad_resultado`;
DROP TABLE IF EXISTS `actividades_proyecto`;
DROP TABLE IF EXISTS `fases_proyecto`;

-- Tablas eliminadas del esquema anterior (por si existen)
DROP TABLE IF EXISTS `actividad_competencia`;
DROP TABLE IF EXISTS `tipo_juicio`;
DROP TABLE IF EXISTS `estado_aprendiz`;
DROP TABLE IF EXISTS `estado_formacion`;
DROP TABLE IF EXISTS `Tipo_documento`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- PROGRAMAS Y COMPETENCIAS
-- ================================================================

CREATE TABLE IF NOT EXISTS `Programa` (
    `id_programa`   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`        VARCHAR(255) NOT NULL,
    `nombre`        VARCHAR(255) NOT NULL,
    PRIMARY KEY(`id_programa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Competencias` (
    `id_competencia`    INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`            VARCHAR(255) NOT NULL UNIQUE,
    `nombre`            VARCHAR(255) NOT NULL,
    `duracion_horas`    DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY(`id_competencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Resultados_aprendizaje` (
    `id_resultado`      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_competencia`    INTEGER UNSIGNED NOT NULL,
    `codigo`            VARCHAR(255) NOT NULL UNIQUE,
    `descripcion`       VARCHAR(500) NOT NULL,
    PRIMARY KEY(`id_resultado`),
    CONSTRAINT `fk_ra_competencia`
        FOREIGN KEY(`id_competencia`) REFERENCES `Competencias`(`id_competencia`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `programa_competencia` (
    `id_programa`       INTEGER UNSIGNED NOT NULL,
    `id_competencia`    INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id_programa`, `id_competencia`),
    CONSTRAINT `fk_pc_programa`
        FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_pc_competencia`
        FOREIGN KEY(`id_competencia`) REFERENCES `Competencias`(`id_competencia`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ================================================================
-- PROYECTO FORMATIVO — FASES, ACTIVIDADES Y COBERTURA DE RAPs
--
-- Estas tres tablas son el corazon del modulo "Fases y Actividades":
-- descomponen el proyecto formativo de un programa en fases (Analisis,
-- Planeacion, Ejecucion, Evaluacion) y actividades, y cada actividad
-- declara que Resultados de Aprendizaje evalua. Sin ellas, la pagina de
-- fases y su dashboard fallan con "Table 'fases_proyecto' doesn't exist".
-- ================================================================

CREATE TABLE IF NOT EXISTS `fases_proyecto` (
    `id_fase`       INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_programa`   INTEGER UNSIGNED NOT NULL,
    `nombre_fase`   VARCHAR(255) NOT NULL,
    `descripcion`   VARCHAR(500),
    -- Orden pedagogico dentro del proyecto. 0 = sin ordenar todavia:
    -- en ese caso se cae al id_fase, que es el orden de creacion.
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

-- ================================================================
-- FORMACIÓN Y APRENDICES
-- ================================================================

CREATE TABLE IF NOT EXISTS `formacion` (
    `ficha`         VARCHAR(255) NOT NULL,
    `id_programa`   INTEGER UNSIGNED NOT NULL,
    `estado`        VARCHAR(255) NOT NULL DEFAULT 'En Ejecución',
    `fecha_inicio`  DATE NOT NULL,
    `fecha_fin`     DATE NOT NULL,
    `modalidad`     VARCHAR(255) NOT NULL DEFAULT 'Presencial',
    PRIMARY KEY(`ficha`),
    CONSTRAINT `fk_f_programa`
        FOREIGN KEY(`id_programa`) REFERENCES `Programa`(`id_programa`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Funcionarios` (
    `documento`         VARCHAR(255) NOT NULL,
    `tipo_doc`          VARCHAR(255) NOT NULL DEFAULT 'Cédula de Ciudadanía',
    `nombre_completo`   VARCHAR(255) NOT NULL,
    PRIMARY KEY(`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Aprendiz` (
    `documento`     VARCHAR(20) NOT NULL,
    `tipo_doc`      VARCHAR(255) NOT NULL DEFAULT 'Cédula de Ciudadanía',
    `nombre`        VARCHAR(255) NOT NULL,
    `apellidos`     VARCHAR(255) NOT NULL,
    `estado`        VARCHAR(255) NOT NULL DEFAULT 'Activo',
    `ficha`         VARCHAR(255) NOT NULL,
    `email`         VARCHAR(255),
    PRIMARY KEY(`documento`),
    CONSTRAINT `fk_a_ficha`
        FOREIGN KEY(`ficha`) REFERENCES `formacion`(`ficha`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- JUICIOS EVALUATIVOS (tabla central)
-- ================================================================

CREATE TABLE IF NOT EXISTS `juicios_evaluativos` (
    `id_juicio`             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `documento_aprendiz`    VARCHAR(255) NOT NULL,
    `id_resultado`          INTEGER UNSIGNED NOT NULL,
    `documento_funcionario` VARCHAR(255) NOT NULL,
    `estado`                VARCHAR(255) NOT NULL DEFAULT 'Pendiente',
    -- Admite NULL a proposito: un juicio importado de Sofia Plus sin fecha
    -- no debe inventarse una. El DEFAULT sigue aplicando cuando la columna
    -- se omite, que es como inserta api/juicios.php.
    `fecha_registro`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`id_juicio`),
    UNIQUE KEY `uq_aprendiz_resultado` (`documento_aprendiz`, `id_resultado`),
    CONSTRAINT `fk_j_aprendiz`
        FOREIGN KEY(`documento_aprendiz`) REFERENCES `Aprendiz`(`documento`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_j_resultado`
        FOREIGN KEY(`id_resultado`) REFERENCES `Resultados_aprendizaje`(`id_resultado`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_j_funcionario`
        FOREIGN KEY(`documento_funcionario`) REFERENCES `Funcionarios`(`documento`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- DATOS INICIALES (Funcionario placeholder para importaciones)
-- ================================================================

INSERT IGNORE INTO `Funcionarios` (`documento`, `tipo_doc`, `nombre_completo`)
    VALUES ('00000000', 'Cédula de Ciudadanía', 'SENA INSTRUCTOR');
