<?php defined('ABSPATH') or die('No direct access');

$post_id = $args['post_id'] ?? get_the_ID();

$permalink = get_permalink($post_id);
$name = get_the_title($post_id);
$position = tondi_worker_position($post_id);

$terms = get_the_terms($post_id, 'worker_department');
$dept_names = (!is_wp_error($terms) && !empty($terms)) ? wp_list_pluck($terms, 'name') : [];

$avatar_url = get_the_post_thumbnail_url($post_id, 'medium');

$initials = '';
if ($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
}

?>

<article class="person" data-worker-card>
    <a class="person__link" href="<?php echo esc_url($permalink); ?>"
        data-worker-modal="<?php echo esc_attr($post_id); ?>" aria-haspopup="dialog">

        <div class="person__avatar" aria-hidden="true">
            <?php if ($avatar_url): ?>
                <?php tondi_worker_avatar($post_id, 'medium', 'person__avatar-img'); ?>
            <?php else: ?>
                <span class="person__avatar_initials"><?php echo esc_html($initials); ?></span>
            <?php endif; ?>
        </div>

        <div class="person__main">
            <h3 class="person__name">
                <?php echo esc_html($name); ?>
            </h3>

            <?php if (!empty($dept_names)): ?>
                <div class="person__badges">
                    <?php foreach ($dept_names as $dn): ?>
                        <span class="person__badge">
                            <?php echo esc_html($dn); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($position): ?>
                <p class="person__role">
                    <?php echo esc_html($position); ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
</article>
