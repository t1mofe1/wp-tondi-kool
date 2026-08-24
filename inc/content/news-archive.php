<?php
/**
 * News archive: year/month pretty URLs, cached archive index, SEO metadata.
 *
 * Adds the following public routes on top of the `news` post type archive:
 *   /uudised/                    all news
 *   /uudised/2026/               one year
 *   /uudised/2026/03/            one month
 *   /uudised/2026/03/page/2/     paginated
 *
 * Unpadded months (/uudised/2026/3/) are accepted and 301'd to the padded form.
 */

if (!defined('ABSPATH')) {
    exit;
}

const TONDI_NEWS_POST_TYPE = 'news';
const TONDI_NEWS_INDEX_TRANSIENT = 'tondi_news_archive_index';
const TONDI_NEWS_REWRITE_OPTION = 'tondi_news_rewrite_version';
const TONDI_NEWS_REWRITE_VERSION = '2';

// -------------------------------------------------
// URLs
// -------------------------------------------------

/**
 * Without pretty permalinks the filter falls back to query-string URLs.
 */
function tondi_news_pretty_permalinks(): bool
{
    return (bool) get_option('permalink_structure');
}

/**
 * Archive path relative to the site root, no surrounding slashes. Taken from
 * WP's own archive link so it follows the CPT rewrite slug.
 */
function tondi_news_archive_base(): string
{
    $link = get_post_type_archive_link(TONDI_NEWS_POST_TYPE);
    $path = $link ? (string) wp_parse_url($link, PHP_URL_PATH) : '';
    $base = trim($path, '/');

    return $base !== '' ? $base : 'uudised';
}

/**
 * Build a URL for the archive, optionally narrowed to a year and month.
 *
 * @param int|null $year  4-digit year; null for the unfiltered archive.
 * @param int|null $month 1-12; null for a whole year, and ignored without one.
 * @param int      $paged Page number; 0 and 1 both give the first page.
 */
function tondi_news_archive_url(?int $year = null, ?int $month = null, int $paged = 0): string
{
    $archive = (string) get_post_type_archive_link(TONDI_NEWS_POST_TYPE);

    $year = ($year !== null && $year > 0) ? $year : null;
    $month = ($month !== null && $month >= 1 && $month <= 12) ? $month : null;

    // A month on its own is not addressable.
    if ($year === null) {
        $month = null;
    }

    // No pretty permalinks: WP_Query reads these straight off the query string.
    if (!tondi_news_pretty_permalinks()) {
        $args = [];

        if ($year !== null) {
            $args['year'] = $year;
        }

        if ($month !== null) {
            $args['monthnum'] = zeroise($month, 2);
        }

        if ($paged > 1) {
            $args['paged'] = $paged;
        }

        return $args ? add_query_arg($args, $archive) : $archive;
    }

    $path = trailingslashit($archive);

    if ($year !== null) {
        $path .= $year . '/';

        if ($month !== null) {
            $path .= zeroise($month, 2) . '/';
        }
    }

    if ($paged > 1) {
        global $wp_rewrite;
        $path .= $wp_rewrite->pagination_base . '/' . $paged . '/';
    }

    // Re-apply whatever trailing-slash convention the permalink structure uses.
    return user_trailingslashit(untrailingslashit($path));
}

// -------------------------------------------------
// Rewrite rules
// -------------------------------------------------

add_action('init', function () {
    if (!tondi_news_pretty_permalinks()) {
        return;
    }

    $base = tondi_news_archive_base();
    $page = 'page';

    global $wp_rewrite;
    if (!empty($wp_rewrite->pagination_base)) {
        $page = $wp_rewrite->pagination_base;
    }

    $b = preg_quote($base, '#');
    $p = preg_quote($page, '#');
    $qs = 'index.php?post_type=' . TONDI_NEWS_POST_TYPE;

    $rules = [
        [
            "{$b}/([0-9]{4})/(0[1-9]|1[0-2])/{$p}/([0-9]{1,})/?$",
            "{$qs}&year=\$matches[1]&monthnum=\$matches[2]&paged=\$matches[3]",
        ],
        [
            "{$b}/([0-9]{4})/(0[1-9]|1[0-2])/?$",
            "{$qs}&year=\$matches[1]&monthnum=\$matches[2]",
        ],
        [
            "{$b}/([0-9]{4})/{$p}/([0-9]{1,})/?$",
            "{$qs}&year=\$matches[1]&paged=\$matches[2]",
        ],
        [
            "{$b}/([0-9]{4})/?$",
            "{$qs}&year=\$matches[1]",
        ],
        // Unpadded / out-of-range month: matched last, then 301'd or 404'd.
        [
            "{$b}/([0-9]{4})/([0-9]{1,2})/?$",
            "{$qs}&year=\$matches[1]&monthnum=\$matches[2]&tondi_news_canonical=1",
        ],
    ];

    // 'top' appends within extra_rules_top, so insertion order is match order:
    // most specific first, or the loose rule shadows the strict one and 301s
    // /2026/03/ onto itself.
    foreach ($rules as [$regex, $query]) {
        add_rewrite_rule($regex, $query, 'top');
    }
}, 11);

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'tondi_news_canonical';

    return $vars;
});

// Flush once whenever the rules or the archive base change.
add_action('init', function () {
    if (!tondi_news_pretty_permalinks()) {
        return;
    }

    $version = TONDI_NEWS_REWRITE_VERSION . ':' . tondi_news_archive_base();

    if (get_option(TONDI_NEWS_REWRITE_OPTION) === $version) {
        return;
    }

    flush_rewrite_rules(false);
    update_option(TONDI_NEWS_REWRITE_OPTION, $version, false);
}, 12);

add_action('after_switch_theme', function () {
    delete_option(TONDI_NEWS_REWRITE_OPTION);
});

// Priority 5 to beat redirect_canonical(), which would otherwise send
// /uudised/2026/13/ to the unrelated /2026/.
add_action('template_redirect', function () {
    if (!get_query_var('tondi_news_canonical')) {
        return;
    }

    $year = (int) get_query_var('year');
    $month = (int) get_query_var('monthnum');

    if ($year <= 0 || $month < 1 || $month > 12) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();

        // Left alone, redirect_canonical() would 301 this 404 to /2026/.
        remove_action('template_redirect', 'redirect_canonical');

        return;
    }

    wp_safe_redirect(tondi_news_archive_url($year, $month), 301);
    exit;
}, 5);

// -------------------------------------------------
// Archive index (year/month post counts)
// -------------------------------------------------

/**
 * Published news grouped by year and month, newest year first.
 *
 * @return array<int, array{year:int, count:int, months:array<int, array{month:int, count:int}>}>
 */
function tondi_news_archive_index(): array
{
    $cached = get_transient(TONDI_NEWS_INDEX_TRANSIENT);
    if (is_array($cached)) {
        return $cached;
    }

    global $wpdb;

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(*) AS c
             FROM {$wpdb->posts}
             WHERE post_type = %s AND post_status = 'publish'
             GROUP BY y, m
             ORDER BY y DESC, m ASC",
            TONDI_NEWS_POST_TYPE
        )
    );

    $years = [];

    foreach ((array) $rows as $row) {
        $y = (int) $row->y;
        $m = (int) $row->m;
        $c = (int) $row->c;

        if ($y <= 0 || $m < 1 || $m > 12) {
            continue;
        }

        if (!isset($years[$y])) {
            $years[$y] = ['year' => $y, 'count' => 0, 'months' => []];
        }

        $years[$y]['count'] += $c;
        $years[$y]['months'][] = ['month' => $m, 'count' => $c];
    }

    /**
     * Month order inside a year: 'asc' for calendar order, 'desc' for newest first.
     */
    if (apply_filters('tondi_news_archive_month_order', 'asc') === 'desc') {
        foreach ($years as &$year) {
            $year['months'] = array_reverse($year['months']);
        }
        unset($year);
    }

    $index = array_values($years);

    set_transient(TONDI_NEWS_INDEX_TRANSIENT, $index, 12 * HOUR_IN_SECONDS);

    return $index;
}

function tondi_news_total_count(): int
{
    $total = 0;

    foreach (tondi_news_archive_index() as $year) {
        $total += $year['count'];
    }

    return $total;
}

function tondi_news_flush_archive_index(): void
{
    delete_transient(TONDI_NEWS_INDEX_TRANSIENT);
}

add_action('save_post_' . TONDI_NEWS_POST_TYPE, 'tondi_news_flush_archive_index');

add_action('deleted_post', function ($post_id, $post = null) {
    if ($post && $post->post_type === TONDI_NEWS_POST_TYPE) {
        tondi_news_flush_archive_index();
    }
}, 10, 2);

// Covers scheduled posts going live via cron, and trash/untrash.
add_action('transition_post_status', function ($new, $old, $post) {
    if ($post instanceof WP_Post && $post->post_type === TONDI_NEWS_POST_TYPE && $new !== $old) {
        tondi_news_flush_archive_index();
    }
}, 10, 3);

// -------------------------------------------------
// Current view
// -------------------------------------------------

/**
 * Year/month currently being viewed. A month without a year is ignored.
 *
 * @return array{year:int|null, month:int|null}
 */
function tondi_news_current_filter(): array
{
    $year = (int) get_query_var('year');
    $month = (int) get_query_var('monthnum');

    if ($year <= 0) {
        return ['year' => null, 'month' => null];
    }

    return [
        'year' => $year,
        'month' => ($month >= 1 && $month <= 12) ? $month : null,
    ];
}

/**
 * Month name in the site locale, e.g. "Märts" under et.
 *
 * @param int  $month   1-12; anything else is returned as the bare number.
 * @param bool $ucfirst Capitalise the first letter, which the locale supplies
 *                      lowercase. Wanted for list and heading text.
 */
function tondi_news_month_name(int $month, bool $ucfirst = true): string
{
    global $wp_locale;

    $name = ($wp_locale instanceof WP_Locale) ? $wp_locale->get_month($month) : '';

    if ($name === '') {
        return (string) $month;
    }

    if (!$ucfirst) {
        return $name;
    }

    return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
}

function tondi_news_archive_heading(): string
{
    $object = get_post_type_object(TONDI_NEWS_POST_TYPE);
    $label = $object ? (string) $object->labels->name : __('Uudised', 'tondi');

    $filter = tondi_news_current_filter();

    if ($filter['year'] === null) {
        return $label;
    }

    if ($filter['month'] === null) {
        /* translators: 1: post type label, 2: year */
        return sprintf(__('%1$s %2$d', 'tondi'), $label, $filter['year']);
    }

    /* translators: 1: post type label, 2: month name, 3: year */
    return sprintf(
        __('%1$s – %2$s %3$d', 'tondi'),
        $label,
        tondi_news_month_name($filter['month']),
        $filter['year']
    );
}

// -------------------------------------------------
// SEO
// -------------------------------------------------

// Google would rather paginated pages stayed indexable; noindexing page 2+ of a
// filter is deliberate here. Override via `tondi_news_archive_noindex`.
add_filter('wp_robots', function (array $robots): array {
    if (!is_post_type_archive(TONDI_NEWS_POST_TYPE)) {
        return $robots;
    }

    $filter = tondi_news_current_filter();
    $paged = max(1, (int) get_query_var('paged'));
    $found = isset($GLOBALS['wp_query']) ? (int) $GLOBALS['wp_query']->found_posts : 0;

    $noindex = ($found === 0) || ($filter['year'] !== null && $paged > 1);

    /**
     * @param bool  $noindex Whether to noindex this view.
     * @param array $filter  Active year/month.
     * @param int   $paged   Current page.
     * @param int   $found   Total matching posts.
     */
    if (apply_filters('tondi_news_archive_noindex', $noindex, $filter, $paged, $found)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
        unset($robots['nofollow'], $robots['index']);
    }

    return $robots;
});

/**
 * Self-referencing canonical plus prev/next for the filtered archive, neither of
 * which WordPress emits for archives on its own.
 */
add_action('wp_head', function () {
    if (!is_post_type_archive(TONDI_NEWS_POST_TYPE)) {
        return;
    }

    $filter = tondi_news_current_filter();
    $paged = max(1, (int) get_query_var('paged'));
    $max = isset($GLOBALS['wp_query']) ? (int) $GLOBALS['wp_query']->max_num_pages : 0;

    printf(
        '<link rel="canonical" href="%s" />' . "\n",
        esc_url(tondi_news_archive_url($filter['year'], $filter['month'], $paged))
    );

    if ($max < 2) {
        return;
    }

    if ($paged > 1) {
        printf(
            '<link rel="prev" href="%s" />' . "\n",
            esc_url(tondi_news_archive_url($filter['year'], $filter['month'], $paged - 1))
        );
    }

    if ($paged < $max) {
        printf(
            '<link rel="next" href="%s" />' . "\n",
            esc_url(tondi_news_archive_url($filter['year'], $filter['month'], $paged + 1))
        );
    }
}, 1);

/**
 * Expose the year/month routes in wp-sitemap.xml.
 *
 * Registered on wp_sitemaps_init (the hook core documents for this) and added
 * straight to the registry. The public wrapper has been renamed across
 * versions -- wp_register_sitemap_provider() in WP 7.1 -- but
 * $sitemaps->registry->add_provider() has been stable since 5.5.
 */
add_action('wp_sitemaps_init', function ($sitemaps) {
    if (!class_exists('WP_Sitemaps_Provider') || !isset($sitemaps->registry)) {
        return;
    }

    require_once __DIR__ . '/class-tondi-news-archive-sitemap.php';

    $sitemaps->registry->add_provider('newsarchives', new Tondi_News_Archive_Sitemap_Provider());
});
