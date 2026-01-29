<?php

if (!defined('ABSPATH')) {
    exit;
}

function tondi_filebird_folder_choices_indented(): array
{
    if (!class_exists(\FileBird\Classes\Tree::class)) {
        return [];
    }

    $tree = \FileBird\Classes\Tree::getFolders(null);
    $choices = [];

    $walk = function ($nodes, int $depth = 0) use (&$walk, &$choices) {
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
                $prefix = str_repeat('— ', max(0, $depth));
                $choices[(string) $id] = $prefix . $name;
            }

            $children = $node['children'] ?? [];
            if (is_array($children) && !empty($children)) {
                $walk($children, $depth + 1);
            }
        }
    };

    $walk(is_array($tree) ? $tree : [], 0);

    return $choices;
}
