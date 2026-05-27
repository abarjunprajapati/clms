<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - CLMS</title>
  
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
        <circle cx="160" cy="130" r="10" fill="#93c5fd"/>
        <circle cx="280" cy="280" r="14" fill="#60a5fa"/>
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
      <p>Configure your new authentication credentials securely under multi-step recovery tokens.</p>
      
      <div class="left-security-tagline">
        <i class="fas fa-shield-halved"></i>
        <span>Advanced Multi-Token Authentication Enforced</span>
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
            <i class="fas fa-lock-open fa-2x" style="color: var(--primary-color);"></i>
          </div>
          <h2 class="auth-card-title" style="font-size: 1.45rem;">Reset Password</h2>
          <p class="auth-card-subtitle">Define New Authentication Credentials</p>
        </div>

        <!-- Centralized Error Banner -->
        <div id="reset-error-container"></div>

        <!-- Form -->
        <form id="reset-form" onsubmit="executeResetPassword(event)" novalidate>
          
          <!-- OTP Verification Boxes -->
          <div class="form-group" style="text-align: center; margin-bottom: 25px;">
            <label class="form-label" style="position:static; transform:none; pointer-events:auto; font-weight:700; font-size:0.82rem; color:var(--text-primary); text-transform:uppercase; margin-bottom:10px; display:block;">Enter 6-digit Reset OTP</label>
            <div class="otp-container" id="reset-otp-wrapper">
              <input type="text" class="otp-box" id="rotp0" maxlength="1">
              <input type="text" class="otp-box" id="rotp1" maxlength="1">
              <input type="text" class="otp-box" id="rotp2" maxlength="1">
              <input type="text" class="otp-box" id="rotp3" maxlength="1">
              <input type="text" class="otp-box" id="rotp4" maxlength="1">
              <input type="text" class="otp-box" id="rotp5" maxlength="1">
            </div>
          </div>

          <!-- New Password Input -->
          <div class="form-group">
            <div id="caps-warning-r1" class="caps-warning">
              <i class="fas fa-keyboard"></i> Caps Lock is Active
            </div>
            <div class="input-wrapper">
              <input type="password" id="new-password" class="form-control" placeholder="New Password" required autocomplete="new-password">
              <i class="fas fa-lock input-icon"></i>
              <i class="fas fa-eye toggle-pwd" id="toggle-r1" onclick="togglePwdVisibility('new-password', 'toggle-r1')"></i>
              <label class="form-label" for="new-password">New Password</label>
            </div>
            <!-- Strength meter bar -->
            <div id="pwd-strength-reset" class="pwd-strength-container">
              <div class="pwd-strength-bar"></div>
              <div class="pwd-strength-bar"></div>
              <div class="pwd-strength-bar"></div>
            </div>
          </div>

          <!-- Confirm Password Input -->
          <div class="form-group">
            <div id="caps-warning-r2" class="caps-warning">
              <i class="fas fa-keyboard"></i> Caps Lock is Active
            </div>
            <div class="input-wrapper">
              <input type="password" id="confirm-password" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
              <i class="fas fa-lock input-icon"></i>
              <i class="fas fa-eye toggle-pwd" id="toggle-r2" onclick="togglePwdVisibility('confirm-password', 'toggle-r2')"></i>
              <label class="form-label" for="confirm-password">Confirm Password</label>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary" id="btn-submit">
            <span>RESET PASSWORD</span>
            <i class="fas fa-key"></i>
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
<script src="js/otp_handler.js"></script>
<script src="js/validation.js"></script>
<script src="js/auth_ui.js"></script>

<script>
// Get URL parameters
const urlParams = new URLSearchParams(window.location.search);
const contractorId = urlParams.get('contractor_id');
const form = document.getElementById('reset-form');

if (!contractorId) {
    ValidationHandler.showFormError(form, 'Invalid reset link. Please request a new password reset.');
    form.style.pointerEvents = 'none';
    form.style.opacity = '0.5';
} else {
    // Initialize modular OTP box controls
    OTPHandler.init('reset-otp-wrapper');
}

// Bind CAPS LOCK warning indicator and strength meter
AuthUI.bindCapsLockDetector(document.getElementById('new-password'), 'caps-warning-r1');
AuthUI.bindCapsLockDetector(document.getElementById('confirm-password'), 'caps-warning-r2');
AuthUI.bindPasswordStrength(document.getElementById('new-password'), 'pwd-strength-reset');

// Toggle Password visibility helper
function togglePwdVisibility(inputId, toggleIconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(toggleIconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

async function executeResetPassword(e) {
    e.preventDefault();
    const otpCode = OTPHandler.getOTP('reset-otp-wrapper');
    const pwdInput = document.getElementById('new-password');
    const cpwdInput = document.getElementById('confirm-password');
    const btn = document.getElementById('btn-submit');

    ValidationHandler.clearFormError(form);
    ValidationHandler.clearFieldState(pwdInput);
    ValidationHandler.clearFieldState(cpwdInput);

    const newPassword = pwdInput.value;
    const confirmPassword = cpwdInput.value;

    // Check states
    if (otpCode.length !== 6) {
        ValidationHandler.showFormError(form, 'Please enter the complete 6-digit OTP reset code.');
        return;
    }
    if (!newPassword) {
        ValidationHandler.setFieldState(pwdInput, 'error', 'Password is required');
        ValidationHandler.showFormError(form, 'Please enter your new password.');
        return;
    }
    if (newPassword.length < 6) {
        ValidationHandler.setFieldState(pwdInput, 'error', 'Must be at least 6 characters');
        ValidationHandler.showFormError(form, 'Password must be at least 6 characters long.');
        return;
    }
    if (newPassword !== confirmPassword) {
        ValidationHandler.setFieldState(cpwdInput, 'error', 'Passwords do not match');
        ValidationHandler.showFormError(form, 'Passwords do not match. Please verify your input.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'SAVING NEW CREDENTIALS...');

    try {
        const result = await AuthUI.sendAPIRequest('api/reset_password.php', {
            contractor_id: contractorId,
            otp: otpCode,
            new_password: newPassword
        });

        if (result.success) {
            // Show success status card
            let successCard = form.querySelector('.status-card-success');
            if (!successCard) {
                successCard = document.createElement('div');
                successCard.className = 'status-card status-card-success';
                successCard.innerHTML = `
                    <i class="fas fa-circle-check"></i>
                    <div>
                       <strong>Password Restored Successfully!</strong><br>
                       Redirecting to Login Page...
                    </div>
                `;
                form.insertBefore(successCard, form.firstChild);
            }
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        } else {
            ValidationHandler.showFormError(form, result.message || 'Failed to reset password.');
            OTPHandler.clearOTP('reset-otp-wrapper');
        }
    } catch (err) {
        console.error('Password reset submit fail:', err);
        ValidationHandler.showFormError(form, err.message || 'API connection failed. Please retry.');
        OTPHandler.clearOTP('reset-otp-wrapper');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'RESET PASSWORD');
    }
}
</script>
</body>
</html>
