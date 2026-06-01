<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['customer']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Customer';

function renderContent() {
    global $conn;
    $customer_code = $_SESSION['customer_code'] ?? '';
    if (empty($customer_code)) {
        echo '<div class="content-header"><h2>No customer code found in session.</h2></div>';
        return;
    }

    $fileUrl = function($path) {
        $path = trim((string)$path);
        if ($path === '') return '';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        if (strpos($path, '../../') === 0 || strpos($path, '../') === 0) return $path;
        return '../../uploads/' . ltrim($path, '/\\');
    };

    // History of Customer Registration actions for this customer
    $hist_sql = "SELECT h.annexure3a_id, h.vendor_code, h.work_order_no, h.status, h.reason, h.updated_at,
                        a.approval_reason, a.approval_file, a.verified_at
                 FROM contractor_annexure3a_history h
                 LEFT JOIN contractor_annexure3a a ON a.id = h.annexure3a_id
                 WHERE h.customer_code = ?
                 ORDER BY COALESCE(a.verified_at, h.updated_at) DESC";
    $annex_history = db_fetch_all($conn, $hist_sql, 's', [$customer_code]);

    // Documents related to Customer Registration for this customer
    $docs_sql = "SELECT d.* , a.vendor_code, a.work_order_no
                 FROM contractor_documents d
                 JOIN contractor_annexure3a a ON a.id = d.annexure3a_id
                 WHERE a.customer_code = ?
                 ORDER BY d.uploaded_at DESC";
    $docs = db_fetch_all($conn, $docs_sql, 's', [$customer_code]);

    $statusClass = function($status) {
        $status = strtolower(trim((string)$status));
        if ($status === 'approved') return 'success';
        if ($status === 'rejected') return 'danger';
        if (in_array($status, ['pending', 'submitted', 'resubmitted', 'correction_required', 'hold'], true)) return 'warning';
        return 'info';
    };
    $formatDate = function($value) {
        if (empty($value)) return '-';
        $time = strtotime($value);
        return $time ? date('d M Y, h:i A', $time) : $value;
    };
    $approvedCount = 0;
    $pendingCount = 0;
    foreach ($annex_history as $row) {
        $st = strtolower(trim((string)($row['status'] ?? '')));
        if ($st === 'approved') {
            $approvedCount++;
        } elseif ($st !== '') {
            $pendingCount++;
        }
    }

    ?>
    <style>
        .welfare-page { display: flex; flex-direction: column; gap: 16px; }
        .welfare-hero {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-end;
            padding: 4px 0 2px;
        }
        .welfare-title { margin: 0; font-size: 24px; font-weight: 850; color: #0f172a; }
        .welfare-subtitle { margin: 6px 0 0; color: #64748b; font-size: 13px; }
        .welfare-code {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: 800;
            font-size: 12px;
            white-space: nowrap;
        }
        .welfare-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .welfare-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .welfare-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ecfeff;
            color: #0e7490;
            flex: 0 0 38px;
        }
        .welfare-stat strong { display: block; color: #0f172a; font-size: 22px; line-height: 1; }
        .welfare-stat span { display: block; margin-top: 4px; color: #64748b; font-size: 12px; font-weight: 700; }
        .welfare-panel {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .welfare-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #edf2f7;
            background: #fbfdff;
        }
        .welfare-panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 16px;
            font-weight: 850;
            color: #1e293b;
        }
        .welfare-panel-title i {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e0f2fe;
            color: #0369a1;
        }
        .welfare-count { color: #64748b; font-size: 12px; font-weight: 800; }
        .welfare-table-wrap { overflow-x: auto; }
        .welfare-table { width: 100%; border-collapse: collapse; min-width: 820px; }
        .welfare-table th {
            padding: 11px 16px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .welfare-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: top;
        }
        .welfare-table tr:last-child td { border-bottom: none; }
        .welfare-table tr:hover td { background: #f8fafc; }
        .welfare-meta { color: #64748b; font-size: 12px; margin-top: 3px; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .status-success { background: #dcfce7; color: #166534; }
        .status-danger { background: #fee2e2; color: #991b1b; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-info { background: #dbeafe; color: #1e40af; }
        .welfare-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }
        .welfare-link:hover { background: #dbeafe; color: #1e40af; }
        .empty-state {
            padding: 34px 18px;
            text-align: center;
            color: #64748b;
        }
        .empty-state i {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .empty-state strong { display: block; color: #334155; margin-bottom: 4px; }
        @media (max-width: 900px) {
            .welfare-hero { align-items: flex-start; flex-direction: column; }
            .welfare-stats { grid-template-columns: 1fr; }
        }
    </style>

    <div class="welfare-page">
        <div class="welfare-hero">
            <div>
                <h2 class="welfare-title">Welfare Action History</h2>
                <p class="welfare-subtitle">Track approval actions, remarks and uploaded documents from Welfare.</p>
            </div>
            <div class="welfare-code"><i class="fas fa-barcode"></i> Customer Code: <?= htmlspecialchars($customer_code) ?></div>
        </div>

        <div class="welfare-stats">
            <div class="welfare-stat">
                <div class="welfare-stat-icon"><i class="fas fa-clock-rotate-left"></i></div>
                <div><strong><?= count($annex_history) ?></strong><span>Total Actions</span></div>
            </div>
            <div class="welfare-stat">
                <div class="welfare-stat-icon" style="background:#dcfce7;color:#166534;"><i class="fas fa-check"></i></div>
                <div><strong><?= $approvedCount ?></strong><span>Approved Actions</span></div>
            </div>
            <div class="welfare-stat">
                <div class="welfare-stat-icon" style="background:#fef3c7;color:#92400e;"><i class="fas fa-file-lines"></i></div>
                <div><strong><?= count($docs) ?></strong><span>Documents</span></div>
            </div>
        </div>

        <section class="welfare-panel">
            <div class="welfare-panel-head">
                <h3 class="welfare-panel-title"><i class="fas fa-list-check"></i> Action History</h3>
                <span class="welfare-count"><?= count($annex_history) ?> records</span>
            </div>
            <?php if (empty($annex_history)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <strong>No actions recorded yet</strong>
                    <span>Welfare decisions and remarks will appear here.</span>
                </div>
            <?php else: ?>
                <div class="welfare-table-wrap">
                    <table class="welfare-table">
                        <thead><tr><th>Date & Time</th><th>Contractor / WO</th><th>Action</th><th>Reason / Remarks</th><th>Attachment</th></tr></thead>
                        <tbody>
                        <?php foreach ($annex_history as $h): ?>
                            <?php
                                $when = $h['verified_at'] ?: ($h['updated_at'] ?? '');
                                $status = strtolower(trim((string)($h['status'] ?? '')));
                                $reason = trim((string)($h['approval_reason'] ?: ($h['reason'] ?? '')));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($formatDate($when)) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($h['vendor_code'] ?: '-') ?></strong>
                                    <div class="welfare-meta">WO: <?= htmlspecialchars($h['work_order_no'] ?: '-') ?></div>
                                </td>
                                <td><span class="status-pill status-<?= htmlspecialchars($statusClass($status)) ?>"><?= htmlspecialchars($status ?: 'updated') ?></span></td>
                                <td><?= $reason !== '' ? nl2br(htmlspecialchars($reason)) : '<span class="welfare-meta">No remarks</span>' ?></td>
                                <td>
                                    <?php if (!empty($h['approval_file'])): ?>
                                        <a class="welfare-link" href="<?= htmlspecialchars($fileUrl($h['approval_file'])) ?>" target="_blank"><i class="fas fa-paperclip"></i> View</a>
                                    <?php else: ?>
                                        <span class="welfare-meta">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="welfare-panel">
            <div class="welfare-panel-head">
                <h3 class="welfare-panel-title"><i class="fas fa-folder-open"></i> Documents</h3>
                <span class="welfare-count"><?= count($docs) ?> files</span>
            </div>
            <?php if (empty($docs)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-circle-xmark"></i>
                    <strong>No documents found</strong>
                    <span>Submitted or welfare-attached documents will be listed here.</span>
                </div>
            <?php else: ?>
                <div class="welfare-table-wrap">
                    <table class="welfare-table">
                        <thead><tr><th>Uploaded</th><th>Contractor / WO</th><th>Document Type</th><th>File</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($docs as $d): ?>
                            <?php $docStatus = strtolower(trim((string)($d['status'] ?? ''))); ?>
                            <tr>
                                <td><?= htmlspecialchars($formatDate($d['uploaded_at'] ?? '')) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($d['vendor_code'] ?: '-') ?></strong>
                                    <div class="welfare-meta">WO: <?= htmlspecialchars($d['work_order_no'] ?: '-') ?></div>
                                </td>
                                <td><?= htmlspecialchars($d['doc_type'] ?: '-') ?></td>
                                <td>
                                    <?php if (!empty($d['file_path'])): ?>
                                        <a class="welfare-link" href="<?= htmlspecialchars($fileUrl($d['file_path'])) ?>" target="_blank"><i class="fas fa-file-arrow-down"></i> <?= htmlspecialchars($d['original_name'] ?: basename($d['file_path'])) ?></a>
                                    <?php else: ?>
                                        <span class="welfare-meta">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-pill status-<?= htmlspecialchars($statusClass($docStatus)) ?>"><?= htmlspecialchars($docStatus ?: 'pending') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php
}

renderLayout('Welfare Actions - 3A', 'renderContent', $role, $name);
?>
