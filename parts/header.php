<?php

$has_logo = function_exists('the_custom_logo') && has_custom_logo();
$is_front = is_front_page() || is_home();

?>

<?php echo '<!-- Header -->'; ?>
<header class="site-header" role="banner">
    <div class="container">
        <div class="header-bar">
            <?php if ($has_logo): ?>
                <?php the_custom_logo(); ?>
            <?php else: ?>
                <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php if ($is_front): ?>
                        <h1 class="site-title">
                            <?php echo esc_html(get_bloginfo('name')); ?>
                        </h1>
                    <?php else: ?>
                        <span class="site-title">
                            <?php echo esc_html(get_bloginfo('name')); ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">
                <span class="nav-toggle__label">
                    ☰ Menüü
                    <?php /* _e('Menu', 'tondi'); */ ?>
                </span>
            </button>

            <nav id="primary-menu" class="primary-nav" aria-label="<?php esc_attr_e('Header', 'tondi'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'header',
                    'menu_class' => 'menu',
                    'container' => false,
                ]);
                ?>
            </nav>
        </div>
    </div>

    <!-- Decorative border -->
    <div class="header-border" aria-hidden="true"></div>
</header>

<script>
    // Navbar toggle button
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.querySelector('.nav-toggle');
        const nav = document.getElementById('primary-menu');

        if (!btn || !nav) return;

        btn.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', String(isOpen));
            document.body.classList.toggle('lock', isOpen);
        });
    });

    // Navbar menu alignment
    document.addEventListener('DOMContentLoaded', () => {
        const subMenus = document.querySelectorAll('.primary-nav .sub-menu');

        function updateMenuDirection(subMenu) {
            // Reset first so we measure the "right-side" version
            subMenu.classList.remove('align-left');

            const rect = subMenu.getBoundingClientRect();
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const edgePadding = 10;

            if (rect.right > viewportWidth - edgePadding) {
                subMenu.classList.add('align-left');
            }
        }

        subMenus.forEach(updateMenuDirection);

        // Recompute on resize
        window.addEventListener('resize', () => {
            subMenus.forEach(updateMenuDirection);
        });
    });

    // Navbar submenu alignment
    document.addEventListener('DOMContentLoaded', () => {
        const subSubMenus = document.querySelectorAll(
            '.primary-nav .sub-menu .sub-menu'
        );

        function updateSubmenuDirection(subMenu) {
            // Reset first so we measure the "right-side" version
            subMenu.classList.remove('align-left');

            const rect = subMenu.getBoundingClientRect();
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const edgePadding = 10;

            if (rect.right > viewportWidth - edgePadding) {
                subMenu.classList.add('align-left');
            }
        }

        subSubMenus.forEach(updateSubmenuDirection);

        // Recompute on resize
        window.addEventListener('resize', () => {
            subSubMenus.forEach(updateSubmenuDirection);
        });
    });
</script>

<?php echo '<!-- /Header -->'; ?>
