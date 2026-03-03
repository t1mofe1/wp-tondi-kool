<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable header menu links for "empty" parent pages.
 * - Only affects theme_location = header
 * - Only affects Page links that have children
 */
add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
    // Only your header menu
    if (empty($args->theme_location) || $args->theme_location !== 'header') {
        return $atts;
    }

    // Only Pages
    if (($item->object ?? '') !== 'page' || empty($item->object_id)) {
        return $atts;
    }

    // Only parents (has submenu)
    $classes = (array) ($item->classes ?? []);
    $is_parent = in_array('menu-item-has-children', $classes, true);
    if (!$is_parent) {
        return $atts;
    }

    $post = get_post((int) $item->object_id);
    if (!$post || $post->post_status !== 'publish') {
        return $atts;
    }

    // Decide what "empty" means:
    // 1) no content
    // 2) (optional) ignore whitespace + NBSP
    $content = (string) $post->post_content;
    $content = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $content); // NBSP
    $content = trim(wp_strip_all_tags($content));

    $is_empty = ($content === '');

    if ($is_empty) {
        // Make it non-clickable + accessible
        $atts['href'] = '#';
        $atts['aria-disabled'] = 'true';
        $atts['tabindex'] = '-1';
        $atts['class'] = trim(($atts['class'] ?? '') . ' is-disabled-link');
    }

    return $atts;
}, 10, 4);
