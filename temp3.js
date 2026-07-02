  <script>
    window.CLMS_BASE_URL = "null";
    window.CLMS_CSRF_TOKEN = "null";
    (function() {
      if (!window.fetch || window.__clmsFetchSecured) return;
      const nativeFetch = window.fetch.bind(window);
      window.fetch = function(input, init) {
        init = init || {};
        const method = String(init.method || 'GET').toUpperCase();
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        const sameOrigin = !/^https?:\/\//i.test(url) || url.indexOf(window.location.origin) === 0;
        if (sameOrigin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
          const headers = new Headers(init.headers || {});
          if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CLMS_CSRF_TOKEN || '');
          init.headers = headers;
          init.credentials = init.credentials || 'same-origin';
        }
        return nativeFetch(input, init);
      };
      window.__clmsFetchSecured = true;
    })();
  </script>
