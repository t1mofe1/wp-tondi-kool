<?php

if (!defined('ABSPATH')) {
    exit;
}

$departments = get_terms([
    'taxonomy' => 'worker_department',
    'hide_empty' => true,
]);

$search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$dept = isset($_GET['dept']) ? sanitize_text_field(wp_unslash($_GET['dept'])) : '';

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

?>

<?php echo '<!-- ContactsList -->'; ?>

<form class="filters" id="contacts-filter" method="get" action="<?php echo esc_url(get_permalink()); ?>">
    <div class="filters__search">
        <input type="search" id="search" name="q" placeholder="<?php echo esc_attr__('Otsi nime järgi...', 'tondi'); ?>"
            value="<?php echo esc_attr($search); ?>" autocomplete="off">

        <button class="filters__clear" type="button" aria-label="<?php echo esc_attr__('Tühjenda otsing', 'tondi'); ?>"
            hidden>×</button>
    </div>

    <select id="dept" name="dept">
        <option value="">
            <?php echo esc_html__('Kõik osakonnad', 'tondi'); ?>
        </option>

        <?php if (!is_wp_error($departments)): ?>
            <?php foreach ($departments as $d): ?>
                <option value="<?php echo esc_attr($d->slug); ?>" <?php selected($dept, $d->slug); ?>>
                    <?php echo esc_html($d->name); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>

    <noscript>
        <button type="submit">
            <?php echo esc_html__('Filtreeri', 'tondi'); ?>
        </button>
    </noscript>
</form>

<div id="contacts-results">
    <?php if (!$q->have_posts()): ?>
        <p class="empty"><?php echo esc_html__('Ühtegi töötajat ei leitud', 'tondi'); ?></p>
    <?php else: ?>
        <div class="contacts-grid">
            <?php while ($q->have_posts()):
                $q->the_post(); ?>
                <?php get_template_part('template-parts/workers/person-card', null, ['post_id' => get_the_ID()]); ?>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('contacts-filter');
        const q = document.getElementById('search');
        const dept = document.getElementById('dept');
        const results = document.getElementById('contacts-results');
        const clearBtn = form?.querySelector('.filters__clear');

        if (!form || !q || !dept || !results) return;

        let timer = null;
        let controller = null;

        function syncClear() {
            if (!clearBtn) return;
            clearBtn.hidden = !q.value;
        }

        async function updateResults(pushState = false) {
            if (controller) controller.abort();
            controller = new AbortController();

            const params = new URLSearchParams(new FormData(form));
            const url = `${form.action}${params.toString() ? `?${params.toString()}` : ''}`;

            if (pushState) {
                window.history.pushState({}, '', url);
            } else {
                window.history.replaceState({}, '', url);
            }

            results.style.opacity = '0.6';

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'fetch' },
                    signal: controller.signal,
                });

                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const next = doc.getElementById('contacts-results');

                if (next) results.innerHTML = next.innerHTML;
            } catch (e) {
                if (e.name !== 'AbortError') console.warn(e);
            } finally {
                results.style.opacity = '';
                controller = null;
            }
        }

        function debouncedUpdate() {
            clearTimeout(timer);
            timer = setTimeout(() => updateResults(false), 200);
        }

        // init clear state
        syncClear();

        // realtime
        q.addEventListener('input', () => {
            syncClear();
            debouncedUpdate();
        });

        dept.addEventListener('change', () => updateResults(true));

        // clear button
        clearBtn?.addEventListener('click', () => {
            q.value = '';
            q.focus();
            syncClear();
            updateResults(false);
        });

        // handle back/forward
        window.addEventListener('popstate', () => updateResults(false));
    });
</script>


<?php echo '<!-- /ContactsList -->'; ?>
