import 'vite/modulepreload-polyfill';

import '../scss/main.scss';

import { initWorkersModal } from './workers-modal.js';
import { initGalleryLightbox } from './lightbox-gallery.js';
import { initLiveSearch } from './live-search.js';
import { initSidebarAccordion } from './sidebar-accordion.js';

document.addEventListener('DOMContentLoaded', () => {
  initWorkersModal();
  initGalleryLightbox();
  initLiveSearch();
  initSidebarAccordion();
});
