/**
 * CLMS Enterprise Authentication - OTP Component Handler
 * Provides arrow navigation, backspace focus shifting, paste handlers, and resend countdowns.
 */

window.OTPHandler = {
  /**
   * Initializes OTP input behaviors for a container of inputs
   * @param {string} containerId - Element ID containing the 6 otp boxes
   * @param {Function} onComplete - Callback executed when all 6 digits are entered
   */
  init: function(containerId, onComplete) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const inputs = container.querySelectorAll('.otp-box');
    
    inputs.forEach((input, index) => {
      // Clear inputs initially
      input.value = '';

      // Force mobile keypad
      input.setAttribute('inputmode', 'numeric');
      input.setAttribute('autocomplete', 'one-time-code');
      input.setAttribute('pattern', '[0-9]*');

      // 1. Keyup navigation (Forward navigation on digit entry)
      input.addEventListener('input', function(e) {
        const val = this.value;
        
        // Remove non-numeric inputs
        this.value = val.replace(/[^0-9]/g, '');

        if (this.value.length >= 1) {
          // Keep only first digit if multiple entered (fallback)
          this.value = this.value.charAt(0);
          
          // Move forward
          if (index < inputs.length - 1) {
            inputs[index + 1].focus();
            inputs[index + 1].select();
          } else {
            // Last input filled - check if all are filled
            const fullCode = OTPHandler.getOTP(containerId);
            if (fullCode.length === 6 && typeof onComplete === 'function') {
              onComplete(fullCode);
            }
          }
        }
      });

      // 2. Keydown handling (Backspace focus back-shifting & Arrow navigation)
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' || e.key === 'Delete') {
          if (this.value === '') {
            // If empty, focus previous
            if (index > 0) {
              inputs[index - 1].focus();
              inputs[index - 1].value = '';
              e.preventDefault();
            }
          } else {
            // If filled, clear current
            this.value = '';
            e.preventDefault();
          }
        } else if (e.key === 'ArrowLeft') {
          if (index > 0) {
            inputs[index - 1].focus();
            inputs[index - 1].select();
            e.preventDefault();
          }
        } else if (e.key === 'ArrowRight') {
          if (index < inputs.length - 1) {
            inputs[index + 1].focus();
            inputs[index + 1].select();
            e.preventDefault();
          }
        }
      });

      // 3. Selection helper on focus
      input.addEventListener('focus', function() {
        this.select();
      });

      // 4. Clipboard paste interceptor
      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const clipboard = (e.clipboardData || window.clipboardData).getData('text');
        const digits = clipboard.trim().replace(/[^0-9]/g, '');

        if (digits.length >= 6) {
          // Fill all boxes starting from index 0
          inputs.forEach((box, i) => {
            box.value = digits.charAt(i) || '';
          });
          
          // Focus the last input box
          inputs[5].focus();
          
          // Trigger complete callback
          const fullCode = digits.substring(0, 6);
          if (typeof onComplete === 'function') {
            onComplete(fullCode);
          }
        } else {
          if (typeof showToast === 'function') {
            showToast('⚠️', 'Please paste a valid 6-digit OTP code.');
          }
        }
      });
    });

    // Automatically focus the first input box
    setTimeout(() => {
      if (inputs[0]) {
        inputs[0].focus();
      }
    }, 200);
  },

  /**
   * Retrieves the combined 6-digit OTP string
   * @param {string} containerId - Element ID containing the 6 otp boxes
   * @returns {string}
   */
  getOTP: function(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return '';

    const inputs = container.querySelectorAll('.otp-box');
    let otp = '';
    inputs.forEach(input => {
      otp += input.value.trim();
    });
    return otp;
  },

  /**
   * Clears all OTP inputs in a container and resets focus
   * @param {string} containerId - Element ID containing the 6 otp boxes
   */
  clearOTP: function(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const inputs = container.querySelectorAll('.otp-box');
    inputs.forEach(input => {
      input.value = '';
    });
    if (inputs[0]) inputs[0].focus();
  },

  /**
   * Starts a visual cooldown timer for the Resend OTP button/link
   * @param {string} linkId - Element ID of the Resend anchor/link
   * @param {number} seconds - Cooldown duration in seconds
   * @param {Function} onResendClick - Callback to trigger when clicked after cooldown
   */
  startResendTimer: function(linkId, seconds, onResendClick) {
    const link = document.getElementById(linkId);
    if (!link) return;

    // Clone element to clear previous event listeners cleanly
    const newLink = link.cloneNode(true);
    link.parentNode.replaceChild(newLink, link);

    let timeLeft = seconds;
    newLink.classList.add('disabled');
    newLink.style.pointerEvents = 'none';
    newLink.innerHTML = `Resend OTP in ${timeLeft}s`;

    const interval = setInterval(() => {
      timeLeft--;
      if (timeLeft <= 0) {
        clearInterval(interval);
        newLink.classList.remove('disabled');
        newLink.style.pointerEvents = 'auto';
        newLink.innerHTML = 'Resend OTP';
        newLink.addEventListener('click', function(e) {
          e.preventDefault();
          if (typeof onResendClick === 'function') {
            onResendClick();
          }
        });
      } else {
        newLink.innerHTML = `Resend OTP in ${timeLeft}s`;
      }
    }, 1000);

    return interval;
  }
};
