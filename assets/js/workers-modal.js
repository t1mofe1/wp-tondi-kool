(function () {
  const modal = document.getElementById('worker-modal');
  if (!modal) return;

  const backdrop = document.getElementById('worker-modal-backdrop');

  const statusEl = document.getElementById('worker-modal-status');
  const spinnerEl = document.getElementById('worker-modal-status-spinner');
  const statusTextEl = document.getElementById('worker-modal-status-text');

  const closeBtn = modal.querySelector('[data-modal-close]');

  const contentEl = document.getElementById('worker-modal-content');

  const cfg = window.TondiWorkerModal || {};

  let lastFocused = null;
  let controller = null;

  function setWorkerInUrl(value) {
    const url = new URL(window.location.href);

    if (value) {
      url.searchParams.set('worker', value);
    } else {
      url.searchParams.delete('worker');
    }

    window.history.pushState({ worker: value || null }, '', url);
  }
  function getWorkerFromUrl() {
    const url = new URL(window.location.href);

    return url.searchParams.get('worker');
  }

  function setStatus(text, showSpinner = false) {
    if (!statusEl) return;
    statusEl.hidden = !text;

    if (spinnerEl) spinnerEl.hidden = !showSpinner;
    if (statusTextEl) statusTextEl.textContent = text || '';
  }

  function openModal() {
    lastFocused = document.activeElement;

    modal.setAttribute('aria-hidden', 'false');
    if (backdrop) backdrop.setAttribute('aria-hidden', 'false');

    document.documentElement.classList.add('is-modal-open');
    document.body.classList.add('is-modal-open');

    closeBtn?.focus?.();
  }

  function closeModal() {
    if (controller) {
      controller.abort();
      controller = null;
    }

    setStatus(null);

    modal.setAttribute('aria-hidden', 'true');
    if (backdrop) backdrop.setAttribute('aria-hidden', 'true');

    document.documentElement.classList.remove('is-modal-open');
    document.body.classList.remove('is-modal-open');

    setWorkerInUrl(null);

    lastFocused?.focus?.();
  }

  async function loadWorker(workerId) {
    if (!contentEl) return;

    if (!cfg.ajaxUrl || !cfg.nonce) {
      setStatus('Viga konfiguratsioonis.');
      return;
    }

    contentEl.innerHTML = '';

    setStatus('Laeb...', true);

    if (controller) controller.abort();
    controller = new AbortController();

    const form = new FormData();
    form.append('action', 'tondi_worker_modal');
    form.append('id', workerId);
    form.append('slug', workerId);
    form.append('nonce', cfg.nonce);

    try {
      const res = await fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: form,
        signal: controller.signal,
      });

      const data = await res.json();

      if (!res.ok || !data?.success) {
        setStatus(
          data?.data?.message || data?.message || 'Tekkis viga laadimisel.',
        );
        return;
      }

      contentEl.innerHTML = data?.data?.html || '';
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      setStatus('Tekkis viga laadimisel.');
    } finally {
      setStatus(null);
      controller = null;
    }
  }

  // Open on trigger click
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-worker-modal]');
    if (!trigger) return;

    const id = trigger.getAttribute('data-worker-modal');
    if (!id) return;

    e.preventDefault();
    openModal();
    setWorkerInUrl(id);
    loadWorker(id);
  });

  // Close on close button
  modal.addEventListener('click', (e) => {
    if (
      e.target.matches('[data-modal-close]') ||
      e.target.closest('[data-modal-close]')
    ) {
      closeModal();
    }
  });

  // Close on backdrop click
  backdrop?.addEventListener('click', closeModal);

  // Close on ESC key
  document.addEventListener('keydown', (e) => {
    if (modal.getAttribute('aria-hidden') === 'false' && e.key === 'Escape') {
      closeModal();
    }
  });

  // Open if worker is in URL
  const initialWorker = getWorkerFromUrl();
  if (initialWorker) {
    openModal();
    loadWorker(initialWorker);
  }

  // Handle back/forward navigation
  window.addEventListener('popstate', (e) => {
    const w = getWorkerFromUrl();

    if (w) {
      if (modal.getAttribute('aria-hidden') !== 'false') {
        openModal();
      }

      loadWorker(w);
    } else {
      if (modal.getAttribute('aria-hidden') === 'false') {
        closeModal();
      }
    }
  });
})();
