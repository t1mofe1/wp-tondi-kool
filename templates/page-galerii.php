<?php

/**
 * Template Name: Galerii
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$albums = function_exists('tondi_get_gallery_albums') ? tondi_get_gallery_albums() : [];

$requested_album_id = function_exists('tondi_gallery_requested_album_id')
    ? tondi_gallery_requested_album_id()
    : 0;

$slice = function_exists('tondi_gallery_current_album_slice')
    ? tondi_gallery_current_album_slice()
    : null;

$album = $slice['album'] ?? null;

$index_url = get_permalink(get_queried_object_id());

?>

<?php if (have_posts()): ?>
    <?php while (have_posts()):
        the_post(); ?>

        <main id="main" class="page sub-page gallery" role="main">
            <div class="container">
                <div class="sub-page-layout">
                    <article <?php post_class('sub-content'); ?>>
                        <header class="page-hero">
                            <?php if ($album): ?>
                                <a class="gallery-album__back" href="<?php echo esc_url($index_url); ?>">
                                    <span aria-hidden="true">&#8592;</span>
                                    <?php
                                    printf(
                                        /* translators: %s: gallery page title */
                                        esc_html__('Tagasi: %s', 'tondi'),
                                        esc_html(get_the_title())
                                    );
                                    ?>
                                </a>

                                <h1 class="page-title">
                                    <?php echo esc_html($album['title']); ?>
                                </h1>

                                <p class="gallery-album__count">
                                    <?php
                                    printf(
                                        esc_html(_n('%s pilt', '%s pilti', $album['count'], 'tondi')),
                                        esc_html(number_format_i18n($album['count']))
                                    );
                                    ?>
                                </p>
                            <?php else: ?>
                                <h1 class="page-title">
                                    <?php the_title(); ?>
                                </h1>
                                <?php if (has_excerpt()): ?>
                                    <p class="page-intro">
                                        <?php echo esc_html(get_the_excerpt()); ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </header>

                        <div class="page-content">
                            <?php if (!$album): ?>
                                <?php the_content(); ?>
                            <?php endif; ?>

                            <?php if ($requested_album_id > 0 && !$album): ?>
                                <p class="gallery-notice"><?php esc_html_e('Albumit ei leitud.', 'tondi'); ?></p>
                            <?php endif; ?>

                            <?php if ($album): ?>

                                <div class="gallery-page__grid">
                                    <?php foreach ($slice['attachment_ids'] as $att_id) :
                                        $full = wp_get_attachment_image_url($att_id, 'full');

                                        $caption = wp_get_attachment_caption($att_id);
                                        if ($caption === '') $caption = get_the_title($att_id);

                                        $alt = (string) get_post_meta($att_id, '_wp_attachment_image_alt', true);
                                        if ($alt === '') $alt = get_the_title($att_id);
                                    ?>
                                        <button
                                            type="button"
                                            class="front-gallery__item js-gallery-item"
                                            data-id="<?php echo (int) $att_id; ?>"
                                            data-alt="<?php echo esc_attr($alt); ?>"
                                            data-full="<?php echo esc_url($full); ?>"
                                            data-caption="<?php echo esc_attr($caption); ?>">
                                            <?php
                                            echo wp_get_attachment_image($att_id, 'medium_large', false, [
                                                'alt' => $alt,
                                                'loading' => 'lazy',
                                                'decoding' => 'async',
                                            ]);
                                            ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($slice['total_pages'] > 1): ?>
                                    <nav class="pagination" aria-label="<?php esc_attr_e('Piltide leheküljed', 'tondi'); ?>">
                                        <?php

                                        echo paginate_links([
                                            'base' => add_query_arg('album_page', '%#%', tondi_gallery_album_url($album['folder_id'], 1, get_queried_object_id())),
                                            'format' => '',
                                            'current' => $slice['page'],
                                            'total' => $slice['total_pages'],
                                            'mid_size' => 1,
                                            'prev_text' => __('‹ Eelmine', 'tondi'),
                                            'next_text' => __('Järgmine ›', 'tondi'),
                                        ]);

                                        ?>
                                    </nav>
                                <?php endif; ?>

                                <?php get_template_part('template-parts/gallery/lightbox'); ?>

                            <?php elseif (!empty($albums)) : ?>

                                <div class="gallery-albums">
                                    <?php foreach ($albums as $item) :
                                        $album_url = add_query_arg('album', $item['folder_id'], $index_url);
                                    ?>
                                        <a class="gallery-album-card" href="<?php echo esc_url($album_url); ?>">
                                            <span class="gallery-album-card__cover">
                                                <?php
                                                echo wp_get_attachment_image($item['cover_id'], 'medium_large', false, [
                                                    'alt' => '',
                                                    'loading' => 'lazy',
                                                    'decoding' => 'async',
                                                ]);
                                                ?>
                                            </span>

                                            <span class="gallery-album-card__body">
                                                <span class="gallery-album-card__name">
                                                    <?php echo esc_html($item['title']); ?>
                                                </span>
                                                <span class="gallery-album-card__count">
                                                    <?php
                                                    printf(
                                                        esc_html(_n('%s pilt', '%s pilti', $item['count'], 'tondi')),
                                                        esc_html(number_format_i18n($item['count']))
                                                    );
                                                    ?>
                                                </span>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>

                            <?php else : ?>
                                <p><?php esc_html_e('Pilte ei leitud.', 'tondi'); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </div>
        </main>
    <?php endwhile; ?>
<?php endif; ?>

<?php get_footer(); ?>
