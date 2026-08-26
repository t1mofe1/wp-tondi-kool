import 'vite/modulepreload-polyfill';

import '../scss/main.scss';

import { initWorkersModal } from './workers-modal.js';
import { initGalleryLightbox } from './lightbox-gallery.js';
import { initLiveSearch } from './live-search.js';
import { initSidebarAccordion } from './sidebar-accordion.js';
import { initCalendarModal } from './calendar-modal.js';
import { lockScroll, unlockScroll } from './scroll-lock.js';

// Inline scripts in header.php and front-page.php reach the lock through here
window.TondiScrollLock = { lock: lockScroll, unlock: unlockScroll };

document.addEventListener('DOMContentLoaded', () => {
  initWorkersModal();
  initGalleryLightbox();
  initLiveSearch();
  initSidebarAccordion();
  initCalendarModal();
});
