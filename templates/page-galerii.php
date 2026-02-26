<?php

/**
 * Template Name: Galerii
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

?>

<?php if (have_posts()): ?>
    <?php while (have_posts()):
        the_post(); ?>

        <main id="main" class="page sub-page gallery" role="main">
            <div class="container">
                <div class="sub-page-layout">
                    <?php # get_template_part('parts/sidebar', 'subnav');
                    ?>

                    <article <?php post_class('sub-content'); ?>>
                        <header class="page-hero">
                            <h1 class="page-title">
                                <?php the_title(); ?>
                            </h1>
                            <?php if (has_excerpt()): ?>
                                <p class="page-intro">
                                    <?php echo esc_html(get_the_excerpt()); ?>
                                </p>
                            <?php endif; ?>
                        </header>

                        <div class="page-content">
                            <?php the_content(); ?>

                            <?php

                            $folder_id = (int) get_field('front_page_gallery_folder', 'option');

                            $attachment_ids = [];
                            if ($folder_id > 0 && class_exists(\FileBird\Classes\Helpers::class)) {
                                $attachment_ids = (array) \FileBird\Classes\Helpers::getAttachmentIdsByFolderId($folder_id);
                                $attachment_ids = array_values(array_filter(array_map('intval', $attachment_ids)));
                            }

                            // Optional: newest first by attachment date (instead of FileBird order)
                            if (!empty($attachment_ids)) {
                                usort($attachment_ids, function ($a, $b) {
                                    return get_post_time('U', true, $b) <=> get_post_time('U', true, $a);
                                });
                            }

                            ?>

                            <?php if (!empty($attachment_ids)) : ?>
                                <div class="gallery-page__grid">
                                    <?php foreach ($attachment_ids as $att_id) :
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

                                <!-- Lightbox overlay (reuse your existing markup + JS) -->
                                <div class="gallery-lightbox" aria-hidden="true">
                                    <div class="gallery-lightbox__backdrop"></div>

                                    <figure class="gallery-lightbox__content" role="dialog" aria-modal="true" aria-label="Pilt suurelt">
                                        <button type="button" class="gallery-lightbox__close" aria-label="Sulge">&times;</button>

                                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__prev" aria-label="Eelmine pilt">
                                            &#10094;
                                        </button>

                                        <div class="gallery-lightbox__stage">
                                            <img src="" alt="" class="gallery-lightbox__img is-active" />
                                        </div>

                                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__next" aria-label="Järgmine pilt">
                                            &#10095;
                                        </button>

                                        <figcaption></figcaption>
                                        <div class="gallery-lightbox__counter" aria-live="polite"></div>
                                    </figure>
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
