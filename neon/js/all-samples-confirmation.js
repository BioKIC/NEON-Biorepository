(function () {
  'use strict';

  const countUrl = document.currentScript.dataset.countUrl;
  let dialog;
  let request;
  let restoreScroll;
  let returnFocus;

  function scopeFor(href) {
    const url = new URL(href, window.location.href);
    if (url.origin !== window.location.origin || !url.pathname.endsWith('/collections/list.php')) return '';
    const db = [...new Set(url.searchParams.getAll('db').join(',').toLowerCase().split(',')
      .map(value => value.trim().replace(/^all-other$/, 'all-others')))];
    if (!db.includes('all') || db.some(value => value !== 'all' && value !== 'all-others')) return '';
    // These options do not narrow a search without any taxon or identifier criteria.
    const options = new Set(['db', 'usethes', 'includeothercatnum', 'taxontype', 'comingFrom']);
    for (const [name, value] of url.searchParams) {
      if (value.trim() && !options.has(name)) return '';
    }
    return db.includes('all-others') ? 'all,all-others' : 'all';
  }

  function createDialog() {
    dialog = document.createElement('dialog');
    dialog.id = 'neon-all-samples-confirmation';
    dialog.setAttribute('aria-labelledby', 'neon-all-samples-message');
    dialog.innerHTML = '<svg class="all-samples-warning" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
      '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>' +
      '<p id="neon-all-samples-message" aria-live="polite"></p>' +
      '<div class="all-samples-actions">' +
      '<button type="button" class="all-samples-cancel" autofocus>' +
      '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m18 6-12 12M6 6l12 12"/></svg>Cancel</button>' +
      '<button type="button" class="all-samples-retry" hidden>Try Again</button>' +
      '<a class="all-samples-continue" aria-disabled="true" tabindex="-1">Continue' +
      '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a></div>';
    document.body.appendChild(dialog);
    dialog.querySelector('.all-samples-cancel').addEventListener('click', () => dialog.close());
    dialog.querySelector('.all-samples-continue').addEventListener('click', event => {
      if (event.currentTarget.getAttribute('aria-disabled') === 'true') event.preventDefault();
      else dialog.close();
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => {
      request?.abort();
      restoreScroll?.();
      returnFocus?.focus({ preventScroll: true });
    });
  }

  function confirm(href, { target = '_self' } = {}) {
    const scope = scopeFor(href);
    if (!scope) return false;
    if (!dialog) createDialog();
    if (dialog.open) return true;
    const url = new URL(href, window.location.href);
    url.searchParams.delete('comingFrom');
    const message = dialog.querySelector('#neon-all-samples-message');
    const proceed = dialog.querySelector('.all-samples-continue');
    const retry = dialog.querySelector('.all-samples-retry');
    const loadCount = async () => {
      request?.abort();
      const controller = new AbortController();
      request = controller;
      message.textContent = 'Counting samples…';
      message.setAttribute('aria-busy', 'true');
      proceed.removeAttribute('href');
      proceed.setAttribute('aria-disabled', 'true');
      proceed.tabIndex = -1;
      retry.hidden = true;
      const timeout = setTimeout(() => controller.abort(), 15000);
      try {
        const response = await fetch(countUrl, {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin', signal: controller.signal,
          body: JSON.stringify({ db: scope }),
        });
        if (!response.ok) throw new Error('Count unavailable');
        const data = await response.json();
        if (!Number.isSafeInteger(data.count) || data.count < 0) throw new Error('Invalid count');
        if (request !== controller || !dialog.open) return;
        message.textContent = `Are you sure you want to list all ${data.count.toLocaleString('en-US')} samples?`;
        proceed.href = url.href;
        proceed.target = target;
        proceed.rel = 'noopener noreferrer';
        proceed.setAttribute('aria-disabled', 'false');
        proceed.tabIndex = 0;
      } catch (error) {
        if (request !== controller || !dialog.open) return;
        message.textContent = 'The sample count could not be loaded. Please try again.';
        retry.hidden = false;
      } finally {
        clearTimeout(timeout);
        if (request === controller) message.removeAttribute('aria-busy');
      }
    };
    retry.onclick = loadCount;
    returnFocus = document.activeElement;
    const rootOverflow = document.documentElement.style.overflow;
    const bodyOverflow = document.body.style.overflow;
    restoreScroll = () => {
      document.documentElement.style.overflow = rootOverflow;
      document.body.style.overflow = bodyOverflow;
    };
    dialog.showModal();
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    loadCount();
    return true;
  }

  window.NEONAllSamples = { confirm };
  function confirmLink(event) {
    if (event.defaultPrevented || (event.type === 'auxclick' && event.button !== 1)) return;
    const anchor = event.target.closest('#biorepo-catalog-filters a.catalog-record-button[href]');
    if (!anchor) return;
    const newTab = event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1;
    if (confirm(anchor.href, { target: newTab ? '_blank' : anchor.target || '_self' })) event.preventDefault();
  }
  document.addEventListener('click', confirmLink);
  document.addEventListener('auxclick', confirmLink);
})();
