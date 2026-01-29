<?php

add_action('init', function () {
    register_post_type('news', [
        'labels' => [
            'name' => __('Uudised', 'tondi'),
            'singular_name' => __('Uudis', 'tondi'),

            // Menu UI
            'menu_name' => __('Uudised', 'tondi'),
            'name_admin_bar' => __('Uudis', 'tondi'),

            // Add/Edit
            'add_new' => __('Lisa uudis', 'tondi'),
            'add_new_item' => __('Lisa uus uudis', 'tondi'),
            'edit_item' => __('Muuda uudist', 'tondi'),
            'new_item' => __('Uus uudis', 'tondi'),
            'view_item' => __('Vaata uudist', 'tondi'),

            // Lists
            'all_items' => __('Kõik uudised', 'tondi'),
            'search_items' => __('Otsi uudiseid', 'tondi'),
            'not_found' => __('Uudiseid ei leitud', 'tondi'),
            'not_found_in_trash' => __('Uudiseid ei leitud prügikastis', 'tondi'),

            // Featured image
            'featured_image' => __('Uudise kaanepilt', 'tondi'),
            'set_featured_image' => __('Määra uudise kaanepilt', 'tondi'),
            'remove_featured_image' => __('Eemalda uudise kaanepilt', 'tondi'),
            'use_featured_image' => __('Kasuta uudise kaanepilti', 'tondi'),
        ],
        'public' => true,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'uudised'],
        'show_in_rest' => true,
    ]);

    #region Worker CPT and taxonomy

    $worker_post_type = 'worker';

    register_post_type($worker_post_type, [
        'labels' => [
            'name' => __('Töötajad', 'tondi'),
            'singular_name' => __('Töötaja', 'tondi'),

            // Menu UI
            'menu_name' => __('Töötajad', 'tondi'),
            'name_admin_bar' => __('Töötaja', 'tondi'),

            // Add/Edit
            'add_new' => __('Lisa töötaja', 'tondi'),
            'add_new_item' => __('Lisa uus töötaja', 'tondi'),
            'edit_item' => __('Muuda töötajat', 'tondi'),
            'new_item' => __('Uus töötaja', 'tondi'),
            'view_item' => __('Vaata töötajat', 'tondi'),

            // Lists
            'all_items' => __('Kõik töötajad', 'tondi'),
            'search_items' => __('Otsi töötajaid', 'tondi'),
            'not_found' => __('Töötajaid ei leitud', 'tondi'),
            'not_found_in_trash' => __('Töötajaid ei leitud prügikastis', 'tondi'),

            // Featured image (photo)
            'featured_image' => __('Töötaja foto', 'tondi'),
            'set_featured_image' => __('Määra töötaja foto', 'tondi'),
            'remove_featured_image' => __('Eemalda töötaja foto', 'tondi'),
            'use_featured_image' => __('Kasuta töötaja fotona', 'tondi'),
        ],

        'public' => true,
        'publicly_queryable' => false,
        'has_archive' => false,
        'rewrite' => false,
        'query_var' => false,
        'exclude_from_search' => true,

        'menu_icon' => 'dashicons-businessperson',
        'supports' => ['title', 'thumbnail'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('worker_department', [$worker_post_type], [
        'labels' => [
            'name' => __('Osakonnad', 'tondi'),
            'singular_name' => __('Osakond', 'tondi'),
            'menu_name' => __('Osakonnad', 'tondi'),

            'search_items' => __('Otsi osakondi', 'tondi'),
            'popular_items' => __('Populaarsed osakonnad', 'tondi'),
            'all_items' => __('Kõik osakonnad', 'tondi'),

            'name_field_description' => __('Sisesta osakonna nimi.', 'tondi'),

            'edit_item' => __('Muuda osakonda', 'tondi'),
            'view_item' => __('Vaata osakonda', 'tondi'),
            'update_item' => __('Uuenda osakonda', 'tondi'),
            'add_new_item' => __('Lisa uus osakond', 'tondi'),
            'new_item_name' => __('Uue osakonna nimi', 'tondi'),

            'add_or_remove_items' => __('Lisa või eemalda osakondi', 'tondi'),
            'choose_from_most_used' => __('Vali enimkasutatavate osakonde seast', 'tondi'),

            'not_found' => __('Osakondi ei leitud', 'tondi'),
            'no_terms' => __('Ühtegi osakonda pole määratud', 'tondi'),

            'filter_by_item' => __('Filtreeri osakonna järgi', 'tondi'),

            'items_list_navigation' => __('Osakondade nimekirja navigeerimine', 'tondi'),
            'items_list' => __('Osakondade nimekiri', 'tondi'),
            'back_to_items' => __('Tagasi osakondade juurde', 'tondi'),
        ],

        'public' => false,
        'rewrite' => false,
        'query_var' => false,

        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    // Hide unused fields for worker_department taxonomy
    function tondi_hide_worker_department_fields()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->taxonomy !== 'worker_department') {
            return;
        }

        echo '<style>
            .term-slug-wrap { display:none !important; }
            .term-parent-wrap { display:none !important; }
            .term-description-wrap { display:none !important; }
        </style>';
    }
    add_action('admin_head-edit-tags.php', 'tondi_hide_worker_department_fields');
    add_action('admin_head-term.php', 'tondi_hide_worker_department_fields');

    // Custom permalink for worker to point to contact page
    add_filter('post_type_link', function ($permalink, $post) use ($worker_post_type) {
        if ($post->post_type !== $worker_post_type) {
            return $permalink;
        }

        return home_url('/kontakt/?worker=' . $post->post_name);
    }, 10, 2);

    // Meta box showing modal link and copy button
    add_action('add_meta_boxes', function () use ($worker_post_type) {
        // Modal URL box
        add_meta_box(
            'tondi_worker_modal_url',
            __('Link', 'tondi'),
            function ($post) use ($worker_post_type) {
                if (!$post || $post->post_type !== $worker_post_type) {
                    return;
                }

                $is_published = ($post->post_status === 'publish');
                $slug = (string) $post->post_name;
                $id = (int) $post->ID;

                // If it's a new post and slug is empty, WordPress will generate it after save.
                if ($slug === '') {
                    echo '<p>' . esc_html__('Salvestage töötaja, et luua link.', 'tondi') . '</p>';
                    return;
                }

                // Choose slug-based param (recommended). If you prefer ID, swap to "?worker={$id}"
                $url = home_url('/kontakt/?worker=' . $slug);

                $note = $is_published
                    ? __('Jagamiskõlbulik link, mis avab selle töötaja modaalis Kontakt lehel.', 'tondi')
                    : __('See töötaja ei ole veel avaldatud. Slug/link võib veel muutuda.', 'tondi');

                ?>
            <div class="tondi-modal-link-box">
                <div style="padding-top:6px">
                    <input id="tondi-worker-modal-url" class="widefat" type="text" readonly value="<?php echo esc_attr($url); ?>"
                        onclick="this.select();"
                        style="font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace" />

                    <div style="padding-block:12px; display: flex; align-items: center; gap: 8px;">
                        <a class="button button-secondary" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html__('Ava', 'tondi'); ?>
                        </a>

                        <button type="button" class="button button-primary" data-tondi-copy="#tondi-worker-modal-url">
                            <?php echo esc_html__('Kopeeri', 'tondi'); ?>
                        </button>

                        <span class="tondi-copy-status" aria-live="polite" style="font-size:14px;color:#2271b1;"></span>
                    </div>
                </div>

                <span><?php echo esc_html($note); ?></span>

                <div style="margin-top:12px;">
                    <strong><?php echo esc_html__('Slug:', 'tondi'); ?></strong>
                    <code><?php echo esc_html($slug); ?></code>
                    &nbsp;•&nbsp;
                    <strong><?php echo esc_html__('ID:', 'tondi'); ?></strong>
                    <code><?php echo esc_html((string) $id); ?></code>
                </div>
            </div>

            <script>
                (function () {
                    // Avoid double-binding if meta box is re-rendered
                    if (window.__tondiWorkerModalLinkBoxBound) return;
                    window.__tondiWorkerModalLinkBoxBound = true;

                    function setStatus(text) {
                        var el = document.querySelector('.tondi-modal-link-box .tondi-copy-status');
                        if (!el) return;
                        el.textContent = text || '';
                        if (text) setTimeout(function () { el.textContent = ''; }, 1800);
                    }

                    document.addEventListener('click', async function (e) {
                        var btn = e.target.closest('button[data-tondi-copy]');
                        if (!btn) return;

                        var sel = btn.getAttribute('data-tondi-copy');
                        var input = sel ? document.querySelector(sel) : null;
                        if (!input) return;

                        var value = input.value || '';
                        input.focus();
                        input.select();

                        try {
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                await navigator.clipboard.writeText(value);
                            } else {
                                document.execCommand('copy');
                            }
                            setStatus('<?php echo esc_js(__('Kopeeritud!', 'tondi')); ?>');
                        } catch (err) {
                            setStatus('<?php echo esc_js(__('Kopeerimine ebaõnnestus — palun kopeeri käsitsi.', 'tondi')); ?>');
                        }
                    });
                })();
            </script>
            <?php
            },
            $worker_post_type,
            'side',
            'high'
        );
    }, 20);

    #endregion Worker CPT and taxonomy
});
