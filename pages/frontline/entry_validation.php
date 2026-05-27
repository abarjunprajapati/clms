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
      <h2 class="page-title"><i class="fas fa-sign-in-alt text-success"></i> Gate Entry Validation</h2>
      <p class="page-subtitle">Scan ACC / Gate Pass / RFID for physical validation and attendance sync.</p>
    </div>

    <div class="row" style="display: flex; gap: 20px;">
        <!-- Scanner Input -->
        <div class="col" style="flex: 1;">
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title">Scanner Panel</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Gate Location</label>
                        <select id="gateLocation" class="form-control" style="margin-bottom:15px; width:100%; padding:10px;">
                            <option value="Main_Gate">Main Gate</option>
                            <option value="Gate_2">Gate 2</option>
                            <option value="Gate_3">Gate 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Scan Worker ID</label>
                        <input type="text" id="scanInput" class="form-control" style="font-size: 24px; padding: 15px; text-align: center; font-weight: bold;" placeholder="Waiting for scanner..." autofocus autocomplete="off">
                        <small class="text-muted">You can scan QR code, barcode, or manually enter ACC ref/Aadhar.</small>
                    </div>
                    <button class="btn btn-primary" onclick="processScan()" style="width:100%; padding: 15px; font-size:18px; margin-top:10px;">Verify Scan</button>
                </div>
            </div>
        </div>

        <!-- Verification Result -->
        <div class="col" style="flex: 2;">
            <div class="card glass" id="resultCard" style="display:none;">
                <div class="card-header" id="resultHeader">
                    <div class="card-title">Verification Result</div>
                </div>
                <div class="card-body" style="display: flex; gap: 20px;">
                    <div id="workerPhotoContainer" style="width: 150px; height: 150px; background: #eee; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user fa-4x text-muted"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 id="workerName" style="margin: 0 0 10px 0;">--</h3>
                        <p style="margin: 5px 0;"><strong>Pass Number:</strong> <span id="passNumber">--</span> (<span id="passType">--</span>)</p>
                        <p style="margin: 5px 0;"><strong>Trade:</strong> <span id="workerTrade">--</span></p>
                        
                        <div id="statusAlerts" style="margin-top: 15px;"></div>

                        <div id="actionContainer" style="margin-top: 20px; display: none;">
                            <div class="alert alert-warning">
                                <strong>Visual Match Required:</strong> Ensure the photo matches the person.
                            </div>
                            <button class="btn btn-success" style="padding: 10px 20px; font-size: 16px;" onclick="allowEntry()">
                                <i class="fas fa-check-circle"></i> Confirm Photo Match & Allow Entry
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentWorkerId = null;

    document.getElementById('scanInput').addEventListener('keypress', function(e) {
        if(e.key === 'Enter') {
            processScan();
        }
    });

    function processScan() {
        const scanData = document.getElementById('scanInput').value.trim();
        const gateId = document.getElementById('gateLocation').value;
        if(!scanData) return;

        // Visual reset
        document.getElementById('resultCard').style.display = 'block';
        document.getElementById('resultHeader').style.background = '#e0e0e0';
        document.getElementById('resultHeader').style.color = '#333';
        document.getElementById('workerName').innerText = 'Loading...';
        document.getElementById('statusAlerts').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking backend...';
        document.getElementById('actionContainer').style.display = 'none';

        const formData = new FormData();
        formData.append('scan_data', scanData);
        formData.append('gate_id', gateId);

        fetch('../../api/frontline/fetch_worker_pass.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                showError(data.error);
                return;
            }

            const w = data.worker;
            currentWorkerId = w.id;
            document.getElementById('workerName').innerText = w.name;
            document.getElementById('passNumber').innerText = w.gatepass_no || 'N/A';
            document.getElementById('passType').innerText = w.pass_type === 'perm' ? 'Permanent (ACC)' : 'Temporary';
            document.getElementById('workerTrade').innerText = w.trade || 'N/A';

            if(w.photo) {
                document.getElementById('workerPhotoContainer').innerHTML = `<img src="${w.photo}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='../../assets/images/default-avatar.png';">`;
            } else {
                document.getElementById('workerPhotoContainer').innerHTML = '<i class="fas fa-user fa-4x text-muted"></i>';
            }

            if(data.can_enter) {
                document.getElementById('resultHeader').style.background = 'rgba(34,197,94,0.2)';
                document.getElementById('resultHeader').style.color = '#166534';
                document.getElementById('statusAlerts').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> System checks passed. Waiting for physical validation.</div>';
                document.getElementById('actionContainer').style.display = 'block';
            } else {
                document.getElementById('resultHeader').style.background = 'rgba(239,68,68,0.2)';
                document.getElementById('resultHeader').style.color = '#991b1b';
                
                let issuesHtml = '<div class="alert alert-danger" style="background:#fef2f2; border-left:4px solid #ef4444; padding:10px;"><strong><i class="fas fa-ban"></i> ENTRY REJECTED</strong><ul style="margin-bottom:0;">';
                data.issues.forEach(iss => {
                    issuesHtml += `<li>${iss}</li>`;
                });
                issuesHtml += '</ul></div>';
                document.getElementById('statusAlerts').innerHTML = issuesHtml;
                
                // Play buzzer sound
                beep();
            }
        })
        .catch(err => {
            showError('Network Error: ' + err.message);
        });

        document.getElementById('scanInput').value = '';
    }

    function showError(msg) {
        document.getElementById('workerName').innerText = 'Unknown';
        document.getElementById('statusAlerts').innerHTML = `<div class="alert alert-danger">${msg}</div>`;
        document.getElementById('resultHeader').style.background = 'rgba(239,68,68,0.2)';
        document.getElementById('resultHeader').style.color = '#991b1b';
        document.getElementById('workerPhotoContainer').innerHTML = '<i class="fas fa-user-slash fa-4x text-danger"></i>';
        beep();
    }

    function allowEntry() {
        const gateId = document.getElementById('gateLocation').value;
        const formData = new FormData();
        formData.append('worker_id', currentWorkerId);
        formData.append('gate_id', gateId);

        fetch('../../api/frontline/process_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('actionContainer').style.display = 'none';
                document.getElementById('statusAlerts').innerHTML = '<div class="alert alert-success" style="font-size:18px; text-align:center;"><i class="fas fa-check-circle fa-2x"></i><br>Entry Authorized & Attendance Marked</div>';
                setTimeout(() => {
                    document.getElementById('resultCard').style.display = 'none';
                    document.getElementById('scanInput').focus();
                }, 3000);
            } else {
                alert('Error: ' + data.error);
            }
        });
    }

    // Audio cue for rejected
    function beep() {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gainNode = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.value = 150;
        gainNode.gain.value = 0.5;
        osc.connect(gainNode);
        gainNode.connect(ctx.destination);
        osc.start();
        setTimeout(() => osc.stop(), 500);
    }
    </script>
    <?php
}

renderLayout("Gate Entry Validation", 'renderContent', $role, $name);

