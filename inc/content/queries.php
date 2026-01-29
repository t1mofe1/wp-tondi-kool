<?php

add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }

    if ($q->is_post_type_archive('news')) {

        // ACF option (stored in wp_options)
        $ppp = (int) get_field('news_archive_posts_per_page', 'option');

        // fallback
        if ($ppp < 1) {
            $ppp = 9;
        }

        $q->set('posts_per_page', $ppp);
    }
});
