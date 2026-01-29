<?php get_header(); ?>

<main id="main" class="single-news" role="main">
    <div class="container">

        <?php while (have_posts()):
            the_post(); ?>

            <article <?php post_class('single-news__article'); ?>>

                <header class="single-news__header">
                    <h1 class="single-news__title">
                        <?php the_title(); ?>
                    </h1>

                    <div class="single-news__meta">
                        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date('d.m.Y')); ?>
                        </time>
                    </div>
                </header>

                <?php if (has_post_thumbnail()): ?>
                    <figure class="single-news__featured">
                        <?php
                        the_post_thumbnail('large', [
                            'alt' => the_title_attribute(['echo' => false]),
                            'loading' => 'eager',
                            'decoding' => 'async',
                        ]);
                        ?>
                    </figure>
                <?php endif; ?>

                <?php if (has_excerpt()): ?>
                    <div class="single-news__lead">
                        <?php echo wp_kses_post(wpautop(get_the_excerpt())); ?>
                    </div>
                <?php endif; ?>

                <div class="single-news__content">
                    <?php the_content(); ?>
                </div>

                <footer class="single-news__footer">
                    <a class="single-news__back" href="<?php echo esc_url(get_post_type_archive_link('news')); ?>">
                        <?php esc_html_e('← Tagasi uudiste juurde', 'tondi'); ?>
                    </a>
                </footer>

            </article>

        <?php endwhile; ?>

    </div>
</main>

<?php get_footer(); ?>
