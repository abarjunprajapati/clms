<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enterprise Contractor Portal Activation</title>
  
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
      <h1>Enterprise<br>Contractor Portal</h1>
      <p>Connect your  Profile to the CLMS workforce grid and start managing your operations seamlessly.</p>
      
      <div class="left-security-tagline">
        <i class="fas fa-shield-halved"></i>
        <span>Two-Factor Authentication Protocols Enabled</span>
      </div>
    </div>
  </div>

  <!-- RIGHT FORM PANEL -->
  <div class="auth-right-pane">
    <div class="auth-card-container">
      <div class="auth-card">
        
        <!-- Header -->
        <div class="auth-card-header" style="margin-bottom: 25px;">
          <h2 class="auth-card-title">Activate Profile</h2>
          <!-- <p class="auth-card-subtitle">SAP Profile Sync & Initialization</p> -->
        </div>

        <!-- Three Step Progress Indicator -->
        <div class="progress-stepper">
          <div class="progress-step active" id="p-step-1">
            <div class="step-num">1</div>
            <div class="step-label">Validate</div>
          </div>
          <div class="progress-line" id="p-line-1"></div>
          <div class="progress-step" id="p-step-2">
            <div class="step-num">2</div>
            <div class="step-label">OTP Check</div>
          </div>
          <div class="progress-line" id="p-line-2"></div>
          <div class="progress-step" id="p-step-3">
            <div class="step-num">3</div>
            <div class="step-label">Credentials</div>
          </div>
        </div>

        <!-- Centralized Error Banner -->
        <div id="activation-error-container"></div>

        <!-- STEP 1: VALIDATE VENDOR CODE -->
        <div id="step-1" class="step-content active" style="transition: var(--transition-smooth);">
          <form id="form-step-1" onsubmit="handleValidateVendor(event)" novalidate>
            <div class="form-group">
              <div class="input-wrapper">
                <input type="text" id="vendor_code" class="form-control" placeholder="Vendor / Customer Code" required autofocus>
                <i class="fas fa-barcode input-icon"></i>
                <label class="form-label" for="vendor_code">Vendor / Customer Code</label>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-verify">
              <span>VALIDATE</span>
              <i class="fas fa-arrow-right"></i>
            </button>

            <a href="index.php" class="btn btn-outline" style="margin-top: 15px;">
              <i class="fas fa-arrow-left"></i> BACK TO LOGIN
            </a>
          </form>
        </div>

        <!-- STEP 2: MOBILE OTP CHECK -->
        <div id="step-2" class="step-content" style="display:none; transition: var(--transition-smooth); opacity: 0; transform: translateX(20px);">
          <form id="form-step-2" onsubmit="handleVerifyMobileOTP(event)" novalidate>
            <!-- Masked Vendor Details Card -->
            <div class="premium-info-card">
              <div class="info-item info-item-full">
                <span class="info-label">Vendor Name</span>
                <span class="info-value" id="disp-name">-</span>
              </div>
              <div class="info-item">
                <span class="info-label">Mobile</span>
                <span class="info-value" id="disp-mob1">-</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value" id="disp-email">-</span>
              </div>
            </div>

            <div class="form-group" style="text-align: center;">
              <label class="form-label" style="position:static; transform:none; pointer-events:auto; font-weight:700; font-size:0.85rem; color:var(--text-primary); text-transform:uppercase; margin-bottom:12px; display:block;">Enter Mobile OTP</label>
              <div class="otp-container" id="activation-mob-otp-wrapper">
                <input type="text" class="otp-box" id="motp0" maxlength="1">
                <input type="text" class="otp-box" id="motp1" maxlength="1">
                <input type="text" class="otp-box" id="motp2" maxlength="1">
                <input type="text" class="otp-box" id="motp3" maxlength="1">
                <input type="text" class="otp-box" id="motp4" maxlength="1">
                <input type="text" class="otp-box" id="motp5" maxlength="1">
              </div>
              <span id="mob-otp-hint" style="display:none; font-size:0.75rem; font-weight:700; background:rgba(37,99,235,0.06); color:var(--primary-color); padding:6px 12px; border-radius:8px; display:inline-block; margin-top:8px;"></span>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-mob-otp">
              <span>VERIFY MOBILE OTP</span>
              <i class="fas fa-check"></i>
            </button>
            
            <div class="otp-resend-wrapper">
              <p class="otp-resend-text">Didn't receive Mobile OTP? <a href="#" id="resend-mob-link" class="otp-resend-link">Resend OTP</a></p>
              <a href="#" onclick="location.reload()" style="display:inline-block; margin-top:15px; font-size:0.82rem; color:var(--text-muted); text-decoration:none; font-weight:600;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">Cancel Activation</a>
            </div>
          </form>
        </div>

        <!-- STEP 3: EMAIL OTP CHECK -->
        <div id="step-3" class="step-content" style="display:none; transition: var(--transition-smooth); opacity: 0; transform: translateX(20px);">
          <form id="form-step-3" onsubmit="handleVerifyEmailOTP(event)" novalidate>
            <div class="form-group" style="text-align: center;">
              <label class="form-label" style="position:static; transform:none; pointer-events:auto; font-weight:700; font-size:0.85rem; color:var(--text-primary); text-transform:uppercase; margin-bottom:12px; display:block;">Enter Email OTP</label>
              <div class="otp-container" id="activation-em-otp-wrapper">
                <input type="text" class="otp-box" id="eotp0" maxlength="1">
                <input type="text" class="otp-box" id="eotp1" maxlength="1">
                <input type="text" class="otp-box" id="eotp2" maxlength="1">
                <input type="text" class="otp-box" id="eotp3" maxlength="1">
                <input type="text" class="otp-box" id="eotp4" maxlength="1">
                <input type="text" class="otp-box" id="eotp5" maxlength="1">
              </div>
              <span id="em-otp-hint" style="display:none; font-size:0.75rem; font-weight:700; background:rgba(37,99,235,0.06); color:var(--primary-color); padding:6px 12px; border-radius:8px; display:inline-block; margin-top:8px;"></span>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-em-otp">
              <span>VERIFY EMAIL OTP</span>
              <i class="fas fa-check"></i>
            </button>
            
            <div class="otp-resend-wrapper">
              <p class="otp-resend-text">Didn't receive Email OTP? <a href="#" id="resend-em-link" class="otp-resend-link">Resend OTP</a></p>
              <a href="#" onclick="location.reload()" style="display:inline-block; margin-top:15px; font-size:0.82rem; color:var(--text-muted); text-decoration:none; font-weight:600;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">Cancel Activation</a>
            </div>
          </form>
        </div>

        <!-- STEP 4: PASSWORD SETUP -->
        <div id="step-4" class="step-content" style="display:none; transition: var(--transition-smooth); opacity: 0; transform: translateX(20px);">
          <form id="form-step-4" onsubmit="handlePasswordSubmit(event)" novalidate>
            
            <!-- Create Password Input -->
            <div class="form-group">
              <!-- Caps Lock warning capsule -->
              <div id="caps-warning-p1" class="caps-warning">
                <i class="fas fa-keyboard"></i> Caps Lock is Active
              </div>
              <div class="input-wrapper">
                <input type="password" id="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
                <i class="fas fa-eye toggle-pwd" id="toggle-p1" onclick="togglePwdVisibility('password', 'toggle-p1')"></i>
                <label class="form-label" for="password">Create Password</label>
              </div>
              <!-- Strength meter bar -->
              <div id="pwd-strength" class="pwd-strength-container">
                <div class="pwd-strength-bar"></div>
                <div class="pwd-strength-bar"></div>
                <div class="pwd-strength-bar"></div>
              </div>
            </div>

            <!-- Confirm Password Input -->
            <div class="form-group">
              <!-- Caps Lock warning capsule -->
              <div id="caps-warning-p2" class="caps-warning">
                <i class="fas fa-keyboard"></i> Caps Lock is Active
              </div>
              <div class="input-wrapper">
                <input type="password" id="confirm_password" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
                <i class="fas fa-eye toggle-pwd" id="toggle-p2" onclick="togglePwdVisibility('confirm_password', 'toggle-p2')"></i>
                <label class="form-label" for="confirm_password">Confirm Password</label>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-submit">
              <span>COMPLETE ACTIVATION</span>
              <i class="fas fa-rocket"></i>
            </button>
          </form>
        </div>

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
// Bind CAPS LOCK warn indicator and strength meter
AuthUI.bindCapsLockDetector(document.getElementById('password'), 'caps-warning-p1');
AuthUI.bindCapsLockDetector(document.getElementById('confirm_password'), 'caps-warning-p2');
AuthUI.bindPasswordStrength(document.getElementById('password'), 'pwd-strength');

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

// Global scope cache for OTP hints
let demoMobileOtp = '';
let demoEmailOtp = '';

// Step transitions with slide-and-fade animation
function transitionStep(currentId, nextId, stepIndex) {
    const current = document.getElementById('step-' + currentId);
    const next = document.getElementById('step-' + nextId);
    
    current.style.opacity = '0';
    current.style.transform = 'translateX(-20px)';
    
    setTimeout(() => {
        current.style.display = 'none';
        
        next.style.display = 'block';
        // Force reflow
        void next.offsetWidth;
        
        next.style.opacity = '1';
        next.style.transform = 'translateX(0)';
    }, 250);

    // Update Progress Stepper indices
    if (stepIndex >= 2) {
        document.getElementById('p-step-2').classList.add('active');
        document.getElementById('p-step-1').classList.remove('active');
        document.getElementById('p-step-1').classList.add('completed');
        document.getElementById('p-line-1').classList.add('completed');
    }
    if (stepIndex >= 3) {
        document.getElementById('p-step-3').classList.add('active');
        document.getElementById('p-step-2').classList.remove('active');
        document.getElementById('p-step-2').classList.add('completed');
        document.getElementById('p-line-2').classList.add('completed');
    }
}

// Step 1: Validate vendor code in SAP
async function handleValidateVendor(e) {
    e.preventDefault();
    const form = document.getElementById('form-step-1');
    const input = document.getElementById('vendor_code');
    const btn = document.getElementById('btn-verify');

    ValidationHandler.clearFormError(form);
    ValidationHandler.clearFieldState(input);

    const vendorCode = input.value.trim();
    if (!vendorCode) {
        ValidationHandler.setFieldState(input, 'error', 'Vendor Code is required');
        ValidationHandler.showFormError(form, 'Please input your unique Vendor or Customer ID.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'VALIDATING PROFILE...');

    try {
        const res = await AuthUI.sendAPIRequest('api/auth/verify_sap_details.php', {
            vendor_code: vendorCode
        });

        if (res.success) {
            // Mask vendor details in info card
            document.getElementById('disp-name').textContent = res.data.vendor_name || 'N/A';
            document.getElementById('disp-mob1').textContent = AuthUI.maskPhone(res.data.mobile1) || 'N/A';
            document.getElementById('disp-email').textContent = AuthUI.maskEmail(res.data.email) || 'N/A';

            // Store demo OTPs in local variables and update DOM hints
            demoMobileOtp = res.mobile_otp_demo;
            demoEmailOtp = res.email_otp_demo;

            const mobHint = document.getElementById('mob-otp-hint');
            if (mobHint && demoMobileOtp) {
                mobHint.textContent = `Demo OTP: ${demoMobileOtp}`;
                mobHint.style.display = 'inline-block';
            }
            const emHint = document.getElementById('em-otp-hint');
            if (emHint && demoEmailOtp) {
                emHint.textContent = `Demo OTP: ${demoEmailOtp}`;
                emHint.style.display = 'inline-block';
            }

            // Automatically move user to OTP verification step with smooth transition
            transitionStep(1, 2, 2);

            // Initialize modular OTP box controls for Mobile
            OTPHandler.init('activation-mob-otp-wrapper', function(code) {
                submitMobileOTP(code);
            });

            // Start Resend timer
            OTPHandler.startResendTimer('resend-mob-link', 60, resendActivationOTPs);

        } else {
            ValidationHandler.showFormError(form, res.message || 'Validation failed. Check vendor code.');
            ValidationHandler.setFieldState(input, 'error');
        }
    } catch (err) {
        console.error('Validation error:', err);
        ValidationHandler.showFormError(form, err.message || 'API connection failed. Please try again.');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'VALIDATE FROM SAP');
    }
}

// Resend OTP trigger (re-validates SAP and gets new code)
async function resendActivationOTPs() {
    const input = document.getElementById('vendor_code');
    const vendorCode = input.value.trim();
    if (!vendorCode) return;

    try {
        const res = await AuthUI.sendAPIRequest('api/auth/verify_sap_details.php', {
            vendor_code: vendorCode
        });

        if (res.success) {
            demoMobileOtp = res.mobile_otp_demo;
            demoEmailOtp = res.email_otp_demo;

            const mobHint = document.getElementById('mob-otp-hint');
            if (mobHint && demoMobileOtp) mobHint.textContent = `Demo OTP: ${demoMobileOtp}`;
            
            const emHint = document.getElementById('em-otp-hint');
            if (emHint && demoEmailOtp) emHint.textContent = `Demo OTP: ${demoEmailOtp}`;

            if (typeof showToast === 'function') showToast('✅', 'OTPs have been successfully regenerated.');
            
            // Restart timer countdown
            OTPHandler.startResendTimer('resend-mob-link', 60, resendActivationOTPs);
            OTPHandler.startResendTimer('resend-em-link', 60, resendActivationOTPs);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('❌', 'Failed to regenerate OTPs.');
    }
}

// Step 2: Validate Mobile OTP Form
async function handleVerifyMobileOTP(e) {
    e.preventDefault();
    submitMobileOTP();
}

async function submitMobileOTP(fullCode) {
    const form = document.getElementById('form-step-2');
    const code = fullCode || OTPHandler.getOTP('activation-mob-otp-wrapper');
    const btn = document.getElementById('btn-mob-otp');

    ValidationHandler.clearFormError(form);

    if (code.length !== 6) {
        ValidationHandler.showFormError(form, 'Please enter a complete 6-digit Mobile OTP.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'VERIFYING...');

    try {
        const res = await AuthUI.sendAPIRequest('api/auth/verify_mobile_otp.php', {
            mobile_otp: code
        });

        if (res.success) {
            transitionStep(2, 3, 2);

            // Initialize modular OTP box controls for Email
            OTPHandler.init('activation-em-otp-wrapper', function(code) {
                submitEmailOTP(code);
            });

            // Start Resend countdown for Email OTP
            OTPHandler.startResendTimer('resend-em-link', 60, resendActivationOTPs);
        } else {
            ValidationHandler.showFormError(form, res.message || 'Incorrect Mobile OTP code.');
            OTPHandler.clearOTP('activation-mob-otp-wrapper');
        }
    } catch (err) {
        ValidationHandler.showFormError(form, err.message || 'API verification fail.');
        OTPHandler.clearOTP('activation-mob-otp-wrapper');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'VERIFY MOBILE OTP');
    }
}

// Step 3: Validate Email OTP Form
async function handleVerifyEmailOTP(e) {
    e.preventDefault();
    submitEmailOTP();
}

async function submitEmailOTP(fullCode) {
    const form = document.getElementById('form-step-3');
    const code = fullCode || OTPHandler.getOTP('activation-em-otp-wrapper');
    const btn = document.getElementById('btn-em-otp');

    ValidationHandler.clearFormError(form);

    if (code.length !== 6) {
        ValidationHandler.showFormError(form, 'Please enter a complete 6-digit Email OTP.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'VERIFYING...');

    try {
        const res = await AuthUI.sendAPIRequest('api/auth/verify_email_otp.php', {
            email_otp: code
        });

        if (res.success) {
            transitionStep(3, 4, 3);
        } else {
            ValidationHandler.showFormError(form, res.message || 'Incorrect Email OTP code.');
            OTPHandler.clearOTP('activation-em-otp-wrapper');
        }
    } catch (err) {
        ValidationHandler.showFormError(form, err.message || 'API verification fail.');
        OTPHandler.clearOTP('activation-em-otp-wrapper');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'VERIFY EMAIL OTP');
    }
}

// Step 4: Validate and Submit password
async function handlePasswordSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('form-step-4');
    const pwdInput = document.getElementById('password');
    const cpwdInput = document.getElementById('confirm_password');
    const btn = document.getElementById('btn-submit');

    ValidationHandler.clearFormError(form);
    ValidationHandler.clearFieldState(pwdInput);
    ValidationHandler.clearFieldState(cpwdInput);

    const password = pwdInput.value;
    const confirmPassword = cpwdInput.value;

    if (!password) {
        ValidationHandler.setFieldState(pwdInput, 'error', 'Password is required');
        ValidationHandler.showFormError(form, 'Please define a password.');
        return;
    }
    if (password.length < 6) {
        ValidationHandler.setFieldState(pwdInput, 'error', 'Must be at least 6 characters');
        ValidationHandler.showFormError(form, 'Your password must be at least 6 characters long.');
        return;
    }
    if (password !== confirmPassword) {
        ValidationHandler.setFieldState(cpwdInput, 'error', 'Passwords do not match');
        ValidationHandler.showFormError(form, 'Passwords do not match. Check credentials.');
        return;
    }

    AuthUI.setButtonLoading(btn, true, 'COMPLETING ACTIVATION...');

    try {
        const res = await AuthUI.sendAPIRequest('api/auth/complete_activation.php', {
            password: password,
            confirm_password: confirmPassword
        });

        if (res.success) {
            // Success alert status card
            let successCard = form.querySelector('.status-card-success');
            if (!successCard) {
                successCard = document.createElement('div');
                successCard.className = 'status-card status-card-success';
                successCard.innerHTML = `
                    <i class="fas fa-circle-check"></i>
                    <div>
                       <strong>Activation Completed!</strong><br>
                       Redirecting to Login Page...
                    </div>
                `;
                form.insertBefore(successCard, form.firstChild);
            }
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        } else {
            ValidationHandler.showFormError(form, res.message || 'Activation finalization failed.');
        }
    } catch (err) {
        ValidationHandler.showFormError(form, err.message || 'API connection failed.');
    } finally {
        AuthUI.setButtonLoading(btn, false, 'COMPLETE ACTIVATION');
    }
}
</script>
</body>
</html>
