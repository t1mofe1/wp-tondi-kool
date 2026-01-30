<?php

add_filter('pre_delete_term', function (int $term, string $taxonomy) {
    $strict_mode = get_field('security_strict_mode', 'option');

    if (!$strict_mode) {
        return $term;
    }

    // Prevent menu deletion
    if ($taxonomy === 'nav_menu') {
        wp_die(__('Menüü kustutamine on strict turbeolekus keelatud.', 'tondi'));
    }

    return $term;
}, 10, 2);
