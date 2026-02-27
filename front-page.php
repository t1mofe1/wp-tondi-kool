<?php get_header(); ?>

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
                    'posts_per_page' => 3,
                    'ignore_sticky_posts' => true,
                    'post_status' => 'publish',
                ]);

                $news_more_url = get_post_type_archive_link('news');

                ?>

                <?php if ($news_q->have_posts()): ?>
                    <ul class="news-cards">
                        <?php while ($news_q->have_posts()):
                            $news_q->the_post(); ?>
                            <li <?php post_class('news-card'); ?>>
                                <h2 class="news-card-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <p class="news-card-excerpt">
                                    <?php
                                    if (has_excerpt()) {
                                        echo esc_html(get_the_excerpt());
                                    } else {
                                        echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_content('')), 24));
                                    }
                                    ?>
                                </p>

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

                                <a class="news-card-more" href="<?php the_permalink(); ?>">
                                    <?php esc_html_e('Loe edasi...', 'tondi'); ?>
                                </a>
                            </li>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    </ul>

                    <a class="read-more-news-btn" href="<?php echo esc_url($news_more_url); ?>">
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
            $events = tondi_get_upcoming_events(5, 5);
            ?>

            <section class="home-calendar">
                <h2 class="home-calendar__title">
                    <?php esc_html_e('Kalender', 'tondi'); ?>
                </h2>

                <?php if (!empty($events)) : ?>
                    <ol class="home-calendar__list">
                        <?php foreach ($events as $index => $event) : ?>
                            <?php

                            /** @var DateTimeImmutable $start */
                            $start = $event['start'] ?? null;
                            /** @var DateTimeImmutable|null $end */
                            $end = $event['end'] ?? null;

                            if (!$start instanceof DateTimeImmutable) {
                                continue;
                            }

                            $tz = new DateTimeZone('Europe/Tallinn');
                            $start = $start->setTimezone($tz);
                            if ($end instanceof DateTimeImmutable) {
                                $end = $end->setTimezone($tz);
                            }

                            $is_all_day = !empty($event['all_day']);

                            // For all-day events, DTEND is typically exclusive -> show end - 1 day
                            $endDisplay = null;
                            if ($end instanceof DateTimeImmutable) {
                                $endDisplay = $is_all_day ? $end->modify('-1 day') : $end;
                            }

                            $start_date_attr = $start->format('Y-m-d');
                            $start_date_text = $start->format('d.m');
                            $start_time_attr = $start->format('H:i');
                            $start_time_text = $start->format('H:i');

                            $end_date_attr = $endDisplay ? $endDisplay->format('Y-m-d') : '';
                            $end_date_text = $endDisplay ? $endDisplay->format('d.m') : '';
                            $end_time_attr = $endDisplay ? $endDisplay->format('H:i') : '';
                            $end_time_text = $endDisplay ? $endDisplay->format('H:i') : '';

                            $same_day = $endDisplay ? ($start->format('Y-m-d') === $endDisplay->format('Y-m-d')) : true;

                            $name = $event['summary'] ?? '';
                            $place = $event['location'] ?? '';

                            ?>

                            <li class="home-calendar__item">
                                <div class="home-calendar__info">
                                    <h3 class="home-calendar__name">
                                        <?php echo esc_html($name); ?>
                                    </h3>

                                    <?php if ($place): ?>
                                        <p class="home-calendar__place">
                                            <?php echo esc_html($place); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="home-calendar__meta">
                                    <!-- DATE GROUP -->
                                    <div class="home-calendar__date-group">
                                        <time class="home-calendar__date" datetime="<?php echo esc_attr($start_date_attr); ?>">
                                            <?php echo esc_html($start_date_text); ?>
                                        </time>

                                        <?php if ($endDisplay && !$same_day): ?>
                                            <span class="home-calendar__dash" aria-hidden="true">–</span>
                                            <time class="home-calendar__date" datetime="<?php echo esc_attr($end_date_attr); ?>">
                                                <?php echo esc_html($end_date_text); ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$is_all_day): ?>
                                        <!-- TIME GROUP -->
                                        <div class="home-calendar__time-group">
                                            <time class="home-calendar__time" datetime="<?php echo esc_attr($start_time_attr); ?>">
                                                <?php echo esc_html($start_time_text); ?>
                                            </time>

                                            <?php if ($endDisplay): ?>
                                                <span class="home-calendar__dash" aria-hidden="true">–</span>
                                                <time class="home-calendar__time" datetime="<?php echo esc_attr($end_time_attr); ?>">
                                                    <?php echo esc_html($end_time_text); ?>
                                                </time>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>

                            <?php if ($index < count($events) - 1) : ?>
                                <hr class="home-calendar__separator" />
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>

                    <!-- <a class="home-events__more_btn" href="#"> -->
                    <?php // esc_html_e('Vaata kõiki sündmusi', 'tondi');
                    ?>
                    <!-- </a> -->
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

                $folder_id = (int) get_field('front_page_gallery_folder', 'option');
                $max_slots = (int) get_field('front_page_gallery_limit', 'option') ?: 6;
                if ($max_slots <= 0) {
                    $max_slots = 6;
                }

                $attachment_ids = [];

                if ($folder_id > 0 && class_exists(\FileBird\Classes\Helpers::class)) {
                    $attachment_ids = (array) \FileBird\Classes\Helpers::getAttachmentIdsByFolderId($folder_id);
                    $attachment_ids = array_values(array_filter(array_map('intval', $attachment_ids)));
                }

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

                <?php $gallery_page = get_page_by_path('galerii'); ?>
                <?php if ($gallery_page): ?>
                    <div class="front-gallery__more_wrap">
                        <a
                            class="front-gallery__more_btn"
                            href="<?php echo esc_url($gallery_page ? get_permalink($gallery_page) : home_url('/')); ?>">
                            <?php esc_html_e('Vaata rohkem', 'tondi'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Lightbox overlay -->
                <div class="gallery-lightbox" aria-hidden="true">
                    <div class="gallery-lightbox__backdrop"></div>

                    <figure class="gallery-lightbox__content" role="dialog" aria-modal="true" aria-label="Pilt suurelt">
                        <button type="button" class="gallery-lightbox__close" aria-label="Sulge">&times;</button>

                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__prev" aria-label="Eelmine pilt">
                            &#10094;
                        </button>

                        <div class="gallery-lightbox__stage">
                            <img src="" alt="" class="gallery-lightbox__img is-active" />
                        </div>

                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__next" aria-label="Järgmine pilt">
                            &#10095;
                        </button>

                        <figcaption></figcaption>
                        <div class="gallery-lightbox__counter" aria-live="polite"></div>
                    </figure>
                </div>
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

            <?php if (!$projects = get_field('projects_columns', 'option')): ?>
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

                document.body.classList.add('lock');

                const input = modal.querySelector('input[type="search"]');
                setTimeout(() => input?.focus(), 50);
            }

            if (e.target.matches('[data-search-close]') || e.target.closest('[data-search-close]')) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');

                document.body.classList.remove('lock');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('site-search-modal');

                if (!modal) return;

                if (modal.classList.contains('is-open')) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.classList.remove('is-open');

                    document.body.classList.remove('lock');
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

        fastlinksMenu.classList.remove('is-hidden');

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

        // Fastlinks mobile overlay
        fastlinksMenu.addEventListener('click', function() {
            if (!mobileQuery.matches) {
                return;
            }

            const isOpen = fastlinksMenu.classList.contains('open');

            if (isOpen) {
                fastlinksMenu.classList.remove('open');
                fastlinksMenu.setAttribute('aria-expanded', 'false');

                const overlay = document.getElementById('fastlinks-overlay');
                if (overlay) {
                    document.body.removeChild(overlay);
                    document.body.classList.remove('lock');
                }
            } else {
                const overlay = document.createElement('div');
                overlay.id = 'fastlinks-overlay';

                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.zIndex = '500';
                overlay.style.background = 'rgba(0, 0, 0, 0.35)';

                overlay.addEventListener('click', function() {
                    fastlinksMenu.classList.remove('open');
                    fastlinksMenu.setAttribute('aria-expanded', 'false');
                    document.body.removeChild(overlay);
                    document.body.classList.remove('lock');
                });

                document.body.appendChild(overlay);
                document.body.classList.add('lock');

                fastlinksMenu.classList.add('open');
                fastlinksMenu.setAttribute('aria-expanded', 'true');
            }
        });
    });
</script>

<?php get_footer(); ?>
