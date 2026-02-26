import 'vite/modulepreload-polyfill';

import '../scss/main.scss';

import { initWorkersModal } from './workers-modal.js';
import { initGalleryLightbox } from './lightbox-gallery.js';

document.addEventListener('DOMContentLoaded', () => {
  initWorkersModal();
  initGalleryLightbox();
});
