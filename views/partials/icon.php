<?php
/**
 * Inline SVG icon (stroke style, 24×24 grid). Inherits the current text colour.
 *
 * @var string $name
 */
$paths = [
    'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2.5 3h2.6l2.3 11.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21.5 7H6"/>',
    'album'    => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>',
    'package'  => '<path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/><path d="M21 8 12 3 3 8v8l9 5 9-5z"/>',
    'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'filter'   => '<path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/>',
    'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
    'back'     => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
    'trash'    => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
    'sparkles' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
    'truck'    => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
    'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
];
?>
<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $paths[$name] ?? '' ?></svg>
