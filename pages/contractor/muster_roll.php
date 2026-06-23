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
    .status-pending, .status-pending_verification { background: #fef3c7; color: #d97706; }
    .status-verified { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .status-not_submitted { background: #f1f5f9; color: #475569; }

    .upload-box {
        border: 2px dashed #cbd5e1;
        padding: 30px 20px;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
        margin-top: 5px;
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

    /* Upload Modal Styling */
    .upload-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .upload-modal-overlay.show {
        opacity: 1;
    }
    .upload-modal-card {
        background: #ffffff;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: scale(0.95) translateY(-20px);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    .upload-modal-overlay.show .upload-modal-card {
        transform: scale(1) translateY(0);
    }
</style>

<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-file-invoice" style="color:#6366f1;margin-right:10px;"></i> Muster Roll</h2>
        <p class="page-subtitle">Download daily attendance logs, obtain employee signatures, and upload the verified copy.</p>
    </div>
</div>

<!-- Unified Control Panel Bar -->
<div class="card glass" style="margin-bottom: 24px; border: 1px solid rgba(99, 102, 241, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
    <div class="card-header" style="background: rgba(99, 102, 241, 0.03); border-bottom: 1px solid rgba(99, 102, 241, 0.08); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div class="card-title" style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-file-signature" style="color: #6366f1;"></i> Muster Roll Submission Desk
        </div>
        <div>
            <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $current_status)) ?>" style="padding: 6px 12px; font-size: 11px;">
                Status: <?= $current_status ?>
            </span>
        </div>
    </div>
    <div class="card-body" style="padding: 20px;">
        <div style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
            
            <!-- Left Side: Selection and Stats -->
            <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap; flex: 1;">
                <!-- Selected Month Form -->
                <div>
                    <h4 style="margin: 0 0 6px 0; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Selected Month</h4>
                    <form method="GET" style="display: flex; gap: 6px; align-items: center;">
                        <select name="month" class="form-control" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; min-width: 110px; height: 34px;">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" class="form-control" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; min-width: 80px; height: 34px;">
                            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 0 12px; font-size: 12px; border-radius: 6px; height: 34px; line-height: 34px; display: inline-flex; align-items: center; justify-content: center;">Go</button>
                    </form>
                </div>

                <!-- Active Workmen Stat -->
                <div style="border-left: 1px solid #e2e8f0; padding-left: 24px; min-width: 120px;">
                    <h4 style="margin: 0 0 4px 0; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Active Workmen</h4>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
                        <span style="font-size: 22px; font-weight: 700; color: #1e293b;"><?= $workers_count ?></span>
                        <span style="font-size: 10px; color: #64748b;">Active</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Action Buttons -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <!-- Download/Print Button -->
                <a href="download_muster_roll.php?month=<?= $month ?>&year=<?= $year ?>" target="_blank" class="btn btn-outline" style="height: 34px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                    <i class="fas fa-print"></i> Print Muster Sheet
                </a>

                <!-- Upload Button -->
                <?php if ($current_record && $current_record['status'] === 'verified'): ?>
                    <button type="button" class="btn btn-success" disabled style="height: 34px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; cursor: not-allowed; opacity: 0.85;">
                        <i class="fas fa-check-circle"></i> Verified & Filed
                    </button>
                <?php elseif ($current_record && $current_record['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-warning" disabled style="height: 34px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; cursor: not-allowed; opacity: 0.85;">
                        <i class="fas fa-hourglass-half"></i> Pending Verification
                    </button>
                <?php else: ?>
                    <button type="button" onclick="openUploadModal()" class="btn btn-primary" style="height: 34px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background-color: #6366f1; border-color: #6366f1; color: white;">
                        <i class="fas fa-upload"></i> Upload Signed Copy
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Rejection Warning Alert -->
<?php if ($current_record && $current_record['status'] === 'rejected'): ?>
    <div class="alert alert-danger" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:12px; margin-bottom:24px; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-exclamation-circle" style="font-size: 18px; flex-shrink: 0;"></i>
        <div>
            <strong>Muster Roll Rejected:</strong> <?= htmlspecialchars($current_record['remarks']) ?>. Please review and re-upload.
        </div>
    </div>
<?php endif; ?>

<!-- Submission Instructions Banner -->
<div class="card glass" style="margin-bottom: 24px; border-left: 4px solid #6366f1; background: rgba(99, 102, 241, 0.02);">
    <div class="card-body" style="padding: 16px 20px; display: flex; align-items: flex-start; gap: 15px;">
        <div style="background: #e0e7ff; color: #6366f1; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fas fa-info-circle" style="font-size: 16px;"></i>
        </div>
        <div>
            <h4 style="margin: 0 0 4px 0; color: #1e293b; font-size: 13.5px; font-weight: 700;">Filing Guidelines</h4>
            <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.5;">
                Follow these compliance steps: 
                <strong>1. Print Muster Sheet:</strong> Generate the attendance & OT log template. 
                <strong>2. Signatures:</strong> Get signatures or thumb impressions from all active workmen. 
                <strong>3. Upload Signed Copy:</strong> Click "Upload Signed Copy" to submit the scanned copy before the <strong>10th of next month</strong>.
            </p>
        </div>
    </div>
</div>

<!-- Submission History Table -->
<div class="card glass" style="margin-bottom: 24px;">
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

<!-- Upload Modal -->
<div id="uploadModal" class="upload-modal-overlay" onclick="if(event.target === this) closeUploadModal()">
    <div class="upload-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-cloud-upload-alt" style="color: #6366f1;"></i> Upload Signed Muster Roll
            </h3>
            <button type="button" onclick="closeUploadModal()" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='transparent'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 20px;">
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
                            <i class="fas fa-exclamation-triangle" style="margin-top:2px; flex-shrink: 0;"></i>
                            <div>
                                <strong>Late Submission Alert:</strong> The submission deadline is the 10th of each month.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" style="font-weight: 600; font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">Wage Month</label>
                        <input type="text" class="form-control" value="<?= $months[$month] ?> <?= $year ?>" readonly style="background: #f1f5f9; cursor: not-allowed; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; box-sizing: border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">Signed Copy File (PDF/JPEG/PNG)</label>
                        <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 36px; color: #6366f1; margin-bottom:10px; display:block;"></i>
                            <span style="font-size:12px; color:#475569; font-weight: 500;">Click to browse files (Max 5MB)</span>
                            <input type="file" id="fileInput" name="muster_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileSelect(this)">
                        </div>
                        <div id="filePreview" class="file-preview" style="display:none;">
                            <i class="fas fa-file-pdf" style="font-size:20px; color:#ef4444;"></i>
                            <span id="fileName" style="font-size:12px; font-weight:600; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                            <button type="button" class="btn btn-outline" style="padding:2px 6px;" onclick="removeFile()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeUploadModal()" class="btn btn-outline" style="height: 38px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 600;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 20px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-paper-plane"></i> Submit Muster Roll
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (!modal) return;
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

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
