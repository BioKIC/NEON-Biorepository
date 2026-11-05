(function () {
  'use strict';

  var iframe = null;
  var timeoutId = null;
  var messageHandler = null;
  var active = false;

  function cleanup() {
    if (timeoutId !== null) {
      window.clearTimeout(timeoutId);
      timeoutId = null;
    }
    if (messageHandler) {
      window.removeEventListener('message', messageHandler);
      messageHandler = null;
    }
    if (iframe && iframe.parentNode) {
      iframe.parentNode.removeChild(iframe);
    }
    iframe = null;
    active = false;
  }

  function start() {
    var syncUrl = window.NEON_BIOREPO_AUTH_SYNC_URL;
    if (active || !syncUrl || !document.body) return;

    active = true;
    iframe = document.createElement('iframe');
    iframe.title = 'Authentication session check';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.setAttribute('tabindex', '-1');
    iframe.style.display = 'none';

    messageHandler = function (event) {
      if (!iframe || event.origin !== window.location.origin || event.source !== iframe.contentWindow) return;
      if (!event.data || event.data.type !== 'neon-biorepo-auth-sync') return;

      var changed = event.data.changed === true;
      var detail = event.data;
      cleanup();
      window.dispatchEvent(new CustomEvent('biorepo:auth-sync-complete', { detail: detail }));

      if (changed) {
        window.location.reload();
      }
    };

    window.addEventListener('message', messageHandler);
    iframe.src = syncUrl + (syncUrl.indexOf('?') === -1 ? '?' : '&')
      + 'refurl=' + encodeURIComponent(window.location.href);
    document.body.appendChild(iframe);

    timeoutId = window.setTimeout(cleanup, 20000);
  }

  window.cancelBiorepoAuthSync = cleanup;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
}());
