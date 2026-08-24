<?php

get_header();

// Redundant at global scope, but matches search.php and quiets static analysis.
global $wp_query;

?>

<main id="main" class="news-archive" role="main">
    <div class="container">

        <div class="news-archive__layout">

            <?php get_template_part('template-parts/news/archive-filter'); ?>

            <div class="news-archive__main">

                <header class="news-archive__header">
                    <h1 class="news-archive__title">
                        <?php echo esc_html(tondi_news_archive_heading()); ?>
                    </h1>

                    <?php $found = (int) $wp_query->found_posts; ?>
                    <?php if ($found > 0): ?>
                        <p class="news-archive__count">
                            <?php echo esc_html(sprintf(
                                /* translators: %d: number of news posts found */
                                _n('%d uudis', '%d uudist', $found, 'tondi'),
                                $found
                            )); ?>
                        </p>
                    <?php endif; ?>
                </header>

                <?php if (have_posts()): ?>
                    <ul class="news-cards">
                        <?php while (have_posts()):
                            the_post(); ?>
                            <li <?php post_class('news-card'); ?>>
                                <h2 class="news-card-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <p class="news-card-date">
                                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                        <?php echo esc_html(get_the_date('d.m.Y')); ?>
                                    </time>
                                </p>

                                <p class="news-card-excerpt">
                                    <?php
                                    if (has_excerpt()) {
                                        echo esc_html(get_the_excerpt());
                                    } else {
                                        echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_content('')), 24));
                                    }
                                    ?>
                                </p>

                                <div class="news-card-image-wrapper">
                                    <?php
                                    if (has_post_thumbnail()) {
                                        the_post_thumbnail('news_card', [
                                            'alt' => the_title_attribute(['echo' => false]),
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                        ]);
                                    } else {
                                        echo '<div class="news-card-image-placeholder" aria-hidden="true"></div>';
                                    }
                                    ?>
                                </div>

                                <a class="news-card-more" href="<?php the_permalink(); ?>">
                                    <?php esc_html_e('Loe edasi...', 'tondi'); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>

                    <nav class="pagination" aria-label="<?php esc_attr_e('Pagination', 'tondi'); ?>">
                        <?php

                        echo paginate_links([
                            'current' => max(1, get_query_var('paged')),
                            'total' => (int) $wp_query->max_num_pages,
                            'mid_size' => 1,
                            'prev_text' => __('‹ Eelmine', 'tondi'),
                            'next_text' => __('Järgmine ›', 'tondi'),
                        ]);

                        ?>
                    </nav>

                <?php else: ?>
                    <p class="no-news">
                        <?php esc_html_e('Uudiseid ei leitud.', 'tondi'); ?>
                    </p>

                    <?php if (tondi_news_current_filter()['year'] !== null): ?>
                        <p class="news-archive__reset">
                            <a href="<?php echo esc_url(tondi_news_archive_url()); ?>">
                                <?php esc_html_e('← Vaata kõiki uudiseid', 'tondi'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>

    </div>
</main>

<?php get_footer(); ?>
