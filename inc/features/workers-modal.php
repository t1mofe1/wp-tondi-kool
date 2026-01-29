<?php

if (!defined('ABSPATH')) {
    exit;
}

function tondi_render_worker_modal_content(int $post_id): string
{
    if (get_post_type($post_id) !== 'worker') {
        return '';
    }

    ob_start();
    get_template_part('template-parts/workers/worker-modal-body', null, [
        'post_id' => $post_id,
    ]);
    return (string) ob_get_clean();
}

function tondi_ajax_worker_modal(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_send_json_error(['message' => 'Method not allowed'], 405);
    }

    check_ajax_referer('tondi_worker_modal', 'nonce');

    $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $slug = isset($_POST['slug']) ? sanitize_title((string) $_POST['slug']) : '';

    if (!$post_id && $slug) {
        $post = get_page_by_path($slug, OBJECT, 'worker');
        $post_id = $post ? (int) $post->ID : 0;
    }

    if (!$post_id || get_post_type($post_id) !== 'worker' || get_post_status($post_id) !== 'publish') {
        wp_send_json_error(['message' => 'Not found'], 404);
    }

    $html = tondi_render_worker_modal_content($post_id);
    if ($html === '') {
        wp_send_json_error(['message' => 'Empty'], 500);
    }

    wp_send_json_success([
        'id' => $post_id,
        'slug' => get_post_field('post_name', $post_id),
        'title' => get_the_title($post_id),
        'html' => $html,
    ]);
}

add_action('wp_ajax_tondi_worker_modal', 'tondi_ajax_worker_modal');
add_action('wp_ajax_nopriv_tondi_worker_modal', 'tondi_ajax_worker_modal');
