<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// pages/contractor/lwf_rate_view.php
require_once '../../include/auth.php';
checkAuth(['contractor']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    error_log('LWF rate view schema init failed: ' . $e->getMessage());
}

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    if (!$c_id) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>';
        return;
    }

    // Fetch LWF rate history
    $rates = db_fetch_all($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC") ?: [];
    $latestRate = $rates[0] ?? null;

    // Fetch my LWF submissions
    $myLwf = db_fetch_all($conn,
        "SELECT c.*, cl.employee_contribution AS emp_c, cl.employer_contribution AS empl_c, cl.challan_date
         FROM compliance c
         LEFT JOIN compliance_lwf cl ON cl.compliance_id = c.id
         WHERE c.contractor_id = ? AND c.type = 'lwf'
         ORDER BY c.month_year DESC",
        'i', [$c_id]
    );
    ?>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-balance-scale" style="color:#8b5cf6;margin-right:10px;"></i> LWF Rate & My Compliance</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Labour Welfare Fund — Rates set by Welfare Admin (Code on Social Security 2020)</div>
        </div>
        <a href="lwf_compliance.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Submit LWF Return
        </a>
    </div>

    <?php if ($latestRate): ?>
    <!-- Current Active Rate -->
    <div class="card glass" style="margin-bottom:20px;border:2px solid #ede9fe;">
        <div class="card-header" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
            <div class="card-title" style="color:#fff;"><i class="fas fa-star"></i> Current Active LWF Rate</div>
            <span style="font-size:11px;color:rgba(255,255,255,0.8);">Effective from <?= date('d M Y', strtotime($latestRate['effective_from'])) ?></span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;text-align:center;">
                <div style="padding:16px;background:#f5f3ff;border-radius:12px;">
                    <div style="font-size:28px;font-weight:800;color:#7c3aed;">₹<?= number_format((float)$latestRate['employee_contribution'], 2) ?></div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">Employee Contribution</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">(Deducted from worker wages)</div>
                </div>
                <div style="padding:16px;background:#faf5ff;border-radius:12px;">
                    <div style="font-size:28px;font-weight:800;color:#6d28d9;">₹<?= number_format((float)$latestRate['employer_contribution'], 2) ?></div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">Employer Contribution</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">(Paid by contractor)</div>
                </div>
                <div style="padding:16px;background:#ede9fe;border-radius:12px;border:2px solid #c4b5fd;">
                    <div style="font-size:28px;font-weight:800;color:#5b21b6;">₹<?= number_format((float)$latestRate['employee_contribution'] + (float)$latestRate['employer_contribution'], 2) ?></div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">Total Per Worker/Period</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">(Employee + Employer)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="alert alert-info" style="margin-bottom:20px;">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>About Labour Welfare Fund (OSH Code 2020):</strong> LWF contributions are typically collected bi-annually (June & December).
            Both the <strong>employee</strong> and <strong>employer</strong> make fixed contributions per period.
            The rates are set by the Welfare Admin and may be revised periodically.
        </div>
    </div>

    <?php else: ?>
    <div class="alert alert-warning" style="margin-bottom:20px;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>LWF rates have not been configured yet by the Welfare Admin. Please contact the welfare department.</div>
    </div>
    <?php endif; ?>

    <!-- Rate History -->
    <div class="card glass" style="margin-bottom:20px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> LWF Rate History</div></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Effective From</th>
                        <th>Employee Contribution</th>
                        <th>Employer Contribution</th>
                        <th>Total</th>
                        <th>Tag</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rates)): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#64748b;">No rate history found.</td></tr>
                <?php else: ?>
                <?php foreach ($rates as $i => $r):
                    $emp  = (float)$r['employee_contribution'];
                    $empl = (float)$r['employer_contribution'];
                ?>
                <tr <?= $i === 0 ? 'style="background:rgba(139,92,246,0.06);"' : '' ?>>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d M Y', strtotime($r['effective_from'])) ?></td>
                    <td>₹<?= number_format($emp, 2) ?></td>
                    <td>₹<?= number_format($empl, 2) ?></td>
                    <td><strong>₹<?= number_format($emp + $empl, 2) ?></strong></td>
                    <td>
                        <?php if ($i === 0): ?>
                            <span class="badge badge-success">Active</span>
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

    <!-- My LWF Submissions -->
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-file-invoice-dollar"></i> My LWF Submission History</div>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Period</th>
                        <th>Challan Date</th>
                        <th>Employee Contribution</th>
                        <th>Employer Contribution</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($myLwf)): ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#64748b;">
                    <i class="fas fa-inbox" style="font-size:28px;display:block;margin-bottom:10px;color:#94a3b8;"></i>
                    No LWF submissions yet.<br>
                    <a href="lwf_compliance.php" class="btn btn-primary" style="margin-top:12px;">
                        <i class="fas fa-plus"></i> Submit LWF Return
                    </a>
                </td></tr>
                <?php else: ?>
                <?php foreach ($myLwf as $i => $s):
                    $status = strtolower($s['status'] ?? 'pending');
                    $badge = '';
                    switch ($status) {
                        case 'approved':  $badge = '<span class="badge badge-success">Approved</span>'; break;
                        case 'rejected':  $badge = '<span class="badge badge-danger">Rejected</span>'; break;
                        case 'submitted': $badge = '<span class="badge badge-warning">Under Review</span>'; break;
                        default:          $badge = '<span class="badge badge-gray">Pending</span>'; break;
                    }
                    list($yr, $mo) = explode('-', $s['month_year']);
                    $mNames = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun',
                               '07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= ($mNames[$mo] ?? $mo).' '.$yr ?></strong></td>
                    <td><?= $s['challan_date'] ? date('d M Y', strtotime($s['challan_date'])) : '—' ?></td>
                    <td><?= $s['emp_c']  ? '₹'.number_format((float)$s['emp_c'], 2)  : '—' ?></td>
                    <td><?= $s['empl_c'] ? '₹'.number_format((float)$s['empl_c'], 2) : '—' ?></td>
                    <td><?= $badge ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

$pageTitle = 'LWF Rate & Compliance';
renderLayout($pageTitle, 'renderContent', $role, $_SESSION['name'] ?? '');
?>
