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
      <h2 class="page-title"><i class="fas fa-sign-out-alt text-danger"></i> Gate Exit Validation</h2>
      <p class="page-subtitle">Scan ACC / Gate Pass / RFID to mark attendance exit.</p>
    </div>

    <div class="row" style="display: flex; gap: 20px;">
        <!-- Scanner Input -->
        <div class="col" style="flex: 1; max-width: 500px;">
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title">Scanner Panel</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Scan Worker ID</label>
                        <input type="text" id="scanInput" class="form-control" style="font-size: 24px; padding: 15px; text-align: center; font-weight: bold;" placeholder="Waiting for scanner..." autofocus autocomplete="off">
                        <small class="text-muted">You can scan QR code, barcode, or manually enter ACC ref/Aadhar.</small>
                    </div>
                    <button class="btn btn-danger" onclick="processScan()" style="width:100%; padding: 15px; font-size:18px; margin-top:10px;">Mark Exit</button>
                </div>
            </div>
        </div>

        <!-- Verification Result -->
        <div class="col" style="flex: 2;">
            <div id="resultArea"></div>
        </div>
    </div>

    <script>
    document.getElementById('scanInput').addEventListener('keypress', function(e) {
        if(e.key === 'Enter') {
            processScan();
        }
    });

    function processScan() {
        const scanData = document.getElementById('scanInput').value.trim();
        if(!scanData) return;

        const formData = new FormData();
        formData.append('scan_data', scanData);

        fetch('../../api/frontline/process_exit.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                document.getElementById('resultArea').innerHTML = `
                    <div class="alert alert-danger" style="font-size:18px;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong> ${data.error}
                    </div>
                `;
                beep();
            } else {
                document.getElementById('resultArea').innerHTML = `
                    <div class="alert alert-success" style="font-size:18px;">
                        <strong><i class="fas fa-check-circle"></i> Exit Recorded!</strong><br><br>
                        <strong>Worker:</strong> ${data.worker_name}<br>
                        <strong>Entry Time:</strong> ${data.entry_time}<br>
                        <strong>Exit Time:</strong> ${data.exit_time}
                    </div>
                `;
            }
        })
        .catch(err => {
            document.getElementById('resultArea').innerHTML = `<div class="alert alert-danger">Network Error: ${err.message}</div>`;
        });

        document.getElementById('scanInput').value = '';
        document.getElementById('scanInput').focus();
    }

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

renderLayout("Gate Exit Validation", 'renderContent', $role, $name);

