<?php
/**
 * Year/month archive filter.
 *
 * <details name> makes the years an exclusive accordion. The outer panel ships
 * open so it still works without JS; the inline script below collapses it on
 * mobile, and must stay inline to avoid a jump after first paint.
 */

if (!defined('ABSPATH')) {
    exit;
}

$index = tondi_news_archive_index();

if (empty($index)) {
    return;
}

$filter = tondi_news_current_filter();
$active_year = $filter['year'];
$active_month = $filter['month'];

// Nothing is "current" on a 404, not even the all-news row.
$mark_active = !is_404();
$total = tondi_news_total_count();

$open_year = $active_year ?? (int) $index[0]['year'];

// Keeps the active filter visible while the mobile panel is shut.
if (!$mark_active || $active_year === null) {
    $state_label = __('Kõik uudised', 'tondi');
} elseif ($active_month === null) {
    $state_label = (string) $active_year;
} else {
    $state_label = tondi_news_month_name($active_month) . ' ' . $active_year;
}
?>

<aside class="news-filter">
    <h2 class="news-filter__title"><?php esc_html_e('Arhiiv', 'tondi'); ?></h2>

    <details class="news-filter__panel" open>
        <summary class="news-filter__panel-summary">
            <span class="news-filter__panel-label"><?php esc_html_e('Arhiiv', 'tondi'); ?></span>
            <span class="news-filter__panel-state"><?php echo esc_html($state_label); ?></span>
        </summary>

    <nav class="news-filter__nav" aria-label="<?php esc_attr_e('Uudiste arhiiv aasta ja kuu järgi', 'tondi'); ?>">
        <ul class="news-filter__years">

            <li class="news-filter__all">
                <?php $all_active = $mark_active && $active_year === null; ?>
                <a class="news-filter__link<?php echo $all_active ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(tondi_news_archive_url()); ?>"
                    <?php echo $all_active ? ' aria-current="page"' : ''; ?>
                    aria-label="<?php echo esc_attr(sprintf(
                        /* translators: %d: number of news posts */
                        _n('Kõik uudised, %d uudis', 'Kõik uudised, %d uudist', $total, 'tondi'),
                        $total
                    )); ?>">
                    <span class="news-filter__label"><?php esc_html_e('Kõik uudised', 'tondi'); ?></span>
                    <span class="news-filter__count" aria-hidden="true"><?php echo esc_html((string) $total); ?></span>
                </a>
            </li>

            <?php foreach ($index as $year_data):
                $year = (int) $year_data['year'];
                $year_count = (int) $year_data['count'];
                $is_current_year = $mark_active && ($active_year === $year);
                ?>
                <li class="news-filter__year<?php echo $is_current_year ? ' is-current' : ''; ?>">
                    <details class="news-filter__details" name="news-filter-year" <?php echo $year === $open_year ? 'open' : ''; ?>>
                        <summary class="news-filter__summary">
                            <span class="news-filter__label"><?php echo esc_html((string) $year); ?></span>
                            <span class="news-filter__count" aria-hidden="true"><?php echo esc_html((string) $year_count); ?></span>
                            <span class="screen-reader-text"><?php echo esc_html(sprintf(
                                /* translators: %d: number of news posts */
                                _n('%d uudis', '%d uudist', $year_count, 'tondi'),
                                $year_count
                            )); ?></span>
                        </summary>

                        <ul class="news-filter__months">
                            <li>
                                <a class="news-filter__link news-filter__link--all<?php echo ($is_current_year && $active_month === null) ? ' is-active' : ''; ?>"
                                    href="<?php echo esc_url(tondi_news_archive_url($year)); ?>"
                                    <?php echo ($is_current_year && $active_month === null) ? ' aria-current="page"' : ''; ?>>
                                    <span class="news-filter__label"><?php esc_html_e('Kogu aasta', 'tondi'); ?></span>
                                    <span class="news-filter__count" aria-hidden="true"><?php echo esc_html((string) $year_count); ?></span>
                                </a>
                            </li>

                            <?php foreach ($year_data['months'] as $month_data):
                                $month = (int) $month_data['month'];
                                $month_count = (int) $month_data['count'];
                                $is_active = ($is_current_year && $active_month === $month);
                                $month_name = tondi_news_month_name($month);
                                ?>
                                <li>
                                    <a class="news-filter__link<?php echo $is_active ? ' is-active' : ''; ?>"
                                        href="<?php echo esc_url(tondi_news_archive_url($year, $month)); ?>"
                                        <?php echo $is_active ? ' aria-current="page"' : ''; ?>
                                        aria-label="<?php echo esc_attr(sprintf(
                                            /* translators: 1: month name, 2: year, 3: number of news posts */
                                            _n('%1$s %2$d, %3$d uudis', '%1$s %2$d, %3$d uudist', $month_count, 'tondi'),
                                            $month_name,
                                            $year,
                                            $month_count
                                        )); ?>">
                                        <span class="news-filter__label"><?php echo esc_html($month_name); ?></span>
                                        <span class="news-filter__count" aria-hidden="true"><?php echo esc_html((string) $month_count); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </li>
            <?php endforeach; ?>

        </ul>
    </nav>
    </details>

    <script>
        (function () {
            var panel = document.currentScript.parentElement.querySelector('.news-filter__panel');
            if (!panel) return;

            var wide = window.matchMedia('(min-width: 1025px)');

            function sync() {
                panel.open = wide.matches;
            }

            sync();
            wide.addEventListener('change', sync);
        })();
    </script>
</aside>
