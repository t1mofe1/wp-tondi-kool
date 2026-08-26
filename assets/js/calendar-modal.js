import { lockScroll, unlockScroll } from './scroll-lock.js';

const FOCUSABLE =
  'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

export function initCalendarModal() {
  const modal = document.getElementById('calendar-modal');
  if (!modal) return;

  const backdrop = document.getElementById('calendar-modal-backdrop');
  const contentEl = document.getElementById('calendar-modal-content');
  const closeBtn = modal.querySelector('[data-calendar-modal-close]');

  let lastFocused = null;

  function isOpen() {
    return modal.getAttribute('aria-hidden') === 'false';
  }

  function openModal(template) {
    if (!contentEl) return;

    contentEl.replaceChildren(template.content.cloneNode(true));

    lastFocused = document.activeElement;

    modal.setAttribute('aria-hidden', 'false');
    backdrop?.setAttribute('aria-hidden', 'false');

    lockScroll();

    closeBtn?.focus?.({ preventScroll: true });
  }

  function closeModal() {
    if (!isOpen()) return;

    modal.setAttribute('aria-hidden', 'true');
    backdrop?.setAttribute('aria-hidden', 'true');

    unlockScroll();

    contentEl?.replaceChildren();

    lastFocused?.focus?.({ preventScroll: true });
    lastFocused = null;
  }

  function trapFocus(e) {
    const focusable = [...modal.querySelectorAll(FOCUSABLE)].filter(
      (el) => el.offsetParent !== null,
    );

    if (!focusable.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-calendar-event]');
    if (!trigger) return;

    const id = trigger.getAttribute('data-calendar-event');
    if (!id) return;

    const template = document.querySelector(
      `[data-calendar-event-body="${CSS.escape(id)}"]`,
    );
    if (!template) return;

    e.preventDefault();
    openModal(template);
  });

  modal.addEventListener('click', (e) => {
    if (e.target.closest('[data-calendar-modal-close]')) closeModal();
  });

  backdrop?.addEventListener('click', closeModal);

  document.addEventListener('keydown', (e) => {
    if (!isOpen()) return;

    if (e.key === 'Escape') {
      closeModal();
    } else if (e.key === 'Tab') {
      trapFocus(e);
    }
  });
}
