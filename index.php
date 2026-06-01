<?php 
$loginScope = $loginScope ?? 'external';
$isInternalLogin = $loginScope === 'internal';
require_once __DIR__ . '/include/session.php'; 

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role']) && !empty($_SESSION['logged_in'])) {
    require_once __DIR__ . '/include/config.php';
    require_once __DIR__ . '/include/onboarding_status.php';
    $role = $_SESSION['role'];
    $redirect = "pages/contractor/dashboard.php";
    switch ($role) {
      case 'super_admin': $redirect = "pages/admin/dashboard.php"; break;
      case 'welfare_admin': $redirect = "pages/welfare/admin_dashboard.php"; break;
      case 'welfare_user': $redirect = "pages/welfare/dashboard.php"; break;
      case 'contractor': $redirect = "pages/contractor/dashboard.php"; break;
      case 'front_line_user': $redirect = "pages/frontline/dashboard.php"; break;
      case 'pass_user': $redirect = "pages/welfare/pass_issuer_dashboard.php"; break;
      case 'safety_user': $redirect = "pages/safety/dashboard.php"; break;
      case 'execution_officer': $redirect = "pages/execution/dashboard.php"; break;
      case 'customer': $redirect = "pages/customer/dashboard.php"; break;
    }
    $redirect = clms_onboarding_redirect_for_session($conn) ?: $redirect;
    header('Location: ' . BASE_URL . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= get_csrf_token() ?>">
  <title>CLMS Web Login - CLMS</title>
  
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
        <!-- Gears representing industry & workforce -->
        <g transform="translate(180, 160) scale(0.9)" style="transform-origin: center;">
          <path d="M50 0 L58 15 A40 40 0 0 1 72 21 L87 13 L93 25 L78 33 A40 40 0 0 1 78 47 L93 55 L87 67 L72 59 A40 40 0 0 1 58 65 L50 80 L38 80 L30 65 A40 40 0 0 1 16 59 L1 67 L-5 55 L10 47 A40 40 0 0 1 10 33 L-5 25 L1 13 L16 21 A40 40 0 0 1 30 15 L38 0 Z" fill="#60a5fa" opacity="0.8"/>
          <circle cx="44" cy="40" r="18" fill="#1e3a8a"/>
        </g>
        <g transform="translate(290, 220) scale(0.65)" style="transform-origin: center;">
          <path d="M50 0 L58 15 A40 40 0 0 1 72 21 L87 13 L93 25 L78 33 A40 40 0 0 1 78 47 L93 55 L87 67 L72 59 A40 40 0 0 1 58 65 L50 80 L38 80 L30 65 A40 40 0 0 1 16 59 L1 67 L-5 55 L10 47 A40 40 0 0 1 10 33 L-5 25 L1 13 L16 21 A40 40 0 0 1 30 15 L38 0 Z" fill="#93c5fd" opacity="0.6"/>
          <circle cx="44" cy="40" r="18" fill="#1d4ed8"/>
        </g>
        <!-- Connected Network Nodes -->
        <line x1="200" y1="260" x2="320" y2="140" stroke="#bfdbfe" stroke-width="3" stroke-dasharray="8 6"/>
        <line x1="160" y1="130" x2="280" y2="280" stroke="#bfdbfe" stroke-width="2" opacity="0.5"/>
        <circle cx="200" cy="260" r="12" fill="#60a5fa"/>
        <circle cx="320" cy="140" r="16" fill="#3b82f6"/>
        <circle cx="160" cy="130" r="10" fill="#93c5fd"/>
        <circle cx="280" cy="280" r="14" fill="#60a5fa"/>
        <!-- Shield of integrity -->
        <path d="M250 120 L300 140 V200 C300 240 250 270 250 270 C250 270 200 240 200 200 V140 Z" fill="url(#grad-shield)" filter="drop-shadow(0 8px 16px rgba(0,0,0,0.15))"/>
        <path d="M245 160 H255 V195 H245 Z M245 205 H255 V215 H245 Z" fill="#ffffff"/>
        <!-- Gradients Definitions -->
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
      <h1>Contract Labour<br>Management System</h1>
      <p>Secure, enterprise-grade contractor workforce compliance and gate pass orchestration portal.</p>
      
      <div class="left-security-tagline">
        <i class="fas fa-shield-halved"></i>
        <span>ISO 27001 Certified Security Infrastructure</span>
      </div>
    </div>
  </div>

  <!-- RIGHT FORM PANEL -->
  <div class="auth-right-pane">
    <div class="auth-card-container">
      <div class="auth-card">
        
        <!-- Header -->
        <div class="auth-card-header">
          <div class="auth-card-logo-wrapper">
            <img src="uploads/logo/logo.png" alt="Logo" onerror="this.outerHTML='<i class=\'fas fa-building fa-2x\' style=\'color: var(--primary-color);\'></i>'">
          </div>
          <h2 class="auth-card-title">CLMS Web</h2>
          <p class="auth-card-subtitle">Contract Labour Management System</p>
        </div>

        <!-- Inline Error Banner -->
        <div id="login-error-container"></div>

        <!-- Form -->
        <form id="login-form" onsubmit="executeLogin(event)" novalidate>
          
          <!-- User ID Input -->
          <div class="form-group">
            <div class="input-wrapper">
              <input type="text" id="login-user" class="form-control" placeholder="<?= $isInternalLogin ? 'STAFF USER ID' : 'CONTRACTOR / CUSTOMER CODE' ?>" required autocomplete="username">
              <i class="fas fa-user-shield input-icon"></i>
              <label class="form-label" for="login-user"><?= $isInternalLogin ? 'STAFF USER ID' : 'CONTRACTOR / CUSTOMER CODE' ?></label>
            </div>
          </div>

          <!-- Password Input -->
          <div class="form-group">
            <!-- Caps Lock warning capsule -->
            <div id="caps-warning-login" class="caps-warning">
              <i class="fas fa-keyboard"></i> Caps Lock is Active
            </div>
            <div class="input-wrapper">
              <input type="password" id="login-pass" class="form-control" placeholder="Password" required autocomplete="current-password">
              <i class="fas fa-lock input-icon"></i>
              <i class="fas fa-eye toggle-pwd" id="toggle-password" onclick="togglePwdVisibility('login-pass', 'toggle-password')"></i>
              <label class="form-label" for="login-pass">Password</label>
            </div>
          </div>

          <!-- Security Captcha Input -->
          <div class="form-group">
            <div class="captcha-row">
              <div style="position:relative; width:100%;">
                <input type="text" id="login-captcha" class="form-control" placeholder="Security Code" required maxlength="6" style="padding-left:16px;">
                <label class="form-label" for="login-captcha" style="left:12px;">Security Code</label>
              </div>
              <div class="captcha-img-container" onclick="refreshCaptcha()" title="Click to refresh security code">
                <img src="api/captcha.php" id="captcha-img" alt="CAPTCHA Code">
              </div>
              <button type="button" class="btn-captcha-refresh" onclick="refreshCaptcha()" title="Refresh Code">
                <i class="fas fa-rotate" id="refresh-icon"></i>
              </button>
            </div>
          </div>

          <!-- Forgot password link -->
          <div style="text-align: right; margin-bottom: 25px; margin-top: -10px;">
            <a href="forgot_password.php" style="color: var(--primary-color); font-size: 0.88rem; font-weight: 700; text-decoration: none; transition: var(--transition-smooth);" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Forgot Password?</a>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary" id="login-btn">
            <span>SIGN IN TO CLMS</span>
          </button>

          <div style="text-align: center; margin-top: 30px; padding-top: 25px; border-top: 1px solid var(--border-color);">
            <?php if ($isInternalLogin): ?>
            <?php else: ?>
              <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 15px; font-weight: 600;">“New User? Register here/Activate”</p>
              <a href="activate.php" class="btn btn-outline">
                <i class="fas fa-user-plus"></i> ACTIVATE ACCOUNT
              </a>
            <?php endif; ?>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<!-- PREMIUM OTP VERIFICATION MODAL -->
<div class="modal-overlay" id="modal-otp">
  <div class="auth-card" style="width: 100%; max-width: 420px; border-radius: var(--border-radius-card); border: 1px solid var(--card-border);">
    <div style="text-align:center; margin-bottom:25px;">
      <div style="width:68px; height:68px; background:rgba(37,99,235,0.06); border-radius:22px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <i class="fas fa-shield-halved fa-2x" style="color:var(--primary-color);"></i>
      </div>
      <h3 class="auth-card-title" style="font-size:1.4rem;">Enter OTP</h3>
      <p class="auth-card-subtitle" style="font-size:0.85rem; line-height:1.4;">An OTP verification code was dispatched to your enterprise registered mobile and email.</p>
    </div>

    <!-- OTP Input Row -->
    <div class="otp-container" id="login-otp-box-wrapper">
      <input type="text" class="otp-box" id="otp0" maxlength="1">
      <input type="text" class="otp-box" id="otp1" maxlength="1">
      <input type="text" class="otp-box" id="otp2" maxlength="1">
      <input type="text" class="otp-box" id="otp3" maxlength="1">
      <input type="text" class="otp-box" id="otp4" maxlength="1">
      <input type="text" class="otp-box" id="otp5" maxlength="1">
    </div>

    <!-- Dev Testing OTP Hint -->
    <div style="text-align: center; margin-bottom: 20px;">
      <span id="login-otp-hint" style="display:none; font-size:0.78rem; font-weight:700; background:rgba(37,99,235,0.06); color:var(--primary-color); padding:6px 12px; border-radius:8px;"></span>
    </div>

    <!-- OTP Error Container -->
    <div id="otp-error-container" style="display:none; margin: 0 0 20px; padding:10px 14px; background:#fef2f2; border:1px solid #fee2e2; border-radius:8px; color:#b91c1c; font-size:13px; font-weight:600; text-align:center;">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
        <span id="otp-error-message"></span>
    </div>

    <button type="button" class="btn btn-primary" id="btn-verify-otp" onclick="submitLoginOTP()">
      <span>VERIFY & ACCESS PORTAL</span>
    </button>
    
    <div class="otp-resend-wrapper">
      <p class="otp-resend-text">Didn't receive the credentials? <a href="#" id="resend-otp-link" class="otp-resend-link">Resend OTP</a></p>
      <a href="#" onclick="closeOtpModal()" style="display:inline-block; margin-top:15px; font-size:0.82rem; color:var(--text-muted); text-decoration:none; font-weight:600;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">Cancel and Return</a>
    </div>
  </div>
</div>

<!-- SCRIPTS FOUNDATION -->
<script src="js/sweet-alert-bridge.js"></script>
<script src="js/utils.js"></script>
<script src="js/app.js"></script>
<script src="js/navigation.js"></script>
<script src="js/otp_handler.js"></script>
<script src="js/validation.js"></script>
<script src="js/auth_ui.js"></script>

<script>
const LOGIN_SCOPE = "<?= $isInternalLogin ? 'internal' : 'external' ?>";
// Bind CAPS LOCK warn indicator
AuthUI.bindCapsLockDetector(document.getElementById('login-pass'), 'caps-warning-login');

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

// Refresh Captcha
function refreshCaptcha() {
    const img = document.getElementById('captcha-img');
    const icon = document.getElementById('refresh-icon');
    if (icon) icon.style.transform = 'rotate(360deg)';
    if (img) img.src = 'api/captcha.php?t=' + Date.now();
    setTimeout(() => { if (icon) icon.style.transform = 'rotate(0)'; }, 400);
}

// Close OTP Overlay cleanly
function closeOtpModal() {
    const modal = document.getElementById('modal-otp');
    if (modal) modal.classList.remove('show');
    // Clear pending login data safely
    sessionStorage.removeItem('pending_user_id');
}

// Login verification Ajax execution
async function executeLogin(e) {
    e.preventDefault();
    const form = document.getElementById('login-form');
    const userField = document.getElementById('login-user');
    const passField = document.getElementById('login-pass');
    const captchaField = document.getElementById('login-captcha');
    const loginBtn = document.getElementById('login-btn');

    ValidationHandler.clearFormError(form);
    ValidationHandler.clearFieldState(userField);
    ValidationHandler.clearFieldState(passField);
    ValidationHandler.clearFieldState(captchaField);

    const username = userField.value.trim();
    const password = passField.value.trim();
    const captcha = captchaField.value.trim();

    // Front-end validations
    if (!username) {
        ValidationHandler.setFieldState(userField, 'error', 'User ID is required');
        ValidationHandler.showFormError(form, 'Please enter a valid User ID.');
        return;
    }
    if (!password) {
        ValidationHandler.setFieldState(passField, 'error', 'Password is required');
        ValidationHandler.showFormError(form, 'Please enter your password.');
        return;
    }
    if (!captcha) {
        ValidationHandler.setFieldState(captchaField, 'error', 'Security Code is required');
        ValidationHandler.showFormError(form, 'Verification Captcha code is mandatory.');
        return;
    }

    AuthUI.setButtonLoading(loginBtn, true, 'AUTHENTICATING...');

    try {
        const result = await AuthUI.sendAPIRequest('api/login.php', {
            contractor_id: username,
            password: password,
            captcha: captcha,
            login_scope: LOGIN_SCOPE
        });

        if (result.success && result.data) {
            if (result.data.status === 'otp_sent') {
                sessionStorage.setItem('pending_user_id', result.data.user_id);
                
                // Show hint OTP in dev
                const otpHint = document.getElementById('login-otp-hint');
                if (otpHint && result.data.otp_demo) {
                    otpHint.textContent = `Dev OTP: ${result.data.otp_demo}`;
                    otpHint.style.display = 'inline-block';
                } else if (otpHint) {
                    otpHint.style.display = 'none';
                }

                // Show modal overlay cleanly
                const modal = document.getElementById('modal-otp');
                if (modal) modal.classList.add('show');

                // Initialize modular OTP handler on fields (no auto-submit on 6th digit)
                OTPHandler.init('login-otp-box-wrapper');

                // Start OTP resend countdown (60s)
                OTPHandler.startResendTimer('resend-otp-link', 60, executeResendOTP);

            } else if (result.data.redirect) {
                // Direct redirect flow
                const targetUrl = result.data.redirect;
                const separator = targetUrl.includes('?') ? '&' : '?';
                window.location.replace(targetUrl + separator + 't=' + Date.now());
            }
        } else {
            handleLoginFail(form, result.message || 'Authentication failed. Please verify credentials.');
        }
    } catch (err) {
        console.error('Login submit error:', err);
        handleLoginFail(form, err.message || 'API Communication error. Check server logs.');
    } finally {
        AuthUI.setButtonLoading(loginBtn, false, 'SIGN IN TO CLMS');
    }
}

// Core login failure renderer
function handleLoginFail(form, errorMsg) {
    refreshCaptcha();
    const captchaField = document.getElementById('login-captcha');
    if (captchaField) captchaField.value = '';
    const cleanError = String(errorMsg || '');
    const lowerError = cleanError.toLowerCase();
    
    // Check if vendor code / user is not registered to show beautiful warning card
    if (lowerError.includes('not found') || lowerError.includes('not activated') || lowerError.includes('activate account')) {
        let warningCard = form.querySelector('.status-card-warning');
        if (!warningCard) {            
            warningCard = document.createElement('div');            
            form.insertBefore(warningCard, form.firstChild);
        }
        warningCard.className = 'status-card status-card-warning error-shake';
        warningCard.innerHTML = `
            <i class="fas fa-circle-exclamation"></i>
            <div>
               <strong>${LOGIN_SCOPE === 'internal' ? 'Staff login failed.' : 'Account activation required.'}</strong><br>
               ${cleanError || 'Please click "Activate Account" below to initialize your credentials.'}
            </div>
        `;
    } else {
        ValidationHandler.showFormError(form, cleanError);
    }
}

// Resend OTP trigger handler
async function executeResendOTP() {
    const userId = sessionStorage.getItem('pending_user_id');
    if (!userId) return;

    try {
        const result = await AuthUI.sendAPIRequest('api/resend_otp.php', {
            user_id: parseInt(userId)
        });
        if (result.status === 'otp_sent') {
            const otpHint = document.getElementById('login-otp-hint');
            if (otpHint && result.otp) {
                otpHint.textContent = `Dev OTP: ${result.otp}`;
            }
            if (typeof showToast === 'function') {
                showToast('✅', 'A new OTP verification code was sent successfully.');
            }
            // Restart countdown
            OTPHandler.startResendTimer('resend-otp-link', 60, executeResendOTP);
        } else {
            if (typeof showToast === 'function') showToast('❌', result.message || 'OTP Resend failed.');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('❌', 'Resend failed. Check connection.');
    }
}

// Submit Verification Code inside Modal
async function submitLoginOTP(fullCode) {
    const code = fullCode || OTPHandler.getOTP('login-otp-box-wrapper');
    const userId = sessionStorage.getItem('pending_user_id');
    const verifyBtn = document.getElementById('btn-verify-otp');
    const errContainer = document.getElementById('otp-error-container');
    const errMsg = document.getElementById('otp-error-message');

    if (errContainer) errContainer.style.display = 'none';

    if (code.length !== 6) {
        if (errContainer && errMsg) {
            errMsg.textContent = 'Please input a complete 6-digit OTP verification code.';
            errContainer.style.display = 'block';
        } else if (typeof showToast === 'function') {
            showToast('⚠️', 'Please input a complete 6-digit OTP verification code.');
        }
        return;
    }
    if (!userId) {
        if (errContainer && errMsg) {
            errMsg.textContent = 'Session expired. Please restart login.';
            errContainer.style.display = 'block';
        } else if (typeof showToast === 'function') {
            showToast('❌', 'Session expired. Please restart login.');
        }
        closeOtpModal();
        return;
    }

    AuthUI.setButtonLoading(verifyBtn, true, 'VERIFYING...');

    try {
        const result = await AuthUI.sendAPIRequest('api/verify_otp.php', {
            user_id: parseInt(userId),
            otp: code
        });

        if (result.success) {
            // Setup dashboard session locally
            if (result.data && result.data.user) {
                const user = result.data.user;
                sessionStorage.setItem('user_id', user.id);
                sessionStorage.setItem('role', user.role);
                sessionStorage.setItem('name', user.name);
            }
            sessionStorage.removeItem('pending_user_id');
            
            // Clean modal close
            const modal = document.getElementById('modal-otp');
            if (modal) modal.classList.remove('show');

            if (typeof showToast === 'function') showToast('✅', 'Verification Successful! Loading Dashboard...');

            setTimeout(() => {
                const targetUrl = result.redirect || 'pages/contractor/dashboard.php';
                const separator = targetUrl.includes('?') ? '&' : '?';
                window.location.replace(targetUrl + separator + 't=' + Date.now());
            }, 500);
        } else {
            if (errContainer && errMsg) {
                errMsg.textContent = result.message || 'Invalid verification OTP.';
                errContainer.style.display = 'block';
            } else if (typeof showToast === 'function') {
                showToast('❌', result.message || 'Invalid verification OTP.');
            }
            OTPHandler.clearOTP('login-otp-box-wrapper');
        }
    } catch (e) {
        if (errContainer && errMsg) {
            errMsg.textContent = e.message || 'OTP Verification failed.';
            errContainer.style.display = 'block';
        } else if (typeof showToast === 'function') {
            showToast('❌', e.message || 'OTP Verification failed.');
        }
        OTPHandler.clearOTP('login-otp-box-wrapper');
    } finally {
        AuthUI.setButtonLoading(verifyBtn, false, 'VERIFY & ACCESS PORTAL');
    }
}
</script>
</body>
</html>
