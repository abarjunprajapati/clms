<?php
// pages/contractor/muster_roll.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

// Auto-run schema checks
ensureComplianceSchema($conn);

function renderContent() {
    global $conn, $user_id;

    // Get contractor ID
    $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;

    $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT);
    $selected_month_year = "$year-$month_str";
    
    // Fetch active workmen count
    $workers_count = db_single($conn, "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')", 'i', [$contractor_id])['cnt'] ?? 0;

    // Fetch history of submissions
    $history = db_fetch_all($conn, "
        SELECT mr.*, u.name as verifier_name 
        FROM muster_rolls mr
        LEFT JOIN users u ON mr.verified_by = u.id
        WHERE mr.contractor_id = ? 
        ORDER BY mr.month_year DESC
    ", 'i', [$contractor_id]);

    // Check status of current month
    $current_status = 'Not Submitted';
    $current_record = null;
    foreach ($history as $h) {
        if ($h['month_year'] === $selected_month_year) {
            $current_record = $h;
            if ($h['status'] === 'pending') {
                $current_status = 'Pending Verification';
            } elseif ($h['status'] === 'verified') {
                $current_status = 'Verified';
            } elseif ($h['status'] === 'rejected') {
                $current_status = 'Rejected';
            }
            break;
        }
    }

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
?>
<style>
    .muster-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-top: 20px;
    }
    @media (min-width: 1024px) {
        .muster-container {
            grid-template-columns: 2fr 1fr;
        }
    }
    .card-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
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
    .status-not_submitted { background: #f1f5f9; color: #475569; }

    .upload-box {
        border: 2px dashed #cbd5e1;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
    }
    .upload-box:hover {
        border-color: #6366f1;
        background: #f0f2fe;
    }
    .file-preview {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f1f5f9;
        padding: 10px;
        border-radius: 8px;
        margin-top: 10px;
    }
</style>

<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-file-invoice" style="color:#6366f1;margin-right:10px;"></i> Muster Roll</h2>
        <p class="page-subtitle">Download daily attendance logs, obtain employee signatures, and upload the verified copy.</p>
    </div>
</div>

<div class="grid-3" style="margin-bottom: 24px;">
    <div class="card glass">
        <div class="card-body">
            <h4 style="margin:0 0 5px 0; color: #64748b;">Selected Month</h4>
            <h2 style="margin:0; font-size: 24px; color: #1e293b;"><?= $months[$month] ?> <?= $year ?></h2>
            <div style="margin-top:10px;">
                <form method="GET" style="display:flex; gap:10px;">
                    <select name="month" class="form-control" style="padding: 6px 10px; font-size:12px;">
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="year" class="form-control" style="padding: 6px 10px; font-size:12px;">
                        <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size:12px;">Go</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card glass">
        <div class="card-body">
            <h4 style="margin:0 0 5px 0; color: #64748b;">Active Workmen</h4>
            <h2 style="margin:0; font-size: 24px; color: #1e293b;"><?= $workers_count ?></h2>
            <p style="margin: 5px 0 0 0; font-size:12px; color: #64748b;">Enrolled in active contract lifecycle</p>
        </div>
    </div>

    <div class="card glass">
        <div class="card-body">
            <h4 style="margin:0 0 5px 0; color: #64748b;">Muster Roll Status</h4>
            <div style="margin-top: 10px;">
                <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $current_status)) ?>">
                    <?= $current_status ?>
                </span>
            </div>
            <?php if ($current_record && $current_record['status'] === 'rejected'): ?>
                <p style="margin:8px 0 0 0; color:#ef4444; font-size:11px; font-weight:bold;">
                    Reason: <?= htmlspecialchars($current_record['remarks']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="muster-container">
    <div>
        <!-- Main Actions & Live Verification -->
        <div class="card glass" style="margin-bottom: 24px;">
            <div class="card-header">
                <div class="card-title">Muster Roll Submission Desk</div>
            </div>
            <div class="card-body">
                <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                    As per the statutory compliance, please follow these steps to file your monthly Muster Roll:
                </p>
                <ol style="font-size: 13px; line-height: 1.8; color: #475569; padding-left: 20px;">
                    <li><strong>Download & Print:</strong> Generate the printable Muster Roll sheet showing daily attendance and overtime logs.</li>
                    <li><strong>Signatures:</strong> Get signatures/thumb impressions from all workmen listed in the grid.</li>
                    <li><strong>Upload Copy:</strong> Upload the scanned, fully signed copy here before the <strong>10th of next month</strong>.</li>
                </ol>

                <div class="card-actions">
                    <a href="download_muster_roll.php?month=<?= $month ?>&year=<?= $year ?>" target="_blank" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print Muster Sheet
                    </a>
                </div>
            </div>
        </div>

        <!-- Submission History Table -->
        <div class="card glass">
            <div class="card-header">
                <div class="card-title">Submission History & Records</div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Month</th>
                            <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Uploaded At</th>
                            <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Status</th>
                            <th style="padding:12px; text-align:left; border-bottom:1px solid #e2e8f0;">Remarks / Verifier</th>
                            <th style="padding:12px; text-align:center; border-bottom:1px solid #e2e8f0;">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="5" style="padding:30px; text-align:center; color:#94a3b8;">
                                    No past submissions found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td style="padding:12px; border-bottom:1px solid #e2e8f0;">
                                        <strong><?= date('F Y', strtotime($row['month_year'] . '-01')) ?></strong>
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
                                        <?php if ($row['status'] === 'rejected'): ?>
                                            <span style="color:#ef4444;"><?= htmlspecialchars($row['remarks']) ?></span>
                                        <?php elseif ($row['status'] === 'verified'): ?>
                                            <span>Verified by: <?= htmlspecialchars($row['verifier_name'] ?? 'System') ?></span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">Waiting for review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px; border-bottom:1px solid #e2e8f0; text-align:center;">
                                        <?php if ($row['file_path']): ?>
                                            <a href="<?= BASE_URL . htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 8px; font-size:11px;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php else: ?>
                                            -
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

    <!-- Right Side: File Upload Panel -->
    <div>
        <div class="card glass">
            <div class="card-header">
                <div class="card-title">Upload Signed Document</div>
            </div>
            <div class="card-body">
                <?php if ($current_record && ($current_record['status'] === 'pending' || $current_record['status'] === 'verified')): ?>
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:10px; text-align:center;">
                        <i class="fas fa-circle-check" style="font-size:32px; color:#15803d; margin-bottom:10px; display:block;"></i>
                        <h4 style="margin:0 0 5px 0; color:#15803d;">Muster Roll Filed</h4>
                        <p style="margin:0; font-size:12px; color:#166534;">
                            You have already uploaded the document for this wage month. 
                            <?php if ($current_record['status'] === 'verified'): ?>
                                It has been verified and approved.
                            <?php else: ?>
                                It is currently awaiting verification from the Welfare department.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="month_year" value="<?= $selected_month_year ?>">
                        
                        <!-- Submission Deadline Alert -->
                        <?php 
                        $today_day = intval(date('d'));
                        $deadline_passed = false;
                        if ($today_day > 10) {
                            $deadline_passed = true;
                        }
                        ?>
                        <?php if ($deadline_passed): ?>
                            <div style="background:#fffbeb; border:1px solid #fef3c7; color:#b45309; padding:10px 12px; border-radius:8px; margin-bottom:15px; font-size:12px; display:flex; gap:8px;">
                                <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
                                <div>
                                    <strong>Late Submission Alert:</strong> The submission deadline is the 10th of each month. 
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Wage Month</label>
                            <input type="text" class="form-control" value="<?= $months[$month] ?> <?= $year ?>" readonly>
                        </div>

                        <div class="form-group" style="margin-top: 15px;">
                            <label class="form-label">Signed Copy File (PDF/JPEG/PNG)</label>
                            <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #6366f1; margin-bottom:10px; display:block;"></i>
                                <span style="font-size:12px; color:#475569;">Click to browse files (Max 5MB)</span>
                                <input type="file" id="fileInput" name="muster_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileSelect(this)">
                            </div>
                            <div id="filePreview" class="file-preview" style="display:none;">
                                <i class="fas fa-file-pdf" style="font-size:20px; color:#ef4444;"></i>
                                <span id="fileName" style="font-size:12px; font-weight:600; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                                <button type="button" class="btn btn-outline" style="padding:2px 6px;" onclick="removeFile()"><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                                <i class="fas fa-paper-plane"></i> Submit Muster Roll
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'File size must be less than 5MB.', 'error');
            input.value = '';
            return;
        }
        document.getElementById('fileName').innerText = file.name;
        document.getElementById('filePreview').style.display = 'flex';
    }
}

function removeFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').style.display = 'none';
}

document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        Swal.fire('Warning', 'Please select a signed Muster Roll file to upload.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Submit Muster Roll?',
        text: 'Are you sure you want to upload this signed copy for verification?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, upload'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(this);
            Swal.fire({
                title: 'Uploading...',
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('../../api/contractor/upload_muster_roll.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message || 'Muster Roll uploaded successfully.', 'success')
                    .then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to upload Muster Roll.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'An unexpected connection error occurred.', 'error');
            });
        }
    });
});
</script>

<?php
}

renderLayout('Muster Roll Control Desk', 'renderContent', $role, $name);
?>
