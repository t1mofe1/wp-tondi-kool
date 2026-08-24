<?php
/**
 * Make core's XML sitemaps return 200 instead of 404.
 *
 * handle_404() has no sitemap exemption. Most sites are saved by the empty
 * query falling through to is_home(), but with a static front page and no
 * classic `post` entries that is false here, so every /wp-sitemap*.xml 404s
 * while serving valid XML -- and crawlers drop a 404'd sitemap.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('pre_handle_404', function ($preempt, $query) {
    // Another plugin already took over 404 handling; don't fight it.
    if (false !== $preempt) {
        return $preempt;
    }

    if (!($query instanceof WP_Query)) {
        return $preempt;
    }

    $is_sitemap = (string) $query->get('sitemap') !== ''
        || (string) $query->get('sitemap-stylesheet') !== '';

    if (!$is_sitemap) {
        return $preempt;
    }

    status_header(200);

    // Genuine misses still 404 later, in WP_Sitemaps::render_sitemaps().
    return true;
}, 10, 2);
