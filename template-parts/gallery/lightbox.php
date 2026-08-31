<?php

/**
 * Lightbox overlay. Driven by assets/js/lightbox-gallery.js, which binds it to
 * every .js-gallery-item on the page.
 *
 * @param bool $args['deep_link'] Whether opening a photo writes ?photo=ID. Default true.
 */

if (!defined('ABSPATH')) {
    exit;
}

$deep_link = !isset($args['deep_link']) || (bool) $args['deep_link'];

?>

<div class="gallery-lightbox" aria-hidden="true"<?php echo $deep_link ? '' : ' data-deep-link="false"'; ?>>
    <div class="gallery-lightbox__backdrop"></div>

    <figure class="gallery-lightbox__content" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Pilt suurelt', 'tondi'); ?>">
        <button type="button" class="gallery-lightbox__close" aria-label="<?php esc_attr_e('Sulge', 'tondi'); ?>">&times;</button>

        <button type="button" class="gallery-lightbox__nav gallery-lightbox__prev" aria-label="<?php esc_attr_e('Eelmine pilt', 'tondi'); ?>">
            &#10094;
        </button>

        <div class="gallery-lightbox__stage">
            <img src="" alt="" class="gallery-lightbox__img is-active" />
        </div>

        <button type="button" class="gallery-lightbox__nav gallery-lightbox__next" aria-label="<?php esc_attr_e('Järgmine pilt', 'tondi'); ?>">
            &#10095;
        </button>

        <figcaption></figcaption>
        <div class="gallery-lightbox__counter" aria-live="polite"></div>
    </figure>
</div>
