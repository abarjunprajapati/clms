/**
 * CLMS Enterprise Authentication - UI and Interactive Enhancements
 * Coordinates Caps Lock banners, password strength gauges, submit loaders, and CSRF AJAX handshakes.
 */

window.AuthUI = {
  /**
   * Binds Caps Lock detection to password inputs
   * @param {HTMLInputElement} inputElement
   * @param {string} warningElementId - ID of warning capsule to show/hide
   */
  bindCapsLockDetector: function(inputElement, warningElementId) {
    if (!inputElement) return;

    const warning = document.getElementById(warningElementId);
    if (!warning) return;

    const checkCapsLock = function(e) {
      if (e.getModifierState && e.getModifierState('CapsLock')) {
        warning.style.display = 'flex';
      } else {
        warning.style.display = 'none';
      }
    };

    inputElement.addEventListener('keydown', checkCapsLock);
    inputElement.addEventListener('keyup', checkCapsLock);
    inputElement.addEventListener('click', checkCapsLock);
    inputElement.addEventListener('focus', checkCapsLock);
    inputElement.addEventListener('blur', () => {
      warning.style.display = 'none';
    });
  },

  /**
   * Binds password strength monitoring to strength meter bars
   * @param {HTMLInputElement} inputElement
   * @param {string} meterContainerId - ID of strength gauge container
   */
  bindPasswordStrength: function(inputElement, meterContainerId) {
    if (!inputElement) return;

    const meter = document.getElementById(meterContainerId);
    if (!meter) return;

    inputElement.addEventListener('input', function() {
      const val = this.value;
      
      meter.className = 'pwd-strength-container'; // Reset
      if (val.length === 0) return;

      let score = 0;
      if (val.length >= 6) score++; // Length check
      if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++; // Mix case
      if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score++; // Mixed digits/symbols

      if (val.length < 6) {
        meter.classList.add('strength-weak');
      } else if (score <= 1) {
        meter.classList.add('strength-weak');
      } else if (score === 2) {
        meter.classList.add('strength-medium');
      } else if (score >= 3) {
        meter.classList.add('strength-strong');
      }
    });
  },

  /**
   * Controls submit loading buttons and spinner states
   * @param {HTMLButtonElement|HTMLElement} buttonElement
   * @param {boolean} isLoading
   * @param {string} [loadingText] - Text to show during load state
   * @param {string} [defaultText] - Text to restore on idle state
   */
  setButtonLoading: function(buttonElement, isLoading, loadingText = 'PROCESSING...', defaultText = 'CONTINUE') {
    if (!buttonElement) return;

    if (isLoading) {
      buttonElement.classList.add('submitting');
      buttonElement.disabled = true;

      // Ensure a spinner icon exists inside the button
      let spinner = buttonElement.querySelector('.spinner-icon');
      if (!spinner) {
        spinner = document.createElement('i');
        spinner.className = 'fas fa-circle-notch fa-spin spinner-icon';
        buttonElement.insertBefore(spinner, buttonElement.firstChild);
      }
      
      // Update text sibling if available, or replace text nodes
      const textSpan = buttonElement.querySelector('span') || buttonElement;
      if (textSpan === buttonElement) {
        // If no child spans, replace text node cleanly
        const textNode = Array.from(buttonElement.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
        if (textNode) textNode.textContent = ' ' + loadingText;
      } else {
        textSpan.textContent = loadingText;
      }
    } else {
      buttonElement.classList.remove('submitting');
      buttonElement.disabled = false;

      const spinner = buttonElement.querySelector('.spinner-icon');
      if (spinner) spinner.remove();

      const textSpan = buttonElement.querySelector('span') || buttonElement;
      if (textSpan === buttonElement) {
        const textNode = Array.from(buttonElement.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
        if (textNode) textNode.textContent = ' ' + defaultText;
      } else {
        textSpan.textContent = defaultText;
      }
    }
  },

  /**
   * Secure AJAX wrapper featuring dynamic CSRF token loading
   * @param {string} url - API Endpoint
   * @param {object} [data] - POST Payload object
   * @returns {Promise<object>}
   */
  sendAPIRequest: async function(url, data = {}) {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    if (csrfToken) {
      headers['X-CSRF-Token'] = csrfToken;
    }

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: JSON.stringify(data)
      });

      if (response.status === 401) {
        const isAuthPage = window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/');
        const isAuthApi = url.includes('login.php') || url.includes('verify_otp.php') || url.includes('resend_otp.php');
        if (!isAuthPage && !isAuthApi) {
          const getBaseURL = () => {
            const path = window.location.pathname;
            if (path.includes('/clms/')) return '/clms/';
            const match = path.match(/^(.*\/)(pages|api|ajax|include|css|js|uploads)\//);
            if (match) return match[1];
            const parts = path.split('/');
            const idx = parts.findIndex(p => ['pages', 'api', 'ajax'].includes(p));
            if (idx > 0) return parts.slice(0, idx).join('/') + '/';
            return '/';
          };
          window.location.replace(getBaseURL() + 'index.php?session_expired=1&t=' + Date.now());
          return;
        }
      }

      const responseText = await response.text();
      let parsed;
      try {
        parsed = JSON.parse(responseText);
      } catch (parseError) {
        const cleanText = responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const friendlyMessage = cleanText.startsWith('Connection')
          ? 'Database connection failed. Please contact administrator.'
          : (cleanText || `Server responded with ${response.status}`);
        throw new Error(friendlyMessage);
      }

      if (!response.ok || parsed.success === false || parsed.status === 'error') {
        throw new Error(parsed.message || parsed.error || `Server responded with ${response.status}`);
      }

      return parsed;
    } catch (e) {
      console.error(`[AuthUI.sendAPIRequest] failed on ${url}:`, e);
      throw e;
    }
  },

  /**
   * Helper to format email address into masked layout
   * @param {string} email
   * @returns {string}
   */
  maskEmail: function(email) {
    if (!email || email === 'N/A' || !email.includes('@')) return 'N/A';
    const parts = email.split('@');
    const name = parts[0];
    const domain = parts[1];
    
    if (name.length <= 2) return `**@${domain}`;
    return `${name.substring(0, 2)}****@${domain}`;
  },

  /**
   * Helper to format phone numbers into masked layout
   * @param {string} phone
   * @returns {string}
   */
  maskPhone: function(phone) {
    if (!phone || phone === 'N/A') return 'N/A';
    const raw = phone.trim();
    if (raw.length <= 4) return '******';
    return `${raw.substring(0, 2)}******${raw.substring(raw.length - 2)}`;
  }
};
