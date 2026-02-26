<?php

if (!defined('ABSPATH')) {
    exit;
}

function tondi_worker_placeholder_url(): string
{
    $url = get_stylesheet_directory_uri() . '/assets/img/worker-placeholder.svg';
    return (string) apply_filters('tondi_worker_placeholder_url', $url);
}

function tondi_worker_hide_photo(int $post_id): bool
{
    if (!function_exists('get_field')) {
        return false;
    }

    return (bool) get_field('worker_hide_photo', $post_id);
}

function tondi_worker_position(int $post_id): string
{
    if (!function_exists('get_field')) {
        return '';
    }

    return (string) (get_field('worker_position', $post_id) ?: '');
}

function tondi_worker_email(int $post_id): string
{
    if (!function_exists('get_field')) {
        return '';
    }

    return (string) (get_field('worker_email', $post_id) ?: '');
}

/**
 * ACF repeater: worker_phones
 * - label (text)
 * - number (text)
 *
 * @return array<int,array{label:string,number:string}>
 */
function tondi_worker_phones(int $post_id): array
{
    if (!function_exists('have_rows')) {
        return [];
    }

    $phones = [];

    if (!have_rows('worker_phones', $post_id)) {
        return $phones;
    }

    while (have_rows('worker_phones', $post_id)) {
        the_row();

        $label = (string) (get_sub_field('label') ?: '');
        $number = (string) (get_sub_field('number') ?: '');

        if (trim($number) !== '') {
            $phones[] = [
                'label' => $label,
                'number' => $number,
            ];
        }
    }

    return $phones;
}

function tondi_phone_to_tel_href(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '')
        return '';

    // Keep + and digits only
    $tel = preg_replace('/[^0-9+]/', '', $raw);

    // preg_replace can return null on error; be defensive
    $tel = is_string($tel) ? $tel : '';

    // Avoid returning raw formatted string for href
    return $tel !== '' ? $tel : '';
}

/**
 * Echo avatar <img> (photo or placeholder)
 */
function tondi_worker_avatar(int $post_id, string $size = 'medium', string $class = 'person__avatar-img'): void
{
    $alt = get_the_title($post_id);
    $alt = is_string($alt) ? $alt : '';

    if (tondi_worker_hide_photo($post_id) || !has_post_thumbnail($post_id)) {
        $classes = trim($class . ' person__avatar-img--placeholder');
        echo '<img class="' . esc_attr($classes) . '" src="' . esc_url(tondi_worker_placeholder_url()) . '" alt="' . esc_attr($alt) . '" loading="lazy" />';
        return;
    }

    echo get_the_post_thumbnail($post_id, $size, [
        'class' => $class,
        'alt' => $alt,
        'loading' => 'lazy',
    ]);
}

function tondi_worker_notes(int $post_id): string
{
    if (!function_exists('get_field')) {
        return '';
    }

    return (string) (get_field('worker_notes', $post_id) ?: '');
}
