export function initSidebarAccordion() {
  const sidebars = document.querySelectorAll('.sub-sidebar');
  if (!sidebars.length) return;

  sidebars.forEach((sidebar) => {
    const items = sidebar.querySelectorAll('.menu-item-has-children');

    items.forEach((li) => {
      const link = li.querySelector(':scope > a');
      const submenu = li.querySelector(':scope > .sub-menu');
      if (!link || !submenu) return;

      // Ensure submenu has an ID for aria-controls
      if (!submenu.id) {
        submenu.id =
          'sub-sidebar-submenu-' + Math.random().toString(36).slice(2, 10);
      }

      // Create toggle button (separate from the link so link can navigate)
      let btn = li.querySelector(':scope > .sub-sidebar__toggle');
      if (!btn) {
        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sub-sidebar__toggle';
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', submenu.id);
        btn.innerHTML = `
        <span class="sub-sidebar__toggle-icon" aria-hidden="true">▾</span>
        <span class="screen-reader-text">Toggle submenu</span>
      `;
        link.insertAdjacentElement('afterend', btn);
      }

      // Open current branch by default
      const shouldOpen =
        li.classList.contains('current-menu-ancestor') ||
        li.classList.contains('current-menu-item') ||
        li.querySelector(
          '.current-menu-item, .current-menu-ancestor, .current_page_item, .current_page_ancestor',
        );

      if (shouldOpen) {
        openItem(li, false);
      } else {
        submenu.style.height = '0px';
        btn.setAttribute('aria-expanded', 'false');
      }

      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = li.classList.contains('is-open');

        if (isOpen) {
          closeItem(li);
        } else {
          closeSiblings(li);
          openItem(li, true);
        }
      });
    });

    function closeSiblings(li) {
      const parentList = li.parentElement; // ul
      if (!parentList) return;

      const openSiblings = parentList.querySelectorAll(
        ':scope > .menu-item-has-children.is-open',
      );
      openSiblings.forEach((sib) => {
        if (sib !== li) closeItem(sib);
      });
    }

    function openItem(li, animate) {
      const submenu = li.querySelector(':scope > .sub-menu');
      const btn = li.querySelector(':scope > .sub-sidebar__toggle');
      if (!submenu || !btn) return;

      li.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');

      submenu.style.overflow = 'hidden';

      submenu.style.height = 'auto';
      const target = submenu.scrollHeight;
      submenu.style.height = '0px';

      if (!animate) {
        submenu.style.height = 'auto';
        return;
      }

      submenu.getBoundingClientRect();
      submenu.style.height = target + 'px';

      submenu.addEventListener(
        'transitionend',
        (ev) => {
          if (ev.propertyName !== 'height') return;
          submenu.style.height = 'auto';
        },
        { once: true },
      );
    }

    function closeItem(li) {
      const submenu = li.querySelector(':scope > .sub-menu');
      const btn = li.querySelector(':scope > .sub-sidebar__toggle');
      if (!submenu || !btn) return;

      li.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');

      submenu.style.height = submenu.scrollHeight + 'px';
      submenu.getBoundingClientRect(); // force reflow
      submenu.style.height = '0px';
    }
  });
}
