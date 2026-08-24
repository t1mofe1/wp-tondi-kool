<?php
/**
 * Sitemap provider for the news year/month archive URLs, which core -- listing
 * only posts, terms and users -- would otherwise never include.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tondi_News_Archive_Sitemap_Provider extends WP_Sitemaps_Provider
{
    public function __construct()
    {
        // Letters only: core's route regex captures the name as ([a-z]+?),
        // so a hyphen here makes the sitemap URL unroutable.
        $this->name = 'newsarchives';
        $this->object_type = 'newsarchive';
    }

    /**
     * @param int    $page_num       Page of results; the list is always one page.
     * @param string $object_subtype Unused; this provider has no subtypes.
     * @return array<int, array{loc:string}>
     */
    public function get_url_list($page_num, $object_subtype = '')
    {
        $urls = [];

        foreach (tondi_news_archive_index() as $year_data) {
            $year = (int) $year_data['year'];

            $urls[] = ['loc' => tondi_news_archive_url($year)];

            foreach ($year_data['months'] as $month_data) {
                $urls[] = ['loc' => tondi_news_archive_url($year, (int) $month_data['month'])];
            }
        }

        /**
         * @param array $urls     Sitemap entries.
         * @param int   $page_num Page of results.
         */
        return apply_filters('tondi_news_archive_sitemap_url_list', $urls, $page_num);
    }

    /**
     * @param string $object_subtype Unused; this provider has no subtypes.
     * @return int 1 while any news exists, else 0. The list always fits one page.
     */
    public function get_max_num_pages($object_subtype = '')
    {
        return empty(tondi_news_archive_index()) ? 0 : 1;
    }
}
