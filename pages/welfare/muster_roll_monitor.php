<?php
// pages/welfare/muster_roll_monitor.php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'welfare', 'super_admin', 'admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

// Auto-run schema checks
ensureComplianceSchema($conn);

function renderContent() {
    global $conn;

    $filter_status = $_GET['status'] ?? 'pending';
    $filter_month = $_GET['month_year'] ?? '';

    $where_clauses = [];
    $params = [];
    $types = '';

    if ($filter_status !== 'all') {
        $where_clauses[] = "mr.status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }

    if (!empty($filter_month)) {
        $where_clauses[] = "mr.month_year = ?";
        $params[] = $filter_month;
        $types .= 's';
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    $query = "
        SELECT mr.*, c.contractor_name, c.vendor_code, u.name as verifier_name
        FROM muster_rolls mr
        JOIN contractors c ON mr.contractor_id = c.id
        LEFT JOIN users u ON mr.verified_by = u.id
        $where_sql
        ORDER BY mr.uploaded_at DESC
    ";

    $submissions = db_fetch_all($conn, $query, $types, $params);

    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June',
        '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
?>
<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-verified { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
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
        <h2 class="page-title"><i class="fas fa-file-shield" style="color:#10b981;margin-right:10px;"></i> Muster Roll Verification</h2>
        <p class="page-subtitle">Verify statutory Muster Roll signatures and attendance records submitted by contractors.</p>
    </div>
</div>

<div class="filter-card glass">
    <form method="GET" class="form-grid-3" style="align-items: flex-end;">
        <div class="form-group">
            <label class="form-label">Filter by Status</label>
            <select name="status" class="form-control">
                <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                <option value="verified" <?= $filter_status == 'verified' ? 'selected' : '' ?>>Verified & Approved</option>
                <option value="rejected" <?= $filter_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Filter by Wage Month</label>
            <input type="month" name="month_year" class="form-control" value="<?= htmlspecialchars($filter_month) ?>">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter Submissions</button>
        </div>
    </form>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title">Muster Roll Submissions Desk</div>
    </div>
    <div class="card-body" style="padding:0">
        <table class="table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Contractor / Vendor</th>
                    <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Wage Month</th>
                    <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Uploaded At</th>
                    <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Status</th>
                    <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Verifier Remarks</th>
                    <th style="padding:12px; text-align:center; border-bottom:1px solid #e2e8f0;">Action Desk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="6" style="padding:40px; text-align:center; color:#94a3b8;">
                            <i class="fas fa-folder-open" style="font-size:36px; display:block; margin-bottom:10px;"></i>
                            No Muster Roll submissions found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $row): ?>
                        <tr>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0;">
                                <strong><?= htmlspecialchars($row['contractor_name']) ?></strong><br>
                                <span style="font-size:11px; color:#64748b;">Code: <?= htmlspecialchars($row['vendor_code']) ?></span>
                            </td>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0;">
                                <strong>
                                    <?php 
                                    $parts = explode('-', $row['month_year']);
                                    echo $months[$parts[1]] . ' ' . $parts[0];
                                    ?>
                                </strong>
                            </td>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#475569;">
                                <?= date('d-M-Y H:i', strtotime($row['uploaded_at'])) ?>
                            </td>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0;">
                                <span class="status-badge status-<?= $row['status'] ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#475569;">
                                <?php if ($row['status'] === 'verified'): ?>
                                    <span style="color:#059669;"><i class="fas fa-check-circle"></i> Approved by <?= htmlspecialchars($row['verifier_name'] ?? 'System') ?></span>
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <span style="color:#dc2626;"><i class="fas fa-times-circle"></i> Rejected: <?= htmlspecialchars($row['remarks']) ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Awaiting Verification</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px; border-bottom:1px solid #e2e8f0; text-align:center;">
                                <div style="display:flex; justify-content:center; gap:8px;">
                                    <a href="<?= BASE_URL . htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-outline" style="padding:5px 10px; font-size:12px;">
                                        <i class="fas fa-eye"></i> View Doc
                                    </a>
                                    <a href="../contractor/download_muster_roll.php?contractor_id=<?= $row['contractor_id'] ?>&month=<?= $parts[1] ?>&year=<?= $parts[0] ?>" target="_blank" class="btn btn-outline" style="padding:5px 10px; font-size:12px; border-color:#6366f1; color:#6366f1;">
                                        <i class="fas fa-print"></i> System Grid
                                    </a>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <button class="btn btn-primary" style="padding:5px 10px; font-size:12px; background:#10b981;" onclick="verifyRoll(<?= $row['id'] ?>)">
                                            <i class="fas fa-check"></i> Verify
                                        </button>
                                        <button class="btn" style="padding:5px 10px; font-size:12px; background:#ef4444; color:white;" onclick="rejectRoll(<?= $row['id'] ?>)">
                                            <i class="fas fa-ban"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verifyRoll(id) {
    Swal.fire({
        title: 'Approve Muster Roll?',
        text: 'Are you sure you have verified the uploaded document signatures and attendance details?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            submitAction(id, 'verify', '');
        }
    });
}

function rejectRoll(id) {
    Swal.fire({
        title: 'Reject Muster Roll',
        input: 'textarea',
        inputLabel: 'Reason for rejection',
        inputPlaceholder: 'Type reason here...',
        inputAttributes: {
            'aria-label': 'Type your reason here'
        },
        showCancelButton: true,
        confirmButtonText: 'Reject Submission',
        confirmButtonColor: '#ef4444',
        preConfirm: (remarks) => {
            if (!remarks) {
                Swal.showValidationMessage('Please enter a reason for rejection');
            }
            return remarks;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitAction(id, 'reject', result.value);
        }
    });
}

function submitAction(id, action, remarks) {
    Swal.fire({
        title: 'Processing...',
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('../../api/welfare/action_muster_roll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
        },
        body: JSON.stringify({
            id: id,
            action: action,
            remarks: remarks
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success', data.message || 'Muster Roll updated successfully.', 'success')
            .then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to update Muster Roll status.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'An unexpected connection error occurred.', 'error');
    });
}
</script>

<?php
}

renderLayout('Muster Roll Verification', 'renderContent', $role, $name);
?>
