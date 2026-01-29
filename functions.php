<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/bootstrap.php';

// -------------------------------------------------
// Image convertion to WebP
// -------------------------------------------------
// add_filter('image_editor_output_format', function ($formats) {
//     $formats['image/jpg'] = 'image/webp';
//     return $formats;
// });
