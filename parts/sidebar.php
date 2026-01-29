<?php

if (!defined('ABSPATH')) {
    exit;
}

// Use an object so we can read back properties set by the filter.
$menu_args = (object) [
    'theme_location' => 'header',
    'container' => false,
    'menu_class' => 'sub-sidebar__list',
    'fallback_cb' => false,
    'sub_menu' => true,
    'echo' => false,
    'items_wrap' => '<ul class="%2$s">%3$s</ul>',
];

$menu_html = wp_nav_menu((array) $menu_args);

if (!$menu_html) {
    return;
}

$title = isset($menu_args->tondi_submenu_root_title) ? (string) $menu_args->tondi_submenu_root_title : '';
$aria = $title !== '' ? $title : __('Sub navigation', 'tondi');
?>

<?php echo '<!-- Sidebar -->'; ?>

<aside class="sub-sidebar">
    <?php if ($title !== ''): ?>
        <h2 class="sub-sidebar__title">
            <?php echo esc_html($title); ?>
        </h2>
    <?php endif; ?>

    <nav class="sub-sidebar__nav" aria-label="<?php echo esc_attr($aria); ?>">
        <?php echo $menu_html; ?>
    </nav>
</aside>

<?php echo '<!-- /Sidebar -->'; ?>
