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
 * - STATUS
 * - RRULE / EXDATE / RECURRENCE-ID
 *
 * Recurring series are returned as they stand; tondi_expand_recurring_events()
 * turns them into instances, and drops what is cancelled.
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
 *    'status' => string,
 *    'rrule' => string,
 *    'exdates' => array<string, bool>,
 *    'recurrence_id' => ?DateTimeImmutable,
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
            $status = strtoupper(trim((string) ($current['STATUS'] ?? '')));

            $start = isset($current['DTSTART']) ? tondi_ics_parse_dt($current['DTSTART'], $tz) : null;
            $end = isset($current['DTEND']) ? tondi_ics_parse_dt($current['DTEND'], $tz) : null;

            $all_day = false;
            if (isset($current['DTSTART']) && str_contains($current['DTSTART'], 'VALUE=DATE')) {
                $all_day = true;
            }

            $recurrence_id = isset($current['RECURRENCE-ID'])
                ? tondi_ics_parse_dt($current['RECURRENCE-ID'], $tz)
                : null;

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
                    'status' => $status,
                    'rrule' => (string) ($current['RRULE'] ?? ''),
                    'exdates' => tondi_ics_parse_exdates((array) ($current['EXDATE'] ?? []), $tz),
                    'recurrence_id' => $recurrence_id,
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
        if (in_array($key, ['DTSTART', 'DTEND', 'RECURRENCE-ID'], true)) {
            // Keep full raw line so we can parse TZID / VALUE params later
            $current[$key] = $left . ':' . $value;
        } else if ($key === 'EXDATE') {
            // A feed may repeat EXDATE, and each line may carry several dates
            $current['EXDATE'][] = $left . ':' . $value;
        } else if (in_array($key, ['SUMMARY', 'LOCATION', 'DESCRIPTION', 'URL', 'UID', 'STATUS', 'RRULE'], true)) {
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
// 4) Recurring events
// -----------------------------
/**
 * Collect the dates an EXDATE property excludes.
 *
 * @param array $lines Raw EXDATE lines, params included.
 * @param DateTimeZone $tz Timezone for values that carry no TZID.
 * @return array<string, bool> Lookup keyed by "Y-m-d H:i:s".
 */
function tondi_ics_parse_exdates(array $lines, DateTimeZone $tz): array
{
    $dates = [];

    foreach ($lines as $line) {
        $parts = explode(':', (string) $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$left, $values] = $parts;

        // One line can list several dates: "EXDATE;TZID=...:2026...,2026..."
        foreach (explode(',', $values) as $value) {
            $dt = tondi_ics_parse_dt($left . ':' . trim($value), $tz);

            if ($dt instanceof DateTimeImmutable) {
                $dates[$dt->format('Y-m-d H:i:s')] = true;
            }
        }
    }

    return $dates;
}

/**
 * Read the parts of an RRULE this theme acts on.
 *
 * Covers what Google Calendar emits for the repeats a school actually sets up.
 * BYSETPOS, BYWEEKNO and BYYEARDAY are not read: nothing in that UI produces
 * them, and guessing at them would be worse than ignoring them.
 *
 * @param string $rrule Raw RRULE value.
 * @param DateTimeZone $tz Timezone for an UNTIL that carries no zone.
 * @return array Parsed rule, or an empty array when there is no usable FREQ.
 */
function tondi_parse_rrule(string $rrule, DateTimeZone $tz): array
{
    $raw = [];

    foreach (explode(';', $rrule) as $pair) {
        $kv = explode('=', $pair, 2);

        if (count($kv) === 2) {
            $raw[strtoupper(trim($kv[0]))] = trim($kv[1]);
        }
    }

    $freq = strtoupper((string) ($raw['FREQ'] ?? ''));
    if (!in_array($freq, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
        return [];
    }

    $until = null;
    if (!empty($raw['UNTIL'])) {
        $value = (string) $raw['UNTIL'];

        // A date-only UNTIL includes the whole of that day
        $line = preg_match('/^\d{8}$/', $value)
            ? 'UNTIL;VALUE=DATE:' . $value
            : 'UNTIL:' . $value;

        $until = tondi_ics_parse_dt($line, $tz);

        if ($until instanceof DateTimeImmutable && preg_match('/^\d{8}$/', $value)) {
            $until = $until->setTime(23, 59, 59);
        }
    }

    $byday = [];
    foreach (explode(',', (string) ($raw['BYDAY'] ?? '')) as $day) {
        $day = strtoupper(trim($day));

        if ($day === '' || !preg_match('/^(-?\d)?(MO|TU|WE|TH|FR|SA|SU)$/', $day, $m)) {
            continue;
        }

        $byday[] = [
            'ordinal' => $m[1] === '' ? null : (int) $m[1],
            'day' => $m[2],
        ];
    }

    $bymonthday = [];
    foreach (explode(',', (string) ($raw['BYMONTHDAY'] ?? '')) as $day) {
        $day = (int) trim($day);

        if ($day >= 1 && $day <= 31) {
            $bymonthday[] = $day;
        }
    }

    return [
        'freq' => $freq,
        'interval' => max(1, (int) ($raw['INTERVAL'] ?? 1)),
        'count' => isset($raw['COUNT']) ? max(1, (int) $raw['COUNT']) : null,
        'until' => $until,
        'byday' => $byday,
        'bymonthday' => $bymonthday,
    ];
}

/**
 * Weekday offset from Monday.
 *
 * @param string $day Two-letter RRULE weekday.
 * @return int 0 for Monday through 6 for Sunday.
 */
function tondi_rrule_weekday_offset(string $day): int
{
    $order = ['MO' => 0, 'TU' => 1, 'WE' => 2, 'TH' => 3, 'FR' => 4, 'SA' => 5, 'SU' => 6];

    return $order[$day] ?? 0;
}

/**
 * Every start date a rule produces inside a window.
 *
 * COUNT is counted from DTSTART, including instances that fall before the
 * window, because that is what it means in the feed; only the instances inside
 * the window are returned.
 *
 * @param DateTimeImmutable $start The series' DTSTART.
 * @param array $rule Output of tondi_parse_rrule().
 * @param DateTimeImmutable $from Earliest instance to return.
 * @param DateTimeImmutable $to Latest instance to return.
 * @param int $max_instances Cap on returned instances.
 * @param int $max_steps Cap on iterations, so an old open-ended daily series
 *   cannot spin.
 * @return DateTimeImmutable[] Sorted ascending.
 */
function tondi_rrule_instances(
    DateTimeImmutable $start,
    array $rule,
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    int $max_instances = 200,
    int $max_steps = 5000
): array {
    if (!$rule) {
        return [];
    }

    $interval = $rule['interval'];
    $limit = $rule['until'] instanceof DateTimeImmutable && $rule['until'] < $to
        ? $rule['until']
        : $to;

    $instances = [];
    $generated = 0;
    $steps = 0;

    // Each pass over the loop produces the dates of one interval period
    $cursor = $start;

    // BYDAY is resolved against the start of a period, so the cursor has to sit
    // there. Without it the series simply repeats DTSTART's own weekday or date.
    if ($rule['freq'] === 'WEEKLY' && $rule['byday']) {
        $cursor = $start->modify('monday this week')->setTime(
            (int) $start->format('H'),
            (int) $start->format('i'),
            (int) $start->format('s')
        );
    } else if ($rule['freq'] === 'MONTHLY') {
        $cursor = $start->modify('first day of this month');
    }

    while ($steps++ < $max_steps) {
        $candidates = tondi_rrule_period_dates($cursor, $start, $rule);

        foreach ($candidates as $candidate) {
            if ($candidate < $start) {
                continue;
            }

            if ($candidate > $limit) {
                // Later periods only move further away
                return tondi_rrule_sorted($instances);
            }

            $generated++;

            if ($candidate >= $from) {
                $instances[$candidate->format('Y-m-d H:i:s')] = $candidate;
            }

            if ($rule['count'] !== null && $generated >= $rule['count']) {
                return tondi_rrule_sorted($instances);
            }

            if (count($instances) >= $max_instances) {
                return tondi_rrule_sorted($instances);
            }
        }

        $cursor = tondi_rrule_advance($cursor, $rule['freq'], $interval);

        if ($cursor > $limit) {
            break;
        }
    }

    return tondi_rrule_sorted($instances);
}

/**
 * The dates one interval period contributes.
 *
 * @param DateTimeImmutable $cursor Start of the current period.
 * @param DateTimeImmutable $start The series' DTSTART, for time of day.
 * @param array $rule Output of tondi_parse_rrule().
 * @return DateTimeImmutable[] Ascending, possibly empty.
 */
function tondi_rrule_period_dates(DateTimeImmutable $cursor, DateTimeImmutable $start, array $rule): array
{
    $hour = (int) $start->format('H');
    $minute = (int) $start->format('i');
    $second = (int) $start->format('s');

    $dates = [];

    switch ($rule['freq']) {
        case 'DAILY':
            $dates[] = $cursor;
            break;

        case 'WEEKLY':
            if (!$rule['byday']) {
                $dates[] = $cursor;
                break;
            }

            foreach ($rule['byday'] as $day) {
                $dates[] = $cursor->modify('+' . tondi_rrule_weekday_offset($day['day']) . ' days');
            }
            break;

        case 'MONTHLY':
            if ($rule['byday']) {
                foreach ($rule['byday'] as $day) {
                    $date = tondi_rrule_nth_weekday($cursor, $day, $hour, $minute, $second);

                    if ($date instanceof DateTimeImmutable) {
                        $dates[] = $date;
                    }
                }
                break;
            }

            $days = $rule['bymonthday'] ?: [(int) $start->format('j')];
            foreach ($days as $day) {
                $date = tondi_rrule_day_of_month($cursor, $day, $hour, $minute, $second);

                if ($date instanceof DateTimeImmutable) {
                    $dates[] = $date;
                }
            }
            break;

        case 'YEARLY':
            $dates[] = $cursor;
            break;
    }

    usort($dates, fn ($a, $b) => $a <=> $b);

    return $dates;
}

/**
 * Move the cursor on by one interval.
 *
 * @param DateTimeImmutable $cursor Current period.
 * @param string $freq Rule frequency.
 * @param int $interval Periods to skip.
 * @return DateTimeImmutable The next period.
 */
function tondi_rrule_advance(DateTimeImmutable $cursor, string $freq, int $interval): DateTimeImmutable
{
    switch ($freq) {
        case 'DAILY':
            return $cursor->modify('+' . $interval . ' days');

        case 'WEEKLY':
            return $cursor->modify('+' . $interval . ' weeks');

        case 'MONTHLY':
            // From the first of the month, so a 31-day month cannot skip one
            return $cursor->modify('first day of this month')->modify('+' . $interval . ' months');

        default:
            return $cursor->modify('+' . $interval . ' years');
    }
}

/**
 * Resolve a BYDAY like "2TU" or "-1SU" inside one month.
 *
 * @param DateTimeImmutable $month Any date in the target month.
 * @param array $day Entry from a parsed rule's byday list.
 * @param int $hour Time of day to apply.
 * @param int $minute Time of day to apply.
 * @param int $second Time of day to apply.
 * @return DateTimeImmutable|null Null when the month has no such weekday.
 */
function tondi_rrule_nth_weekday(DateTimeImmutable $month, array $day, int $hour, int $minute, int $second): ?DateTimeImmutable
{
    $names = [
        'MO' => 'monday',
        'TU' => 'tuesday',
        'WE' => 'wednesday',
        'TH' => 'thursday',
        'FR' => 'friday',
        'SA' => 'saturday',
        'SU' => 'sunday',
    ];

    $name = $names[$day['day']] ?? null;
    if (!$name) {
        return null;
    }

    $ordinal = $day['ordinal'];

    // No ordinal means every such weekday, which a monthly rule cannot express
    // as a single date; treat it as the first one
    if ($ordinal === null) {
        $ordinal = 1;
    }

    $reference = $month->modify('first day of this month');

    if ($ordinal > 0) {
        $date = $reference->modify(
            sprintf('%s %s of this month', tondi_rrule_ordinal_word($ordinal), $name)
        );
    } else {
        $date = $reference->modify('last day of this month')->modify('last ' . $name . ' of this month');

        for ($i = -1; $i > $ordinal; $i--) {
            $date = $date->modify('-1 week');
        }
    }

    if ($date->format('n') !== $reference->format('n')) {
        return null;
    }

    return $date->setTime($hour, $minute, $second);
}

/**
 * English ordinal word PHP's relative formats accept.
 *
 * @param int $ordinal 1 through 5.
 * @return string "first" through "fifth".
 */
function tondi_rrule_ordinal_word(int $ordinal): string
{
    $words = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'];

    return $words[$ordinal] ?? 'first';
}

/**
 * A fixed day of the month, skipped when that month is too short.
 *
 * @param DateTimeImmutable $month Any date in the target month.
 * @param int $day Day of month.
 * @param int $hour Time of day to apply.
 * @param int $minute Time of day to apply.
 * @param int $second Time of day to apply.
 * @return DateTimeImmutable|null Null when the month has no such day.
 */
function tondi_rrule_day_of_month(DateTimeImmutable $month, int $day, int $hour, int $minute, int $second): ?DateTimeImmutable
{
    $first = $month->modify('first day of this month');

    if ($day > (int) $first->format('t')) {
        return null;
    }

    return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), $day)
        ->setTime($hour, $minute, $second);
}

/**
 * Sort an instance map into a plain ascending list.
 *
 * @param array<string, DateTimeImmutable> $instances Keyed by formatted date.
 * @return DateTimeImmutable[] Ascending.
 */
function tondi_rrule_sorted(array $instances): array
{
    ksort($instances);

    return array_values($instances);
}

/**
 * Turn recurring series into individual events, and drop what is cancelled.
 *
 * A single cancelled event is removed. A cancelled instance of a series arrives
 * as its own VEVENT carrying RECURRENCE-ID, and removes just that occurrence -
 * which is why cancelled events survive parsing and are resolved here.
 *
 * @param array $events Output of tondi_parse_ics_events().
 * @param DateTimeImmutable $from Earliest instance to keep.
 * @param DateTimeImmutable $to Latest instance to keep.
 * @return array Events with no recurrence left to resolve.
 */
function tondi_expand_recurring_events(array $events, DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $overrides = [];

    foreach ($events as $event) {
        $recurrence_id = $event['recurrence_id'] ?? null;

        if ($recurrence_id instanceof DateTimeImmutable) {
            $overrides[$event['uid']][$recurrence_id->format('Y-m-d H:i:s')] = $event;
        }
    }

    $used = [];
    $out = [];

    foreach ($events as $event) {
        if (($event['recurrence_id'] ?? null) instanceof DateTimeImmutable) {
            continue;
        }

        $cancelled = strtoupper((string) ($event['status'] ?? '')) === 'CANCELLED';
        $rrule = (string) ($event['rrule'] ?? '');

        if ($rrule === '') {
            if (!$cancelled) {
                $out[] = $event;
            }

            continue;
        }

        if ($cancelled) {
            continue;
        }

        $rule = tondi_parse_rrule($rrule, $event['start']->getTimezone());
        if (!$rule) {
            // An unreadable rule still has a first occurrence worth showing
            $out[] = $event;

            continue;
        }

        $length = $event['end'] instanceof DateTimeImmutable
            ? $event['start']->diff($event['end'])
            : null;

        $uid = (string) ($event['uid'] ?? '');
        $exdates = (array) ($event['exdates'] ?? []);

        foreach (tondi_rrule_instances($event['start'], $rule, $from, $to) as $instance) {
            $key = $instance->format('Y-m-d H:i:s');

            if (isset($exdates[$key])) {
                continue;
            }

            if (isset($overrides[$uid][$key])) {
                $override = $overrides[$uid][$key];
                $used[$uid . '|' . $key] = true;

                if (strtoupper((string) ($override['status'] ?? '')) === 'CANCELLED') {
                    continue;
                }

                $out[] = $override;

                continue;
            }

            $out[] = array_merge($event, [
                'start' => $instance,
                'end' => $length ? $instance->add($length) : null,
                'rrule' => '',
                'exdates' => [],
            ]);
        }
    }

    // An override whose series is not in the feed is still a real event
    foreach ($overrides as $uid => $by_key) {
        foreach ($by_key as $key => $override) {
            if (isset($used[$uid . '|' . $key])) {
                continue;
            }

            if (strtoupper((string) ($override['status'] ?? '')) === 'CANCELLED') {
                continue;
            }

            $out[] = $override;
        }
    }

    return $out;
}

// -----------------------------
// 5) Get upcoming events (sorted)
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

    // Yesterday, so an event running today still counts
    $events = tondi_expand_recurring_events(
        $events,
        $now->modify('-1 day'),
        $now->modify('+12 months')
    );

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
// 6) Display helpers
// -----------------------------
/**
 * Strip the blocks Google Calendar injects into DESCRIPTION by itself.
 *
 * Google adds a conferencing section whenever an event carries a video call,
 * whether or not anyone wrote a description, so without this every such event
 * renders dial-in boilerplate as its body text. Both shapes are handled: the
 * delimited "-::~:~::" block older feeds emit, and the bare lines current ones
 * use. The join URL is dropped rather than surfaced - these are physical school
 * events, and a public page is no place to hand out a link into the call.
 *
 * @param string $text Description with ICS escapes already decoded.
 * @return string Only what a human actually wrote.
 */
function tondi_strip_ics_boilerplate(string $text): string
{
    $text = preg_replace('/-::~:~:.*?:~:~::-/s', '', $text) ?? $text;

    $lines = [
        '/^[ \t]*Join with Google Meet:.*$/mi',
        '/^[ \t]*This event has a video call\.?[ \t]*$/mi',
        '/^[ \t]*Join:[ \t]*https?:\/\/meet\.google\.com\S*[ \t]*$/mi',
        '/^[ \t]*Or dial:.*$/mi',
        '/^[ \t]*More phone numbers:.*$/mi',
        '/^[ \t]*Learn more about Meet at:.*$/mi',
        '/^[ \t]*View your event at[ \t]*https?:\/\/\S+[ \t]*$/mi',
    ];

    $text = preg_replace($lines, '', $text) ?? $text;

    return trim($text);
}

/**
 * Tags kept from a feed's rich-text description.
 *
 * Google's editor emits h1 for what is visually a small heading, so headings
 * are not on the list: wp_kses() drops the tag and keeps the words.
 *
 * @return array<string, array<string, bool>> Allowlist in wp_kses() shape.
 */
function tondi_event_description_tags(): array
{
    return [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
        'b' => [],
        'strong' => [],
        'i' => [],
        'em' => [],
        'u' => [],
        'br' => [],
        'p' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
    ];
}

/**
 * Turn a raw ICS description into safe rich text.
 *
 * Descriptions written in Google Calendar arrive as markup, so a safe subset of
 * it is kept - flattening the tags would lose every list and emphasis. Plain
 * text descriptions take the other branch and get paragraphs and links.
 *
 * @param string $description Raw DESCRIPTION value.
 * @return string HTML safe to print, or an empty string when nothing is left.
 */
function tondi_format_event_description(string $description): string
{
    $description = tondi_strip_ics_boilerplate($description);

    if ($description === '') {
        return '';
    }

    if (!preg_match('#<[a-z][a-z0-9]*\b[^>]*>#i', $description)) {
        return make_clickable(wpautop(esc_html($description)));
    }

    $html = wp_kses($description, tondi_event_description_tags());

    // Google pads descriptions with empty <p><br></p> and <h1><br></h1> rows
    $html = preg_replace('#<(p|li)\b[^>]*>(?:\s|&nbsp;|\x{00a0}|<br\s*/?>)*</\1>#iu', '', $html) ?? $html;
    $html = preg_replace('#(?:<br\s*/?>\s*){2,}#i', '<br />', $html) ?? $html;

    // A stripped <h1><br></h1> leaves its <br> behind, which reads as a blank
    // line at the end of the description
    $html = preg_replace('#^(?:\s*<br\s*/?>)+|(?:<br\s*/?>\s*)+$#i', '', $html) ?? $html;
    $html = trim($html);

    if ($html === '') {
        return '';
    }

    // Inline-only markup still needs paragraphs around it
    if (!preg_match('#<(p|ul|ol)\b#i', $html)) {
        $html = wpautop($html);
    }

    return $html;
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
    $description = tondi_strip_ics_boilerplate($description);

    $description = preg_replace('#<br\s*/?>|</(p|div|li|h[1-6])>#i', ' ', $description) ?? $description;
    $description = wp_strip_all_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = str_replace("\u{00a0}", ' ', $description);
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
