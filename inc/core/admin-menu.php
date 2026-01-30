<?php

// Remove unnecessary admin menu items
add_action('admin_menu', function () {
    $hide_menus = get_field('security_hide_extra_admin_menus', 'option');

    if (!$hide_menus) {
        return;
    }

    remove_menu_page('edit.php');          // Posts
    remove_menu_page('edit-comments.php'); // Comments

    // Remove some sub-menus under "Settings"
    remove_submenu_page('options-general.php', 'options-writing.php'); // Writing
    remove_submenu_page('options-general.php', 'options-reading.php'); // Reading
    remove_submenu_page('options-general.php', 'options-discussion.php'); // Discussion
    remove_submenu_page('options-general.php', 'options-media.php'); // Media
    remove_submenu_page('options-general.php', 'options-permalink.php'); // Permalinks

    // Remove ACF and FileBird menus
    remove_menu_page('edit.php?post_type=acf-field-group');
    remove_menu_page('filebird-settings');

    // Remove "Appearance" menu
    remove_menu_page('themes.php');

    // Remove "Plugins" menu
    remove_menu_page('plugins.php');

    // Remove "Tools" menu
    remove_menu_page('tools.php');

    // Remove "General" menu
    remove_menu_page('options-general.php');
});

// Remove unncessary admin bar items
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $hide_menus = get_field('security_hide_extra_admin_menus', 'option');

    if (!$hide_menus) {
        return;
    }
    // Remove "New" menu items
    $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_node('new-user');

    // Remove comments icon
    $wp_admin_bar->remove_node('comments');

    // Remove WordPress logo menu
    $wp_admin_bar->remove_node('wp-logo');

    // Remove customize icon
    $wp_admin_bar->remove_node('customize');
}, 999);

// Add custom admin menu page for theme settings
add_action('admin_menu', function () {
    $hide_menus = get_field('security_hide_extra_admin_menus', 'option');

    if (!$hide_menus) {
        return;
    }

    add_menu_page(
        __('Menüüd', 'tondi'),
        __('Menüüd', 'tondi'),
        'manage_options',
        'nav-menus.php',
        '',
        'dashicons-menu',
        60
    );
});
