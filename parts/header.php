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
    // Navbar mobile submenu toggles
    document.addEventListener('DOMContentLoaded', () => {
        const nav = document.getElementById('primary-menu');
        if (!nav) return;

        const mobileMq = window.matchMedia('(max-width: 1024px)');
        const items = nav.querySelectorAll('.menu-item-has-children');

        function updateAncestorSubmenuHeights(startItem) {
            if (!mobileMq.matches) return;

            let parentItem = startItem.parentElement?.closest('.menu-item-has-children');

            while (parentItem) {
                const parentSubmenu = parentItem.querySelector(':scope > .sub-menu');

                if (parentSubmenu && parentItem.classList.contains('submenu-open')) {
                    parentSubmenu.style.height = 'auto';
                    parentSubmenu.style.height = `${parentSubmenu.scrollHeight}px`;
                }

                parentItem = parentItem.parentElement?.closest('.menu-item-has-children');
            }
        }

        function closeItem(item) {
            const toggle = item.querySelector(':scope > .submenu-toggle');
            const submenu = item.querySelector(':scope > .sub-menu');

            if (!toggle || !submenu) return;

            // Close children first
            const openChildren = item.querySelectorAll('.menu-item-has-children.submenu-open');
            openChildren.forEach((child) => {
                if (child === item) return;

                const childToggle = child.querySelector(':scope > .submenu-toggle');
                const childSubmenu = child.querySelector(':scope > .sub-menu');

                child.classList.remove('submenu-open');

                if (childToggle) {
                    childToggle.setAttribute('aria-expanded', 'false');
                }

                if (childSubmenu) {
                    childSubmenu.setAttribute('aria-hidden', 'true');
                    childSubmenu.style.height = '0px';
                }
            });

            submenu.style.height = `${submenu.scrollHeight}px`;

            requestAnimationFrame(() => {
                item.classList.remove('submenu-open');
                toggle.setAttribute('aria-expanded', 'false');
                submenu.setAttribute('aria-hidden', 'true');
                submenu.style.height = '0px';
                updateAncestorSubmenuHeights(item);
            });
        }

        function closeSiblingItems(item) {
            const parentList = item.parentElement;
            if (!parentList) return;

            const siblings = Array.from(parentList.children).filter((sibling) => {
                return sibling !== item && sibling.classList.contains('menu-item-has-children');
            });

            siblings.forEach((sibling) => {
                if (sibling.classList.contains('submenu-open')) {
                    closeItem(sibling);
                }
            });
        }

        function openItem(item) {
            const toggle = item.querySelector(':scope > .submenu-toggle');
            const submenu = item.querySelector(':scope > .sub-menu');

            if (!toggle || !submenu) return;

            closeSiblingItems(item);

            item.classList.add('submenu-open');
            toggle.setAttribute('aria-expanded', 'true');
            submenu.setAttribute('aria-hidden', 'false');
            submenu.style.height = `${submenu.scrollHeight}px`;

            updateAncestorSubmenuHeights(item);
        }

        items.forEach((item, index) => {
            const link = item.querySelector(':scope > a');
            const submenu = item.querySelector(':scope > .sub-menu');

            if (!link || !submenu) return;
            if (item.querySelector(':scope > .submenu-toggle')) return;

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'submenu-toggle';
            toggle.setAttribute('aria-expanded', 'false');

            const submenuId = submenu.id || `submenu-${index + 1}`;
            submenu.id = submenuId;

            toggle.setAttribute('aria-controls', submenuId);
            toggle.setAttribute('aria-label', `${link.textContent.trim()} alammenüü`);
            toggle.innerHTML = '<span class="submenu-toggle__icon" aria-hidden="true">▾</span>';

            item.insertBefore(toggle, submenu);

            submenu.style.height = '0px';
            submenu.setAttribute('aria-hidden', 'true');

            toggle.addEventListener('click', (e) => {
                e.preventDefault();

                if (!mobileMq.matches) return;

                if (item.classList.contains('submenu-open')) {
                    closeItem(item);
                } else {
                    openItem(item);
                }
            });

            submenu.addEventListener('transitionend', (e) => {
                if (e.propertyName !== 'height') return;
                if (!mobileMq.matches) return;

                if (item.classList.contains('submenu-open')) {
                    submenu.style.height = 'auto';
                    updateAncestorSubmenuHeights(item);
                }
            });
        });

        function resetSubmenusForBreakpoint() {
            items.forEach((item) => {
                const toggle = item.querySelector(':scope > .submenu-toggle');
                const submenu = item.querySelector(':scope > .sub-menu');

                if (!toggle || !submenu) return;

                if (!mobileMq.matches) {
                    item.classList.remove('submenu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    submenu.setAttribute('aria-hidden', 'false');
                    submenu.style.height = '';
                } else {
                    item.classList.remove('submenu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    submenu.setAttribute('aria-hidden', 'true');
                    submenu.style.height = '0px';
                }
            });
        }

        resetSubmenusForBreakpoint();
        mobileMq.addEventListener('change', resetSubmenusForBreakpoint);
    });

    // Navbar toggle button
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.querySelector('.nav-toggle');
        const nav = document.getElementById('primary-menu');

        if (!btn || !nav) return;

        btn.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');

            btn.setAttribute('aria-expanded', String(isOpen));
            btn.querySelector('.nav-toggle__label').textContent = isOpen ? '✕ Sulge' : '☰ Menüü';

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
