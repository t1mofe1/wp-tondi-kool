/**
 * Gallery Lightbox with:
 * - arrows + keyboard
 * - swipe
 * - URL query param ?photo=ATTACHMENT_ID (shareable + back/forward)
 * - slide + fade animation
 *
 * Requires buttons:
 *   .js-gallery-item
 *     data-id="123"
 *     data-full="https://..."
 *     data-caption="..."
 *
 * Requires lightbox markup:
 *   .gallery-lightbox
 *     .gallery-lightbox__backdrop
 *     .gallery-lightbox__close
 *     .gallery-lightbox__prev (optional but recommended)
 *     .gallery-lightbox__next (optional but recommended)
 *     .gallery-lightbox__stage
 *       img.gallery-lightbox__img.is-active
 *     figcaption
 *     .gallery-lightbox__counter
 */

export function initGalleryLightbox() {
  const items = Array.from(document.querySelectorAll('.js-gallery-item'));
  const overlay = document.querySelector('.gallery-lightbox');
  if (!overlay || !items.length) return;

  const stage = overlay.querySelector('.gallery-lightbox__stage');
  let activeImg = overlay.querySelector('.gallery-lightbox__img.is-active');
  const captionEl = overlay.querySelector('figcaption');
  const closeBtn = overlay.querySelector('.gallery-lightbox__close');
  const backdrop = overlay.querySelector('.gallery-lightbox__backdrop');
  const prevBtn = overlay.querySelector('.gallery-lightbox__prev');
  const nextBtn = overlay.querySelector('.gallery-lightbox__next');
  const counter = overlay.querySelector('.gallery-lightbox__counter');

  // Safety checks
  if (!stage || !activeImg || !closeBtn || !backdrop || !captionEl) return;

  let lastFocused = null;
  let index = 0;
  let isOpen = false;
  let isAnimating = false;

  // Swipe state
  let startX = 0;
  let startY = 0;
  let isPointerDown = false;

  function lockBody(lock) {
    document.body.classList.toggle('lock', !!lock);
  }

  function preventGesture(e) {
    if (!isOpen) return;
    e.preventDefault();
  }
  overlay.addEventListener('touchmove', preventGesture, { passive: false });
  document.addEventListener('gesturestart', preventGesture, { passive: false });
  document.addEventListener('gesturechange', preventGesture, {
    passive: false,
  });
  document.addEventListener('gestureend', preventGesture, { passive: false });

  function parseId(val) {
    const n = parseInt(String(val || ''), 10);
    return Number.isFinite(n) ? n : null;
  }

  function getItemId(i) {
    return parseId(items[i]?.dataset?.id);
  }

  function findIndexById(id) {
    return items.findIndex((el) => parseId(el.dataset?.id) === id);
  }

  function readUrlPhoto() {
    const url = new URL(window.location.href);
    return parseId(url.searchParams.get('photo'));
  }

  function setUrlPhoto(id, mode = 'replace') {
    const url = new URL(window.location.href);

    if (id) url.searchParams.set('photo', String(id));
    else url.searchParams.delete('photo');

    const qs = url.searchParams.toString();
    const newUrl = url.pathname + (qs ? `?${qs}` : '');

    if (mode === 'push') history.pushState({ photo: id || null }, '', newUrl);
    else history.replaceState({ photo: id || null }, '', newUrl);
  }

  function preloadNeighbor(url) {
    if (!url) return;
    const im = new Image();
    im.src = url;
  }

  function setMeta(text) {
    captionEl.textContent = text || '';
    if (counter) counter.textContent = `${index + 1} / ${items.length}`;
  }

  function instantSet(src, alt) {
    activeImg.src = src || '';
    activeImg.alt = alt || '';
    activeImg.classList.add('is-active');
    activeImg.style.transform = '';
    activeImg.style.opacity = '';
  }

  function createImg(src, alt) {
    const el = document.createElement('img');
    el.className = 'gallery-lightbox__img';
    el.alt = alt || '';
    el.src = src || '';
    return el;
  }

  // Slide + fade animation
  function animateTo(src, alt, direction = 1) {
    if (isAnimating) return;
    isAnimating = true;

    const nextImg = createImg(src, alt);

    // Start offscreen (direction: 1 = next to left, -1 = prev to right)
    nextImg.style.transform = `translateX(${direction * 18}%)`;
    nextImg.style.opacity = '0';

    stage.appendChild(nextImg);

    // Force layout
    // eslint-disable-next-line no-unused-expressions
    nextImg.getBoundingClientRect();

    // Animate current out + next in
    activeImg.style.transform = `translateX(${direction * -18}%)`;
    activeImg.style.opacity = '0';

    nextImg.style.transform = 'translateX(0)';
    nextImg.style.opacity = '1';

    const finish = () => {
      // Remove old image
      if (activeImg && activeImg.parentNode) {
        activeImg.parentNode.removeChild(activeImg);
      }

      nextImg.classList.add('is-active');
      nextImg.style.transform = '';
      nextImg.style.opacity = '';
      activeImg = nextImg;

      isAnimating = false;
    };

    nextImg.addEventListener('transitionend', finish, { once: true });
  }

  function setSlide(
    i,
    { updateUrl = true, animate = false, direction = 1 } = {},
  ) {
    index = (i + items.length) % items.length;

    const btn = items[index];
    const src = btn.dataset?.full || '';
    const text = btn.dataset?.caption || '';
    const id = getItemId(index);

    setMeta(text);

    if (!animate) {
      instantSet(src, text);
    } else {
      animateTo(src, text, direction);
    }

    // Preload neighbors
    const nextSrc = items[(index + 1) % items.length]?.dataset?.full;
    const prevSrc =
      items[(index - 1 + items.length) % items.length]?.dataset?.full;
    preloadNeighbor(nextSrc);
    preloadNeighbor(prevSrc);

    if (updateUrl && id) setUrlPhoto(id, 'replace');
  }

  function openAt(i, origin, { pushUrl = true } = {}) {
    lastFocused = origin || document.activeElement;
    isOpen = true;

    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    lockBody(true);

    // Open instantly (no animation)
    setSlide(i, { updateUrl: false, animate: false });

    const id = getItemId(index);
    if (pushUrl && id) setUrlPhoto(id, 'push');

    closeBtn.focus();
  }

  function close({ updateUrl = true } = {}) {
    isOpen = false;

    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    lockBody(false);

    // Keep activeImg element, just clear its src to release memory
    activeImg.src = '';
    activeImg.alt = '';

    if (updateUrl) setUrlPhoto(null, 'push');

    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  function next() {
    setSlide(index + 1, { updateUrl: true, animate: true, direction: 1 });
  }

  function prev() {
    setSlide(index - 1, { updateUrl: true, animate: true, direction: -1 });
  }

  // Open on click
  items.forEach((btn, i) => {
    btn.addEventListener('click', () => openAt(i, btn, { pushUrl: true }));
  });

  // Close controls
  closeBtn.addEventListener('click', () => close({ updateUrl: true }));
  backdrop.addEventListener('click', () => close({ updateUrl: true }));

  // Nav controls
  if (nextBtn) nextBtn.addEventListener('click', next);
  if (prevBtn) prevBtn.addEventListener('click', prev);

  // Keyboard
  document.addEventListener('keydown', (e) => {
    if (!isOpen) return;

    if (e.key === 'Escape') close({ updateUrl: true });
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });

  // Swipe (Pointer Events) - mouse + touch
  overlay.addEventListener('pointerdown', (e) => {
    if (!isOpen) return;
    isPointerDown = true;
    startX = e.clientX;
    startY = e.clientY;
  });

  overlay.addEventListener('pointerup', (e) => {
    if (!isOpen || !isPointerDown) return;
    isPointerDown = false;

    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    // Ignore mostly-vertical movement
    if (Math.abs(dy) > Math.abs(dx)) return;

    const threshold = 40;
    if (dx > threshold) prev();
    if (dx < -threshold) next();
  });

  overlay.addEventListener('pointercancel', () => {
    isPointerDown = false;
  });

  // Back/forward support (popstate)
  window.addEventListener('popstate', () => {
    const id = readUrlPhoto();

    if (!id) {
      if (isOpen) close({ updateUrl: false });
      return;
    }

    const i = findIndexById(id);
    if (i === -1) return;

    if (!isOpen) {
      openAt(i, null, { pushUrl: false });
    } else {
      // URL navigation should be instant (no slide direction guess)
      setSlide(i, { updateUrl: false, animate: false });
    }
  });

  // Auto-open if URL has ?photo=ID on load
  const initialId = readUrlPhoto();
  if (initialId) {
    const i = findIndexById(initialId);
    if (i !== -1) openAt(i, null, { pushUrl: false });
  }
}
