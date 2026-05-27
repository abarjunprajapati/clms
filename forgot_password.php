<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - CLMS</title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="css/auth_redesign.css" />
  <link rel="stylesheet" href="css/auth_components.css" />
  <link rel="stylesheet" href="css/auth_responsive.css" />
</head>
<body>

<div class="auth-split-wrapper">
  <!-- LEFT BRAND PANEL (Desktop-only) -->
  <div class="auth-left-pane">
    <div class="left-brand-content">
      <!-- High-fidelity inline SVG workforce illustration -->
      <svg class="brand-illustration" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="250" cy="200" r="160" fill="url(#grad-circle)" opacity="0.15"/>
        <g transform="translate(180, 160) scale(0.9)" style="transform-origin: center;">
          <path d="M50 0 L58 15 A40 40 0 0 1 72 21 L87 13 L93 25 L78 33 A40 40 0 0 1 78 47 L93 55 L87 67 L72 59 A40 40 0 0 1 58 65 L50 80 L38 80 L30 65 A40 40 0 0 1 16 59 L1 67 L-5 55 L10 47 A40 40 0 0 1 10 33 L-5 25 L1 13 L16 21 A40 40 0 0 1 30 15 L38 0 Z" fill="#60a5fa" opacity="0.8"/>
          <circle cx="44" cy="40" r="18" fill="#1e3a8a"/>
        </g>
        <g transform="translate(290, 220) scale(0.65)" style="transform-origin: center;">
          <path d="M50 0 L58 15 A40 40 0 0 1 72 21 L87 13 L93 25 L78 33 A40 40 0 0 1 78 47 L93 55 L87 67 L72 59 A40 40 0 0 1 58 65 L50 80 L38 80 L30 65 A40 40 0 0 1 16 59 L1 67 L-5 55 L10 47 A40 40 0 0 1 10 33 L-5 25 L1 13 L16 21 A40 40 0 0 1 30 15 L38 0 Z" fill="#93c5fd" opacity="0.6"/>
          <circle cx="44" cy="40" r="18" fill="#1d4ed8"/>
        </g>
        <line x1="200" y1="260" x2="320" y2="140" stroke="#bfdbfe" stroke-width="3" stroke-dasharray="8 6"/>
        <line x1="160" y1="130" x2="280" y2="280" stroke="#bfdbfe" stroke-width="2" opacity="0.5"/>
        <circle cx="200" cy="260" r="12" fill="#60a5fa"/>
        <circle cx="320" cy="140" r="16" fill="#3b82f6"/>
        <circle cx="160" y1="130" x2="280" y2="280" stroke="#bfdbfe" stroke-width="2" opacity="0.5"/>
        <path d="M250 120 L300 140 V200 C300 240 250 270 250 270 C250 270 200 240 200 200 V140 Z" fill="url(#grad-shield)" filter="drop-shadow(0 8px 16px rgba(0,0,0,0.15))"/>
        <path d="M245 160 H255 V195 H245 Z M245 205 H255 V215 H245 Z" fill="#ffffff"/>
        <defs>
          <linearGradient id="grad-circle" x1="90" y1="40" x2="410" y2="360" gradientUnits="userSpaceOnUse">
            <stop stop-color="#ffffff"/>
            <stop offset="1" stop-color="#bfdbfe" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="grad-shield" x1="200" y1="120" x2="300" y2="270" gradientUnits="userSpaceOnUse">
            <stop stop-color="#60a5fa"/>
            <stop offset="1" stop-color="#2563eb"/>
          </linearGradient>
        </defs>
      </svg>
      <h1>Contractor Portal</h1>
      <p>Restore secure credentials access dynamically utilizing SMS-tied and email authentication systems.</p>
      
      <div class="left-security-tagline">
        <i class="fas fa-shield-halved"></i>
        <span>Advanced Credentials Recovery Protection</span>
      </div>
    </div>
  </div>

  <!-- RIGHT FORM PANEL -->
  <div class="auth-right-pane">
    <div class="auth-card-container">
      <div class="auth-card">
        
        <!-- Header -->
        <div class="auth-card-header">
          <div class="auth-card-logo-wrapper" style="width: 60px; height: 60px; border-radius: 16px;">
            <i class="fas fa-key fa-2x" style="color: var(--primary-color);"></i>
          </div>
          <h2 class="auth-card-title" style="font-size: 1.45rem;">Forgot Password</h2>
          <p class="auth-card-subtitle">Verify Contractor Identity for Reset Code</p>
        </div>

        <!-- Centralized Error Banner -->
        <div id="forgot-error-container"></div>

        <!-- Form -->
        <form id="forgot-form" onsubmit="executeForgotPassword(event)" novalidate>
          
          <!-- Contractor ID Input -->
          <div class="form-group">
            <div class="input-wrapper">
              <input type="text" id="contractor-id" class="form-control" placeholder="Contractor ID" required autofocus autocomplete="username">
              <i class="fas fa-user-shield input-icon"></i>
              <label class="form-label" for="contractor-id">Contractor ID</label>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary" id="btn-submit">
            <span>SEND RESET OTP</span>
            <i class="fas fa-paper-plane"></i>
          </button>

          <!-- Divider & Back link -->
          <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <a href="index.php" style="color: var(--text-muted); font-size: 0.88rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition-smooth);" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">
              <i class="fas fa-arrow-left"></i> Return to Login Page
            </a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS FOUNDATION -->
<script src="js/utils.js"></script>
<script src="js/validation.js"></script>
<script src="js/auth_ui.js"></script>

<script>
async function executeForgotPassword(e) {
    e.preventDefault();
    const form = document.getElementById('forgot-form');
    const input = document.getElementById('contractor-id');
    const btn = document.getElementById('btn-submit');

    ValidationHandler.clearFormError(form);
    ValidationHandler.clearFieldState(input);

    const contractorId = input.value.trim();
    if (!contractorId) {
        ValidationHandler.setFieldState(input, 'error', 'Contractor ID is required');
        ValidationHandler.showFormError(form, 'Please enter your registered Contractor ID.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'SENDING CODE...');

    try {
        const result = await AuthUI.sendAPIRequest('api/forgot_password.php', {
            contractor_id: contractorId
        });

        if (result.success) {
            // Show dynamic success state card
            let successCard = form.querySelector('.status-card-success');
            if (!successCard) {
                successCard = document.createElement('div');
                successCard.className = 'status-card status-card-success';
                const otpDebug = result.data?.otp_debug ? `<br><strong>Debug OTP: ${result.data.otp_debug}</strong>` : '';
                successCard.innerHTML = `
                    <i class="fas fa-circle-check"></i>
                    <div>
                       <strong>OTP Code Transmitted!</strong><br>
                       Loading Verification Module...${otpDebug}
                    </div>
                `;
                form.insertBefore(successCard, form.firstChild);
            }
            
            setTimeout(() => {
                window.location.href = `reset_password.php?contractor_id=${contractorId}`;
            }, 2500);

        } else {
            // Check if error is User Not Found to show modern warning card with shake
            if (result.message.includes('not found') || result.message.includes('Invalid') || result.message.includes('does not exist')) {
                ValidationHandler.setFieldState(input, 'error');
                let warningCard = form.querySelector('.status-card-warning');
                if (!warningCard) {
                    warningCard = document.createElement('div');
                    warningCard.className = 'status-card status-card-warning error-shake';
                    warningCard.innerHTML = `
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                           <strong>Identity Not Found</strong><br>
                           The specified Contractor ID is not registered in our master records.
                        </div>
                    `;
                    form.insertBefore(warningCard, form.firstChild);
                }
            } else {
                ValidationHandler.showFormError(form, result.message || 'Verification initialization failed.');
            }
        }
    } catch (err) {
        console.error('Forgot password submission fail:', err);
        ValidationHandler.showFormError(form, err.message || 'API connection failed. Please retry.');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'SEND RESET OTP');
    }
}
</script>
</body>
</html>
