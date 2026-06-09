<?php
require_once __DIR__ . '/../../include/auth_middleware.php';
require_once __DIR__ . '/../../include/config.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'welfare', 'welfare_user', 'welfare_admin', 'contractor']);

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'temp';
$action = $_GET['action'] ?? 'view';

if (!$id) {
    die("Invalid request");
}

$query = "SELECT w.*, c.contractor_name 
          FROM workmen w 
          JOIN contractors c ON w.contractor_id = c.id 
          WHERE w.id = ?";
$workman = db_single($conn, $query, 'i', [$id]);

if (!$workman) {
    die("Workman not found");
}

$is_temp = ($type === 'temp');
$pass_number = $is_temp ? ($workman['temp_pass_no'] ?: 'TEMP-PENDING') : ($workman['acc_number'] ?: 'PERM-PENDING');
$validity_from = $is_temp ? ($workman['temp_valid_from'] ?: $workman['valid_from']) : $workman['valid_from'];
$validity_to = $is_temp ? ($workman['temp_valid_to'] ?: $workman['valid_to']) : $workman['valid_to'];
$pass_title = $is_temp ? "TEMPORARY GATE PASS" : "PERMANENT GATE PASS";
$photo = $workman['photo'] ? '../../uploads/workers/' . $workman['photo'] : 'https://via.placeholder.com/150';

$bg_color = $is_temp ? '#fff3cd' : '#d4edda';
$border_color = $is_temp ? '#ffeeba' : '#c3e6cb';
$text_color = $is_temp ? '#856404' : '#155724';

// Dummy QR Code URL based on pass number
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($pass_number);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pass_title ?> - <?= htmlspecialchars($workman['name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fa;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .pass-card {
            background: #fff;
            width: 320px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 2px solid <?= $border_color ?>;
            position: relative;
        }
        .pass-header {
            background: <?= $bg_color ?>;
            color: <?= $text_color ?>;
            text-align: center;
            padding: 15px 10px;
            font-weight: bold;
            font-size: 16px;
            border-bottom: 2px solid <?= $border_color ?>;
        }
        .pass-body {
            padding: 20px;
            text-align: center;
        }
        .photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid <?= $border_color ?>;
            margin-bottom: 15px;
        }
        .worker-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .worker-type {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
            font-size: 12px;
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
        }
        .detail-label {
            color: #777;
            font-size: 10px;
            text-transform: uppercase;
        }
        .detail-value {
            color: #333;
            font-weight: bold;
            margin-top: 2px;
        }
        .qr-container {
            margin-top: 15px;
        }
        .qr-code {
            width: 80px;
            height: 80px;
        }
        .pass-footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-size: 11px;
        }
        .safety-badge {
            position: absolute;
            top: 60px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        @media print {
            body { background: white; padding: 0; align-items: flex-start; }
            .pass-card { box-shadow: none; border-color: #000; }
            .actions { display: none; }
        }
    </style>
</head>
<body>

    <div>
        <div class="pass-card">
            <div class="pass-header">
                <?= $pass_title ?>
            </div>
            <?php if($workman['training_status'] === 'pass' || $workman['training_status'] === 'completed' || $workman['safety_training_status'] == 1): ?>
                <div class="safety-badge">✓ SAFETY CLEARED</div>
            <?php endif; ?>
            <div class="pass-body">
                <img src="<?= htmlspecialchars($photo) ?>" alt="Photo" class="photo" onerror="this.src='https://via.placeholder.com/150'">
                <div class="worker-name"><?= htmlspecialchars($workman['name']) ?></div>
                <div class="worker-type"><?= htmlspecialchars($workman['worker_type']) ?></div>
                
                <div class="details-grid">
                    <div>
                        <div class="detail-label">Pass No</div>
                        <div class="detail-value"><?= htmlspecialchars($pass_number) ?></div>
                    </div>
                    <?php if(!$is_temp && $workman['acc_number']): ?>
                    <div>
                        <div class="detail-label">ACC No</div>
                        <div class="detail-value"><?= htmlspecialchars($workman['acc_number']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="detail-label">Valid From</div>
                        <div class="detail-value"><?= $validity_from ? date('d M Y', strtotime($validity_from)) : 'N/A' ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Valid Till</div>
                        <div class="detail-value"><?= $validity_to ? date('d M Y', strtotime($validity_to)) : 'N/A' ?></div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <div class="detail-label">Contractor</div>
                        <div class="detail-value"><?= htmlspecialchars($workman['contractor_name']) ?></div>
                    </div>
                </div>

                <div class="qr-container">
                    <img src="<?= $qr_url ?>" alt="QR Code" class="qr-code">
                </div>
            </div>
            <div class="pass-footer">
                Issued by CLMS Welfare Department
            </div>
        </div>
        
        <div class="actions">
            <button class="btn" onclick="window.print()">Print / Save as PDF</button>
        </div>
    </div>

    <?php if($action === 'print' || $action === 'download'): ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>

</body>
</html>
