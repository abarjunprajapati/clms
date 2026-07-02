<?php
// pages/contractor/esi_compliance_check.php
require_once '../../include/auth.php';
checkAuth(['contractor']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Safely run schema setup — don't crash on live server if something fails
try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    // Log silently, continue
    error_log('ensureComplianceSchema error: ' . $e->getMessage());
}

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = isset($contractor['id']) ? $contractor['id'] : null;

    if (!$c_id) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>';
        return;
    }

    // Fetch this contractor's ESI submissions
    $submissions = db_fetch_all($conn,
        "SELECT c.*, ce.challan_date, ce.employer_contribution, ce.employee_contribution
         FROM compliance c
         LEFT JOIN compliance_esi ce ON ce.compliance_id = c.id
         WHERE c.contractor_id = ? AND c.type = 'esi'
         ORDER BY c.month_year DESC",
        'i', [$c_id]
    );
    if (!is_array($submissions)) $submissions = [];

    $totalSubmitted = count($submissions);
    $approved = 0; $pending = 0; $rejected = 0;
    foreach ($submissions as $s) {
        $st = strtolower(isset($s['status']) ? $s['status'] : '');
        if ($st === 'approved') $approved++;
        elseif ($st === 'submitted' || $st === 'pending') $pending++;
        elseif ($st === 'rejected') $rejected++;
    }
    ?>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-shield-check" style="color:#10b981;margin-right:10px;"></i> My ESI Compliance Status</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">
                Vendor: <strong><?= htmlspecialchars(isset($contractor['vendor_code']) ? $contractor['vendor_code'] : '') ?></strong> — <?= htmlspecialchars(isset($contractor['contractor_name']) ? $contractor['contractor_name'] : '') ?>
            </div>
        </div>
        <a href="esi_contribution.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Submit New ESI Return
        </a>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div class="card glass" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:#2563eb;"><?= $totalSubmitted ?></div>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Total Submissions</div>
        </div>
        <div class="card glass" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:#16a34a;"><?= $approved ?></div>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Approved</div>
        </div>
        <div class="card glass" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:#f59e0b;"><?= $pending ?></div>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Under Review</div>
        </div>
        <div class="card glass" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:#dc2626;"><?= $rejected ?></div>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Rejected</div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info" style="margin-bottom:20px;">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>ESI Compliance Under OSH Code 2020:</strong> Contractors employing 10 or more workers must contribute to ESI.
            Employee contribution is <strong>0.75%</strong> of wages and Employer contribution is <strong>3.25%</strong> of wages.
            Monthly returns must be submitted by the <strong>15th of the following month</strong>.
        </div>
    </div>

    <!-- Submission History -->
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-history"></i> My ESI Submission History</div>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Period (Month-Year)</th>
                        <th>Challan Date</th>
                        <th>Employee Contribution</th>
                        <th>Employer Contribution</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Welfare Remarks</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="8" style="text-align:center;padding:32px;color:#64748b;">
                    <i class="fas fa-inbox" style="font-size:28px;display:block;margin-bottom:10px;color:#94a3b8;"></i>
                    No ESI submissions found.<br>
                    <a href="esi_contribution.php" class="btn btn-primary" style="margin-top:12px;">
                        <i class="fas fa-plus"></i> Submit First ESI Return
                    </a>
                </td></tr>
                <?php else: ?>
                <?php foreach ($submissions as $i => $sub):
                    $status = strtolower(isset($sub['status']) ? $sub['status'] : 'pending');
                    $statusBadge = '';
                    switch ($status) {
                        case 'approved':  $statusBadge = '<span class="badge badge-success">Approved</span>'; break;
                        case 'rejected':  $statusBadge = '<span class="badge badge-danger">Rejected</span>'; break;
                        case 'submitted': $statusBadge = '<span class="badge badge-warning">Under Review</span>'; break;
                        default:          $statusBadge = '<span class="badge badge-gray">Pending</span>'; break;
                    }
                    $empC  = (float)(isset($sub['employee_contribution']) ? $sub['employee_contribution'] : 0);
                    $emplC = (float)(isset($sub['employer_contribution']) ? $sub['employer_contribution'] : 0);
                    $total = $empC + $emplC;
                    $monthYear = isset($sub['month_year']) ? $sub['month_year'] : '';
                    $parts = explode('-', $monthYear);
                    $yr = isset($parts[0]) ? $parts[0] : '';
                    $mo = isset($parts[1]) ? $parts[1] : '';
                    $monthNames = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun',
                                   '07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                    $period = (isset($monthNames[$mo]) ? $monthNames[$mo] : $mo) . ' ' . $yr;
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($period) ?></strong></td>
                    <td><?= (isset($sub['challan_date']) && $sub['challan_date']) ? date('d M Y', strtotime($sub['challan_date'])) : '—' ?></td>
                    <td><?= $empC  > 0 ? '₹'.number_format($empC, 2)  : '—' ?></td>
                    <td><?= $emplC > 0 ? '₹'.number_format($emplC, 2) : '—' ?></td>
                    <td><?= $total > 0 ? '<strong>₹'.number_format($total, 2).'</strong>' : '—' ?></td>
                    <td><?= $statusBadge ?></td>
                    <td style="font-size:12px;color:#64748b;"><?= htmlspecialchars(isset($sub['remarks']) ? $sub['remarks'] : '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .form-control{width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);font-size:13px;background:var(--card-bg,#fff);color:var(--text-primary);box-sizing:border-box;}
    </style>
    <?php
}

$pageTitle = 'My ESI Compliance Status';
renderLayout($pageTitle, 'renderContent', $role, isset($_SESSION['name']) ? $_SESSION['name'] : '');
?>
