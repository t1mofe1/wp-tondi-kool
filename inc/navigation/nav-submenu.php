<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limit wp_nav_menu() to the current branch when 'sub_menu' => true is used.
 * Stores:
 * - $args->tondi_submenu_root_id
 * - $args->tondi_submenu_root_title
 */
add_filter('wp_nav_menu_objects', function ($sorted_menu_items, $args) {
    // Always reset for this render to avoid stale values
    $args->tondi_submenu_root_id = 0;
    $args->tondi_submenu_root_title = '';

    if (empty($args->sub_menu)) {
        return $sorted_menu_items;
    }

    $root_id = 0;

    // Build quick index: id => item
    $by_id = [];
    foreach ($sorted_menu_items as $it) {
        $by_id[(int) $it->ID] = $it;
    }

    // 1) Find current menu item (or ancestor)
    foreach ($sorted_menu_items as $item) {
        $classes = (array) ($item->classes ?? []);

        $is_current =
            !empty($item->current) ||
            in_array('current-menu-item', $classes, true) ||
            in_array('current-menu-ancestor', $classes, true) ||
            in_array('current_page_item', $classes, true) ||
            in_array('current_page_ancestor', $classes, true);

        if ($is_current) {
            // Start from this item's parent if it has one, otherwise itself
            $root_id = $item->menu_item_parent ? (int) $item->menu_item_parent : (int) $item->ID;
            break;
        }
    }

    // No current item found (page not in menu) -> don't modify
    if (!$root_id) {
        return $sorted_menu_items;
    }

    // 2) Walk up to top-level ancestor within this menu
    $parent_id_to_check = $root_id;
    while ($parent_id_to_check && isset($by_id[$parent_id_to_check])) {
        $node = $by_id[$parent_id_to_check];

        if (!empty($node->menu_item_parent)) {
            $root_id = (int) $node->menu_item_parent;
            $parent_id_to_check = $root_id;
        } else {
            break;
        }
    }

    // 3) Store root info on args for templates (sidebar title etc.)
    $args->tondi_submenu_root_id = $root_id;
    $args->tondi_submenu_root_title = isset($by_id[$root_id]) ? (string) $by_id[$root_id]->title : '';

    // 4) Collect all descendants of the root item
    $keep_ids = [$root_id];
    $changed = true;

    while ($changed) {
        $changed = false;

        foreach ($sorted_menu_items as $item) {
            $id = (int) $item->ID;
            $parent = (int) $item->menu_item_parent;

            if (in_array($parent, $keep_ids, true) && !in_array($id, $keep_ids, true)) {
                $keep_ids[] = $id;
                $changed = true;
            }
        }
    }

    // 5) Keep only root descendants, remove root itself, and lift direct children to top-level
    $new_items = [];

    foreach ($sorted_menu_items as $item) {
        $id = (int) $item->ID;
        $parent = (int) $item->menu_item_parent;

        if (!in_array($id, $keep_ids, true)) {
            continue;
        }

        if ($id === $root_id) {
            continue;
        }

        if ($parent === $root_id) {
            $item->menu_item_parent = 0;
        }

        $new_items[] = $item;
    }

    return $new_items;
}, 10, 2);
