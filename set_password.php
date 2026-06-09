<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password – CLMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    /* Background shapes */
    .bg-shape {
      position: absolute; border-radius: 50%; opacity: 0.06;
    }
    .bg-shape.s1 { width: 500px; height: 500px; background: #6366f1; top: -150px; right: -150px; }
    .bg-shape.s2 { width: 350px; height: 350px; background: #f59e0b; bottom: -100px; left: -100px; }
    .bg-shape.s3 { width: 200px; height: 200px; background: #10b981; top: 40%; left: 10%; }

    .set-pwd-container {
      position: relative; z-index: 1;
      background: #fff; border-radius: 24px; padding: 48px 40px;
      width: 100%; max-width: 480px;
      box-shadow: 0 24px 80px rgba(0,0,0,0.3);
      animation: slideUp 0.5s ease;
    }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .sp-header { text-align: center; margin-bottom: 32px; }
    .sp-icon {
      width: 72px; height: 72px; border-radius: 20px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px; font-size: 32px; color: #fff;
      box-shadow: 0 8px 24px rgba(99,102,241,0.3);
    }
    .sp-header h2 { font-size: 24px; font-weight: 800; color: #1e293b; }
    .sp-header p { font-size: 14px; color: #64748b; margin-top: 8px; line-height: 1.5; }

    .sp-alert {
      background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25);
      border-radius: 12px; padding: 14px 16px; margin-bottom: 24px;
      font-size: 13px; color: #92400e; display: flex; align-items: flex-start; gap: 10px;
    }
    .sp-alert i { color: #f59e0b; margin-top: 2px; }

    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .form-control {
      width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px;
      font-size: 14px; font-family: 'Inter', sans-serif; color: #1e293b;
      transition: border-color 0.2s, box-shadow 0.2s; background: #fff;
    }
    .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .input-error { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.15) !important; }

    .pwd-wrapper { position: relative; }
    .pwd-toggle {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      cursor: pointer; color: #94a3b8; font-size: 14px; transition: color 0.2s;
    }
    .pwd-toggle:hover { color: #6366f1; }

    /* Password Rules */
    .pwd-rules { list-style: none; padding: 0; margin: 16px 0 0; }
    .pwd-rules li {
      padding: 6px 0; font-size: 12px; color: #94a3b8;
      display: flex; align-items: center; gap: 8px; transition: color 0.3s;
    }
    .pwd-rules li.valid { color: #10b981; }
    .pwd-rules li i { font-size: 10px; width: 16px; text-align: center; }
    .pwd-rules li.valid i::before { content: "\f00c"; font-family: "Font Awesome 6 Free"; font-weight: 900; }

    /* Strength bar */
    .pwd-strength-bar {
      height: 4px; border-radius: 4px; background: #e2e8f0; margin-top: 10px; overflow: hidden;
    }
    .pwd-strength-fill {
      height: 100%; border-radius: 4px; transition: width 0.3s, background 0.3s; width: 0;
    }

    .btn-primary {
      width: 100%; padding: 14px; border: none; border-radius: 12px;
      background: linear-gradient(135deg, #6366f1, #4f46e5);
      color: #fff; font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif;
      cursor: pointer; transition: all 0.2s; display: flex; align-items: center;
      justify-content: center; gap: 8px;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .sp-error {
      background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25);
      border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
      font-size: 13px; color: #dc2626; display: none; align-items: center; gap: 8px;
    }

    .sp-success {
      background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25);
      border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
      font-size: 13px; color: #059669; display: none; align-items: center; gap: 8px;
    }
  </style>
</head>
<body>
  <div class="bg-shape s1"></div>
  <div class="bg-shape s2"></div>
  <div class="bg-shape s3"></div>

  <div class="set-pwd-container">
    <div class="sp-header">
      <div class="sp-icon"><i class="fas fa-key"></i></div>
      <h2>Set Your Password</h2>
      <p>Your account requires a new password.<br>Please create a strong password to secure your account.</p>
    </div>

    <div class="sp-alert">
      <i class="fas fa-info-circle"></i>
      <div>This is a <strong>one-time setup</strong>. After setting your password, you'll be redirected to your dashboard.</div>
    </div>

    <div class="sp-error" id="sp-error">
      <i class="fas fa-exclamation-circle"></i>
      <span id="sp-error-msg"></span>
    </div>

    <div class="sp-success" id="sp-success">
      <i class="fas fa-check-circle"></i>
      <span id="sp-success-msg"></span>
    </div>

    <form id="setPasswordForm" novalidate>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="pwd-wrapper">
          <input type="password" class="form-control" id="sp-new-pwd" placeholder="Create a strong password" required>
          <i class="fas fa-eye pwd-toggle" onclick="togglePwd('sp-new-pwd', this)"></i>
        </div>
        <div class="pwd-strength-bar">
          <div class="pwd-strength-fill" id="sp-strength-fill"></div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="pwd-wrapper">
          <input type="password" class="form-control" id="sp-confirm-pwd" placeholder="Re-enter your password" required>
          <i class="fas fa-eye pwd-toggle" onclick="togglePwd('sp-confirm-pwd', this)"></i>
        </div>
      </div>

      <ul class="pwd-rules" id="sp-pwd-rules">
        <li data-rule="length"><i class="fas fa-circle"></i> At least 6 characters</li>
        <li data-rule="upper"><i class="fas fa-circle"></i> At least 1 uppercase letter (A-Z)</li>
        <li data-rule="lower"><i class="fas fa-circle"></i> At least 1 lowercase letter (a-z)</li>
        <li data-rule="number"><i class="fas fa-circle"></i> At least 1 number (0-9)</li>
        <li data-rule="match"><i class="fas fa-circle"></i> Both passwords match</li>
      </ul>

      <div style="margin-top:24px;">
        <button type="submit" class="btn-primary" id="sp-submit-btn">
          <i class="fas fa-shield-alt"></i> Set Password & Continue
        </button>
      </div>
    </form>
  </div>

<script>
function togglePwd(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Live password validation
const newPwd = document.getElementById('sp-new-pwd');
const confirmPwd = document.getElementById('sp-confirm-pwd');
const strengthFill = document.getElementById('sp-strength-fill');

function checkRules() {
    const pwd = newPwd.value;
    const conf = confirmPwd.value;

    const rules = {
        length: pwd.length >= 6,
        upper: /[A-Z]/.test(pwd),
        lower: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd),
        match: pwd.length > 0 && pwd === conf
    };

    let validCount = 0;
    document.querySelectorAll('#sp-pwd-rules li').forEach(li => {
        const rule = li.getAttribute('data-rule');
        li.classList.toggle('valid', rules[rule]);
        if (rules[rule]) validCount++;
    });

    // Strength bar
    const strength = validCount / 5;
    strengthFill.style.width = (strength * 100) + '%';
    if (strength <= 0.4) {
        strengthFill.style.background = '#ef4444';
    } else if (strength <= 0.7) {
        strengthFill.style.background = '#f59e0b';
    } else {
        strengthFill.style.background = '#10b981';
    }

    return Object.values(rules).every(v => v);
}

newPwd.addEventListener('input', checkRules);
confirmPwd.addEventListener('input', checkRules);

// Submit
document.getElementById('setPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const errorDiv = document.getElementById('sp-error');
    const successDiv = document.getElementById('sp-success');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (!checkRules()) {
        errorDiv.style.display = 'flex';
        document.getElementById('sp-error-msg').textContent = 'Please meet all password requirements.';
        return;
    }

    const btn = document.getElementById('sp-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting Password...';

    try {
        const res = await fetch('api/set_new_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                new_password: newPwd.value,
                confirm_password: confirmPwd.value
            })
        });
        const data = await res.json();
        if (data.success) {
            successDiv.style.display = 'flex';
            document.getElementById('sp-success-msg').textContent = data.message || 'Password set! Redirecting...';
            setTimeout(() => {
                window.location.href = data.data?.redirect || 'index.php';
            }, 1500);
        } else {
            errorDiv.style.display = 'flex';
            document.getElementById('sp-error-msg').textContent = data.message || 'Failed to set password.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shield-alt"></i> Set Password & Continue';
        }
    } catch (err) {
        errorDiv.style.display = 'flex';
        document.getElementById('sp-error-msg').textContent = 'Network error: ' + err.message;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-alt"></i> Set Password & Continue';
    }
});
</script>
</body>
</html>
