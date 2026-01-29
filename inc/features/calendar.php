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
 * Very small ICS parser for common Google Calendar fields:
 * - DTSTART / DTEND
 * - SUMMARY
 * - LOCATION
 *
 * Returns array of events:
 * [
 *  [
 *    'start' => DateTimeImmutable,
 *    'end' => ?DateTimeImmutable,
 *    'summary' => string,
 *    'location' => string,
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

    // Unfold lines (RFC 5545): lines starting with space are continuations
    $ics = preg_replace("/\r\n[ \t]/", '', $ics) ?? $ics;

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
            $summary = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], (string) ($current['SUMMARY'] ?? ''));
            $location = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], (string) ($current['LOCATION'] ?? ''));
            $uid = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], (string) ($current['UID'] ?? ''));

            $start = isset($current['DTSTART']) ? tondi_ics_parse_dt($current['DTSTART'], $tz) : null;
            $end = isset($current['DTEND']) ? tondi_ics_parse_dt($current['DTEND'], $tz) : null;

            if ($start instanceof DateTimeImmutable && $summary !== '') {
                $events[] = [
                    'start' => $start,
                    'end' => $end instanceof DateTimeImmutable ? $end : null,
                    'summary' => $summary,
                    'location' => $location,
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
        } else if (in_array($key, ['SUMMARY', 'LOCATION', 'UID'], true)) {
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

    // Date-only
    if (!empty($params['VALUE']) && strtoupper((string) $params['VALUE']) === 'DATE') {
        // YYYYMMDD
        if (!preg_match('/^\d{8}$/', $val)) {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat('Ymd', $val, $tz);

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
// 5) Render helper (your HTML structure)
// -----------------------------
function tondi_render_home_calendar_list(array $events): void
{
    if (empty($events)) {
        echo '<p class="home-calendar__empty">' . esc_html__('Üritusi ei leitud.', 'tondi') . '</p>';
        return;
    }

    echo '<ol class="home-calendar__list">';

    $count = count($events);
    foreach ($events as $i => $e) {
        /** @var DateTimeImmutable $start */
        $start = $e['start'];
        $summary = (string) ($e['summary'] ?? '');
        $location = (string) ($e['location'] ?? '');

        $date_iso = $start->format('Y-m-d');
        $time_iso = $start->format('H:i');
        $date_ui = $start->format('d.m');
        $time_ui = $start->format('H:i');

        ?>

        <li class="home-calendar__item">
            <div class="home-calendar__info">
                <h3 class="home-calendar__name"><?php echo esc_html($summary); ?></h3>
                <?php if ($location): ?>
                    <p class="home-calendar__place"><?php echo esc_html($location); ?></p>
                <?php endif; ?>
            </div>

            <div class="home-calendar__meta">
                <time class="home-calendar__date"
                    datetime="<?php echo esc_attr($date_iso); ?>"><?php echo esc_html($date_ui); ?></time>
                <time class="home-calendar__time"
                    datetime="<?php echo esc_attr($time_iso); ?>"><?php echo esc_html($time_ui); ?></time>
            </div>
        </li>

        <?php

        if ($i < $count - 1) {
            echo '<hr class="home-calendar__separator" />';
        }
    }

    echo '</ol>';
}
