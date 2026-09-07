<?php
/**
 * ================================================================
 * NORMALIZACIÓN DE ESTADOS
 * ----------------------------------------------------------------
 * Sofia Plus exporta los estados en MAYÚSCULAS y sin tildes
 * ('EN FORMACION', 'RETIRO VOLUNTARIO', 'EN EJECUCION'), mientras que
 * los registros creados desde la aplicación usan Title Case
 * ('Activo', 'En Ejecución').
 *
 * Comparar contra un literal concreto deja fuera a unos u otros: por eso
 * el indicador de "aprendices activos" marcaba 0 pese a haber 130 en
 * formación. Aquí "activo" se define por EXCLUSIÓN de los estados
 * terminales e ignorando mayúsculas, que es estable frente a ambas
 * convenciones y frente a estados nuevos que aparezcan en el reporte.
 * ================================================================
 */

/** Estados que sacan a un aprendiz de la formación activa. */
const ESTADOS_APRENDIZ_INACTIVO = [
    'RETIRO VOLUNTARIO',
    'RETIRADO',
    'DESERCION',
    'DESERCIÓN',
    'CANCELADO',
    'CANCELAMIENTO',
    'TRASLADADO',
    'APLAZADO',
];

/**
 * Condición SQL que deja pasar sólo a los aprendices en formación.
 * La lista es una constante del código, no entrada del usuario, por lo que
 * puede interpolarse sin riesgo de inyección.
 *
 * @param string $alias Alias de la tabla Aprendiz en la consulta.
 */
function sqlAprendizActivo(string $alias = 'a'): string {
    $lista = implode(', ', array_map(
        static fn(string $e): string => "'" . $e . "'",
        ESTADOS_APRENDIZ_INACTIVO
    ));
    return "UPPER(TRIM($alias.estado)) NOT IN ($lista)";
}

/** Versión PHP de la misma regla, para filtrar en memoria. */
function aprendizEstaActivo(?string $estado): bool {
    return !in_array(mb_strtoupper(trim((string)$estado), 'UTF-8'), ESTADOS_APRENDIZ_INACTIVO, true);
}
