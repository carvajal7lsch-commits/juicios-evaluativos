<?php
/**
 * ================================================================
 * REGLAS DEL DOMINIO DE FORMACIÓN
 * ----------------------------------------------------------------
 * Sofia Plus reporta en `formacion.fecha_fin` el cierre de la ficha
 * COMPLETA, es decir etapa lectiva + etapa productiva. Los juicios
 * evaluativos de la lectiva, en cambio, deben estar cerrados al
 * terminar la lectiva, no al terminar la ficha.
 *
 * Medir el avance lectivo contra el calendario completo hace que la
 * meta parezca más lejana de lo que es: en la ficha 3142784 daba 245
 * días restantes cuando a los instructores les quedaban 2 meses.
 * ================================================================
 */

/**
 * Duración de la etapa productiva, en meses.
 *
 * Es el estándar SENA y coincide con la estructura de las fichas
 * cargadas (15, 24 y 27 meses = 9+6, 18+6 y 21+6). No viene en el
 * reporte de Sofia Plus, así que se asume aquí.
 *
 * Si algún programa usa una duración distinta, este es el único
 * punto a cambiar — o conviene pasar a guardar la fecha real de fin
 * de lectiva por ficha en la tabla `formacion`.
 */
const MESES_ETAPA_PRODUCTIVA = 6;

/** Fecha estimada de cierre de la etapa lectiva de una ficha. */
function finEtapaLectiva(string $fechaFinFicha): DateTime {
    return (new DateTime($fechaFinFicha))->modify('-' . MESES_ETAPA_PRODUCTIVA . ' months');
}

/**
 * Condición SQL que identifica la competencia de etapa productiva.
 *
 * La collation de la base es utf8mb4_unicode_ci, insensible a tildes
 * y mayúsculas, así que un único patrón cubre "ETAPA PRACTICA",
 * "Etapa Práctica" y demás variantes de escritura del reporte.
 *
 * @param string $alias Alias de la tabla Competencias en la consulta.
 */
function sqlEsCompetenciaPractica(string $alias = 'c'): string {
    return "$alias.nombre LIKE '%ETAPA PRACTICA%'";
}

/** Complemento: la competencia pertenece a la etapa lectiva. */
function sqlEsCompetenciaLectiva(string $alias = 'c'): string {
    return "$alias.nombre NOT LIKE '%ETAPA PRACTICA%'";
}
