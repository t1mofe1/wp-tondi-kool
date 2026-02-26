<?php defined('ABSPATH') or die('No direct access');

$post_id = $args['post_id'] ?? get_the_ID();

$name = get_the_title($post_id);
$position = (string) tondi_worker_position($post_id);
$email = (string) tondi_worker_email($post_id);
$phones = (array) tondi_worker_phones($post_id);
$notes = (string) tondi_worker_notes($post_id);

$terms = get_the_terms($post_id, 'worker_department');
$dept_names = (!is_wp_error($terms) && !empty($terms)) ? wp_list_pluck($terms, 'name') : [];

$avatar_url = get_the_post_thumbnail_url($post_id, 'medium');

// Initials
$initials = '';
if ($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = mb_strtoupper(
        mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1),
        'UTF-8'
    );
}

// filter empty phone rows
$phones = array_values(array_filter($phones, function ($p) {
    $num = is_array($p) ? (string) ($p['number'] ?? '') : '';
    return trim($num) !== '';
}));

?>

<div class="worker_modal__body">
    <span class="worker_modal__avatar">
        <?php if ($avatar_url): ?>
            <?php tondi_worker_avatar($post_id, 'medium', 'worker_modal__avatar_img'); ?>
        <?php else: ?>
            <span class="worker_modal__avatar_initials"><?php echo esc_html($initials); ?></span>
        <?php endif; ?>
    </span>

    <h2 class="worker_modal__name"><?php echo esc_html($name); ?></h2>

    <?php if (!empty($dept_names)): ?>
        <div class="worker_modal__badges" aria-label="<?php echo esc_attr__('Osakonnad', 'tondi'); ?>">
            <?php foreach ($dept_names as $dn): ?>
                <span class="worker_modal__badge"><?php echo esc_html($dn); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="worker_modal__info">
        <?php if ($position !== ''): ?>
            <div class="worker_modal__info_row">
                <!-- briefcase -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    <rect width="20" height="14" x="2" y="6" rx="2"></rect>
                </svg>
                <span><?php echo esc_html($position); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($email !== '' && is_email($email)): ?>
            <div class="worker_modal__info_row">
                <!-- mail -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                </svg>

                <a class="worker_modal__link" href="mailto:<?php echo esc_attr($email); ?>">
                    <?php echo esc_html($email); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($phones)): ?>
            <?php foreach ($phones as $p):
                $label = is_array($p) ? (string) ($p['label'] ?? '') : '';
                $num = is_array($p) ? (string) ($p['number'] ?? '') : '';
                $tel = tondi_phone_to_tel_href($num);

                if ($tel === '') {
                    continue;
                }

            ?>

                <div class="worker_modal__info_row">
                    <!-- phone -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>

                    <a class="worker_modal__link" href="tel:<?php echo esc_attr($tel); ?>">
                        <?php echo esc_html(trim(($label ? $label . ' ' : '') . $num)); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($notes !== ''): ?>
            <div class="worker_modal__info_row worker_modal__notes">
                <!-- info -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="16" y2="12"></line>
                    <line x1="12" x2="12.01" y1="8" y2="8"></line>
                </svg>

                <span style="text-align: start; line-height: 1;"><?php echo wp_kses_post(nl2br(esc_html($notes))); ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
