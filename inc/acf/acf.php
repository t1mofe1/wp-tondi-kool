<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('acf/settings/save_json', function () {
    return get_stylesheet_directory() . '/acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
    if (!is_array($paths)) {
        $paths = [];
    }

    $paths[] = get_stylesheet_directory() . '/acf-json';

    return $paths;
});

// -------------------------------------------------
// Load ACF definitions when ACF is ready
// -------------------------------------------------
add_action('acf/init', function () {
    // Field groups + options pages
    require_once __DIR__ . '/field-groups.php';

    // Menu icons field group (nav menu item ACF field)
    require_once __DIR__ . '/menu-icons.php';
});
