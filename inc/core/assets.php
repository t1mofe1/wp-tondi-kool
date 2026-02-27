<?php
/**
 * Enqueue Vite assets (dev server) or built assets (manifest) for the theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vite_origin = 'https://localhost:5173';

/**
 * Check if Vite dev server is running.
 */
function tondi_is_vite_running(): bool
{
    global $vite_origin;

    static $is_up = null;
    if ($is_up !== null) {
        return $is_up;
    }

    $res = wp_remote_get(
        $vite_origin . '/@vite/client',
        [
            'timeout' => 0.75,
            'sslverify' => false,
        ]
    );

    $is_up = !is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200;

    return $is_up;
}

/**
 * Read Vite manifest.
 */
function tondi_manifest(): array
{
    static $manifest = null;
    if ($manifest !== null) {
        return $manifest;
    }

    $manifestPath = get_stylesheet_directory() . '/dist/.vite/manifest.json';

    $manifest = file_exists($manifestPath)
        ? json_decode((string) file_get_contents($manifestPath), true)
        : [];

    return $manifest;
}

/**
 * Add type="module" to Vite scripts.
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (in_array($handle, ['vite-client', 'tondi-main'], true)) {
        if (strpos($tag, 'type=') === false) {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
    }
    return $tag;
}, 10, 3);

/**
 * Enqueue scripts/styles.
 */
add_action('wp_enqueue_scripts', function () {
    global $vite_origin;

    // Vite entry source (as defined in vite.config rollup input)
    $entry_src = 'assets/js/main.js';

    // Kontakt page config
    $is_kontakt_page = is_page_template('templates/page-kontakt.php');
    $modal_config = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tondi_worker_modal'),
    ];

    $live_search_config = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tondi_live_search'),
        'minLen' => 2,
    ];

    // ----------------------------
    // DEV (Vite)
    // ----------------------------
    if (tondi_is_vite_running()) {
        // Dev scripts are usually fine in head; keep as you had.
        wp_enqueue_script('vite-client', $vite_origin . '/@vite/client', [], null, false);
        wp_enqueue_script('tondi-main', $vite_origin . '/' . $entry_src, [], null, false);

        wp_add_inline_script(
            'tondi-main',
            'window.TondiLiveSearch=' . wp_json_encode($live_search_config) . ';',
            'before'
        );

        if ($is_kontakt_page) {
            wp_add_inline_script(
                'tondi-main',
                'window.TondiWorkerModal=' . wp_json_encode($modal_config) . ';',
                'before'
            );
        }

        return;
    }

    // ----------------------------
    // PROD (manifest)
    // ----------------------------
    $manifest = tondi_manifest();
    if (empty($manifest)) {
        error_log('[Tondi] Vite manifest missing/empty.');
        return;
    }

    $entry = $manifest[$entry_src] ?? null;

    // Fallback: find any entry if key differs for some reason
    if (!$entry) {
        foreach ($manifest as $k => $v) {
            if (!empty($v['isEntry'])) {
                $entry = $v;
                break;
            }
        }
    }

    if (!$entry) {
        error_log('[Tondi] Vite manifest entry missing: ' . $entry_src);
        return;
    }

    $dist_dir = trailingslashit(get_stylesheet_directory()) . 'dist/';
    $dist_uri = trailingslashit(get_stylesheet_directory_uri()) . 'dist/';

    // Main JS (in footer)
    if (!empty($entry['file'])) {
        $rel = ltrim((string) $entry['file'], '/');
        $abs = $dist_dir . $rel;
        $uri = $dist_uri . $rel;

        wp_enqueue_script(
            'tondi-main',
            $uri,
            [],
            file_exists($abs) ? (string) filemtime($abs) : null,
            true
        );

        wp_add_inline_script(
            'tondi-main',
            'window.TondiLiveSearch=' . wp_json_encode($live_search_config) . ';',
            'before'
        );
    } else {
        error_log('[Tondi] Vite manifest entry has no "file": ' . $entry_src);
        return;
    }

    // CSS (your manifest shows CSS on the entry)
    if (!empty($entry['css']) && is_array($entry['css'])) {
        foreach ($entry['css'] as $i => $rel) {
            $handle = $i === 0 ? 'tondi-styles' : 'tondi-styles-' . $i;

            $rel = ltrim((string) $rel, '/');
            $abs = $dist_dir . $rel;
            $uri = $dist_uri . $rel;

            wp_enqueue_style(
                $handle,
                $uri,
                [],
                file_exists($abs) ? (string) filemtime($abs) : null
            );
        }
    }

    // Inline config for Kontakt (PROD)
    if ($is_kontakt_page) {
        wp_add_inline_script(
            'tondi-main',
            'window.TondiWorkerModal=' . wp_json_encode($modal_config) . ';',
            'before'
        );
    }
}, 20);
