<?php

use WP_Query;

/**
 * Highlight search query inside plain text.
 * Safe: works on text (not HTML), escapes output, wraps matches in <mark>.
 */
function tondi_highlight_search_text(string $text, string $query): string
{
    $text = trim($text);
    $query = trim($query);

    if ($text === '' || $query === '') {
        return esc_html($text);
    }

    // Split query into words, ignore very short tokens
    $parts = preg_split('/\s+/', $query) ?: [];
    $parts = array_values(array_filter(array_unique(array_map('trim', $parts)), fn($w) => mb_strlen($w) >= 2));

    if (empty($parts)) {
        return esc_html($text);
    }

    $escaped = esc_html($text);

    // Build regex from query parts (escaped for regex)
    $pattern = '/' . implode('|', array_map(fn($w) => preg_quote($w, '/'), $parts)) . '/iu';

    // Wrap with <mark>
    $highlighted = preg_replace($pattern, '<mark>$0</mark>', $escaped);

    // preg_replace can return null on error
    return is_string($highlighted) ? $highlighted : $escaped;
}

add_action('wp_ajax_tondi_live_search', 'tondi_live_search');
add_action('wp_ajax_nopriv_tondi_live_search', 'tondi_live_search');

function tondi_live_search(): void
{
    $nonce = isset($_GET['nonce']) ? (string) $_GET['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'tondi_live_search')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $q = isset($_GET['q']) ? (string) $_GET['q'] : '';
    $q = trim(wp_unslash($q));

    if (mb_strlen($q) < 2) {
        wp_send_json_success(['items' => []]);
    }

    $query = new WP_Query([
        'post_type'              => ['worker', 'news', 'page', 'post'],
        'post_status'            => 'publish',
        'posts_per_page'         => 8,
        's'                      => $q,
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        $id = (int) $post->ID;
        $pt = (string) get_post_type($id);

        $label = match ($pt) {
            'worker' => __('Töötaja', 'tondi'),
            'news'   => __('Uudis', 'tondi'),
            'page'   => __('Leht', 'tondi'),
            'post'   => __('Postitus', 'tondi'),
            default  => ucfirst($pt),
        };

        $subtitle = '';
        if ($pt === 'worker' && function_exists('tondi_worker_position')) {
            $subtitle = trim(tondi_worker_position($id));
        }

        $items[] = [
            'id'       => $id,
            'title'    => get_the_title($id),
            'url'      => get_permalink($id),
            'type'     => $pt,
            'label'    => $label,
            'subtitle' => $subtitle,
        ];
    }

    wp_send_json_success(['items' => $items]);
}
