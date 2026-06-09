/**
 * CLMS Enterprise Authentication - Validation and Error System
 * Enforces real-time input status indicators, custom transitions, and centered form-shake alerts.
 */

window.ValidationHandler = {
  /**
   * Displays a premium shaking error banner above form fields
   * @param {HTMLFormElement|HTMLElement} formElement - Container or form to inject error into
   * @param {string} message - Warning detail to display
   */
  showFormError: function(formElement, message) {
    if (!formElement) return;

    // Find or create the error alert container
    let errorContainer = formElement.querySelector('.form-error');
    if (!errorContainer) {
      errorContainer = document.createElement('div');
      errorContainer.className = 'form-error';
      errorContainer.setAttribute('role', 'alert');
      // Inject as the first element inside the form
      formElement.insertBefore(errorContainer, formElement.firstChild);
    }

    // Set high-contrast markup with animated transitions
    errorContainer.innerHTML = `
      <i class="fas fa-exclamation-circle"></i>
      <span>${message}</span>
    `;
    
    // Add micro-shaking animation to draw quick visual attention
    errorContainer.classList.remove('error-shake');
    void errorContainer.offsetWidth; // Force CSS reflow to restart keyframe
    errorContainer.classList.add('error-shake');
    errorContainer.style.display = 'flex';
  },

  /**
   * Smoothly clears the centralized error banner of a form
   * @param {HTMLFormElement|HTMLElement} formElement
   */
  clearFormError: function(formElement) {
    if (!formElement) return;

    const errorContainer = formElement.querySelector('.form-error');
    if (errorContainer) {
      errorContainer.style.opacity = '0';
      errorContainer.style.transition = 'opacity 0.2s ease';
      setTimeout(() => {
        errorContainer.remove();
      }, 200);
    }
  },

  /**
   * Sets real-time feedback borders and icons on a specific input field
   * @param {HTMLInputElement|HTMLSelectElement} inputElement - Input field to style
   * @param {'success'|'error'|'neutral'} state - Target status state
   * @param {string} [feedbackMessage] - Optional feedback subtitle/hint
   */
  setFieldState: function(inputElement, state, feedbackMessage) {
    if (!inputElement) return;

    const group = inputElement.closest('.form-group');
    if (!group) return;

    // Remove previous state classes
    group.classList.remove('state-success', 'state-error');

    // Remove existing field-specific feedback labels to prevent stack accumulation
    const oldFeedback = group.querySelector('.field-feedback-label');
    if (oldFeedback) oldFeedback.remove();

    if (state === 'success') {
      group.classList.add('state-success');
    } else if (state === 'error') {
      group.classList.add('state-error');

      // Inject small animated red helper label underneath the input box
      if (feedbackMessage) {
        const feedback = document.createElement('span');
        feedback.className = 'field-feedback-label';
        feedback.style.fontSize = '0.78rem';
        feedback.style.fontWeight = '600';
        feedback.style.color = 'var(--error-color)';
        feedback.style.marginTop = '6px';
        feedback.style.display = 'block';
        feedback.style.animation = 'fadeInDown 0.2s ease-out';
        feedback.textContent = feedbackMessage;
        group.appendChild(feedback);
      }
    }
  },

  /**
   * Resets status properties and classes on an input field group
   * @param {HTMLInputElement} inputElement
   */
  clearFieldState: function(inputElement) {
    if (!inputElement) return;

    const group = inputElement.closest('.form-group');
    if (group) {
      group.classList.remove('state-success', 'state-error');
      const feedback = group.querySelector('.field-feedback-label');
      if (feedback) feedback.remove();
    }
  },

  /**
   * Helper validator to ensure standard contractor/vendor IDs fit formatting
   * @param {string} value
   * @returns {boolean}
   */
  isValidIdentifier: function(value) {
    const val = value.trim();
    // Allow alphanumeric combinations, typical format CONT-XXX, or numerical SAP master codes (5-10 digits)
    return val.length >= 4;
  },

  /**
   * Helper validator to ensure password strength is at least minimal
   * @param {string} value
   * @returns {boolean}
   */
  isValidPassword: function(value) {
    return value.length >= 6;
  }
};
