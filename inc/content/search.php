<?php

/**
 * Highlight search query inside plain text.
 * Safe: works on text (not HTML), escapes output, wraps matches in <mark>.
 */
function tondi_highlight_search_text(string $text, string $query): string
{
    $text = trim($text);
    $query = trim($query);

    if ($text === '' || $query === '') {
        return esc_html($text);
    }

    // Split query into words, ignore very short tokens
    $parts = preg_split('/\s+/', $query) ?: [];
    $parts = array_values(array_filter(array_unique(array_map('trim', $parts)), fn($w) => mb_strlen($w) >= 2));

    if (empty($parts)) {
        return esc_html($text);
    }

    $escaped = esc_html($text);

    // Build regex from query parts (escaped for regex)
    $pattern = '/' . implode('|', array_map(fn($w) => preg_quote($w, '/'), $parts)) . '/iu';

    // Wrap with <mark>
    $highlighted = preg_replace($pattern, '<mark>$0</mark>', $escaped);

    // preg_replace can return null on error
    return is_string($highlighted) ? $highlighted : $escaped;
}
