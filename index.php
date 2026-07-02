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

$popup_message = "";
$popup_file = __DIR__ . '/uploads/popup.txt';
if (file_exists($popup_file)) {
    $popup_message = trim(file_get_contents($popup_file));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= get_csrf_token() ?>">
  <title>CLMS</title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="css/auth_redesign.css" />
  <link rel="stylesheet" href="css/auth_components.css" />
  <link rel="stylesheet" href="css/auth_responsive.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    input[type='text']:not([id*='pass']):not([name*='pass'])<?php echo $isInternalLogin ? ':not(#login-user)' : ''; ?>, 
    input[type='search'], 
    textarea { text-transform: uppercase; }
  </style>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($popup_message)): ?>
    Swal.fire({
      title: 'Announcement',
      text: <?= json_encode($popup_message) ?>,
      icon: 'info',
      confirmButtonText: 'Proceed'
    });
    <?php endif; ?>
    <?php if (isset($_GET['blocked'])): ?>
    Swal.fire({
      title: 'Access Denied',
      text: <?= json_encode('Your account has been blocked: ' . ($_GET['reason'] ?? 'Administrative action') . '. Please contact Welfare Admin.') ?>,
      icon: 'error',
      confirmButtonText: 'Understood'
    });
    <?php endif; ?>
  });
  
  document.addEventListener('input', function(e) {
    if (e.target.id === 'login-pass') return; // Exclude password field even if visibility toggled
    <?php if ($isInternalLogin): ?>
    if (e.target.id === 'login-user') return; // Do not force uppercase for internal login user ID
    <?php endif; ?>
    if ((e.target.tagName === 'INPUT' && (e.target.type === 'text' || e.target.type === 'search')) || e.target.tagName === 'TEXTAREA') {
      let start = e.target.selectionStart;
      let end = e.target.selectionEnd;
      e.target.value = e.target.value.toUpperCase();
      e.target.setSelectionRange(start, end);
    }
  });
  </script>
</head>
<body>

<div class="auth-split-wrapper">
  <!-- LEFT BRAND PANEL (Desktop-only) -->
  <div class="auth-left-pane" style="position: relative; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
    
    <!-- Top Left Logo -->
    <div style="position: absolute; top: 35px; left: 45px; z-index: 10;">
      <div style="background: white; padding: 10px; border-radius: 16px; display: inline-block; box-shadow: 0 8px 25px rgba(0,0,0,0.25);">
        <img src="uploads/logo/logo.png" alt="CSL Logo" style="height: 65px; object-fit: contain;">
      </div>
    </div>

    <!-- Premium Glassmorphism Showcase Card -->
    <div class="left-brand-content" style="
        position: relative; 
        z-index: 5;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 4rem 3rem;
        border-radius: 28px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        max-width: 640px;
        transform: translateY(-5%);
        animation: floatIllustration 8s ease-in-out infinite;
    ">


      <!-- Decorative UI Accents -->
      <div style="position: absolute; top: -15px; right: 40px; background: #3b82f6; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; font-weight: 800; color: #fff; letter-spacing: 1px; box-shadow: 0 8px 16px rgba(59,130,246,0.4);">
        ENTERPRISE PORTAL
      </div>
      <div style="width: 60px; height: 4px; background: linear-gradient(90deg, #60a5fa, transparent); margin-bottom: 25px; border-radius: 2px;"></div>

      <h1 style="font-size: 3.2rem; font-weight: 800; line-height: 1.1; margin-bottom: 1rem; color: #ffffff; text-shadow: 0 4px 20px rgba(0,0,0,0.15); letter-spacing: -0.02em;">
        Contract Labour<br>
        <span style="color: #ffffff; font-weight: 300;">Management System</span>
      </h1>
      
      <h2 style="font-size: 1.4rem; font-weight: 600; color: #e2e8f0; margin-bottom: 2rem; letter-spacing: 0.5px;">
        Cochin Shipyard Ltd. (CLMS-CSL)
      </h2>

      <p style="font-size: 1.1rem; color: #bfdbfe; font-weight: 400; line-height: 1.7; margin-bottom: 3.5rem; max-width: 90%;">
        Navigating Workforce Excellence with Smart Digital Solution. Streamlined orchestrations, secure access, and enterprise-grade compliance.
      </p>
      
      <div class="left-security-tagline" style="
          display: inline-flex;
          background: rgba(0, 0, 0, 0.2);
          border: 1px solid rgba(255, 255, 255, 0.08);
          padding: 14px 24px;
          border-radius: 50px;
          box-shadow: inset 0 2px 10px rgba(255,255,255,0.05);
      ">
        <i class="fas fa-shield-halved" style="color: #60a5fa; font-size: 1.3rem;"></i>
        <span style="font-size: 0.95rem; font-weight: 600; letter-spacing: 0.5px;">ISO 27001 Certified Security Infrastructure</span>
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
          <h2 class="auth-card-title">CLMS</h2>
          <p class="auth-card-subtitle">Contract Labour Management System</p>
        </div>

        <!-- Inline Error Banner -->
        <div id="login-error-container"></div>

        <!-- Form -->
        <form id="login-form" onsubmit="executeLogin(event)" novalidate>
          
          <!-- User ID Input -->
          <div class="form-group">
            <div class="input-wrapper">
              <input type="text" id="login-user" class="form-control" placeholder=" " required autocomplete="username">
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
              <input type="password" id="login-pass" class="form-control" placeholder=" " required autocomplete="current-password">
              <i class="fas fa-lock input-icon"></i>
              <i class="fas fa-eye toggle-pwd" id="toggle-password" onclick="togglePwdVisibility('login-pass', 'toggle-password')"></i>
              <label class="form-label" for="login-pass">Password</label>
            </div>
          </div>

          <!-- Security Captcha Input -->
          <div class="form-group">
            <div class="captcha-row">
              <div style="position:relative; width:100%;">
                <input type="text" id="login-captcha" class="form-control" placeholder=" " required maxlength="6" style="padding-left:16px; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
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
