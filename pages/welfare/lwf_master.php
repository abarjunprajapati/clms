<?php
// pages/welfare/lwf_master.php
require_once '../../include/auth.php';
checkAuth(['welfare_admin', 'admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

ensureComplianceSchema($conn);

function renderContent() {
    global $conn, $user_id;

    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $empC = floatval($_POST['employee_contribution'] ?? 0);
        $emplC = floatval($_POST['employer_contribution'] ?? 0);
        $effFrom = trim($_POST['effective_from'] ?? '');
        if ($empC > 0 && $emplC > 0 && $effFrom) {
            $conn->query("INSERT INTO lwf_rate_master (employee_contribution, employer_contribution, effective_from, created_by)
                VALUES (" . $conn->real_escape_string($empC) . ", " . $conn->real_escape_string($emplC) . ", '" . $conn->real_escape_string($effFrom) . "', " . intval($user_id) . ")");
            $msg = 'success';
        } else {
            $msg = 'error';
        }
    }

    // Fetch all rate history
    $rates = db_fetch_all($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC");
    $latestRate = $rates[0] ?? ['employee_contribution' => 45, 'employer_contribution' => 45, 'effective_from' => date('Y-m-d')];
    ?>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-balance-scale" style="color:#8b5cf6;margin-right:10px;"></i> LWF Rate Master</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px">Code on Social Security 2020 – Labour Welfare Fund contribution rates</div>
        </div>
    </div>

    <?php if ($msg === 'success'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><div>LWF rate updated successfully! The latest rate will be used for compliance reports.</div></div>
    <?php elseif ($msg === 'error'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><div>Please fill all fields correctly.</div></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;align-items:start;">

        <!-- Add new rate card -->
        <div class="card glass">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Add / Update Rate</div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" style="margin-bottom:16px;">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        For setting Employee compensation &amp; Employer compensation Amts (say 45 each).<br>
                        May change with period. If rate changes, welfare admin should change the rate.<br>
                        <strong>Latest value logic:</strong> If rate is 45 for Jan–Jun &amp; a change occurs (may be on Apr from 45 → 100), take the compliance report of the period with 100 (latest value).
                    </div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label required">Employee Contribution (₹)</label>
                        <input type="number" step="0.01" name="employee_contribution" class="form-control" placeholder="e.g. 45" value="<?= htmlspecialchars($latestRate['employee_contribution']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Employer Contribution (₹)</label>
                        <input type="number" step="0.01" name="employer_contribution" class="form-control" placeholder="e.g. 45" value="<?= htmlspecialchars($latestRate['employer_contribution']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Effective From</label>
                        <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">
                        <i class="fas fa-save"></i> Save Rate
                    </button>
                </form>
            </div>
        </div>

        <!-- Rate history -->
        <div class="card glass">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-history"></i> Rate History</div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Employee Contribution</th>
                            <th>Employer Contribution</th>
                            <th>Effective From</th>
                            <th>Tag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rates)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:24px;color:#64748b;">No rates configured yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($rates as $i => $r): ?>
                        <tr <?= $i === 0 ? 'style="background:rgba(139,92,246,0.07);"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td><strong>₹<?= number_format((float)$r['employee_contribution'], 2) ?></strong></td>
                            <td><strong>₹<?= number_format((float)$r['employer_contribution'], 2) ?></strong></td>
                            <td><?= date('d M Y', strtotime($r['effective_from'])) ?></td>
                            <td>
                                <?php if ($i === 0): ?>
                                    <span class="badge badge-success">Latest (Active)</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Historical</span>
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

    <style>
    .form-group { margin-bottom: 14px; }
    .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
    .form-label.required::after { content:' *'; color:#ef4444; }
    .form-control { width:100%; padding:9px 13px; border-radius:8px; border:1.5px solid var(--border-color); background:var(--input-bg,rgba(255,255,255,.04)); color:var(--text-primary); font-size:13px; transition:.2s; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.12); }
    </style>
    <?php
}

$pageTitle = 'LWF Rate Master';
$pageIcon = 'fas fa-balance-scale';
renderLayout($pageTitle, $pageIcon, 'renderContent');
?>
