<?php defined('ABSPATH') or die('No direct access');

/** @var array $args */
$event = $args['event'] ?? [];

if (empty($event['name'])) {
    return;
}

$all_day = !empty($event['all_day']);
$same_day = !empty($event['same_day']);

$date_text = $event['start_date_long'];
if (!$same_day && $event['end_date_long']) {
    $date_text .= ' – ' . $event['end_date_long'];
}

$time_text = '';
if (!$all_day) {
    $time_text = $event['start_time_text'];

    if ($event['end_time_text']) {
        $time_text .= ' – ' . $event['end_time_text'];
    }
}

?>

<div class="calendar-modal__body">
    <p class="calendar-modal__eyebrow">
        <?php echo esc_html($same_day ? $event['weekday'] : __('Sündmus', 'tondi')); ?>
    </p>

    <h2 class="calendar-modal__title" id="calendar-modal-title">
        <?php echo esc_html($event['name']); ?>
    </h2>

    <dl class="calendar-modal__facts">
        <div class="calendar-modal__fact">
            <dt><?php esc_html_e('Kuupäev', 'tondi'); ?></dt>
            <dd>
                <time datetime="<?php echo esc_attr($event['start_date_attr']); ?>">
                    <?php echo esc_html($date_text); ?>
                </time>
            </dd>
        </div>

        <div class="calendar-modal__fact">
            <dt><?php esc_html_e('Kellaaeg', 'tondi'); ?></dt>
            <dd>
                <?php if ($all_day): ?>
                    <?php esc_html_e('Terve päev', 'tondi'); ?>
                <?php else: ?>
                    <time datetime="<?php echo esc_attr($event['start_time_attr']); ?>">
                        <?php echo esc_html($time_text); ?>
                    </time>
                <?php endif; ?>
            </dd>
        </div>

        <?php if (!empty($event['place'])): ?>
            <div class="calendar-modal__fact">
                <dt><?php esc_html_e('Asukoht', 'tondi'); ?></dt>
                <dd><?php echo esc_html($event['place']); ?></dd>
            </div>
        <?php endif; ?>
    </dl>

    <?php if (!empty($event['description_html'])): ?>
        <div class="calendar-modal__description">
            <?php echo wp_kses_post($event['description_html']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($event['url'])): ?>
        <a class="calendar-modal__link" href="<?php echo esc_url($event['url']); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Ava kalendris', 'tondi'); ?>
        </a>
    <?php endif; ?>
</div>
