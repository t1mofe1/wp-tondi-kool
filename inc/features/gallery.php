<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flatten the FileBird folder tree into an ordered list.
 *
 * @return array<int, array{id: int, name: string, depth: int}> Depth-first order, root folders first.
 */
function tondi_filebird_folders_flat(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!class_exists(\FileBird\Classes\Tree::class)) {
        return [];
    }

    $tree = \FileBird\Classes\Tree::getFolders(null);
    $folders = [];

    $walk = function ($nodes, int $depth = 0) use (&$walk, &$folders) {
        if (!is_array($nodes)) {
            return;
        }

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $id = (int) ($node['id'] ?? 0);
            $name = (string) ($node['text'] ?? ($node['title'] ?? ''));

            if ($id > 0 && $name !== '') {
                $folders[] = [
                    'id' => $id,
                    'name' => $name,
                    'depth' => $depth,
                ];
            }

            $children = $node['children'] ?? [];
            if (is_array($children) && !empty($children)) {
                $walk($children, $depth + 1);
            }
        }
    };

    $walk(is_array($tree) ? $tree : [], 0);

    $cache = $folders;

    return $folders;
}

/**
 * Folder choices split into published albums and everything else.
 *
 * Each folder appears in exactly one group: duplicate option values would make a
 * single-select mark both copies selected, and the browser would show the wrong one.
 *
 * @return array<string, string|array<string, string>> Optgroup label => choices, or a
 *                                                     flat choice list when no albums exist.
 */
function tondi_filebird_folder_choices_grouped(): array
{
    $folders = tondi_filebird_folder_choices_indented();

    $albums = [];

    foreach (tondi_get_gallery_albums() as $album) {
        $albums[(string) $album['folder_id']] = sprintf(
            '%s (%s)',
            $album['title'],
            number_format_i18n($album['count'])
        );
    }

    if (!$albums) {
        return $folders;
    }

    return [
        __('Albumid', 'tondi') => $albums,
        __('Muud kaustad', 'tondi') => array_diff_key($folders, $albums),
    ];
}

/**
 * Attachment counts for every FileBird folder, in a single grouped query.
 *
 * @return array<int, int> Folder ID => attachment count. Empty folders are absent.
 */
function tondi_filebird_folder_counts(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!class_exists(\FileBird\Classes\Tree::class)) {
        return [];
    }

    $counts = \FileBird\Classes\Tree::getAllFoldersAndCount();
    $cache = is_array($counts) ? array_map('intval', $counts) : [];

    return $cache;
}

/**
 * FileBird folders as ACF select choices, nesting shown with em dashes.
 *
 * Labels carry the image count in admin, so an editor can tell a filled folder
 * from an empty one without leaving the settings page.
 *
 * @return array<string, string> Folder ID (as string) => indented folder name.
 */
function tondi_filebird_folder_choices_indented(): array
{
    $counts = tondi_filebird_folder_counts();

    $choices = [];

    foreach (tondi_filebird_folders_flat() as $folder) {
        $choices[(string) $folder['id']] = sprintf(
            '%s%s (%s)',
            str_repeat('— ', max(0, $folder['depth'])),
            $folder['name'],
            number_format_i18n($counts[$folder['id']] ?? 0)
        );
    }

    return $choices;
}

/**
 * Look up a single FileBird folder name.
 *
 * @param int $folder_id FileBird folder ID.
 * @return string Folder name, or an empty string when the folder is gone.
 */
function tondi_filebird_folder_name(int $folder_id): string
{
    foreach (tondi_filebird_folders_flat() as $folder) {
        if ($folder['id'] === $folder_id) {
            return $folder['name'];
        }
    }

    return '';
}

function tondi_get_filebird_taxonomy_name(): ?string
{
    $candidates = [
        'nt_wmc_folder',       // common for FileBird
        'filebird_folder',
        'fb_folder',
    ];

    foreach ($candidates as $tax) {
        if (taxonomy_exists($tax)) return $tax;
    }

    return null;
}

/**
 * Attachment IDs inside a FileBird folder.
 *
 * @param int  $folder_id    FileBird folder ID.
 * @param bool $newest_first Sort by attachment date descending instead of FileBird's own order.
 * @return int[] Attachment IDs, empty when the folder is empty or FileBird is inactive.
 */
function tondi_get_folder_attachment_ids(int $folder_id, bool $newest_first = false): array
{
    if ($folder_id <= 0 || !class_exists(\FileBird\Classes\Helpers::class)) {
        return [];
    }

    $ids = (array) \FileBird\Classes\Helpers::getAttachmentIdsByFolderId($folder_id);
    $ids = array_values(array_filter(array_map('intval', $ids)));

    if ($newest_first && $ids) {
        usort($ids, function ($a, $b) {
            return get_post_time('U', true, $b) <=> get_post_time('U', true, $a);
        });
    }

    return $ids;
}

/**
 * Gallery albums configured on the Galerii options page.
 *
 * Rows pointing at a missing or empty folder are dropped, so every returned album
 * has at least one image.
 *
 * @return array<int, array{folder_id: int, title: string, cover_id: int, attachment_ids: int[], count: int}>
 */
function tondi_get_gallery_albums(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!function_exists('get_field')) {
        return [];
    }

    $rows = get_field('gallery_albums', 'option');
    if (!is_array($rows)) {
        return [];
    }

    $albums = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $folder_id = (int) ($row['album_folder'] ?? 0);
        if ($folder_id <= 0) {
            continue;
        }

        $attachment_ids = tondi_get_folder_attachment_ids($folder_id, true);
        if (!$attachment_ids) {
            continue;
        }

        $title = trim((string) ($row['album_title'] ?? ''));
        if ($title === '') {
            $title = tondi_filebird_folder_name($folder_id);
        }
        if ($title === '') {
            $title = __('Album', 'tondi');
        }

        $cover_id = (int) ($row['album_cover'] ?? 0);
        if ($cover_id <= 0) {
            $cover_id = $attachment_ids[0];
        }

        $albums[] = [
            'folder_id' => $folder_id,
            'title' => $title,
            'cover_id' => $cover_id,
            'attachment_ids' => $attachment_ids,
            'count' => count($attachment_ids),
        ];
    }

    $cache = $albums;

    return $albums;
}

/**
 * Find one configured album by its FileBird folder ID.
 *
 * @param int   $folder_id FileBird folder ID, typically from the ?album= query var.
 * @param array $albums    Album list to search; fetched from options when omitted.
 * @return array{folder_id: int, title: string, cover_id: int, attachment_ids: int[], count: int}|null
 */
function tondi_get_gallery_album(int $folder_id, ?array $albums = null): ?array
{
    if ($folder_id <= 0) {
        return null;
    }

    $albums = $albums ?? tondi_get_gallery_albums();

    foreach ($albums as $album) {
        if ($album['folder_id'] === $folder_id) {
            return $album;
        }
    }

    return null;
}

/**
 * Whether the current request is rendering the Galerii page template.
 */
function tondi_gallery_is_album_template(): bool
{
    return is_page() && is_page_template('templates/page-galerii.php');
}

/**
 * Album requested via the ?album= query arg.
 *
 * @return int Folder ID, or 0 when no album was asked for.
 */
function tondi_gallery_requested_album_id(): int
{
    return isset($_GET['album']) ? (int) $_GET['album'] : 0;
}

/**
 * The album the current request resolves to, if any.
 *
 * @return array{folder_id: int, title: string, cover_id: int, attachment_ids: int[], count: int}|null
 */
function tondi_gallery_current_album(): ?array
{
    if (!tondi_gallery_is_album_template()) {
        return null;
    }

    return tondi_get_gallery_album(tondi_gallery_requested_album_id());
}

/**
 * Images rendered per page inside one album.
 *
 * @return int Positive page size.
 */
function tondi_gallery_album_page_size(): int
{
    /**
     * @param int $size Images per album page.
     */
    $size = (int) apply_filters('tondi_gallery_album_page_size', 48);

    return $size > 0 ? $size : 48;
}

/**
 * Album page requested via the ?album_page= query arg.
 *
 * @return int Page number, never below 1.
 */
function tondi_gallery_requested_album_page(): int
{
    $page = isset($_GET['album_page']) ? (int) $_GET['album_page'] : 1;

    return max(1, $page);
}

/**
 * The album, page and image slice the current request resolves to.
 *
 * A ?photo= naming an image on another page beats ?album_page=, so a link to
 * image 120 opens on the page that actually holds it instead of an empty view.
 *
 * @return array{album: array, page: int, total_pages: int, attachment_ids: int[]}|null
 */
function tondi_gallery_current_album_slice(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }

    $album = tondi_gallery_current_album();
    if (!$album) {
        $cache = null;

        return null;
    }

    $size = tondi_gallery_album_page_size();
    $total_pages = max(1, (int) ceil($album['count'] / $size));
    $page = min(tondi_gallery_requested_album_page(), $total_pages);

    $photo_id = isset($_GET['photo']) ? (int) $_GET['photo'] : 0;
    if ($photo_id > 0) {
        $offset = array_search($photo_id, $album['attachment_ids'], true);

        if ($offset !== false) {
            $page = (int) floor($offset / $size) + 1;
        }
    }

    $cache = [
        'album' => $album,
        'page' => $page,
        'total_pages' => $total_pages,
        'attachment_ids' => array_slice($album['attachment_ids'], ($page - 1) * $size, $size),
    ];

    return $cache;
}

/**
 * The page using the Galerii template.
 *
 * @return int Page ID, or 0 when no published page uses the template.
 */
function tondi_gallery_page_id(): int
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_wp_page_template',
        'meta_value' => 'templates/page-galerii.php',
    ]);

    $cache = $pages ? (int) $pages[0] : 0;

    return $cache;
}

/**
 * Whether a folder is published as an album, without costing the attachment
 * lookups that building the full album list would.
 *
 * @param int $folder_id FileBird folder ID.
 * @return bool True when a repeater row points at this folder.
 */
function tondi_gallery_folder_has_album(int $folder_id): bool
{
    if ($folder_id <= 0 || !function_exists('get_field')) {
        return false;
    }

    $rows = get_field('gallery_albums', 'option');
    if (!is_array($rows)) {
        return false;
    }

    foreach ($rows as $row) {
        if (is_array($row) && (int) ($row['album_folder'] ?? 0) === $folder_id) {
            return true;
        }
    }

    return false;
}

/**
 * Permalink of one album, optionally a specific page of it.
 *
 * @param int $folder_id FileBird folder ID.
 * @param int $page      Album page; 1 leaves the page arg off.
 * @param int $page_id   Page to build the URL from; 0 resolves the Galerii page.
 * @return string Absolute URL, or an empty string when no gallery page exists.
 */
function tondi_gallery_album_url(int $folder_id, int $page = 1, int $page_id = 0): string
{
    $page_id = $page_id > 0 ? $page_id : tondi_gallery_page_id();
    $permalink = $page_id > 0 ? get_permalink($page_id) : '';

    if (!$permalink) {
        return '';
    }

    $url = add_query_arg('album', $folder_id, $permalink);

    return $page > 1 ? add_query_arg('album_page', $page, $url) : $url;
}

// Title album views after the album, so shared ?album= links preview correctly.
add_filter('document_title_parts', function ($parts) {
    $album = tondi_gallery_current_album();

    if ($album) {
        $parts['title'] = $album['title'];
    }

    return $parts;
});

// An album is its own destination. Left alone, core's rel_canonical() points every
// ?album= URL back at the bare index, so no album can ever be indexed.
add_filter('get_canonical_url', function ($canonical_url, $post) {
    if (!$canonical_url || (int) $post->ID !== get_queried_object_id()) {
        return $canonical_url;
    }

    $slice = tondi_gallery_current_album_slice();
    if (!$slice) {
        return $canonical_url;
    }

    $url = add_query_arg('album', $slice['album']['folder_id'], $canonical_url);

    return $slice['page'] > 1
        ? add_query_arg('album_page', $slice['page'], $url)
        : $url;
}, 10, 2);

// A stale ?album= link renders the index with a notice, which is the right thing
// for a visitor and the wrong thing to let into an index.
add_filter('wp_robots', function (array $robots): array {
    if (!tondi_gallery_is_album_template()) {
        return $robots;
    }

    $requested = tondi_gallery_requested_album_id();

    if ($requested > 0 && !tondi_get_gallery_album($requested)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
        unset($robots['nofollow'], $robots['index']);
    }

    return $robots;
});

// Folder choices are built when a field loads, not when it is registered. Building
// them at acf/init read gallery_albums before its own field existed — ACF fell back
// to raw metadata and handed back the repeater's row count instead of its rows — and
// queried FileBird on every request, front end included.
add_filter('acf/load_field/key=field_gallery_album_folder', function ($field) {
    if (!is_admin()) {
        return $field;
    }

    $field['choices'] = tondi_filebird_folder_choices_indented()
        ?: ['' => __('Kauste ei leitud', 'tondi')];

    return $field;
});

add_filter('acf/load_field/key=field_front_page_gallery_folder', function ($field) {
    if (!is_admin()) {
        return $field;
    }

    $field['choices'] = tondi_filebird_folder_choices_grouped()
        ?: ['' => __('Kauste ei leitud', 'tondi')];

    return $field;
});
