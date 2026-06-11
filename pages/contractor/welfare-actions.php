<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';

function welfareActionsColumnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function renderContent() {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? 0;
    $contractorSelect = [
        'id',
        'vendor_code',
        'contractor_name',
        'status',
        welfareActionsColumnExists($conn, 'contractors', 'approval_reason') ? 'approval_reason' : "'' AS approval_reason",
        welfareActionsColumnExists($conn, 'contractors', 'approval_pdf') ? 'approval_pdf' : "'' AS approval_pdf",
        welfareActionsColumnExists($conn, 'contractors', 'last_action_at') ? 'last_action_at' : "'' AS last_action_at",
        welfareActionsColumnExists($conn, 'contractors', 'updated_at') ? 'updated_at' : "'' AS updated_at"
    ];
    $contractorSql = "SELECT " . implode(', ', $contractorSelect) . " FROM contractors";
    $contractor = db_single($conn, $contractorSql . " WHERE user_id = ?", 'i', [$user_id]);
    if (!$contractor && !empty($_SESSION['contractor_id'])) {
        $contractor = db_single($conn, $contractorSql . " WHERE vendor_code = ?", 's', [$_SESSION['contractor_id']]);
    }
    if (!$contractor) {
        echo '<div class="content-header"><h2>No contractor record found for your account.</h2></div>';
        return;
    }
    $cid = intval($contractor['id']);
    $vendor_code_raw = $contractor['vendor_code'] ?? '';
    $vendor_code = htmlspecialchars($vendor_code_raw);

    $fileUrl = function($path) {
        $path = trim((string)$path);
        if ($path === '') return '';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        if (strpos($path, '../../') === 0 || strpos($path, '../') === 0) return $path;
        return '../../uploads/' . ltrim($path, '/\\');
    };

    // Fetch annexure2a history
    $annex_history = db_fetch_all($conn, "SELECT annexure2a_id, status, reason, updated_at FROM contractor_annexure2a_history WHERE contractor_id = ? ORDER BY updated_at DESC", 'i', [$cid]);

    // Fetch contractor status history. Live schema may not have created_at on older installs.
    $statusDateExpr = welfareActionsColumnExists($conn, 'contractor_status_history', 'created_at') ? 'created_at' : "''";
    $statusOrderCol = welfareActionsColumnExists($conn, 'contractor_status_history', 'created_at')
        ? 'created_at'
        : (welfareActionsColumnExists($conn, 'contractor_status_history', 'id') ? 'id' : 'contractor_id');
    $status_history = db_fetch_all(
        $conn,
        "SELECT contractor_id, status, reason, pdf_path, {$statusDateExpr} AS created_at FROM contractor_status_history WHERE contractor_id = ? ORDER BY {$statusOrderCol} DESC",
        'i',
        [$cid]
    );

    $registration_history = [];
    $fallbackDateByStatus = [];
    $fallbackAnyDate = '';
    foreach ($annex_history as $row) {
        $rowStatus = strtolower((string)($row['status'] ?? ''));
        if (!empty($row['updated_at'])) {
            if (!isset($fallbackDateByStatus[$rowStatus])) {
                $fallbackDateByStatus[$rowStatus] = $row['updated_at'];
            }
            if ($fallbackAnyDate === '') {
                $fallbackAnyDate = $row['updated_at'];
            }
        }
    }
    foreach ($status_history as $row) {
        $rowStatus = strtolower((string)($row['status'] ?? ''));
        $rowDate = trim((string)($row['created_at'] ?? ''));
        if ($rowDate === '') {
            $rowDate = $fallbackDateByStatus[$rowStatus] ?? ($contractor['last_action_at'] ?? ($contractor['updated_at'] ?? $fallbackAnyDate));
        }
        $registration_history[] = [
            'date' => $rowDate,
            'status' => $row['status'] ?? '',
            'reason' => $row['reason'] ?? '',
            'file' => $row['pdf_path'] ?? ''
        ];
    }
    if (empty($registration_history)) {
        foreach ($annex_history as $row) {
            $registration_history[] = [
                'date' => $row['updated_at'] ?? '',
                'status' => $row['status'] ?? '',
                'reason' => $row['reason'] ?? '',
                'file' => ''
            ];
        }
    }

    $statusBadge = function($status) {
        $status = strtolower((string)$status);
        if ($status === 'approved') return 'wh-badge wh-badge-success';
        if ($status === 'rejected') return 'wh-badge wh-badge-danger';
        if (in_array($status, ['correction_required', 'hold'], true)) return 'wh-badge wh-badge-warning';
        return 'wh-badge wh-badge-info';
    };

    ?>
    <div class="content-header">
        <h2 class="page-title">Contractor Registration Welfare History</h2>
        <p class="page-subtitle">Reason, rejection date and attachment history for vendor code: <strong><?= $vendor_code ?></strong></p>
    </div>

    <style>
        .wh-card { border-radius: 12px; overflow: hidden; }
        .wh-empty { padding: 34px 18px; text-align: center; color: #64748b; font-weight: 600; }
        .wh-table-wrap { width: 100%; overflow-x: auto; }
        .wh-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .wh-table th { text-align: left; padding: 14px 16px; background: #f8fafc; color: #334155; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e2e8f0; }
        .wh-table td { padding: 15px 16px; border-bottom: 1px solid #eef2f7; color: #1e293b; vertical-align: top; }
        .wh-table tr:last-child td { border-bottom: 0; }
        .wh-reason { white-space: pre-wrap; line-height: 1.5; min-width: 260px; }
        .wh-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; letter-spacing: .03em; }
        .wh-badge-success { background: #dcfce7; color: #166534; }
        .wh-badge-danger { background: #fee2e2; color: #991b1b; }
        .wh-badge-warning { background: #fef3c7; color: #92400e; }
        .wh-badge-info { background: #dbeafe; color: #1e40af; }
        .wh-attachment { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; color: #1d4ed8; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .wh-attachment:hover { background: #eff6ff; text-decoration: none; }
    </style>

    <div class="card glass wh-card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Contractor Registration History</div>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($registration_history)): ?>
                <div class="wh-empty">No Contractor Registration welfare actions recorded yet.</div>
            <?php else: ?>
                <div class="wh-table-wrap">
                    <table class="wh-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Reason / Remarks</th>
                                <th>Attachment</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($registration_history as $row): ?>
                            <tr>
                                <td><?= !empty($row['date']) ? htmlspecialchars(date('d M Y h:i A', strtotime($row['date']))) : '-' ?></td>
                                <td><span class="<?= $statusBadge($row['status']) ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', (string)$row['status']))) ?></span></td>
                                <td class="wh-reason"><?= nl2br(htmlspecialchars($row['reason'] ?: 'No remarks provided.')) ?></td>
                                <td>
                                    <?php if (!empty($row['file'])): ?>
                                        <a class="wh-attachment" href="<?= htmlspecialchars($fileUrl($row['file'])) ?>" target="_blank">
                                            <i class="fas fa-paperclip"></i> View Attachment
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#64748b;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
}

renderLayout('Contractor Registration History', 'renderContent', $role, $name);
?>
