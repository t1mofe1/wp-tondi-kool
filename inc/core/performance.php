<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance + head hygiene (safe defaults)
 * - Only affects front-end (not admin)
 * - Avoids aggressive changes that break plugins
 */
add_action('init', function () {
    if (is_admin()) {
        return;
    }

    // Remove emoji scripts/styles
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');

    // Remove RSD, WLW, generator meta
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');

    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);

    // Remove REST API links from head (REST still works)
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);

    // Remove oEmbed discovery links (embedding still possible via block)
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    remove_action('wp_head', 'wp_oembed_add_host_js');
});

/**
 * Optional: Remove jQuery migrate on front-end (often unused).
 * Comment out if any plugin relies on it.
 */
add_action('wp_default_scripts', function ($scripts) {
    if (is_admin()) {
        return;
    }

    if (!isset($scripts->registered['jquery'])) {
        return;
    }

    $jquery = $scripts->registered['jquery'];

    if (!empty($jquery->deps)) {
        $jquery->deps = array_diff($jquery->deps, ['jquery-migrate']);
    }
});

/**
 * Optional: Dequeue block library CSS on front-end if you're not using block styling.
 * If you use Gutenberg blocks on pages, you might NOT want this.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    // Comment these out if you use core blocks that need styles.
    // wp_dequeue_style('wp-block-library');
    // wp_dequeue_style('wp-block-library-theme');
    // wp_dequeue_style('global-styles');
}, 100);

/**
 * Preload fonts.
 */
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    $font_uri = get_stylesheet_directory_uri() . '/assets/fonts/Quicksand/Quicksand-VariableFont_wght.woff2';

    ?>

    <link rel="preload" href="<?php echo esc_url($font_uri); ?>" as="font" type="font/woff2" crossorigin />

    <?php
}, 2);
