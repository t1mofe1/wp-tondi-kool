<?php

echo '<!-- Footer -->';

$columns = get_field('footer_columns', 'option') ?: [];

$logo_caption_text = get_field('footer_bottom_content', 'option') ?: '';

/**
 * Footer link icon HTML
 * Prefers uploaded image (attachment ID), falls back to bundled icon.
 */
if (!function_exists('tondi_footer_icon_html')) {
    function tondi_footer_icon_html(int $attachment_id = 0) {
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
                    $style = ($width !== 'auto') ? ' style="--col-width:' . esc_attr($width) . ';"' : '';

                    ?>

                    <div class="footer-col"<?php echo $style; ?>>
                        <?php if ($heading !== ''): ?>
                            <h3 class="footer-col__heading"><?php echo esc_html($heading); ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($col['blocks'])): ?>
                            <?php foreach ($col['blocks'] as $block): ?>
                                <?php switch ($block['acf_fc_layout']) {
                                    case 'contact':
                                        $org = trim($block['org_name'] ?? '');
                                        $regno = trim($block['org_regno'] ?? '');
                                        $addr = trim($block['address'] ?? '');
                                        $phones = $block['phones'] ?? [];
                                        $email = trim($block['email'] ?? '');
                                        ?>

                                        <div class="footer-block footer-block--contact">
                                            <?php if ($org !== ''): ?>
                                                <div class="contact__org"><?php echo esc_html($org); ?></div>
                                            <?php endif; ?>

                                            <?php if ($regno !== ''): ?>
                                                <div class="contact__regno"><?php echo esc_html__('Reg. nr. ', 'tondi') . esc_html($regno); ?></div>
                                            <?php endif; ?>

                                            <?php if ($addr !== ''): ?>
                                                <address class="contact__address"><?php echo nl2br(esc_html($addr)); ?></address>
                                            <?php endif; ?>

                                            <?php if (!empty($phones)): ?>
                                                <ul class="contact__phones">
                                                    <?php foreach ($phones as $p):
                                                        $label = trim($p['label'] ?? '');
                                                        $num = trim($p['number'] ?? '');

                                                        if ($num === '') continue;

                                                        $href = 'tel:' . preg_replace('/\s+/', '', $num);

                                                    ?>

                                                        <li>
                                                            <?php if ($label !== ''): ?>
                                                                <span class="phone__label"><?php echo esc_html($label); ?></span>
                                                            <?php endif; ?>

                                                            <a class="phone__number" href="<?php echo esc_url($href); ?>">
                                                                <?php echo esc_html($num); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>

                                            <?php if ($email !== ''): ?>
                                                <div class="contact__email">
                                                    <a href="mailto:<?php echo esc_attr($email); ?>">
                                                        <?php echo esc_html($email); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php

                                        break;

                                    case 'list':
                                        $items = $block['items'] ?? [];

                                        if (!empty($items)): ?>
                                            <ul class="footer-block footer-block--list">
                                                <?php foreach ($items as $item):
                                                    $text = trim($item['text'] ?? '');
                                                    if ($text === '') continue; ?>

                                                    <li><?php echo esc_html($text); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif;
                                        break;

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
                                                            <?php (!empty($rel) ? ' rel="' . esc_attr(implode(' ', $rel)) . '"' : ''); ?>
                                                        >
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
                class="logo-caption__image"
            />
            <span class="logo-caption__text">
                <?php echo esc_html($logo_caption_text); ?>
            </span>
        </div>
    </div>
</footer>

<?php
echo '<!-- /Footer -->';
