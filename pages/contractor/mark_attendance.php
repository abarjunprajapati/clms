<?php
// pages/contractor/mark_attendance.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'] ?? 'super_admin';
$name = $_SESSION['name'] ?? 'Welfare Officer';

function renderContent() {
    global $conn;
    
    $success_msg = '';
    $error_msg = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acc_number = trim($_POST['acc_number'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $in_time = trim($_POST['in_time'] ?? '');
        $out_time = trim($_POST['out_time'] ?? '');
        $ot_hours = floatval($_POST['ot_hours'] ?? 0.00);

        if (empty($acc_number) || empty($date) || empty($in_time)) {
            $error_msg = 'Please fill all required fields (Card Number, Date, In Time).';
        } else {
            // Find workman
            $workman = db_single($conn, "SELECT id FROM workmen WHERE acc_number = ? LIMIT 1", 's', [$acc_number]);
            if (!$workman) {
                // Try acc_card_number column or generic check
                $workman = db_single($conn, "SELECT id FROM workmen WHERE acc_card_number = ? LIMIT 1", 's', [$acc_number]);
            }

            if (!$workman) {
                $error_msg = "Workman with Card Number '$acc_number' not found in database.";
            } else {
                $workman_id = $workman['id'];
                $check_in = $date . ' ' . $in_time;
                $check_out = !empty($out_time) ? ($date . ' ' . $out_time) : NULL;
                $status = 'present';

                // Insert into local attendance table
                $stmt = $conn->prepare("
                    INSERT INTO attendance 
                        (workman_id, acc_card_number, check_in, check_out, source, device_id, status, ot_hours, created_at)
                    VALUES (?, ?, ?, ?, 'manual', 'test_portal', ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        check_in = VALUES(check_in),
                        check_out = VALUES(check_out),
                        status = VALUES(status),
                        ot_hours = VALUES(ot_hours)
                ");

                if ($stmt) {
                    $stmt->bind_param('issssd', 
                        $workman_id, 
                        $acc_number, 
                        $check_in, 
                        $check_out, 
                        $status, 
                        $ot_hours
                    );
                    if ($stmt->execute()) {
                        $success_msg = "Attendance marked successfully for Worker Card $acc_number on $date!";
                    } else {
                        $error_msg = "Database Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Database Preparation Error: " . $conn->error;
                }
            }
        }
    }

    // Fetch list of workmen with card numbers for easy selection
    $workmen_list = db_fetch_all($conn, "SELECT name, acc_number FROM workmen WHERE acc_number IS NOT NULL AND acc_number != '' ORDER BY name ASC");
    ?>
    <style>
        .form-card {
            max-width: 550px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-calendar-check" style="color:#6366f1; margin-right:10px;"></i> Manual Attendance Marker</h2>
            <p class="page-subtitle">Add/mark test attendance and overtime entries for any worker to populate the Muster Roll grid.</p>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success" style="max-width:550px; margin: 0 auto 20px auto;"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger" style="max-width:550px; margin: 0 auto 20px auto;"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="form-card glass">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Select Worker</label>
                <select name="acc_number" class="form-control" required onchange="document.getElementById('manualCardNo').value = this.value">
                    <option value="">-- Choose registered worker --</option>
                    <?php foreach ($workmen_list as $w): ?>
                        <option value="<?= htmlspecialchars($w['acc_number']) ?>"><?= htmlspecialchars($w['name']) ?> (ACC: <?= htmlspecialchars($w['acc_number']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="form-label">Or Enter Card Number Manually</label>
                <input type="text" id="manualCardNo" name="acc_number" class="form-control" placeholder="e.g. 00000002" required>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="form-label">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="grid-2" style="margin-top:15px; display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label class="form-label">Check In Time</label>
                    <input type="time" name="in_time" class="form-control" value="08:00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Check Out Time</label>
                    <input type="time" name="out_time" class="form-control" value="17:00">
                </div>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="form-label">Overtime Hours (OT Hours)</label>
                <input type="number" name="ot_hours" class="form-control" step="0.1" min="0" value="0.0" placeholder="e.g. 1.5">
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                    <i class="fas fa-check-circle"></i> Save Attendance Entry
                </button>
            </div>
        </form>
    </div>
    <?php
}

renderLayout("Manual Attendance", 'renderContent', $role, $name);
?>
