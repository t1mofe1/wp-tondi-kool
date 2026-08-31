<?php

get_header();

?>

<main id="main" class="home" role="main">
    <div class="container">
        <section class="home-highlights">
            <section class="fastlinks">
                <h2 class="fastlinks-title">Kiirviited</h2>

                <?php
                wp_nav_menu([
                    'theme_location' => 'fastlinks',
                    'container' => 'nav',
                    'container_class' => 'fastlinks-nav',
                    'menu_class' => 'fastlinks-menu',
                    'walker' => new Tondi_Fastlinks_Walker(),
                ]);
                ?>
            </section>

            <section class="news">
                <h2 class="news-title"><?php esc_html_e('Uudised', 'tondi'); ?></h2>

                <?php

                $news_q = new WP_Query([
                    'post_type' => 'news',
                    'posts_per_page' => 6,
                    'post_status' => 'publish',
                    'orderby' => [
                        'menu_order' => 'ASC',
                        'date' => 'DESC',
                    ],
                    'order' => 'ASC',
                ]);

                $news_more_url = get_post_type_archive_link('news');

                ?>

                <?php if ($news_q->have_posts()): ?>
                    <ul class="news-cards">
                        <?php while ($news_q->have_posts()):
                            $news_q->the_post(); ?>
                            <li <?php post_class('news-card'); ?>>
                                <a href="<?php the_permalink(); ?>">
                                    <h2 class="news-card-title">
                                        <?php the_title(); ?>
                                    </h2>

                                    <div class="news-card-image-wrapper">
                                        <?php
                                        if (has_post_thumbnail()) {
                                            the_post_thumbnail('news_card', [
                                                'alt' => the_title_attribute(['echo' => false]),
                                                'loading' => 'lazy',
                                                'decoding' => 'async',
                                            ]);
                                        } else {
                                            // Optional placeholder image
                                            echo '<div class="news-card-image-placeholder" aria-hidden="true"></div>';
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    </ul>

                    <a class="more_button" href="<?php echo esc_url($news_more_url); ?>">
                        <?php esc_html_e('Loe veel', 'tondi'); ?>
                    </a>
                <?php else: ?>
                    <p class="no-news">
                        <?php esc_html_e('Uudiseid ei leitud.', 'tondi'); ?>
                    </p>
                <?php endif; ?>
            </section>
        </section>
    </div>

    <!-- Decorative border -->
    <div class="home-border-one" aria-hidden="true"></div>

    <div class="container">
        <section class="home-calendar-gallery">
            <?php
            $events = tondi_get_upcoming_events(5, 900);
            ?>

            <section class="home-calendar">
                <h2 class="home-calendar__title">
                    <?php esc_html_e('Kalender', 'tondi'); ?>
                </h2>

                <?php if (!empty($events)) : ?>
                    <ol class="home-calendar__list">
                        <?php foreach ($events as $event) : ?>
                            <?php

                            $item = tondi_prepare_event_display($event);

                            if (!$item) {
                                continue;
                            }

                            ?>

                            <li class="home-calendar__item">
                                <button
                                    type="button"
                                    class="home-calendar__trigger"
                                    data-calendar-event="<?php echo esc_attr($item['id']); ?>"
                                    aria-haspopup="dialog">
                                    <span class="screen-reader-text">
                                        <?php
                                        printf(
                                            /* translators: %s: event name */
                                            esc_html__('Vaata sündmust: %s', 'tondi'),
                                            esc_html($item['name'])
                                        );
                                        ?>
                                    </span>
                                </button>

                                <div class="home-calendar__badge <?php echo $item['badge_is_span'] ? 'home-calendar__badge--span' : ''; ?>" aria-hidden="true">
                                    <span class="home-calendar__badge-day">
                                        <?php echo esc_html($item['badge_day']); ?>
                                    </span>
                                    <span class="home-calendar__badge-month">
                                        <?php echo esc_html($item['month_short']); ?>
                                    </span>
                                </div>

                                <div class="home-calendar__body">
                                    <h3 class="home-calendar__name">
                                        <?php echo esc_html($item['name']); ?>
                                    </h3>

                                    <p class="home-calendar__meta">
                                        <?php if ($item['meta_when']): ?>
                                            <time datetime="<?php echo esc_attr($item['start_date_attr']); ?>">
                                                <?php echo esc_html($item['meta_when']); ?>
                                            </time>
                                        <?php endif; ?>

                                        <?php if ($item['place']): ?>
                                            <span class="home-calendar__place">
                                                <?php echo esc_html($item['place']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($item['description_excerpt']): ?>
                                        <p class="home-calendar__excerpt">
                                            <?php echo esc_html($item['description_excerpt']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <span class="home-calendar__chevron" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                        <path d="M9 6l6 6-6 6" />
                                    </svg>
                                </span>

                                <template data-calendar-event-body="<?php echo esc_attr($item['id']); ?>">
                                    <?php get_template_part('template-parts/calendar/event-details', null, ['event' => $item]); ?>
                                </template>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                <?php else : ?>
                    <div class="home-calendar__empty">
                        <strong><?php esc_html_e('Sündmused on tulekul!', 'tondi'); ?></strong>
                        <p><?php esc_html_e('Uuendame kalendrit peagi.', 'tondi'); ?></p>
                    </div>

                <?php endif; ?>
            </section>

            <section class="front-gallery">
                <h2 class="front-gallery__title">
                    <?php esc_html_e('Galerii', 'tondi'); ?>
                </h2>

                <?php

                $folder_id = function_exists('get_field')
                    ? (int) get_field('front_page_gallery_folder', 'option')
                    : 0;

                $max_slots = function_exists('get_field')
                    ? (int) get_field('front_page_gallery_limit', 'option')
                    : 6;

                if ($max_slots <= 0) {
                    $max_slots = 6;
                }

                $attachment_ids = tondi_get_folder_attachment_ids($folder_id);

                // Randomize and shuffle
                if ($attachment_ids) {
                    shuffle($attachment_ids);
                    $attachment_ids = array_slice($attachment_ids, 0, $max_slots);
                }

                $found = count($attachment_ids);

                ?>

                <div class="front-gallery__grid">
                    <?php if (!empty($attachment_ids)): ?>
                        <?php foreach ($attachment_ids as $att_id):
                            $full = wp_get_attachment_image_url($att_id, 'full');

                            // Caption preference: attachment caption, fallback to title
                            $caption = wp_get_attachment_caption($att_id);
                            if ($caption === '') {
                                $caption = get_the_title($att_id);
                            }

                            // Alt: stored on attachment
                            $alt = get_post_meta($att_id, '_wp_attachment_image_alt', true);
                            if ($alt === '') {
                                $alt = get_the_title($att_id);
                            }

                        ?>

                            <button
                                type="button"
                                class="front-gallery__item js-gallery-item"
                                data-id="<?php echo (int) $att_id; ?>"
                                data-full="<?php echo esc_url($full); ?>"
                                data-caption="<?php echo esc_attr($caption); ?>">
                                <?php
                                echo wp_get_attachment_image($att_id, 'front_gallery', false, [
                                    'alt' => $alt,
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                ]);
                                ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php
                    // Placeholders if fewer than max slots items
                    for ($i = $found; $i < $max_slots; $i++): ?>
                        <div class="front-gallery__item front-gallery__item--placeholder reveal-on-scroll">
                            <div class="front-gallery-placeholder-inner"></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php

                $gallery_page_id = tondi_gallery_page_id();

                $more_url = tondi_gallery_folder_has_album($folder_id)
                    ? tondi_gallery_album_url($folder_id, 1, $gallery_page_id)
                    : ($gallery_page_id > 0 ? (string) get_permalink($gallery_page_id) : '');

                ?>

                <?php if ($more_url !== ''): ?>
                    <a class="more_button" href="<?php echo esc_url($more_url); ?>">
                        <?php esc_html_e('Vaata rohkem', 'tondi'); ?>
                    </a>
                <?php endif; ?>

                <?php get_template_part('template-parts/gallery/lightbox', null, ['deep_link' => false]); ?>
            </section>
        </section>
    </div>

    <!-- Decorative border -->
    <div class="home-border-two" aria-hidden="true"></div>

    <!-- home-projects -->
    <div class="container">
        <section class="home-projects">
            <h2 class="home-projects__title">
                <?php esc_html_e('Projektid', 'tondi'); ?>
            </h2>

            <?php $projects = function_exists('get_field')
                ? get_field('projects_columns', 'option')
                : false;
            ?>

            <?php if (empty($projects)) : ?>
                <p>
                    <?php esc_html_e('Projekte ei leitud.', 'tondi'); ?>
                </p>
            <?php else: ?>
                <div class="home-projects-grid">
                    <?php foreach ($projects as $project):
                        $image_id = $project['image'] ?? 0;
                        $link = $project['link'] ?? null;

                        if (!$image_id || empty($link['url'])) {
                            continue;
                        }

                        $url = $link['url'];
                        $target = $link['target'] ?? '_self';
                        $title = $link['title'] ?? '';

                        $alt = $title ?: __('Project', 'tondi');

                        $img_html = wp_get_attachment_image(
                            $image_id,
                            'medium_large',
                            false,
                            [
                                'class' => 'home-projects__image',
                                'alt' => $alt,
                            ]
                        );
                    ?>

                        <article class="home-projects__item">
                            <a
                                href="<?php echo esc_url($url); ?>"
                                class="home-projects__link"
                                target="<?php echo esc_attr($target); ?>"
                                <?php echo ($target === '_blank') ? 'rel="noopener"' : ''; ?>>
                                <?php echo $img_html; ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Decorative border -->
    <div class="home-border-three" aria-hidden="true"></div>
</main>

<div class="calendar-modal__backdrop" id="calendar-modal-backdrop" aria-hidden="true"></div>
<div
    class="calendar-modal"
    id="calendar-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="calendar-modal-title">
    <button
        type="button"
        class="calendar-modal__close"
        data-calendar-modal-close
        aria-label="<?php esc_attr_e('Sulge', 'tondi'); ?>">
        &times;
    </button>

    <div class="calendar-modal__content" id="calendar-modal-content"></div>
</div>

<div id="site-search-modal" class="site-search-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="site-search-modal__backdrop" data-search-close></div>

    <div class="site-search-modal__panel" role="document">
        <button type="button" class="site-search-modal__close" aria-label="<?php esc_attr_e('Close search', 'tondi'); ?>" data-search-close>
            ✕
        </button>

        <h2 class="site-search-modal__title"><?php esc_html_e('Otsing', 'tondi'); ?></h2>

        <form role="search" method="get" class="site-search-modal__form" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" name="s" class="site-search-modal__input" placeholder="<?php esc_attr_e('Kirjuta siia…', 'tondi'); ?>" />
            <button type="submit" class="site-search-modal__submit"><?php esc_html_e('Otsi', 'tondi'); ?></button>
        </form>

        <div class="site-search-modal__results" hidden></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', (e) => {
            const openBtn = e.target.closest('.fastlinks-search-open');
            const modal = document.getElementById('site-search-modal');

            if (!modal) return;

            if (openBtn) {
                e.preventDefault();

                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('is-open');

                window.TondiScrollLock?.lock();

                const input = modal.querySelector('input[type="search"]');
                setTimeout(() => input?.focus(), 50);
            }

            if (e.target.matches('[data-search-close]') || e.target.closest('[data-search-close]')) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');

                window.TondiScrollLock?.unlock();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('site-search-modal');

                if (!modal) return;

                if (modal.classList.contains('is-open')) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.classList.remove('is-open');

                    window.TondiScrollLock?.unlock();
                }
            }
        });
    });
</script>

<script>
    const mobileQuery = window.matchMedia('(max-width: 1024px)');

    document.addEventListener('DOMContentLoaded', function() {
        // --- Fade-in on scroll ---
        const revealItems = document.querySelectorAll('.front-gallery__item');

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.2
            });

            revealItems.forEach(el => io.observe(el));
        } else {
            // Fallback for browsers without IntersectionObserver
            revealItems.forEach(el => el.classList.add('is-visible'));
        }

        // --- Sidebar menu toggle ---
        const fastlinksMenu = document.querySelector('.fastlinks-menu');

        // Fastlinks mobile scroll visibility
        let lastY = window.scrollY;
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (ticking) return;

            window.requestAnimationFrame(function() {
                const y = window.scrollY;

                // If near top, always show
                if (y < 50) {
                    fastlinksMenu.classList.remove('is-hidden');

                    lastY = y;
                    ticking = false;

                    return;
                }

                const goingUp = y < lastY;

                if (goingUp) {
                    fastlinksMenu.classList.remove('is-hidden');
                } else {
                    fastlinksMenu.classList.add('is-hidden');
                }

                lastY = y;
                ticking = false;
            });

            ticking = true;
        }, {
            passive: true
        });
    });
</script>

<?php get_footer(); ?>
