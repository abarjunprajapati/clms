(function () {
  if (window.CLMS_SWAL_BRIDGE_READY) return;
  window.CLMS_SWAL_BRIDGE_READY = true;

  const originalAlert = window.alert ? window.alert.bind(window) : function () {};
  const originalConfirm = window.confirm ? window.confirm.bind(window) : function () { return false; };
  const swalUrl = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';

  function loadSweetAlert() {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      return Promise.resolve(window.Swal);
    }
    if (window.CLMS_SWAL_LOADING) {
      return window.CLMS_SWAL_LOADING;
    }
    window.CLMS_SWAL_LOADING = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = swalUrl;
      script.async = true;
      script.onload = () => resolve(window.Swal);
      script.onerror = reject;
      document.head.appendChild(script);
    });
    return window.CLMS_SWAL_LOADING;
  }

  function normalizeMessage(message) {
    if (message === undefined || message === null) return '';
    if (typeof message === 'string') return message;
    try {
      return JSON.stringify(message, null, 2);
    } catch (e) {
      return String(message);
    }
  }

  function inferIcon(message) {
    const text = normalizeMessage(message).toLowerCase();
    if (text.includes('success') || text.includes('successfully') || text.includes('approved') || text.includes('saved') || text.includes('generated') || text.includes('issued') || text.includes('✅')) return 'success';
    if (text.includes('warning') || text.includes('mandatory') || text.includes('required') || text.includes('⚠')) return 'warning';
    if (text.includes('error') || text.includes('failed') || text.includes('invalid') || text.includes('network') || text.includes('denied') || text.includes('❌')) return 'error';
    return 'info';
  }

  window.clmsSweetAlert = function (message, options) {
    const text = normalizeMessage(message);
    const config = Object.assign({
      title: options && options.title ? options.title : 'CLMS',
      text: text,
      icon: options && options.icon ? options.icon : inferIcon(text),
      confirmButtonText: 'OK',
      confirmButtonColor: '#2563eb',
      width: 460
    }, options || {});

    return loadSweetAlert()
      .then(() => window.Swal.fire(config))
      .catch(() => originalAlert(text));
  };

  window.alert = function (message) {
    window.clmsSweetAlert(message);
  };

  window.clmsConfirm = function (message, options) {
    const text = normalizeMessage(message);
    const config = Object.assign({
      title: options && options.title ? options.title : 'Confirm Action',
      text: text,
      icon: options && options.icon ? options.icon : 'question',
      showCancelButton: true,
      confirmButtonText: options && options.confirmButtonText ? options.confirmButtonText : 'Yes, Continue',
      cancelButtonText: options && options.cancelButtonText ? options.cancelButtonText : 'Cancel',
      confirmButtonColor: options && options.confirmButtonColor ? options.confirmButtonColor : '#2563eb',
      cancelButtonColor: '#64748b',
      width: 480
    }, options || {});

    return loadSweetAlert()
      .then(() => window.Swal.fire(config))
      .then((result) => !!result.isConfirmed)
      .catch(() => originalConfirm(text));
  };

  window.clmsNativeConfirm = originalConfirm;
})();
