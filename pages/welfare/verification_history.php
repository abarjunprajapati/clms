<?php
require_once '../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user', 'welfare']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function renderContent() {
    global $conn;

    // 1. Fetch Contractor Status History (2A)
    $status_history = db_fetch_all($conn, "
        SELECT h.*, c.vendor_code, c.vendor_name, u.name as action_by_name
        FROM contractor_status_history h
        JOIN contractors c ON h.contractor_id = c.id
        LEFT JOIN users u ON h.action_by = u.id
        ORDER BY h.action_at DESC
    ");

    // 2. Fetch Employee Compensation Policy History
    $ecp_history = db_fetch_all($conn, "
        SELECT h.*, c.vendor_code, c.vendor_name
        FROM contractor_ecp_history h
        JOIN contractors c ON h.contractor_id = c.id
        ORDER BY h.uploaded_at DESC
    ");

    // 3. Fetch Customer Registration Status & Submission History
    $annexure3a_history = db_fetch_all($conn, "
        SELECT h.*, c.vendor_name as contractor_name, s.customer_name
        FROM contractor_annexure3a_history h
        LEFT JOIN contractors c ON h.vendor_code = c.vendor_code
        LEFT JOIN sap_customer_master s ON h.customer_code = s.customer_code
        ORDER BY h.updated_at DESC
    ");
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-history" style="color:#6366f1;margin-right:10px;"></i>Contractor History & Audit Centre</h2>
        <p class="page-subtitle" style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Monitor historical changes of contractor statuses, Employee Compensation Policies, and Customer Registration compliance logs.</p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-container glass" style="margin-bottom: 24px; padding: 6px; border-radius: 12px; display: inline-flex; gap: 8px;">
    <button class="tab-btn active" onclick="switchTab('status-tab', this)">
        <i class="fas fa-user-check me-2"></i> Registration History (2A)
    </button>
    <button class="tab-btn" onclick="switchTab('ecp-tab', this)">
        <i class="fas fa-file-shield me-2"></i> EC Policy History (Section 5)
    </button>
    <button class="tab-btn" onclick="switchTab('3a-tab', this)">
        <i class="fas fa-file-invoice me-2"></i> Customer Registration History
    </button>
</div>

<!-- Search & Filter Bar -->
<div class="card glass" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 24px; display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; align-items: center; flex: 1; max-width: 400px;">
            <i class="fas fa-search text-muted" style="font-size: 14px;"></i>
            <input type="text" id="historySearch" onkeyup="filterHistoryTables()" placeholder="Search by Contractor Code or Name..." class="form-control-simple" style="width: 100%; border: none; background: transparent; font-size: 14px; outline: none; color: var(--text-primary);">
        </div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">
            <i class="fas fa-info-circle me-1"></i> Showing real-time revision changes
        </div>
    </div>
</div>

<!-- 1. REGISTRATION STATUS HISTORY TAB -->
<div id="status-tab" class="tab-content-panel">
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-list-check"></i> Registration Approval / Status Changes Logs</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table" id="statusTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Contractor Details</th>
                        <th>Status Changed To</th>
                        <th>Remarks / Reason</th>
                        <th>Processed By</th>
                        <th>Approval PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($status_history)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No registration status logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($status_history as $row): ?>
                            <tr class="history-row-data" data-search="<?= htmlspecialchars(strtolower($row['vendor_code'] . ' ' . $row['vendor_name'])) ?>">
                                <td style="font-size: 12.5px;"><?= date('d M Y, h:i A', strtotime($row['action_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['vendor_name']) ?></strong><br>
                                    <code style="font-size: 11.5px;"><?= htmlspecialchars($row['vendor_code']) ?></code>
                                </td>
                                <td>
                                    <?php 
                                    $st = strtolower($row['status']);
                                    $badge = 'badge-secondary';
                                    if ($st === 'approved') $badge = 'badge-success';
                                    elseif ($st === 'rejected' || $st === 'block') $badge = 'badge-danger';
                                    elseif ($st === 'hold') $badge = 'badge-warning';
                                    elseif ($st === 'correction_required') $badge = 'badge-info';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= strtoupper(str_replace('_', ' ', $st)) ?></span>
                                </td>
                                <td style="max-width: 300px; font-size: 12.5px; font-weight: 500; color: var(--text-primary); line-height: 1.4;">
                                    <?= htmlspecialchars($row['reason'] ?: '—') ?>
                                </td>
                                <td style="font-size: 12.5px; font-weight: 600;"><?= htmlspecialchars($row['action_by_name'] ?: 'System') ?></td>
                                <td>
                                    <?php if (!empty($row['pdf_path'])): ?>
                                        <a href="../../uploads/<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-outline text-primary" style="padding: 4px 8px; font-size: 11px; border-radius: 6px;">
                                            <i class="fas fa-file-pdf text-danger me-1"></i> View PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px;">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. EC POLICY HISTORY TAB -->
<div id="ecp-tab" class="tab-content-panel hidden">
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-file-shield"></i> Employee Compensation Policy Revision History</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table" id="ecpTable">
                <thead>
                    <tr>
                        <th>Uploaded Date</th>
                        <th>Contractor Details</th>
                        <th>EC Policy Number</th>
                        <th>Validity Period</th>
                        <th>Workers Count</th>
                        <th>Policy Document</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ecp_history)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No Employee Compensation Policy updates logged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ecp_history as $row): ?>
                            <tr class="history-row-data" data-search="<?= htmlspecialchars(strtolower($row['vendor_code'] . ' ' . $row['vendor_name'])) ?>">
                                <td style="font-size: 12.5px;"><?= date('d M Y, h:i A', strtotime($row['uploaded_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['vendor_name']) ?></strong><br>
                                    <code style="font-size: 11.5px;"><?= htmlspecialchars($row['vendor_code']) ?></code>
                                </td>
                                <td><span style="font-weight: 700; color: #1e3a8a;"><?= htmlspecialchars($row['ecp_number']) ?></span></td>
                                <td style="font-size: 12.5px;">
                                    <div style="font-weight: 500;">From: <?= date('d/m/Y', strtotime($row['ecp_valid_from'])) ?></div>
                                    <div style="font-weight: 500; margin-top: 2px;">To: <?= date('d/m/Y', strtotime($row['ecp_valid_to'])) ?></div>
                                </td>
                                <td><span class="badge badge-success" style="font-size: 11px; font-weight: 700;"><?= $row['workers_ecp'] ?> Workers</span></td>
                                <td>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline text-success" style="padding: 4px 8px; font-size: 11px; border-radius: 6px;">
                                            <i class="fas fa-file-shield me-1"></i> View Certificate
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px;">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. CUSTOMER REGISTRATION HISTORY TAB -->
<div id="3a-tab" class="tab-content-panel hidden">
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-file-contract"></i> Customer Registration Manpower Deployment Submission Logs</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table" id="a3Table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Contractor Name</th>
                        <th>Customer / Client Name</th>
                        <th>Work Order No</th>
                        <th>Insurance / Validity</th>
                        <th>Manpower Engagement</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($annexure3a_history)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted);">No Customer Registration status logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($annexure3a_history as $row): ?>
                            <tr class="history-row-data" data-search="<?= htmlspecialchars(strtolower($row['vendor_code'] . ' ' . $row['contractor_name'] . ' ' . $row['work_order_no'])) ?>">
                                <td style="font-size: 12.5px;"><?= date('d M Y, h:i A', strtotime($row['updated_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['contractor_name'] ?: 'N/A') ?></strong><br>
                                    <code style="font-size: 11px;"><?= htmlspecialchars($row['vendor_code']) ?></code>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['customer_name'] ?: 'N/A') ?></strong><br>
                                    <code style="font-size: 11px;"><?= htmlspecialchars($row['customer_code']) ?></code>
                                </td>
                                <td style="font-weight: 700; font-size: 12.5px;"><?= htmlspecialchars($row['work_order_no'] ?: '—') ?></td>
                                <td style="font-size: 12px; line-height: 1.4;">
                                    <div style="font-weight: 600; color: #1e3a8a;"><?= htmlspecialchars($row['insurance_policy_no'] ?: '—') ?></div>
                                    <div style="font-weight: 500; margin-top: 1px;">Exp: <?= $row['insurance_validity'] ? date('d/m/Y', strtotime($row['insurance_validity'])) : '—' ?></div>
                                </td>
                                <td><span class="badge badge-success" style="font-size: 11px; font-weight: 700;"><?= $row['insurance_workers_count'] ?> Engaged</span></td>
                                <td>
                                    <?php 
                                    $st = strtolower($row['status']);
                                    $badge = 'badge-secondary';
                                    if ($st === 'approved') $badge = 'badge-success';
                                    elseif ($st === 'rejected') $badge = 'badge-danger';
                                    elseif ($st === 'submitted') $badge = 'badge-info';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= strtoupper($st) ?></span>
                                </td>
                                <td style="font-size: 12.5px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($row['reason'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* CSS styles matching modern glass design guidelines */
.tabs-container {
    background: rgba(255, 255, 255, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px -3px rgba(0,0,0,0.05);
}
.tab-btn {
    background: transparent;
    border: none;
    padding: 10px 20px;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-muted);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
}
.tab-btn:hover {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
}
.tab-btn.active {
    color: white;
    background: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}
.tab-content-panel {
    animation: fadeIn 0.3s ease-out;
}
.tab-content-panel.hidden {
    display: none;
}
.form-control-simple {
    border: none;
    outline: none;
    font-weight: 500;
}
.form-control-simple::placeholder {
    color: var(--text-muted);
    opacity: 0.8;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function switchTab(tabId, btn) {
    // Hide all tabs
    document.querySelectorAll('.tab-content-panel').forEach(panel => {
        panel.classList.add('hidden');
    });
    // Remove active class from buttons
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
    });
    // Show select tab & mark active
    document.getElementById(tabId).classList.remove('hidden');
    btn.classList.add('active');
}

function filterHistoryTables() {
    let input = document.getElementById('historySearch');
    let filter = input.value.toLowerCase().trim();
    let rows = document.querySelectorAll('.history-row-data');
    
    rows.forEach(row => {
        let text = row.getAttribute('data-search') || '';
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
<?php
}

renderLayout('Contractor Verification History', 'renderContent', $role, $name);
?>
