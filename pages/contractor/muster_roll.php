<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;

    // Get current contractor ID
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;

    $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Fetch workers
    $workers = db_fetch_all($conn, "SELECT id, name, aadhaar_no, gender FROM workmen WHERE contractor_id = ? AND status='active'", 'i', [$contractor_id]);

    // Fetch attendance for this month
    $attendance_data = [];
    $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-$days_in_month";
    
    $attendance_query = db_fetch_all($conn, "
        SELECT worker_id, DAY(attendance_date) as day, status 
        FROM attendance 
        WHERE worker_id IN (SELECT id FROM workmen WHERE contractor_id = ?) 
        AND attendance_date BETWEEN ? AND ?
    ", 'iss', [$contractor_id, $start_date, $end_date]);

    foreach ($attendance_query as $row) {
        $attendance_data[$row['worker_id']][$row['day']] = $row['status'];
    }

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
?>
<style>
    .muster-table-container {
        overflow-x: auto;
        margin-top: 20px;
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }
    .muster-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .muster-table th, .muster-table td {
        border: 1px solid #e2e8f0;
        padding: 6px 4px;
        text-align: center;
        min-width: 25px;
    }
    .muster-table th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #475569;
    }
    .muster-table .worker-info {
        text-align: left;
        min-width: 150px;
        position: sticky;
        left: 0;
        background: white;
        z-index: 10;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    }
    .status-P { color: #10b981; font-weight: bold; }
    .status-A { color: #ef4444; font-weight: bold; }
    .status-H { color: #6366f1; font-weight: bold; }
    .filter-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
    }
</style>

<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-calendar-alt" style="color:#6366f1;margin-right:10px;"></i> Monthly Muster Roll</h2>
        <p class="page-subtitle">Statutory attendance record for contract labour</p>
    </div>
    <div class="action-buttons">
        <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print Register</button>
        <button class="btn btn-primary"><i class="fas fa-file-excel"></i> Export Excel</button>
    </div>
</div>

<div class="filter-card glass">
    <form method="GET" class="form-grid-3" style="align-items: flex-end;">
        <div class="form-group">
            <label class="form-label">Select Month</label>
            <select name="month" class="form-control">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Select Year</label>
            <select name="year" class="form-control">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Generate View</button>
        </div>
    </form>
</div>

<div class="muster-table-container glass">
    <table class="muster-table">
        <thead>
            <tr>
                <th rowspan="2" class="worker-info">Worker Name</th>
                <th rowspan="2">Aadhaar / ID</th>
                <th colspan="<?= $days_in_month ?>">Days of the Month (<?= $months[$month] ?> <?= $year ?>)</th>
                <th colspan="3">Summary</th>
            </tr>
            <tr>
                <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                    <th><?= $d ?></th>
                <?php endfor; ?>
                <th>P</th>
                <th>A</th>
                <th>T</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($workers)): ?>
                <tr>
                    <td colspan="<?= $days_in_month + 5 ?>" style="padding: 40px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-user-slash" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                        No active workers found in your record.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($workers as $w): ?>
                    <?php 
                        $p_count = 0;
                        $a_count = 0;
                    ?>
                    <tr>
                        <td class="worker-info"><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                        <td><?= htmlspecialchars($w['aadhaar_no']) ?></td>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                            <?php 
                                $status = $attendance_data[$w['id']][$d] ?? '-'; 
                                if ($status == 'Present') { $p_count++; $char = 'P'; }
                                elseif ($status == 'Absent') { $a_count++; $char = 'A'; }
                                elseif ($status == 'Holiday') { $char = 'H'; }
                                else { $char = '-'; }
                            ?>
                            <td class="status-<?= $char ?>"><?= $char ?></td>
                        <?php endfor; ?>
                        <td style="background: #f0fdf4; font-weight: 700; color: #166534;"><?= $p_count ?></td>
                        <td style="background: #fef2f2; font-weight: 700; color: #991b1b;"><?= $a_count ?></td>
                        <td style="background: #f8fafc; font-weight: 700;"><?= $p_count + $a_count ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; display: flex; gap: 20px; font-size: 12px; color: #64748b;">
    <span><strong class="status-P">P</strong>: Present</span>
    <span><strong class="status-A">A</strong>: Absent</span>
    <span><strong class="status-H">H</strong>: Holiday</span>
    <span><strong>-</strong>: No Record</span>
</div>

<?php
}
renderLayout('Muster Roll', 'renderContent', $role, $name);
?>
