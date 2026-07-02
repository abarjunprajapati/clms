<?php
// pages/contractor/lwf_compliance.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    error_log('LWF compliance schema init failed: ' . $e->getMessage());
}

function renderContent() {
    global $conn, $user_id, $role;

    // Get latest LWF rate (use latest value applicable)
    $latestRate = db_single($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC LIMIT 1");
    $empC  = (float)($latestRate['employee_contribution'] ?? 45);
    $emplC = (float)($latestRate['employer_contribution'] ?? 45);

    // Contractor resolution
    $contractorList = [];
    if ($role === 'contractor') {
        $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
        $c_id = $contractor['id'] ?? null;
    } else {
        $c_id = isset($_GET['contractor_id']) ? intval($_GET['contractor_id']) : null;
        $contractorList = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");
    }

    $selectedHalf = $_GET['half'] ?? 'first';
    $selectedYear = intval($_GET['year'] ?? date('Y'));

    // First half: 01/01 - 30/06, Second half: 01/07 - 31/12
    if ($selectedHalf === 'first') {
        $startDate = "$selectedYear-01-01";
        $endDate   = "$selectedYear-06-30";
        $halfLabel = "First Half $selectedYear";
        $halfRange = "01/01/$selectedYear – 30/06/$selectedYear";
    } else {
        $startDate = "$selectedYear-07-01";
        $endDate   = "$selectedYear-12-31";
        $halfLabel = "Second Half $selectedYear";
        $halfRange = "01/07/$selectedYear – 31/12/$selectedYear";
    }

    // Fetch existing LWF record
    $lwfRecord = $c_id ? db_single($conn,
        "SELECT * FROM compliance_lwf WHERE contractor_id = ? AND period_half = ? AND period_year = ?",
        'isi', [$c_id, $selectedHalf, $selectedYear]
    ) : null;

    // Fetch eligible workers (>= 15 days)
    $eligibleWorkers = [];
    $dueAmount = 0;
    if ($c_id) {
        $workers = db_fetch_all($conn, "
            SELECT w.id, w.name, w.esic_number, w.aadhar_number, w.mobile_number, w.account_number, w.ifsc_code, w.bank_name, w.bank_branch
            FROM workmen w
            WHERE w.contractor_id = ? AND w.status NOT IN ('draft','pending','inactive','blocked')
            ORDER BY w.name ASC
        ", 'i', [$c_id]);

        foreach ($workers as $w) {
            $days = db_single($conn, "
                SELECT COUNT(DISTINCT DATE(check_in)) AS present_days
                FROM attendance
                WHERE workman_id = ? AND DATE(check_in) BETWEEN ? AND ?
                  AND LOWER(status) IN ('present','p')
            ", 'iss', [$w['id'], $startDate, $endDate]);
            $pd = (int)($days['present_days'] ?? 0);
            if ($pd >= 15) {
                $w['present_days'] = $pd;
                $w['emp_contribution'] = $empC;
                $w['empl_contribution'] = $emplC;
                $eligibleWorkers[] = $w;
                $dueAmount += $empC + $emplC;
            }
        }
    }

    // Show payment proof screen if requested
    $showPaymentScreen = isset($_GET['action']) && $_GET['action'] === 'payment_proof';
    ?>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-balance-scale" style="color:#8b5cf6;margin-right:10px;"></i> Labour Welfare Fund (LWF)</h2>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="card glass" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="font-weight:600; font-size:13px;">Select Period</label>
                    <select name="half" class="form-control" style="min-width:140px;">
                        <option value="first" <?= $selectedHalf === 'first' ? 'selected' : '' ?>>First Half</option>
                        <option value="second" <?= $selectedHalf === 'second' ? 'selected' : '' ?>>Second Half</option>
                    </select>
                    <select name="year" class="form-control" style="width:90px;">
                        <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <?php if (!empty($contractorList)): ?>
                    <select name="contractor_id" class="form-control" style="min-width:220px;">
                        <option value="">-- Select Contractor --</option>
                        <?php foreach($contractorList as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= $c_id == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['contractor_name']) ?> (<?= htmlspecialchars($cl['vendor_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$c_id && $role === 'contractor'): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <?php if (!$c_id): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i><div>Please select a contractor to view LWF compliance.</div></div>
    <?php return; endif; ?>

    <?php if ($showPaymentScreen): ?>
    <!-- ============== PAYMENT PROOF SCREEN (Page 51) ============== -->
    <div class="card glass" style="max-width:600px; margin:0 auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-upload"></i> Upload Payment Proof</div>
            <a href="?half=<?= $selectedHalf ?>&year=<?= $selectedYear ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div style="margin-bottom:16px; font-size:13px; line-height:2;">
                <div><strong><?= $halfLabel ?>:</strong></div>
                <div>Due Amount: <strong style="color:#ef4444;">₹<?= number_format($dueAmount, 2) ?></strong></div>
            </div>
            <form id="lwfPaymentForm" enctype="multipart/form-data">
                <input type="hidden" name="contractor_id" value="<?= $c_id ?>">
                <input type="hidden" name="period_half" value="<?= $selectedHalf ?>">
                <input type="hidden" name="period_year" value="<?= $selectedYear ?>">
                <input type="hidden" name="due_amount" value="<?= $dueAmount ?>">

                <div class="form-group">
                    <label class="form-label required">Amount paid to LWF (₹)</label>
                    <input type="number" step="0.01" name="paid_amount" class="form-control" placeholder="e.g. <?= number_format($dueAmount, 0) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label required">Upload Proof of payment</label>
                    <input type="file" name="payment_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div style="background:rgba(99,102,241,0.08);border-radius:8px;padding:12px;margin-bottom:16px;">
                    <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-size:13px;">
                        <input type="checkbox" name="declaration" value="1" style="margin-top:3px;flex-shrink:0;" required>
                        <span>I here by certify that the amount remitted by me is correct as per the details provided by welfare section in CLMS.</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;" id="lwfPayBtn"><i class="fas fa-upload"></i> Submit</button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- ============== MAIN LWF COMPLIANCE SCREEN ============== -->

    <!-- Period and Rule Info -->
    <div class="card glass" style="margin-bottom:20px; border-left:4px solid #8b5cf6;">
        <div class="card-body" style="padding:14px 18px; font-size:13px; line-height:1.9;">
            <div style="font-weight:700; font-size:14px; margin-bottom:6px;">1. <?= $halfLabel ?></div>
            <div>If any worker works for <strong>≥ 15 days</strong> during <?= ucfirst($selectedHalf) === 'First' ? 'First' : 'Second' ?> Half (<strong><?= $halfRange ?></strong>) then need to pay LWF for that worker for this Half. Similarly for both halfs.</div>
            <div style="margin-top:4px; color:#64748b;">If days is &lt; 15, avoid from report. While clicking on Download data should be downloaded to XL with ≥15 condition. This list should <strong>not</strong> contain workers with &lt;15 days.</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap;">
        <a href="../../api/contractor/download_lwf_dues.php?half=<?= urlencode($selectedHalf) ?>&year=<?= $selectedYear ?><?= $c_id && $role !== 'contractor' ? '&contractor_id='.$c_id : '' ?>" class="btn btn-primary">
            <i class="fas fa-file-excel"></i> Download LWF Dues
        </a>
        <a href="?half=<?= $selectedHalf ?>&year=<?= $selectedYear ?>&action=payment_proof" class="btn btn-outline" style="border:1.5px solid #8b5cf6; color:#8b5cf6;">
            <i class="fas fa-upload"></i> Upload Payment Proof
        </a>
        <button class="btn btn-outline" style="border:1.5px solid #64748b;" onclick="document.getElementById('contribDetails').style.display = document.getElementById('contribDetails').style.display === 'none' ? 'block' : 'none'">
            <i class="fas fa-list"></i> Contribution Details
        </button>
    </div>

    <!-- Contribution Details Table (toggled) -->
    <div id="contribDetails" style="display:none;">
        <div class="card glass" style="margin-bottom:20px;">
            <div class="card-header"><div class="card-title">Contribution Details – <?= $halfLabel ?></div></div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($eligibleWorkers)): ?>
                <div style="text-align:center;padding:40px;color:#64748b;">
                    <p>No workers with ≥ 15 days found for this period.</p>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Name of Employee</th>
                            <th>Days</th>
                            <th>Employee Contribution (₹)</th>
                            <th>Employer Contribution (₹)</th>
                            <th>Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleWorkers as $i => $w): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><span class="badge badge-success"><?= $w['present_days'] ?></span></td>
                            <td>₹<?= number_format($w['emp_contribution'], 2) ?></td>
                            <td>₹<?= number_format($w['empl_contribution'], 2) ?></td>
                            <td><strong>₹<?= number_format($w['emp_contribution'] + $w['empl_contribution'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:rgba(139,92,246,0.07);">
                            <td colspan="3" style="text-align:right;font-weight:700;padding:12px 16px;">Example Total:</td>
                            <td colspan="3" style="font-weight:700;font-size:15px;padding:12px 16px;">₹<?= number_format($dueAmount, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Status (if uploaded) -->
    <?php if ($lwfRecord): ?>
    <div class="card glass" style="border-left:4px solid <?= $lwfRecord['paid_amount'] >= $lwfRecord['due_amount'] ? '#10b981' : '#ef4444' ?>;">
        <div class="card-body" style="font-size:13px; line-height:2;">
            <div><strong>Due Amount:</strong> ₹<?= number_format((float)$lwfRecord['due_amount'], 2) ?></div>
            <div><strong>Amount Paid:</strong> ₹<?= number_format((float)$lwfRecord['paid_amount'], 2) ?></div>
            <?php $diff = $lwfRecord['due_amount'] - $lwfRecord['paid_amount']; ?>
            <?php if ($diff > 0): ?>
            <div style="color:#ef4444;"><strong>Mismatch Amount:</strong> ₹<?= number_format($diff, 2) ?> – Not Completed</div>
            <?php else: ?>
            <div style="color:#10b981;"><strong>Status:</strong> Complied ✓</div>
            <?php endif; ?>
            <?php if ($lwfRecord['payment_proof_path']): ?>
            <div style="margin-top:8px;">
                <a href="../../uploads/compliance/<?= rawurlencode($lwfRecord['payment_proof_path']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf"></i> View Proof</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <style>
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;transition:.2s;box-sizing:border-box; }
    .form-control:focus { outline:none;border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12); }
    .btn-sm { padding:5px 12px;font-size:12px; }
    </style>

    <script>
    document.getElementById('lwfPaymentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const btn = document.getElementById('lwfPayBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        fetch('../../api/contractor/upload_lwf_payment.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Payment proof submitted successfully.');
                    location.href = '?half=<?= $selectedHalf ?>&year=<?= $selectedYear ?>';
                } else {
                    alert('Error: ' + (data.message || 'Upload failed.'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload"></i> Submit';
                }
            })
            .catch(() => {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload"></i> Submit';
            });
    });
    </script>
    <?php
}

$pageTitle = 'Labour Welfare Fund (LWF)';
renderLayout($pageTitle, 'renderContent', $role, $_SESSION['name'] ?? '');
?>
