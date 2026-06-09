<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-unlock-alt text-warning"></i> Manual Gate Override</h2>
      <p class="page-subtitle text-danger"><strong>WARNING:</strong> Bypassing standard gate checks requires Supervisor Authentication. All actions are strictly audited.</p>
    </div>

    <div class="row justify-content-center" style="display: flex; justify-content: center; margin-top: 20px;">
        <div class="col-md-6" style="width: 100%; max-width: 600px;">
            <div class="card glass border-warning">
                <div class="card-header bg-warning text-dark">
                    <div class="card-title"><i class="fas fa-shield-alt"></i> Override Authorization Panel</div>
                </div>
                <div class="card-body">
                    <div id="alertArea"></div>
                    
                    <form id="overrideForm" onsubmit="event.preventDefault(); submitOverride();">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Worker ID / ACC No / Aadhar <span class="text-danger">*</span></label>
                            <input type="text" id="workerId" class="form-control" required placeholder="Enter identifier of worker">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Reason for Override <span class="text-danger">*</span></label>
                            <textarea id="overrideReason" class="form-control" rows="3" required placeholder="E.g., Biometric machine failure, Emergency entry..."></textarea>
                        </div>
                        
                        <hr style="border-color: rgba(0,0,0,0.1); margin: 20px 0;">
                        <h5 class="text-danger"><i class="fas fa-user-shield"></i> Supervisor Authentication</h5>
                        <p class="text-muted" style="font-size: 13px;">A Manager, Admin, or Safety Officer must enter their credentials to authorize this action.</p>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Supervisor Username <span class="text-danger">*</span></label>
                            <input type="text" id="supUsername" class="form-control" required placeholder="Manager/Admin Username">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Supervisor PIN / Password <span class="text-danger">*</span></label>
                            <input type="password" id="supPassword" class="form-control" required placeholder="Authentication PIN">
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-block" style="width: 100%; padding: 12px; font-weight: bold; font-size: 16px;">
                            <i class="fas fa-exclamation-triangle"></i> AUTHORIZE & ALLOW ENTRY
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function submitOverride() {
        const workerId = document.getElementById('workerId').value.trim();
        const reason = document.getElementById('overrideReason').value.trim();
        const supUsername = document.getElementById('supUsername').value.trim();
        const supPassword = document.getElementById('supPassword').value.trim();

        if(!workerId || !reason || !supUsername || !supPassword) {
            showAlert('All fields are required.', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('worker_id', workerId);
        formData.append('reason', reason);
        formData.append('supervisor_username', supUsername);
        formData.append('supervisor_password', supPassword);

        fetch('../../api/frontline/manual_override.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('overrideForm').reset();
                showAlert(`<strong><i class="fas fa-check-circle"></i> Success:</strong> Override authorized. ${data.worker_name} has been granted entry.`, 'success');
            } else {
                showAlert(`<strong><i class="fas fa-ban"></i> Error:</strong> ${data.error}`, 'danger');
            }
        })
        .catch(err => {
            showAlert(`<strong>Network Error:</strong> ${err.message}`, 'danger');
        });
    }

    function showAlert(msg, type) {
        const area = document.getElementById('alertArea');
        area.innerHTML = `<div class="alert alert-${type}" style="margin-bottom: 20px;">${msg}</div>`;
    }
    </script>
    <?php
}

renderLayout("Manual Gate Override", 'renderContent', $role, $name);

