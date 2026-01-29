<?php

/**
 * Template Name: Kontakt
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

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

?>

<?php get_header(); ?>

<?php if (have_posts()): ?>
    <?php while (have_posts()):
        the_post(); ?>

        <main id="main" class="page sub-page contacts" role="main">
            <div class="container">
                <div class="sub-page-layout">
                    <?php get_template_part('parts/sidebar', 'subnav'); ?>

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

                            <?php get_template_part('parts/contacts-list'); ?>
                        </div>
                    </article>
                </div>
            </div>
        </main>

        <div class="worker_modal_backdrop" id="worker-modal-backdrop" aria-hidden="true"></div>
        <div class="worker_modal" id="worker-modal" aria-hidden="true" role="dialog" aria-modal="true"
            aria-labelledby="worker-modal-title">
            <button class="worker_modal__close_button" type="button" data-modal-close
                aria-label="<?php echo esc_attr__('Close', 'tondi'); ?>">
                ×
            </button>

            <div class="worker_modal__status" id="worker-modal-status" hidden>
                <span class="spinner" aria-hidden="true" id="worker-modal-status-spinner"></span>
                <span id="worker-modal-status-text">
                    <?php echo esc_html__('Laeb...', 'tondi'); ?>
                </span>
            </div>

            <div class="worker_modal__content" id="worker-modal-content"></div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php get_footer(); ?>
