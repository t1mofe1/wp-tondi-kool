<?php

use Walker_Nav_Menu;

if (!defined('ABSPATH')) {
    exit;
}

class Tondi_Fastlinks_Walker extends Walker_Nav_Menu
{
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $class_names = trim(implode(' ', array_map('sanitize_html_class', $classes)));
        $li_class = trim('fastlinks-menu-item ' . $class_names);

        $is_search = in_array('fastlinks-is-search', $classes, true);

        $icon_id = 0;
        if (function_exists('get_field') && isset($item->ID)) {
            $icon_id = (int) get_field('menu_icon', $item->ID);
        }

        $title = isset($item->title) ? $item->title : '';
        $url = isset($item->url) ? $item->url : '';

        if ($icon_id) {
            $icon_html = wp_get_attachment_image($icon_id, 'thumbnail', false, [
                'class' => 'fastlinks-menu-icon',
                'alt' => '',
                'loading' => 'lazy',
            ]);
        }

        $output .= '<li class="' . esc_attr($li_class) . '">';

        if ($is_search) {
            $output .= '<button type="button" class="fastlinks-menu-link fastlinks-search-open" aria-haspopup="dialog" aria-controls="site-search-modal">';

            if ($icon_html) $output .= $icon_html;

            $output .= '<span class="fastlinks-menu-text">' . esc_html($title) . '</span>';
            $output .= '</button>';
        } else {
            $output .= '<a class="fastlinks-menu-link" href="' . esc_url($url) . '">';

            if ($icon_html) $output .= $icon_html;

            $output .= '<span class="fastlinks-menu-text">' . esc_html($title) . '</span>';
            $output .= '</a>';
        }

        $output .= '</li>';
    }
}
