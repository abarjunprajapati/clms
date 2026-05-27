<?php
require_once __DIR__ . '/../include/config.php';

// Handle POST request for Punch IN/OUT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $acc_no = $_POST['acc_no'] ?? '';
    $punch_type = $_POST['punch_type'] ?? '';

    if (empty($acc_no) || !in_array($punch_type, ['IN', 'OUT'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid Request']);
        exit;
    }

    // Check worker eligibility
    $stmt = $conn->prepare("SELECT w.*, c.contractor_name, c.is_blocked as c_blocked, c.status as c_status 
                            FROM workmen w 
                            LEFT JOIN contractors c ON w.contractor_id = c.id
                            WHERE w.acc_number = ? OR w.aadhaar = ? LIMIT 1");
    $stmt->bind_param("ss", $acc_no, $acc_no);
    $stmt->execute();
    $worker = $stmt->get_result()->fetch_assoc();

    if (!$worker) {
        echo json_encode(['success' => false, 'error' => 'Worker not found']);
        exit;
    }

    if ($worker['status'] === 'blocked') {
        echo json_encode(['success' => false, 'error' => 'Worker is BLOCKED']);
        exit;
    }

    if ($worker['c_blocked'] || $worker['c_status'] === 'blocked') {
        echo json_encode(['success' => false, 'error' => 'Contractor is BLOCKED']);
        exit;
    }

    if (!empty($worker['valid_to']) && strtotime($worker['valid_to']) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'error' => 'Gate Pass has EXPIRED']);
        exit;
    }

    // For simplicity of demo, we fetch ACC from worker record if they scanned aadhar
    $final_acc = $worker['acc_number'] ?: $worker['acc_card_number']; // fallback if acc_number is empty
    if (!$final_acc) {
        echo json_encode(['success' => false, 'error' => 'Worker has no ACC Number yet']);
        exit;
    }

    $worker_name = $worker['name'];
    $contractor_name = $worker['contractor_name'] ?: 'Direct';
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $device_id = 'DEMO_DEVICE_01';

    if ($punch_type === 'IN') {
        // Check if already punched IN without OUT
        $check_stmt = $conn->prepare("SELECT id FROM sap_attendance WHERE acc_no = ? AND attendance_date = ? AND punch_status = 'IN' AND out_time IS NULL");
        $check_stmt->bind_param("ss", $final_acc, $today);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Already Punched IN']);
            exit;
        }

        $ins_stmt = $conn->prepare("INSERT INTO sap_attendance (acc_no, worker_name, contractor_name, attendance_date, in_time, attendance_status, punch_status, device_id, sap_sync_status, sync_source) VALUES (?, ?, ?, ?, ?, 'PRESENT', 'IN', ?, 'PENDING', 'DEMO_MACHINE')");
        $ins_stmt->bind_param("ssssss", $final_acc, $worker_name, $contractor_name, $today, $now, $device_id);
        
        if ($ins_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "IN Punch Successful for $worker_name"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }

    } else if ($punch_type === 'OUT') {
        // Find existing IN punch
        $check_stmt = $conn->prepare("SELECT id, in_time FROM sap_attendance WHERE acc_no = ? AND attendance_date = ? AND out_time IS NULL ORDER BY in_time DESC LIMIT 1");
        $check_stmt->bind_param("ss", $final_acc, $today);
        $check_stmt->execute();
        $res = $check_stmt->get_result();

        if ($res->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'No active IN punch found for today']);
            exit;
        }

        $row = $res->fetch_assoc();
        $id = $row['id'];
        
        // Update OUT time and working hours
        $upd_stmt = $conn->prepare("UPDATE sap_attendance SET out_time = ?, working_hours = TIMEDIFF(?, in_time), punch_status = 'OUT', sap_sync_status = 'PENDING' WHERE id = ?");
        $upd_stmt->bind_param("ssi", $now, $now, $id);
        
        if ($upd_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "OUT Punch Successful for $worker_name"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAP Biometric Machine (Demo)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .machine-body { background: #1e293b; padding: 40px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 2px solid #334155; width: 400px; text-align: center; }
        .screen { background: #000; border-radius: 12px; padding: 20px; border: 4px solid #475569; margin-bottom: 25px; min-height: 100px; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; }
        .screen::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 50%; background: linear-gradient(rgba(255,255,255,0.1), transparent); pointer-events: none; }
        .status-text { font-family: monospace; font-size: 16px; color: #10b981; margin: 0; white-space: pre-wrap; word-break: break-all; }
        .status-error { color: #ef4444; }
        .time-display { font-family: monospace; font-size: 28px; color: #e2e8f0; margin-bottom: 10px; font-weight: bold; letter-spacing: 2px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .form-control { width: 100%; padding: 15px; background: #0f172a; border: 1px solid #475569; border-radius: 8px; color: #fff; font-size: 18px; text-align: center; font-family: monospace; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }
        .btn-group { display: flex; gap: 15px; }
        .btn { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s; }
        .btn-in { background: #10b981; color: white; }
        .btn-in:hover { background: #059669; transform: translateY(-2px); }
        .btn-out { background: #ef4444; color: white; }
        .btn-out:hover { background: #dc2626; transform: translateY(-2px); }
        .sensor { width: 60px; height: 60px; border-radius: 50%; border: 3px dashed #3b82f6; margin: 25px auto 10px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 24px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(59,130,246,0.4); } 70% { box-shadow: 0 0 0 15px rgba(59,130,246,0); } 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); } }
    </style>
</head>
<body>
    <div class="machine-body">
        <h3 style="margin:0 0 20px 0; color:#cbd5e1; font-weight:500;"><i class="fas fa-fingerprint"></i> SAP Biometric Demo</h3>
        <div class="screen" id="screen">
            <div class="time-display" id="clock">00:00:00</div>
            <p class="status-text" id="statusMsg">Ready for Scan...</p>
        </div>
        
        <div class="input-group">
            <label>Scan ACC Number / Aadhar</label>
            <input type="text" id="accInput" class="form-control" placeholder="ACC-XXXX-XXXXXX" autofocus autocomplete="off">
        </div>

        <div class="btn-group">
            <button class="btn btn-in" onclick="punch('IN')"><i class="fas fa-sign-in-alt"></i> IN Punch</button>
            <button class="btn btn-out" onclick="punch('OUT')"><i class="fas fa-sign-out-alt"></i> OUT Punch</button>
        </div>

        <div class="sensor"><i class="fas fa-fingerprint"></i></div>
        <p style="font-size:12px; color:#64748b; margin-top:10px;">Place finger on sensor (Demo)</p>
    </div>

    <script>
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('en-US', { hour12: false });
        }, 1000);

        function punch(type) {
            const acc = document.getElementById('accInput').value.trim();
            const screen = document.getElementById('screen');
            const msg = document.getElementById('statusMsg');

            if (!acc) {
                msg.className = 'status-text status-error';
                msg.innerText = 'Error: Please enter ACC No';
                beepError();
                return;
            }

            msg.className = 'status-text';
            msg.innerText = 'Processing SAP Sync...';
            
            const formData = new FormData();
            formData.append('acc_no', acc);
            formData.append('punch_type', type);

            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    msg.className = 'status-text';
                    msg.innerText = `[${type}] SUCCESS\n${data.message}`;
                    beepSuccess();
                    document.getElementById('accInput').value = '';
                } else {
                    msg.className = 'status-text status-error';
                    msg.innerText = `[FAILED]\n${data.error}`;
                    beepError();
                }
                setTimeout(() => {
                    if(msg.innerText.includes('SUCCESS') || msg.innerText.includes('FAILED')) {
                        msg.className = 'status-text';
                        msg.innerText = 'Ready for Scan...';
                    }
                }, 4000);
            })
            .catch(e => {
                msg.className = 'status-text status-error';
                msg.innerText = 'Network/Server Error';
                beepError();
            });
        }

        function beepSuccess() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            osc.type = 'sine'; osc.frequency.setValueAtTime(800, ctx.currentTime);
            osc.connect(ctx.destination); osc.start(); osc.stop(ctx.currentTime + 0.2);
            setTimeout(() => {
                const osc2 = ctx.createOscillator();
                osc2.type = 'sine'; osc2.frequency.setValueAtTime(1000, ctx.currentTime);
                osc2.connect(ctx.destination); osc2.start(); osc2.stop(ctx.currentTime + 0.2);
            }, 250);
        }

        function beepError() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            osc.type = 'sawtooth'; osc.frequency.setValueAtTime(150, ctx.currentTime);
            osc.connect(ctx.destination); osc.start(); osc.stop(ctx.currentTime + 0.5);
        }
    </script>
</body>
</html>
