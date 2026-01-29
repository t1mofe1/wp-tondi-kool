<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('acf_add_local_field_group')) {
    return;
}

acf_add_local_field_group([
    'key' => 'group_tondi_menu_icon',
    'title' => 'Menu Icon',
    'fields' => [
        [
            'key' => 'field_tondi_menu_icon',
            'label' => 'Icon',
            'name' => 'menu_icon',
            'type' => 'image',
            'return_format' => 'id',
            'preview_size' => 'thumbnail',
            'library' => 'all',
        ],
    ],
    'location' => [
        [
            [
                'param' => 'nav_menu_item',
                'operator' => '==',
                'value' => 5,
            ],
        ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
]);
