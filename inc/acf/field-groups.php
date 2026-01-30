<?php

if (!defined('ABSPATH')) {
    exit;
}

if (
    !function_exists('acf_add_local_field_group') ||
    !function_exists('acf_add_options_page')
) {
    return;
}

// Settings pages
$siteSettingsMenuSlug = 'tondi-site-settings';
acf_add_options_page([
    'page_title' => __('Tondi seaded', 'tondi'),
    'menu_title' => __('Tondi seaded', 'tondi'),
    'menu_slug' => $siteSettingsMenuSlug,
    'capability' => 'manage_options',
    'redirect' => true,
    'position' => 59,
    'icon_url' => 'dashicons-admin-generic',
]);

#region Main settings

$mainSettingsMenuSlug = 'tondi-main-settings';
acf_add_options_sub_page([
    'page_title' => __('Üldine', 'tondi'),
    'menu_title' => __('Üldine', 'tondi'),
    'parent_slug' => $siteSettingsMenuSlug,
    'menu_slug' => $mainSettingsMenuSlug,
]);

// Security settings
acf_add_local_field_group([
    'key' => 'group_security_settings',
    'title' => 'Turvaseaded',
    'fields' => [
        [
            'key' => 'field_security_strict_mode',
            'label' => 'Range režiim',
            'name' => 'security_strict_mode',
            'type' => 'true_false',
            'instructions' => 'Kui sees, rakendatakse täiendavaid turvameetmeid, et kaitsta saidi sisu volitamata muutmise eest.',
            'default_value' => 1,
            'ui' => 1,
        ],
        [
            'key' => 'field_security_hide_extra_admin_menus',
            'label' => 'Peida ebavajalikud admin menüüd',
            'name' => 'security_hide_extra_admin_menus',
            'type' => 'true_false',
            'instructions' => 'Kui sees, peidetakse mõned ebavajalikud menüüd WordPressi admin paneelis ja tööriistaribal.',
            'default_value' => 1,
            'ui' => 1,
        ]
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $mainSettingsMenuSlug,
            ]
        ]
    ],
    'active' => true,
]);

// Calendar settings
acf_add_local_field_group([
    'key' => 'group_calendar',
    'title' => 'Kalendri seaded',
    'fields' => [
        [
            'key' => 'field_calendar_ics_url',
            'label' => 'ICS URL',
            'name' => 'calendar_ics_url',
            'type' => 'url',
            'instructions' => 'Sisesta siia oma kalendri ICS URL (nt Google Calendarist).',
            'required' => true,
        ]
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $mainSettingsMenuSlug,
            ]
        ]
    ],
    'active' => true,
]);

// News settings
acf_add_local_field_group([
    'key' => 'group_news_settings',
    'title' => 'Uudiste seaded',
    'fields' => [
        [
            'key' => 'field_news_archive_posts_per_page',
            'label' => 'Lehekülje kohta uudiseid',
            'name' => 'news_archive_posts_per_page',
            'type' => 'number',
            'default_value' => 9,
            'min' => 1,
            'step' => 1,
            'instructions' => 'Mitu uudist kuvatakse uudiste arhiivilehel lehekülje kohta',
        ]
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $mainSettingsMenuSlug,
            ]
        ]
    ],
    'active' => true,
]);

#endregion Main settings

#region Front Page

$frontPageMenuSlug = 'tondi-front-page-settings';
acf_add_options_sub_page([
    'page_title' => __('Esileht', 'tondi'),
    'menu_title' => __('Esileht', 'tondi'),
    'parent_slug' => $siteSettingsMenuSlug,
    'menu_slug' => $frontPageMenuSlug,
]);

// Gallery section
acf_add_local_field_group([
    'key' => 'group_tondi_front_page_gallery',
    'title' => 'Galerii seaded',
    'fields' => [
        [
            'key' => 'field_front_page_gallery_folder',
            'label' => __('Galerii kaust', 'tondi'),
            'name' => 'front_page_gallery_folder',
            'type' => 'select',
            'instructions' => __('Vali kaust, kust laadida pildid esilehele.', 'tondi'),
            'choices' => tondi_filebird_folder_choices_indented() ?: ['' => __('Kauste ei leitud', 'tondi')],
            'allow_null' => 1,
            'ui' => 1,
            'return_format' => 'value', // we store folder ID (int-like)
        ],
        [
            'key' => 'field_front_page_gallery_limit',
            'label' => __('Kuvatavate piltide arv', 'tondi'),
            'name' => 'front_page_gallery_limit',
            'type' => 'number',
            'instructions' => __('Mitu pilti esilehel näidata.', 'tondi'),
            'default_value' => 6,
            'min' => 3,
            'max' => 15,
            'step' => 3,
        ],
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $frontPageMenuSlug,
            ],
        ],
    ],
]);

// Projects section
acf_add_local_field_group([
    'key' => 'group_tondi_projects',
    'title' => 'Projektid',
    'fields' => [
        [
            'key' => 'field_projects_columns',
            'label' => 'Veerud',
            'name' => 'projects_columns',
            'type' => 'repeater',
            'min' => 1,
            'required' => true,
            'layout' => 'row',
            'button_label' => 'Lisa veerg',
            'sub_fields' => [
                [
                    'key' => 'field_col_image',
                    'label' => 'Pilt',
                    'name' => 'image',
                    'type' => 'image',
                    'return_format' => 'id',
                    'preview_size' => 'medium',
                    'mime_types' => 'jpg,jpeg,png,webp,gif,svg',
                    'instructions' => 'Laadi üles pilt, mis esindab projekti.',
                ],
                [
                    'key' => 'field_col_link',
                    'label' => 'Link',
                    'name' => 'link',
                    'type' => 'link',
                    'return_format' => 'array',
                ],
            ],
        ],
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $frontPageMenuSlug,
            ]
        ]
    ],
    'active' => true,
]);

#endregion Front Page

#region Footer page

$footerMenuSlug = 'tondi-footer-settings';
acf_add_options_sub_page([
    'page_title' => __('Jalus', 'tondi'),
    'menu_title' => __('Jalus', 'tondi'),
    'parent_slug' => $siteSettingsMenuSlug,
    'menu_slug' => $footerMenuSlug,
]);
acf_add_local_field_group([
    'key' => 'group_tondi_footer_settings',
    'title' => 'Jaluse seaded',
    'fields' => [
        [
            'key' => 'field_footer_columns',
            'label' => 'Veerud',
            'name' => 'footer_columns',
            'type' => 'repeater',
            'min' => 1,
            'max' => 4,
            'required' => true,
            'layout' => 'row',
            'button_label' => 'Lisa veerg',
            'sub_fields' => [
                [
                    'key' => 'field_col_heading',
                    'label' => 'Pealkiri',
                    'name' => 'heading',
                    'type' => 'text',
                    'placeholder' => 'KONTAKT:',
                ],
                [
                    'key' => 'field_col_width',
                    'label' => 'Laius',
                    'name' => 'width',
                    'type' => 'select',
                    'choices' => [
                        'auto' => 'Auto',
                        '25' => '25%',
                        '33' => '33%',
                        '50' => '50%',
                        '66' => '66%',
                        '75' => '75%',
                        '100' => '100%',
                    ],
                    'default_value' => 'auto',
                    'ui' => 1,
                    'return_format' => 'value',
                ],
                [
                    'key' => 'field_col_blocks',
                    'label' => 'Plokid',
                    'name' => 'blocks',
                    'type' => 'flexible_content',
                    'button_label' => 'Lisa plokk',
                    'layouts' => [
                        // LINKS (list of links)
                        [
                            'key' => 'layout_links',
                            'name' => 'links',
                            'label' => 'Lingid',
                            'display' => 'block',
                            'sub_fields' => [
                                [
                                    'key' => 'field_links_items',
                                    'label' => 'Lingid',
                                    'name' => 'items',
                                    'type' => 'repeater',
                                    'layout' => 'table',
                                    'button_label' => 'Lisa link',
                                    'sub_fields' => [
                                        [
                                            'key' => 'field_links_item_icon',
                                            'label' => 'Ikoon',
                                            'name' => 'icon',
                                            'type' => 'image',
                                            'return_format' => 'id',
                                            'preview_size' => 'thumbnail',
                                            'mime_types' => 'svg,png,jpg,jpeg,webp,gif',
                                            'instructions' => 'Upload a square icon (SVG preferred) ~20-24px.',
                                        ],
                                        [
                                            'key' => 'field_links_item',
                                            'label' => 'Link',
                                            'name' => 'item',
                                            'type' => 'link',
                                            'return_format' => 'array',
                                        ]
                                    ],
                                ],
                            ],
                        ],

                        // RICH TEXT (for any custom copy)
                        [
                            'key' => 'layout_richtext',
                            'name' => 'rich_text',
                            'label' => 'Rikas tekst',
                            'display' => 'block',
                            'sub_fields' => [
                                [
                                    'key' => 'field_richtext_content',
                                    'label' => 'Sisu',
                                    'name' => 'content',
                                    'type' => 'wysiwyg',
                                    'tabs' => 'visual',
                                    'media_upload' => 0,
                                    'delay' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'key' => 'field_footer_bottom_content',
            'label' => 'Alumine sisu',
            'name' => 'footer_bottom_content',
            'type' => 'textarea',
        ]
    ],
    'location' => [
        [
            [
                'param' => 'options_page',
                'operator' => '==',
                'value' => $footerMenuSlug,
            ]
        ]
    ],
    'active' => true,
]);

#endregion Footer page

// Workers (personal) fields
acf_add_local_field_group([
    'key' => 'group_tondi_workers',
    'title' => 'Töötaja väljad',
    'fields' => [
        [
            'key' => 'field_worker_position',
            'label' => 'Ametikoht',
            'name' => 'worker_position',
            'type' => 'text',
            'placeholder' => 'Õpetaja / Direktor / jne',
        ],
        [
            'key' => 'field_worker_email',
            'label' => 'Email',
            'name' => 'worker_email',
            'type' => 'email',
            'placeholder' => 'nimi.perekonnanimi@tondi.edu.ee',
        ],
        [
            'key' => 'field_worker_phones',
            'label' => 'Telefonid',
            'name' => 'worker_phones',
            'type' => 'repeater',
            'layout' => 'table',
            'button_label' => 'Lisa telefoninumber',
            'sub_fields' => [
                [
                    'key' => 'field_worker_phones_label',
                    'label' => 'Silt',
                    'name' => 'label',
                    'type' => 'text',
                    'placeholder' => 'Tel.',
                ],
                [
                    'key' => 'field_worker_phones_number',
                    'label' => 'Number',
                    'name' => 'number',
                    'type' => 'text',
                    'placeholder' => '551 195 51',
                ],
            ],
        ],
    ],
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'worker',
            ],
        ],
    ],
    'active' => true,
]);
