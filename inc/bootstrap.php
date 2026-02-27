<?php
if (!defined('ABSPATH')) {
    exit;
}

// --- Core ---
require_once __DIR__ . '/core/admin-menu.php';
require_once __DIR__ . '/core/assets.php';
require_once __DIR__ . '/core/performance.php';
require_once __DIR__ . '/core/secure-mistakes.php';
require_once __DIR__ . '/core/theme-setup.php';
require_once __DIR__ . '/core/uploads.php';

// --- ACF ---
require_once __DIR__ . '/acf/acf.php';

// --- Content ---
require_once __DIR__ . '/content/cpt-registers.php';
require_once __DIR__ . '/content/queries.php';
require_once __DIR__ . '/content/search.php';

// --- Features ---
require_once __DIR__ . '/features/calendar.php';
require_once __DIR__ . '/features/gallery.php';
require_once __DIR__ . '/features/workers.php';
require_once __DIR__ . '/features/workers-modal.php';

// --- Navigation ---
require_once __DIR__ . '/navigation/class-tondi-fastlinks-walker.php';
require_once __DIR__ . '/navigation/nav-submenu.php';
