<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <!-- Developed by Timoteos Mägi (https://www.linkedin.com/in/t1m0fe1/) -->
    <!-- Version: <?php echo wp_get_theme()->get('Version'); ?> -->
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <a class="skip-link screen-reader-text" href="#main">
        <?php _e('Skip to content', 'tondi'); ?>
    </a>

    <?php
    $partial = get_stylesheet_directory() . '/parts/header.php';

    if (file_exists($partial)) {
        include $partial;
    }
