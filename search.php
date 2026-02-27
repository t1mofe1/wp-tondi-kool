<?php get_header(); ?>

<main id="main" class="search-page" role="main">
    <div class="container">

        <header class="search-header">
            <h1 class="search-title">
                <?php

                printf(
                    esc_html__('Otsingu tulemused: %s', 'tondi'),
                    '<span class="search-term">' . esc_html(get_search_query()) . '</span>'
                );

                ?>
            </h1>

            <?php global $wp_query; ?>

            <p class="search-meta">
                <?php

                printf(
                    esc_html(_n('%s tulemus', '%s tulemust', (int) $wp_query->found_posts, 'tondi')),
                    number_format_i18n((int) $wp_query->found_posts)
                );

                ?>
            </p>

            <div class="search-form-wrap">
                <?php get_search_form(); ?>
            </div>
        </header>

        <?php if (have_posts()) : ?>
            <div class="search-results">
                <?php while (have_posts()) : the_post();
                    $post_id = get_the_ID();
                    $post_type = get_post_type($post_id);

                    $type_label = match ($post_type) {
                        'news' => __('Uudis', 'tondi'),
                        'page' => __('Leht', 'tondi'),
                        'worker' => __('Töötaja', 'tondi'),
                        default => ucfirst((string) $post_type),
                    };
                ?>

                    <?php if ($post_type === 'worker') : ?>
                        <article <?php post_class('search-card search-card--worker'); ?>>
                            <a class="search-worker-avatar" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                <?php tondi_worker_avatar($post_id, 'thumbnail', 'search-worker-avatar__img'); ?>
                            </a>

                            <div class="search-body">
                                <div class="search-topline">
                                    <span class="search-badge"><?php echo esc_html($type_label); ?></span>
                                </div>

                                <h2 class="search-item-title">
                                    <?php $q = get_search_query(); ?>

                                    <a href="<?php the_permalink(); ?>">
                                        <?php echo wp_kses_post(tondi_highlight_search_text(get_the_title(), $q)); ?>
                                    </a>
                                </h2>

                                <?php $position = trim(tondi_worker_position($post_id)); ?>
                                <?php if ($position !== '') : ?>
                                    <p class="search-worker-position">
                                        <?php echo wp_kses_post(tondi_highlight_search_text($position, get_search_query())); ?>
                                    </p>
                                <?php endif; ?>

                                <?php $email = trim(tondi_worker_email($post_id)); ?>
                                <?php if ($email !== '') : ?>
                                    <p class="search-worker-contact">
                                        <a class="search-worker-email" href="<?php echo esc_url('mailto:' . $email); ?>">
                                            <?php echo esc_html($email); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>

                                <?php $phones = tondi_worker_phones($post_id); ?>
                                <?php if (!empty($phones)) : ?>
                                    <ul class="search-worker-phones">
                                        <?php foreach ($phones as $p) :
                                            $label = trim((string) ($p['label'] ?? ''));
                                            $num   = trim((string) ($p['number'] ?? ''));
                                            $tel   = tondi_phone_to_tel_href($num);
                                            if ($num === '') continue;
                                        ?>
                                            <li>
                                                <?php if ($label !== '') : ?>
                                                    <span class="search-worker-phone-label"><?php echo esc_html($label); ?>:</span>
                                                <?php endif; ?>

                                                <?php if ($tel !== '') : ?>
                                                    <a href="<?php echo esc_url('tel:' . $tel); ?>"><?php echo esc_html($num); ?></a>
                                                <?php else : ?>
                                                    <span><?php echo esc_html($num); ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <div class="search-actions">
                                    <a class="search-readmore" href="<?php the_permalink(); ?>">
                                        <?php esc_html_e('Ava', 'tondi'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>

                    <?php else : ?>

                        <?php

                        $has_thumb = has_post_thumbnail();
                        $thumb_class = $has_thumb ? 'has-thumb' : 'no-thumb';

                        ?>

                        <article <?php post_class('search-card ' . $thumb_class); ?>>
                            <?php if ($has_thumb) : ?>
                                <a class="search-thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                    <?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
                                </a>
                            <?php endif; ?>

                            <div class="search-body">
                                <div class="search-topline">
                                    <span class="search-badge"><?php echo esc_html($type_label); ?></span>

                                    <?php if (in_array($post_type, ['news', 'post'], true)) : ?>
                                        <time class="search-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                            <?php echo esc_html(get_the_date()); ?>
                                        </time>
                                    <?php endif; ?>
                                </div>

                                <h2 class="search-item-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>

                                <?php
                                $excerpt = get_the_excerpt();
                                if (!$excerpt) {
                                    $content = wp_strip_all_tags(get_the_content());
                                    $content = preg_replace('/\[[^\]]+\]/', '', $content);
                                    $excerpt = is_string($content) ? $content : '';
                                }
                                $excerpt = wp_trim_words((string) $excerpt, 32, '…');
                                ?>

                                <div class="search-excerpt">
                                    <?php $q = get_search_query(); ?>
                                    <p><?php echo wp_kses_post(tondi_highlight_search_text($excerpt, $q)); ?></p>
                                </div>

                                <div class="search-actions">
                                    <a class="search-readmore" href="<?php the_permalink(); ?>">
                                        <?php esc_html_e('Ava', 'tondi'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>

                    <?php endif; ?>

                <?php endwhile; ?>
            </div>

            <nav class="search-pagination">
                <?php

                the_posts_pagination([
                    'mid_size' => 1,
                    'prev_text' => __('← Eelmine', 'tondi'),
                    'next_text' => __('Järgmine →', 'tondi'),
                ]);

                ?>
            </nav>

        <?php else : ?>
            <section class="search-empty">
                <h2><?php esc_html_e('Tulemusi ei leitud', 'tondi'); ?></h2>
                <div class="search-form-wrap"><?php get_search_form(); ?></div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
