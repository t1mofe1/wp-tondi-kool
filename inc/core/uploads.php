<?php

if (!defined('ABSPATH')) {
    exit;
}

// -------------------------------------------------
// Uploads & MIME types
// -------------------------------------------------

/**
 * Allow SVG uploads (admin only).
 * SVGs are useful for icons (menus, UI), but should not be editable by authors.
 */
add_filter('upload_mimes', function ($mimes) {
    // Only allow SVG for users who can manage options (admins)
    if (current_user_can('manage_options')) {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }

    return $mimes;
});

/**
 * Fix SVG preview in Media Library.
 * This only affects admin UI rendering.
 */
add_action('admin_head', function () {
    echo '<style>
        .media-icon img[src$=".svg"],
        img[src$=".svg"].attachment-post-thumbnail {
            width: 100% !important;
            height: auto !important;
        }
    </style>';
});
