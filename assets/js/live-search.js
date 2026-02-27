export function initLiveSearch() {
  const cfg = window.TondiLiveSearch;
  if (!cfg) return;

  const modal = document.getElementById('site-search-modal');
  if (!modal) return;

  const input = modal.querySelector('input[type="search"][name="s"]');
  const resultsEl = modal.querySelector('.site-search-modal__results');
  const form = modal.querySelector('form[role="search"]');

  if (!input || !resultsEl || !form) return;

  let timer = null;
  let aborter = null;

  const escapeHtml = (s) =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;',
        })[c],
    );

  const highlight = (text, q) => {
    const safe = escapeHtml(text);
    const parts = q
      .trim()
      .split(/\s+/)
      .filter((w) => w.length >= 2);
    if (!parts.length) return safe;

    const re = new RegExp(
      `(${parts.map((p) => p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`,
      'ig',
    );
    return safe.replace(re, '<mark>$1</mark>');
  };

  const hideResults = () => {
    resultsEl.hidden = true;
    resultsEl.innerHTML = '';
  };

  const showResults = (items, q) => {
    resultsEl.hidden = false;

    if (!items.length) {
      resultsEl.innerHTML = `<div class="live-search-empty">Tulemusi ei leitud</div>`;
      return;
    }

    const html = items
      .map((item) => {
        const title = highlight(item.title, q);
        const subtitle = item.subtitle
          ? `<div class="live-search-sub">${escapeHtml(item.subtitle)}</div>`
          : '';

        return `
        <a class="live-search-item live-search-item--${escapeHtml(item.type)}" href="${escapeHtml(item.url)}">
          <span class="live-search-badge">${escapeHtml(item.label)}</span>
          <span class="live-search-main">
            <span class="live-search-title">${title}</span>
            ${subtitle}
          </span>
        </a>
      `;
      })
      .join('');

    resultsEl.innerHTML = `<div class="live-search-list">${html}</div>`;
  };

  const fetchResults = async (q) => {
    if (aborter) aborter.abort();
    aborter = new AbortController();

    const url = new URL(cfg.ajaxUrl);
    url.searchParams.set('action', 'tondi_live_search');
    url.searchParams.set('nonce', cfg.nonce);
    url.searchParams.set('q', q);

    const res = await fetch(url.toString(), { signal: aborter.signal });
    const data = await res.json();

    if (!data || !data.success) return [];
    return data.data?.items || [];
  };

  const onInput = () => {
    const q = input.value.trim();
    if (q.length < (cfg.minLen || 2)) {
      hideResults();
      return;
    }

    clearTimeout(timer);
    timer = setTimeout(async () => {
      try {
        const items = await fetchResults(q);
        showResults(items, q);
      } catch (e) {
        if (e?.name !== 'AbortError') hideResults();
      }
    }, 220);
  };

  input.addEventListener('input', onInput);

  form.addEventListener('submit', () => hideResults());

  // close button/backdrop should clear dropdown
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-search-close]')) hideResults();
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') hideResults();
  });
}
