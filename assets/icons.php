<?php
/**
 * ================================================================
 * SISTEMA DE ICONOS — Sprite SVG inline
 * ----------------------------------------------------------------
 * Reemplaza los emojis del sistema. Ventajas:
 *  - Heredan el color del texto (stroke: currentColor)
 *  - Escalan con la tipografía (tamaño en em)
 *  - Se ven idénticos en Windows, macOS, Linux y Android
 *  - Cero requests extra: el sprite se inyecta inline una sola vez
 *
 * Uso en PHP:  <?= icon('users') ?>  /  <?= icon('users', 'ic-lg') ?>
 * Uso en JS:   ${ic('users')}        (helper definido en footer.php)
 * ================================================================
 */

/** Devuelve el markup de un icono del sprite. */
function icon(string $name, string $class = '', string $title = ''): string {
    $cls  = trim('ic ' . $class);
    $aria = $title !== ''
        ? ' role="img" aria-label="' . htmlspecialchars($title, ENT_QUOTES) . '"'
        : ' aria-hidden="true" focusable="false"';
    return '<svg class="' . htmlspecialchars($cls, ENT_QUOTES) . '"' . $aria . '><use href="#i-'
         . htmlspecialchars($name, ENT_QUOTES) . '"></use></svg>';
}

/** Imprime el sprite. Debe llamarse una sola vez, al inicio del <body>. */
function render_icon_sprite(): void {
    // Todos los símbolos comparten viewBox 0 0 24 24 y grosor de trazo uniforme.
    $paths = [
        // ── Navegación principal ──
        'dashboard'      => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'book'           => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'school'         => '<path d="m4 10 8-4 8 4-8 4z"/><path d="M6 11.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/><path d="M20 10v5"/>',
        'user'           => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'          => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16.5 5a3.5 3.5 0 0 1 0 6.9"/><path d="M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
        'user-search'    => '<circle cx="10" cy="8" r="4"/><path d="M3 21a7 7 0 0 1 9.5-6.5"/><circle cx="17" cy="17" r="3.5"/><path d="m20 20 1.5 1.5"/>',
        'layers'         => '<path d="m12 2 9 5-9 5-9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
        'target'         => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/>',
        'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 9 19.4a1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 9a1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',

        // ── Estados / feedback ──
        'check-circle'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.6-5"/>',
        'x-circle'       => '<circle cx="12" cy="12" r="9"/><path d="m14.8 9.2-5.6 5.6M9.2 9.2l5.6 5.6"/>',
        'clock'          => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.2 1.9"/>',
        'alert-triangle' => '<path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><circle cx="12" cy="17" r=".9" fill="currentColor" stroke="none"/>',
        'siren'          => '<path d="M7 18v-5a5 5 0 0 1 10 0v5"/><rect x="4" y="18" width="16" height="3.5" rx="1.5"/><path d="M12 3v1.5M4.2 6.2l1 1M19.8 6.2l-1 1"/>',
        'info'           => '<circle cx="12" cy="12" r="9"/><path d="M12 11.2v5"/><circle cx="12" cy="7.9" r=".9" fill="currentColor" stroke="none"/>',
        'activity'       => '<path d="M3 12h3.5L9 5l5 14 2.6-7H21"/>',

        // ── Acciones ──
        'search'         => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'filter'         => '<path d="M3.5 5h17l-6.6 7.8V19l-3.8 2v-8.2z"/>',
        'sliders'        => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
        'plus'           => '<path d="M12 5v14M5 12h14"/>',
        'x'              => '<path d="M6 6l12 12M18 6 6 18"/>',
        'trash'          => '<path d="M3.5 6h17M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6"/><path d="M5.8 6l.8 13.2A2 2 0 0 0 8.6 21h6.8a2 2 0 0 0 2-1.8L18.2 6"/><path d="M10 10.5v6M14 10.5v6"/>',
        'pencil'         => '<path d="M4 20.2 4.7 16 16.4 4.3a2 2 0 0 1 2.8 0l.5.5a2 2 0 0 1 0 2.8L8 19.3z"/><path d="m14.5 6.2 3.3 3.3"/>',
        'save'           => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h10L20 8.5v10a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5z"/><path d="M8 4v5h7"/><rect x="8" y="13" width="8" height="7" rx="1"/>',
        'download'       => '<path d="M12 3.5v11"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 17v2a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-2"/>',
        'upload'         => '<path d="M12 20.5v-11"/><path d="M7.5 13.5 12 9l4.5 4.5"/><path d="M4 7V5a1.5 1.5 0 0 1 1.5-1.5h13A1.5 1.5 0 0 1 20 5v2"/>',
        'upload-cloud'   => '<path d="M6.5 18.5A4.5 4.5 0 0 1 6 9.6a6 6 0 0 1 11.6-1.1A4 4 0 0 1 18 18.4"/><path d="M12 21v-8"/><path d="m9 15.5 3-3 3 3"/>',
        'eye'            => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'lock'           => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'lock-open'      => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 7.6-1.7"/>',
        'refresh'        => '<path d="M20.5 12a8.5 8.5 0 1 1-2.6-6.1"/><path d="M20.5 4.5V10H15"/>',

        // ── Documentos / datos ──
        'clipboard-list' => '<rect x="5" y="4.5" width="14" height="16" rx="2"/><path d="M9 4.5V3.8A1.8 1.8 0 0 1 10.8 2h2.4A1.8 1.8 0 0 1 15 3.8v.7"/><path d="M9 10h6M9 14h6M9 18h3"/>',
        'file-text'      => '<path d="M14 2.5H7.5A2 2 0 0 0 5.5 4.5v15a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7z"/><path d="M14 2.5V7h4.5"/><path d="M9 12.5h6M9 16.5h6"/>',
        'file-pen'       => '<path d="M18.5 10.5V7L14 2.5H7.5a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2H12"/><path d="M14 2.5V7h4.5"/><path d="m20.7 13.8-5.2 5.2-.4 2.6 2.6-.4 5.2-5.2a1.5 1.5 0 0 0 0-2.2 1.5 1.5 0 0 0-2.2 0z"/>',
        'folder-open'    => '<path d="M3.5 8.5V6a1.5 1.5 0 0 1 1.5-1.5h4l2 2.5h6.5A1.5 1.5 0 0 1 19 8.5v1"/><path d="M3.5 8.5h17.1a1 1 0 0 1 1 1.2l-1.7 8.5a1.5 1.5 0 0 1-1.5 1.3H5a1.5 1.5 0 0 1-1.5-1.5z"/>',
        'inbox'          => '<path d="M21 12.5v6A1.5 1.5 0 0 1 19.5 20h-15A1.5 1.5 0 0 1 3 18.5v-6"/><path d="M3 12.5 5.8 5a1.5 1.5 0 0 1 1.4-1h9.6a1.5 1.5 0 0 1 1.4 1L21 12.5h-5l-1.5 2.5h-5L8 12.5z"/>',
        'graduation-cap' => '<path d="m2.5 8.5 9.5-4.5 9.5 4.5L12 13z"/><path d="M6.5 10.6v4.6c0 1.6 2.5 2.8 5.5 2.8s5.5-1.2 5.5-2.8v-4.6"/><path d="M21.5 8.5v5"/>',

        // ── Analítica ──
        'chart-bar'      => '<path d="M4 20.5V4"/><path d="M4 20.5h16.5"/><rect x="7.5" y="12" width="3" height="6" rx=".8"/><rect x="13" y="8" width="3" height="10" rx=".8"/><rect x="18" y="14" width="3" height="4" rx=".8"/>',
        'chart-pie'      => '<path d="M12 3a9 9 0 1 0 9 9h-9z"/><path d="M14.5 2.4A9 9 0 0 1 21.6 9.5h-7.1z"/>',
        'trending-up'    => '<path d="m3.5 16.5 5.5-5.5 3.5 3.5 6-6"/><path d="M14.5 8.5h4v4"/>',
        'timer'          => '<circle cx="12" cy="13.5" r="7.5"/><path d="M12 10v3.8l2.4 1.4"/><path d="M9.5 2.5h5"/>',
        'calendar'       => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17"/><path d="M8 3v4M16 3v4"/>',
        'map-pin'        => '<path d="M19 10.5c0 5.2-7 11-7 11s-7-5.8-7-11a7 7 0 1 1 14 0z"/><circle cx="12" cy="10.3" r="2.6"/>',

        // ── Chevrons / flechas ──
        'chevron-right'  => '<path d="m9.5 5.5 6.5 6.5-6.5 6.5"/>',
        'chevron-left'   => '<path d="M14.5 5.5 8 12l6.5 6.5"/>',
        'chevron-down'   => '<path d="m5.5 9.5 6.5 6.5 6.5-6.5"/>',
        'arrow-right'    => '<path d="M4 12h15.5"/><path d="m13.5 6 6 6-6 6"/>',
        'arrow-left'     => '<path d="M20 12H4.5"/><path d="m10.5 6-6 6 6 6"/>',
        'arrow-up'       => '<path d="M12 20V4.5"/><path d="m6 10.5 6-6 6 6"/>',

        // ── Interfaz / tema ──
        'sun'            => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.2M12 19.8V22M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2 12h2.2M19.8 12H22M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/>',
        'moon'           => '<path d="M20.5 14.3A8.7 8.7 0 0 1 9.7 3.5a8.8 8.8 0 1 0 10.8 10.8z"/>',
        'monitor'        => '<rect x="2.5" y="4" width="19" height="12.5" rx="2"/><path d="M8.5 20.5h7M12 16.5v4"/>',
        'panel-left'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9.5 4v16"/>',
        'grid'           => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'table'          => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9.5h18M9 9.5V20"/>',
        'zap'            => '<path d="M13.5 2 4 13.5h7L10.5 22 20 10.5h-7z"/>',
        'sparkles'       => '<path d="m12 3 1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M18.5 16.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
    ];

    echo '<svg xmlns="http://www.w3.org/2000/svg" class="icon-sprite" aria-hidden="true"><defs>' . "\n";
    foreach ($paths as $name => $d) {
        echo '<symbol id="i-' . $name . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
           . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $d . '</symbol>' . "\n";
    }
    echo '</defs></svg>' . "\n";
}

/**
 * Lista de nombres disponibles — la usa footer.php para exponer el helper ic()
 * a JavaScript y validar en desarrollo.
 */
function icon_names(): array {
    static $names = null;
    if ($names === null) {
        ob_start(); render_icon_sprite(); $svg = ob_get_clean();
        preg_match_all('/id="i-([a-z0-9\-]+)"/', $svg, $m);
        $names = $m[1];
    }
    return $names;
}
