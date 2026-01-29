<?php

get_header();

$search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$dept = isset($_GET['dept']) ? sanitize_text_field(wp_unslash($_GET['dept'])) : '';

$dept_order = [
    'juhtkond',
    'pedagoogiline-personal',
    'abiopetajad',
    'tugipersonal',
    'tugispetsialistid',
    'tehniline-personal',
];

$departments = get_terms([
    'taxonomy' => 'worker_department',
    'hide_empty' => true,
]);

if (!is_wp_error($departments) && !empty($dept_order)) {
    usort($departments, function ($a, $b) use ($dept_order) {
        $ai = array_search($a->slug, $dept_order, true);
        $bi = array_search($b->slug, $dept_order, true);

        $ai = ($ai === false) ? 999 : $ai;
        $bi = ($bi === false) ? 999 : $bi;

        if ($ai === $bi) {
            return strcasecmp($a->name, $b->name);
        }

        return $ai <=> $bi;
    });
}

$args = [
    'post_type' => 'worker',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
];

if ($search) {
    $args['s'] = $search;
}

if ($dept) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'worker_department',
            'field' => 'slug',
            'terms' => $dept,
        ]
    ];
}

$q = new WP_Query($args);

// Group by department (first term)
$grouped = [];
if ($q->have_posts()) {
    foreach ($q->posts as $p) {
        $terms = get_the_terms($p->ID, 'worker_department');

        $group_key = 'uncategorized';
        $group_name = __('Other', 'tondi');

        if (!is_wp_error($terms) && !empty($terms)) {
            $t = $terms[0];
            $group_key = $t->slug;
            $group_name = $t->name;
        }

        if (!isset($grouped[$group_key])) {
            $grouped[$group_key] = ['name' => $group_name, 'items' => []];
        }

        $grouped[$group_key]['items'][] = $p->ID;
    }
}

// Order groups (according to dept_order; uncategorized last)
uksort($grouped, function ($a, $b) use ($dept_order, $grouped) {
    if ($a === 'uncategorized') {
        return 1;
    }
    if ($b === 'uncategorized') {
        return -1;
    }

    $ai = array_search($a, $dept_order, true);
    $bi = array_search($b, $dept_order, true);

    $ai = ($ai === false) ? 999 : $ai;
    $bi = ($bi === false) ? 999 : $bi;

    if ($ai === $bi) {
        return strcasecmp($grouped[$a]['name'], $grouped[$b]['name']);
    }

    return $ai <=> $bi;
});

?>

<main class="staff-directory">
    <div class="container">
        <header class="staff-directory__header">
            <h1 class="staff-directory__title">
                <?php post_type_archive_title(); ?>
            </h1>

            <form class="staff-directory__controls" method="get"
                action="<?php echo esc_url(get_post_type_archive_link('worker')); ?>">
                <input class="staff-directory__search" type="search" name="q" value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php echo esc_attr__('Search name, role, email…', 'tondi'); ?>"
                    aria-label="<?php echo esc_attr__('Search workers', 'tondi'); ?>" />
            </form>

            <?php if (!is_wp_error($departments) && !empty($departments)): ?>
                <nav class="staff-tabs" aria-label="<?php echo esc_attr__('Departments', 'tondi'); ?>">
                    <a class="staff-tab <?php echo $dept === '' ? 'is-active' : ''; ?>"
                        href="<?php echo esc_url(remove_query_arg('dept')); ?>">
                        <?php echo esc_html__('All', 'tondi'); ?>
                    </a>

                    <?php foreach ($departments as $d): ?>
                        <?php

                        $url = add_query_arg('dept', $d->slug, remove_query_arg(['paged']));

                        if ($search) {
                            $url = add_query_arg('q', $search, $url);
                        }

                        ?>

                        <a class="staff-tab <?php echo $dept === $d->slug ? 'is-active' : ''; ?>"
                            href="<?php echo esc_url($url); ?>">
                            <?php echo esc_html($d->name); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </header>

        <?php if (!$q->have_posts()): ?>
            <p class="staff-directory__empty">
                <?php echo esc_html__('No workers found.', 'tondi'); ?>
            </p>
        <?php else: ?>
            <section class="staff-directory__groups">
                <?php foreach ($grouped as $group_slug => $group): ?>
                    <details class="staff-group" <?php echo ($dept && $dept === $group_slug) ? ' open' : ''; ?>>
                        <summary class="staff-group__summary">
                            <span class="staff-group__title">
                                <?php echo esc_html($group['name']); ?>
                            </span>

                            <span class="staff-group__count">
                                <?php echo esc_html(count($group['items'])); ?>
                            </span>
                        </summary>

                        <div class="staff-group__list">
                            <?php foreach ($group['items'] as $post_id): ?>
                                <?php get_template_part('template-parts/workers/person-card', null, ['post_id' => $post_id]); ?>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </section>
        <?php endif;

        wp_reset_postdata();

        ?>
    </div>
</main>

<?php get_footer(); ?>
