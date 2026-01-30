<?php

// Prevent menu deletion
add_action('wp_delete_nav_menu', function ($term_id) {
    $strict_mode = get_field('security_strict_mode', 'option');

    if ($strict_mode) {
        wp_die(__('Menüü kustutamine on strict turbeolekus keelatud.', 'tondi'));
    }
});
