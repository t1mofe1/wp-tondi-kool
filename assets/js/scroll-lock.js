let savedY = 0;

/**
 * Freeze the page behind an overlay.
 *
 * iOS Safari scrolls the document even when it is set to overflow: hidden, so
 * the body is pinned with position: fixed instead and carries the reader's
 * offset in its inline top.
 *
 * @param {Object} [options] Lock options.
 * @param {boolean} [options.toTop] Show the top of the page while locked
 *   instead of the current position. The mobile menu needs this: it opens
 *   below the header, so the header has to be on screen.
 * @return {void}
 */
export function lockScroll({ toTop = false } = {}) {
  if (document.body.classList.contains('lock')) return;

  savedY = window.scrollY;

  document.body.style.top = toTop ? '0px' : `-${savedY}px`;
  document.documentElement.classList.add('lock');
  document.body.classList.add('lock');
}

/**
 * Release the page and return to where the reader was before the lock.
 *
 * @return {void}
 */
export function unlockScroll() {
  if (!document.body.classList.contains('lock')) return;

  document.documentElement.classList.remove('lock');
  document.body.classList.remove('lock');
  document.body.style.top = '';

  window.scrollTo(0, savedY);
}
