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

    $edit_requests = db_fetch_all($conn, "SELECT * FROM contractor_edit_requests WHERE contractor_id = ? ORDER BY submitted_at DESC", 'i', [$cid]);

    $statusBadge = function($status) {
        $status = strtolower((string)$status);
        if ($status === 'approved') return 'wh-badge wh-badge-success';
        if ($status === 'rejected') return 'wh-badge wh-badge-danger';
        if (in_array($status, ['correction_required', 'hold', 'pending'], true)) return 'wh-badge wh-badge-warning';
        return 'wh-badge wh-badge-info';
    };

    $getJsonDiff = function($originalJson, $requestedJson) {
        $original = json_decode($originalJson, true) ?: [];
        $requested = json_decode($requestedJson, true) ?: [];
        
        $changes = [];
        
        $fieldLabels = [
            'mobile' => 'Mobile No',
            'vendor_mob2' => 'Alternate Mobile',
            'email' => 'Email',
            'address' => 'Office Address',
            'work_awarding_department' => 'Project Name / Department',
            'epf_registered' => 'EPF Registered',
            'epf_code' => 'EPF Code',
            'esi_registered' => 'ESI Registered',
            'esi_code' => 'ESI Code',
            'epf_esi_exemption_reason' => 'EPF/ESI Exemption Reason',
            'wage_category' => 'Wage Category',
            'wage_declaration' => 'Wage Declaration File',
            'ecp_covered' => 'EC Policy Covered',
            'ecp_details_json' => 'EC Policy Details',
            'license_details_json' => 'Labour License Details',
            'ecp_number' => 'EC Policy Number',
            'ecp_valid_from' => 'EC Policy Valid From',
            'ecp_valid_to' => 'EC Policy Valid To',
            'workers_ecp' => 'Workers under EC Policy',
            'workers_proposed_to_be_engaged' => 'Max Workers Proposed',
            'worker_category' => 'Worker Categories',
            'license_no' => 'Labour License No',
            'license_issued' => 'Labour License Issued To',
            'issued_date' => 'Labour License Issue Date',
            'expiry_date' => 'Labour License Expiry Date',
            'license_file' => 'Labour License File',
            'labour_license_appl_no' => 'Labour License Appl. No',
            'labour_identification_no' => 'LIN / LARR No',
            'contact_person' => 'Contact Person',
            'remarks' => 'Contractor Remarks',
            'selected_pos' => 'Selected POs',
            'selected_pwos' => 'Selected PWOs',
            'selected_sales' => 'Selected Sales Orders'
        ];
        
        foreach ($requested as $key => $newVal) {
            $oldVal = $original[$key] ?? '';
            
            $newValStr = is_array($newVal) ? json_encode($newVal) : (string)$newVal;
            $oldValStr = is_array($oldVal) ? json_encode($oldVal) : (string)$oldVal;
            
            if (trim((string)$newValStr) !== trim((string)$oldValStr)) {
                $label = $fieldLabels[$key] ?? $key;
                
                $formatValue = function($val, $key) {
                    if (empty($val)) return '<span style="color:#64748b;font-style:italic;">None</span>';
                    if ($key === 'ecp_details_json' || $key === 'license_details_json') {
                        $decoded = is_string($val) ? json_decode($val, true) : $val;
                        if (is_array($decoded)) {
                            $lines = [];
                            foreach ($decoded as $idx => $row) {
                                $rowParts = [];
                                foreach ($row as $k => $v) {
                                    if (!empty($v)) {
                                        $rowParts[] = "<strong>" . htmlspecialchars($k) . ":</strong> " . htmlspecialchars($v);
                                    }
                                }
                                if (!empty($rowParts)) {
                                    $lines[] = "#" . ($idx + 1) . ": " . implode(', ', $rowParts);
                                }
                            }
                            return implode('<br>', $lines);
                        }
                    }
                    if ($key === 'selected_pos' || $key === 'selected_pwos' || $key === 'selected_sales') {
                        $decoded = is_string($val) ? json_decode($val, true) : $val;
                        if (is_array($decoded)) {
                            return implode(', ', array_map('htmlspecialchars', $decoded));
                        }
                    }
                    if (is_array($val)) {
                        return htmlspecialchars(json_encode($val));
                    }
                    if (($key === 'license_file' || $key === 'wage_declaration') && !empty($val)) {
                        $fileUrl = function($path) {
                            $path = trim((string)$path);
                            if ($path === '') return '';
                            if (preg_match('/^https?:\/\//i', $path)) return $path;
                            if (strpos($path, '../../') === 0 || strpos($path, '../') === 0) return $path;
                            return '../../uploads/' . ltrim($path, '/\\');
                        };
                        $url = $fileUrl($val);
                        return '<a href="' . htmlspecialchars($url) . '" target="_blank" style="color:#1d4ed8;font-weight:600;"><i class="fas fa-file-pdf"></i> View File</a>';
                    }
                    return htmlspecialchars((string)$val);
                };
                
                $changes[] = [
                    'field' => $label,
                    'old' => $formatValue($oldVal, $key),
                    'new' => $formatValue($newVal, $key)
                ];
            }
        }
        return $changes;
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
        .wh-reason { white-space: pre-wrap; line-height: 1.5; min-width: 200px; }
        .wh-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; letter-spacing: .03em; }
        .wh-badge-success { background: #dcfce7; color: #166534; }
        .wh-badge-danger { background: #fee2e2; color: #991b1b; }
        .wh-badge-warning { background: #fef3c7; color: #92400e; }
        .wh-badge-info { background: #dbeafe; color: #1e40af; }
        .wh-attachment { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; color: #1d4ed8; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .wh-attachment:hover { background: #eff6ff; text-decoration: none; }
        .diff-table { font-size: 12px; margin-bottom: 0; width: 100%; border-collapse: collapse; background: #fff; }
        .diff-table th { padding: 6px 10px; font-weight: 700; font-size: 11px; border: 1px solid #cbd5e1; background: #f1f5f9; text-transform: none; letter-spacing: 0; }
        .diff-table td { padding: 6px 10px; border: 1px solid #e2e8f0; line-height: 1.4; }
    </style>

    <div class="card glass wh-card" style="margin-bottom:24px;">
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

    <div class="card glass wh-card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-edit"></i> Profile Change Requests History</div>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($edit_requests)): ?>
                <div class="wh-empty">No profile change requests recorded yet.</div>
            <?php else: ?>
                <div class="wh-table-wrap">
                    <table class="wh-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Submission & Action Dates</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 45%;">Changed Fields (Previous vs Updated)</th>
                                <th style="width: 20%;">Welfare Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($edit_requests as $req): 
                            $diffs = $getJsonDiff($req['original_data_json'], $req['requested_data_json']);
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Requested:</div>
                                    <div style="margin-bottom: 10px; font-weight: 500;"><?= !empty($req['submitted_at']) ? htmlspecialchars(date('d M Y h:i A', strtotime($req['submitted_at']))) : '-' ?></div>
                                    
                                    <?php if ($req['status'] !== 'pending'): ?>
                                        <div style="font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Action Date:</div>
                                        <div style="font-weight: 500;"><?= !empty($req['action_at']) ? htmlspecialchars(date('d M Y h:i A', strtotime($req['action_at']))) : '-' ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?= $statusBadge($req['status']) ?>">
                                        <?= htmlspecialchars(strtoupper($req['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (empty($diffs)): ?>
                                        <span style="color:#64748b; font-style:italic;">No changes detected.</span>
                                    <?php else: ?>
                                        <div style="overflow-x: auto; border: 1px solid #cbd5e1; border-radius: 6px;">
                                            <table class="diff-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 30%;">Field</th>
                                                        <th style="width: 35%; background: #fee2e2; color: #991b1b;">Previous Value</th>
                                                        <th style="width: 35%; background: #dcfce7; color: #166534;">Updated Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($diffs as $diff): ?>
                                                        <tr>
                                                            <td style="font-weight: 600; background: #fafafa;"><?= htmlspecialchars($diff['field']) ?></td>
                                                            <td style="background: #fff5f5; color: #3f0f0f;"><?= $diff['old'] ?></td>
                                                            <td style="background: #f6fff6; color: #0f3f0f;"><?= $diff['new'] ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="wh-reason"><?= nl2br(htmlspecialchars($req['remarks'] ?: 'No remarks provided.')) ?></td>
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
