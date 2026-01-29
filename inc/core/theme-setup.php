<?php

if (!defined('ABSPATH')) {
    exit;
}

// -------------------------------------------------
// Theme setup
// -------------------------------------------------
add_action('after_setup_theme', function () {
    // Internationalization
    load_theme_textdomain('tondi', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    add_theme_support('html5', [
        'search-form',
        'gallery',
        'caption',
        'style',
        'script',
        'navigation-widgets'
    ]);

    add_theme_support('editor-styles');
    add_theme_support('align-wide');

    add_theme_support('custom-logo', [
        'width' => 240,
        'height' => 80,
        'flex-width' => true,
        'flex-height' => true,
    ]);

    register_nav_menus([
        'header' => __('Header', 'tondi'),
        'fastlinks' => __('Fastlinks', 'tondi'),
    ]);

    // Images sizes used in theme templates
    add_image_size('news_card', 640, 400, true);
    add_image_size('front_gallery', 480, 320, true);
});
