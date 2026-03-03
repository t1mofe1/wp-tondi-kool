<?php

echo '<!-- Footer -->';

$columns = get_field('footer_columns', 'option') ?: [];

$logo_caption_text = get_field('footer_bottom_content', 'option') ?: '';

/**
 * Footer link icon HTML
 * Prefers uploaded image (attachment ID), falls back to bundled icon.
 */
if (!function_exists('tondi_footer_icon_html')) {
    function tondi_footer_icon_html(int $attachment_id = 0)
    {
        if ($attachment_id > 0) {
            // Adds width/height + loading="lazy"
            $img = wp_get_attachment_image(
                $attachment_id,
                'full',
                false,
                [
                    'class' => 'footer-link__icon-img',
                    'alt' => '',
                    'decoding' => 'async',
                    'fetchpriority' => 'low',
                ]
            );

            if ($img) {
                return '<span class="footer-link__icon">' . $img . '</span>';
            }
        }

        $src = get_stylesheet_directory_uri() . '/assets/images/icons/Lingid.svg';

        return '
            <span class="footer-link__icon">
                <img
                    class="footer-link__icon-img"
                    src="' . esc_url($src) . '"
                    alt=""
                    decoding="async"
                />
            </span>';
    }
}

?>

<!-- Decorative border -->
<div class="footer-border" aria-hidden="true"></div>

<footer class="site-footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            <?php if (!empty($columns)): ?>
                <?php foreach ($columns as $col): ?>
                    <?php

                    $heading = trim($col['heading'] ?? '');
                    $width = $col['width'] ?? 'auto';
                    $style = ($width !== 'auto') ? 'style="--col-width:' . esc_attr($width) . ';"' : '';

                    ?>

                    <div class="footer-col" <?php echo $style; ?>>
                        <?php if ($heading !== ''): ?>
                            <h3 class="footer-col__heading"><?php echo esc_html($heading); ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($col['blocks'])): ?>
                            <?php foreach ($col['blocks'] as $block): ?>
                                <?php switch ($block['acf_fc_layout']) {
                                    case 'links':
                                        $links = $block['items'] ?? [];

                                        if (!empty($links)): ?>
                                            <ul class="footer-block footer-block--links">
                                                <?php foreach ($links as $ln):
                                                    $item = $ln['item'] ?? null;

                                                    if (!is_array($item)) continue;

                                                    $url = trim($item['url'] ?? '');
                                                    $title = trim($item['title'] ?? '');
                                                    $target = $item['target'] ?? '_self';

                                                    if (!$url || !$title) continue;

                                                    $icon_id = 0;
                                                    if (isset($ln['icon_img'])) {
                                                        $icon_id = (int) $ln['icon_img'];
                                                    } elseif (isset($ln['icon'])) {
                                                        $icon_id = (int) $ln['icon'];
                                                    }

                                                    $icon_html = tondi_footer_icon_html($icon_id);

                                                    $rel = [];
                                                    if ($target === '_blank') {
                                                        $rel[] = 'noopener';
                                                        $rel[] = 'noreferrer';
                                                    }
                                                ?>

                                                    <li class="footer-link">
                                                        <a
                                                            class="footer-link__anchor"
                                                            href="<?php echo esc_url($url); ?>"
                                                            target="<?php echo esc_attr($target); ?>"
                                                            <?php (!empty($rel) ? ' rel="' . esc_attr(implode(' ', $rel)) . '"' : ''); ?>>
                                                            <?php echo $icon_html; ?>

                                                            <span class="footer-link__label">
                                                                <?php echo esc_html($title); ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif;
                                        break;

                                    case 'rich_text':
                                        $html = $block['content'] ?? '';
                                        if ($html !== ''): ?>
                                            <div class="footer-block footer-block--richtext">
                                                <?php echo wp_kses_post($html); ?>
                                            </div>
                                        <?php endif;
                                        break;

                                    case 'map':
                                        $embed_url = trim($block['embed_url'] ?? '');
                                        $height = (int) ($block['height'] ?? 280);
                                        $title = trim($block['title'] ?? 'Google Map');

                                        // Basic allow-list for safety
                                        $ok = ($embed_url !== '' && preg_match('#^https://www\.google\.com/maps/embed\?#', $embed_url));

                                        if ($ok):
                                            if ($height < 180) $height = 180;
                                            if ($height > 600) $height = 600;
                                        ?>

                                            <div class="footer-block footer-block--map">
                                                <div class="footer-map" style="--map-h: <?php echo esc_attr($height); ?>px;">
                                                    <iframe
                                                        title="<?php echo esc_attr($title); ?>"
                                                        src="<?php echo esc_url($embed_url); ?>"
                                                        loading="lazy"
                                                        frameborder="0" style="border:0"
                                                        referrerpolicy="no-referrer-when-downgrade"
                                                        allowfullscreen></iframe>
                                                </div>
                                            </div>

                                <?php
                                        endif;
                                        break;
                                } ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="footer-col">
                    <p><?php esc_html_e('Please configure footer columns in settings', 'tondi'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="logo-caption">
            <img
                src="<?php echo get_stylesheet_directory_uri() . '/assets/images/Logo_valge.svg'; ?>"
                alt=""
                class="logo-caption__image" />
            <span class="logo-caption__text">
                <?php echo esc_html($logo_caption_text); ?>
            </span>
        </div>
    </div>
</footer>

<?php
echo '<!-- /Footer -->';
