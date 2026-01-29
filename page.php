<?php get_header(); ?>

<?php the_post(); ?>

<main id="main" class="page sub-page" role="main">
    <div class="container">
        <div class="sub-page-layout">
            <?php get_template_part('parts/sidebar', 'subnav'); ?>

            <article <?php post_class('sub-content'); ?>>
                <header class="page-hero">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    <?php if (has_excerpt()): ?>
                        <p class="page-intro"><?php echo get_the_excerpt(); ?></p>
                    <?php endif; ?>
                </header>

                <div class="page-content">
                    <?php the_content(); ?>
                </div>
            </article>
        </div>
    </div>
</main>

<?php get_footer(); ?>
