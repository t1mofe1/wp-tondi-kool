<?php

/**
 * 404 template.
 *
 * Previously every missing URL fell through to index.php and rendered blank.
 */

get_header();

$kontakt = get_pages([
    'meta_key' => '_wp_page_template',
    'meta_value' => 'templates/page-kontakt.php',
    'number' => 1,
]);

$kontakt_url = !empty($kontakt) ? (string) get_permalink($kontakt[0]) : home_url('/kontakt/');

?>

<main id="main" class="error-404" role="main">
    <div class="container">
        <div class="error-404__inner">

            <h1 class="error-404__title">
                <?php esc_html_e('Lehte ei leitud', 'tondi'); ?>
            </h1>

            <div class="error-404__text">
                <p><?php esc_html_e('Seda lehte ei õnnestunud leida.', 'tondi'); ?></p>
                <p><?php esc_html_e('Võib-olla on link vananenud või on leht kolinud.', 'tondi'); ?></p>
                <p><?php esc_html_e('Proovi otsingut või alusta avalehelt.', 'tondi'); ?></p>
            </div>

            <div class="error-404__search">
                <?php get_search_form(); ?>
            </div>

            <nav class="error-404__links" aria-label="<?php esc_attr_e('Kasulikud lingid', 'tondi'); ?>">
                <ul>
                    <li>
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <?php esc_html_e('Avaleht', 'tondi'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url((string) get_post_type_archive_link('news')); ?>">
                            <?php esc_html_e('Uudised', 'tondi'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($kontakt_url); ?>">
                            <?php esc_html_e('Kontakt', 'tondi'); ?>
                        </a>
                    </li>
                </ul>
            </nav>

        </div>
    </div>
</main>

<?php get_footer(); ?>
