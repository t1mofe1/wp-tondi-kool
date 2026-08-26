<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calendar / ICS helpers
 *
 * Depends on:
 * - ACF option field: calendar_ics_url (type: url) saved on options page
 */

// -----------------------------
// 1) Get ICS URL from ACF options
// -----------------------------
function tondi_get_ics_url(): string
{
    if (!function_exists('get_field')) {
        return '';
    }

    // Prefer field NAME (not field key)
    $url = (string) (get_field('calendar_ics_url', 'option') ?: '');

    return trim($url);
}

// -----------------------------
// 2) Fetch ICS with caching
// -----------------------------
function tondi_fetch_ics(string $url, int $cache_seconds = 300): string
{
    if (!$url) {
        return '';
    }

    $key = 'tondi_ics_' . md5($url);

    $cached = get_transient($key);
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }

    $res = wp_remote_get($url, [
        'timeout' => 10,
        'redirection' => 5,
        'headers' => [
            'Accept' => 'text/calendar',
        ],
    ]);

    if (is_wp_error($res)) {
        return '';
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) {
        return '';
    }

    $body = (string) wp_remote_retrieve_body($res);
    if ($body === '') {
        return '';
    }

    // Cache raw ics
    set_transient($key, $body, $cache_seconds);

    return $body;
}

// -----------------------------
// 3) Parse ICS into event array
// -----------------------------
/**
 * Decode RFC 5545 escaped TEXT values: \n \N \, \; and \\
 *
 * @param string $value Raw property value straight from the feed.
 * @return string Plain text with real newlines.
 */
function tondi_ics_unescape_text(string $value): string
{
    return str_replace(
        ['\\n', '\\N', '\\,', '\\;', '\\\\'],
        ["\n", "\n", ',', ';', '\\'],
        $value
    );
}

/**
 * Very small ICS parser for common Google Calendar fields:
 * - DTSTART / DTEND
 * - SUMMARY
 * - LOCATION
 * - DESCRIPTION
 * - URL
 *
 * Returns array of events:
 * [
 *  [
 *    'start' => DateTimeImmutable,
 *    'end' => ?DateTimeImmutable,
 *    'all_day' => bool,
 *    'summary' => string,
 *    'location' => string,
 *    'description' => string,
 *    'url' => string,
 *    'uid' => string,
 *  ],
 * ]
 */
function tondi_parse_ics_events(string $ics, ?DateTimeZone $tz = null): array
{
    if (!$ics) {
        return [];
    }

    $tz = $tz ?: wp_timezone();

    // Unfold lines (RFC 5545): lines starting with space are continuations.
    // Long DESCRIPTION values are almost always folded, and some feeds use bare LF.
    $ics = preg_replace("/(?:\r\n|\n|\r)[ \t]/", '', $ics) ?? $ics;

    $lines = preg_split("/\r\n|\n|\r/", $ics);
    if (!$lines) {
        return [];
    }

    $events = [];
    $inEvent = false;
    $current = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === 'BEGIN:VEVENT') {
            $inEvent = true;
            $current = [];

            continue;
        }

        if ($line === 'END:VEVENT') {
            $inEvent = false;

            // Build event
            $summary = tondi_ics_unescape_text((string) ($current['SUMMARY'] ?? ''));
            $location = tondi_ics_unescape_text((string) ($current['LOCATION'] ?? ''));
            $description = tondi_ics_unescape_text((string) ($current['DESCRIPTION'] ?? ''));
            $url = tondi_ics_unescape_text((string) ($current['URL'] ?? ''));
            $uid = tondi_ics_unescape_text((string) ($current['UID'] ?? ''));

            $start = isset($current['DTSTART']) ? tondi_ics_parse_dt($current['DTSTART'], $tz) : null;
            $end = isset($current['DTEND']) ? tondi_ics_parse_dt($current['DTEND'], $tz) : null;

            $all_day = false;
            if (isset($current['DTSTART']) && str_contains($current['DTSTART'], 'VALUE=DATE')) {
                $all_day = true;
            }

            if ($start instanceof DateTimeImmutable && $summary !== '') {
                $events[] = [
                    'start' => $start,
                    'end' => $end instanceof DateTimeImmutable ? $end : null,
                    'all_day' => $all_day,
                    'summary' => $summary,
                    'location' => $location,
                    'description' => $description,
                    'url' => $url,
                    'uid' => $uid,
                ];
            }

            $current = [];

            continue;
        }

        if (!$inEvent || $line === '') {
            continue;
        }

        // Split "KEY;PARAMS:VALUE" or "KEY:VALUE"
        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $left = $parts[0];
        $value = $parts[1];

        // Extract key before any ;PARAM
        $keyParts = explode(';', $left, 2);
        $key = strtoupper($keyParts[0]);

        // Keep only what we need
        if (in_array($key, ['DTSTART', 'DTEND'], true)) {
            // Keep full raw line so we can parse TZID / VALUE params later
            $current[$key] = $left . ':' . $value;
        } else if (in_array($key, ['SUMMARY', 'LOCATION', 'DESCRIPTION', 'URL', 'UID'], true)) {
            // Keep only the value part (prevents "SUMMARY:" showing in UI)
            $current[$key] = trim($value);
        }
    }

    return $events;
}

/**
 * Parse ICS datetime line.
 * Supports:
 * - DTSTART:20250101T120000Z
 * - DTSTART;TZID=Europe/Tallinn:20250101T120000
 * - DTSTART;VALUE=DATE:20250101
 */
function tondi_ics_parse_dt(string $rawLine, DateTimeZone $defaultTz): ?DateTimeImmutable
{
    // rawLine is like "DTSTART;TZID=Europe/Tallinn:20250101T120000"
    $parts = explode(':', $rawLine, 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$left, $val] = $parts;
    $val = trim($val);

    $tz = $defaultTz;

    // Parameters
    $params = [];
    if (str_contains($left, ';')) {
        $leftParts = explode(';', $left);

        array_shift($leftParts); // remove key

        foreach ($leftParts as $p) {
            $kv = explode('=', $p, 2);

            if (count($kv) === 2) {
                $params[strtoupper($kv[0])] = $kv[1];
            } else {
                $params[strtoupper($p)] = true;
            }
        }
    }

    // TZID param
    if (!empty($params['TZID'])) {
        try {
            $tz = new DateTimeZone($params['TZID']);
        } catch (Throwable $e) {
            $tz = $defaultTz;
        }
    }

    // Date-only (all-day)
    if (!empty($params['VALUE']) && strtoupper((string) $params['VALUE']) === 'DATE') {
        // YYYYMMDD
        if (!preg_match('/^\d{8}$/', $val)) {
            return null;
        }

        // Force midnight to avoid "current time" leaking in
        $dt = DateTimeImmutable::createFromFormat('!Ymd', $val, $tz);
        // "!" resets time to 00:00:00

        return $dt ?: null;
    }

    // UTC Z
    if (str_ends_with($val, 'Z')) {
        $val = rtrim($val, 'Z');

        $dt = DateTimeImmutable::createFromFormat('Ymd\THis', $val, new DateTimeZone('UTC'));

        return $dt ? $dt->setTimezone($defaultTz) : null;
    }

    // Local datetime
    // Most common: YYYYMMDDTHHMMSS
    if (preg_match('/^\d{8}T\d{6}$/', $val)) {
        $dt = DateTimeImmutable::createFromFormat('Ymd\THis', $val, $tz);

        return $dt ?: null;
    }

    // Sometimes: YYYYMMDDTHHMM
    if (preg_match('/^\d{8}T\d{4}$/', $val)) {
        $dt = DateTimeImmutable::createFromFormat('Ymd\THi', $val, $tz);

        return $dt ?: null;
    }

    return null;
}

// -----------------------------
// 4) Get upcoming events (sorted)
// -----------------------------
function tondi_get_upcoming_events(int $limit = 6, int $cache_seconds = 300): array
{
    $url = tondi_get_ics_url();
    if (!$url) {
        return [];
    }

    $ics = tondi_fetch_ics($url, $cache_seconds);
    if (!$ics) {
        return [];
    }

    $events = tondi_parse_ics_events($ics, wp_timezone());
    if (!$events) {
        return [];
    }

    $now = new DateTimeImmutable('now', wp_timezone());

    // Keep events that haven't ended (or start in future if no end)
    $events = array_filter($events, function ($e) use ($now) {
        /** @var DateTimeImmutable $start */
        $start = $e['start'];
        /** @var ?DateTimeImmutable $end */
        $end = $e['end'] ?? null;

        if ($end instanceof DateTimeImmutable) {
            return $end >= $now;
        }

        return $start >= $now;
    });

    // Sort by start ascending
    usort($events, function ($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    return array_slice(array_values($events), 0, max(0, $limit));
}

// -----------------------------
// 5) Display helpers
// -----------------------------
/**
 * Turn a raw ICS description into safe rich text.
 *
 * Google Calendar feeds mix plain text with fragments of HTML (mostly <br> and
 * <a>), so tags are flattened to newlines first and only then re-escaped.
 *
 * @param string $description Raw DESCRIPTION value.
 * @return string Escaped HTML with paragraphs and clickable links.
 */
function tondi_format_event_description(string $description): string
{
    $description = trim($description);

    if ($description === '') {
        return '';
    }

    $description = preg_replace('#<br\s*/?>#i', "\n", $description) ?? $description;
    $description = preg_replace('#</(p|div|li)>#i', "\n", $description) ?? $description;
    $description = wp_strip_all_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return make_clickable(wpautop(esc_html(trim($description))));
}

/**
 * Short plain-text teaser for the calendar list.
 *
 * @param string $description Raw DESCRIPTION value.
 * @param int $words Maximum number of words to keep.
 * @return string Plain text, no markup.
 */
function tondi_event_description_excerpt(string $description, int $words = 14): string
{
    $description = wp_strip_all_tags(
        preg_replace('#<br\s*/?>#i', ' ', $description) ?? $description
    );

    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = trim(preg_replace('/\s+/u', ' ', $description) ?? $description);

    if ($description === '') {
        return '';
    }

    return wp_trim_words($description, $words, '…');
}

/**
 * Normalize one parsed event into display-ready strings.
 *
 * Both the front page list and the event modal read from this, so date logic
 * (exclusive all-day DTEND, same-day ranges) lives in one place.
 *
 * @param array $event Single event from tondi_get_upcoming_events().
 * @return array|null Null when the event carries no usable start date.
 */
function tondi_prepare_event_display(array $event): ?array
{
    $start = $event['start'] ?? null;
    if (!$start instanceof DateTimeImmutable) {
        return null;
    }

    $tz = new DateTimeZone('Europe/Tallinn');
    $start = $start->setTimezone($tz);

    $end = $event['end'] ?? null;
    $end = $end instanceof DateTimeImmutable ? $end->setTimezone($tz) : null;

    $all_day = !empty($event['all_day']);

    // For all-day events DTEND is exclusive -> show the last inclusive day
    $end_display = null;
    if ($end instanceof DateTimeImmutable) {
        $end_display = $all_day ? $end->modify('-1 day') : $end;
    }

    $same_day = $end_display ? ($start->format('Y-m-d') === $end_display->format('Y-m-d')) : true;

    $description = (string) ($event['description'] ?? '');

    $url = (string) ($event['url'] ?? '');
    $url = $url !== '' ? esc_url_raw($url) : '';

    $uid = (string) ($event['uid'] ?? '');
    $place = (string) ($event['location'] ?? '');

    $same_month = $end_display
        ? ($start->format('Y-n') === $end_display->format('Y-n'))
        : true;

    // A range inside one month collapses into the badge ("19–25 okt"),
    // a range across months is spelled out in the meta line instead.
    $badge_day = $start->format('j');
    if (!$same_day && $same_month) {
        $badge_day .= '–' . $end_display->format('j');
    }

    $time_text = '';
    if (!$all_day) {
        $time_text = $start->format('H:i');

        if ($end_display) {
            $time_text .= '–' . $end_display->format('H:i');
        }
    }

    // Dates the badge cannot hold, then the clock: place is kept separate so
    // only this part sits inside a <time> element.
    $when_parts = [];

    if (!$same_day && !$same_month) {
        $when_parts[] = $start->format('d.m') . '–' . $end_display->format('d.m');
    }

    if ($all_day) {
        $when_parts[] = __('Terve päev', 'tondi');
    } else if ($time_text !== '') {
        $when_parts[] = $time_text;
    }

    return [
        'id' => 'event-' . substr(md5($uid . $start->format('c')), 0, 12),
        'name' => (string) ($event['summary'] ?? ''),
        'place' => $place,
        'url' => $url,
        'all_day' => $all_day,
        'same_day' => $same_day,

        'badge_day' => $badge_day,
        'badge_is_span' => !$same_day && $same_month,
        'meta_when' => implode(' · ', $when_parts),

        'description_html' => tondi_format_event_description($description),
        'description_excerpt' => tondi_event_description_excerpt($description),

        'month_short' => tondi_event_month_short($start, $tz),
        'weekday' => wp_date('l', $start->getTimestamp(), $tz),

        'start_date_attr' => $start->format('Y-m-d'),
        'start_date_text' => $start->format('d.m'),
        'start_date_long' => wp_date('j. F Y', $start->getTimestamp(), $tz),
        'start_time_attr' => $start->format('H:i'),
        'start_time_text' => $start->format('H:i'),

        'end_date_attr' => $end_display ? $end_display->format('Y-m-d') : '',
        'end_date_text' => $end_display ? $end_display->format('d.m') : '',
        'end_date_long' => $end_display ? wp_date('j. F Y', $end_display->getTimestamp(), $tz) : '',
        'end_time_attr' => $end_display ? $end_display->format('H:i') : '',
        'end_time_text' => $end_display ? $end_display->format('H:i') : '',
    ];
}

/**
 * Abbreviated month name for the date badge.
 *
 * @param DateTimeImmutable $date Event start.
 * @param DateTimeZone $tz Timezone used for formatting.
 * @return string Localized short month, lowercased (e.g. "jaan").
 */
function tondi_event_month_short(DateTimeImmutable $date, DateTimeZone $tz): string
{
    return mb_strtolower((string) wp_date('M', $date->getTimestamp(), $tz), 'UTF-8');
}
