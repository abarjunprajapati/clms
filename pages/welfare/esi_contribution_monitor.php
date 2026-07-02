<?php
// pages/welfare/esi_contribution_monitor.php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

ensureComplianceSchema($conn);

function renderContent() {
    global $conn;

    $filterMonth  = isset($_GET['month'])  ? $_GET['month']  : date('m');
    $filterYear   = isset($_GET['year'])   ? $_GET['year']   : date('Y');
    $filterVendor = isset($_GET['vendor']) ? trim($_GET['vendor']) : '';
    $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

    $monthYear = "$filterYear-" . str_pad($filterMonth, 2, '0', STR_PAD_LEFT);

    // All approved contractors
    $allContractors = db_fetch_all($conn,
        "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC"
    );

    // ESI submissions for this month
    $submittedMap = [];
    $sql = "SELECT c.*, con.vendor_code, con.contractor_name,
                   ce.challan_date, ce.employer_contribution, ce.employee_contribution
            FROM compliance c
            JOIN contractors con ON c.contractor_id = con.id
            LEFT JOIN compliance_esi ce ON ce.compliance_id = c.id
            WHERE c.type = 'esi' AND c.month_year = ?";
    $params = [$monthYear]; $types = 's';

    if ($filterVendor !== '') {
        $sql .= " AND (con.vendor_code LIKE ? OR con.contractor_name LIKE ?)";
        $params[] = '%'.$filterVendor.'%'; $params[] = '%'.$filterVendor.'%'; $types .= 'ss';
    }
    if ($filterStatus !== '') {
        $sql .= " AND c.status = ?";
        $params[] = $filterStatus; $types .= 's';
    }
    $sql .= " ORDER BY con.contractor_name ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $submittedMap[$row['contractor_id']] = $row;
    }

    $totalContractors  = count($allContractors);
    $submittedCount    = count($submittedMap);
    $pendingCount      = $totalContractors - $submittedCount;

    $months = [
        '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
        '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
        '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
    ];
    $years = range(date('Y'), date('Y') - 4);
    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-hand-holding-dollar" style="color:#10b981;margin-right:10px;"></i> ESI Contribution Monitor</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Occupational Safety, Health & Working Condition Code 2020 — Employee State Insurance compliance tracking</div>
        </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
        <div class="card glass" style="padding:18px;display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:grid;place-items:center;">
                <i class="fas fa-building" style="color:#2563eb;font-size:20px;"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#1e293b;"><?= $totalContractors ?></div>
                <div style="font-size:12px;color:#64748b;">Total Contractors</div>
            </div>
        </div>
        <div class="card glass" style="padding:18px;display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:grid;place-items:center;">
                <i class="fas fa-check-circle" style="color:#16a34a;font-size:20px;"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#16a34a;"><?= $submittedCount ?></div>
                <div style="font-size:12px;color:#64748b;">Submitted</div>
            </div>
        </div>
        <div class="card glass" style="padding:18px;display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#fef2f2,#fee2e2);display:grid;place-items:center;">
                <i class="fas fa-clock" style="color:#dc2626;font-size:20px;"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#dc2626;"><?= $pendingCount ?></div>
                <div style="font-size:12px;color:#64748b;">Pending / Not Submitted</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card glass" style="margin-bottom:20px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-filter"></i> Filter</div></div>
        <div class="card-body">
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:130px;">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Month</label>
                    <select name="month" class="form-control">
                        <?php foreach ($months as $m => $mName): ?>
                        <option value="<?= $m ?>" <?= $filterMonth === $m ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;min-width:100px;">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Year</label>
                    <select name="year" class="form-control">
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:2;min-width:160px;">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Vendor / Contractor Name</label>
                    <input type="text" name="vendor" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($filterVendor) ?>">
                </div>
                <div style="flex:1;min-width:130px;">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="submitted" <?= $filterStatus==='submitted'?'selected':'' ?>>Submitted</option>
                        <option value="approved"  <?= $filterStatus==='approved'?'selected':'' ?>>Approved</option>
                        <option value="rejected"  <?= $filterStatus==='rejected'?'selected':'' ?>>Rejected</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="esi_contribution_monitor.php" class="btn btn-outline" style="margin-left:6px;"><i class="fas fa-rotate-left"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-table"></i> ESI Submissions — <?= $months[$filterMonth] ?> <?= $filterYear ?></div>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vendor Code</th>
                        <th>Contractor Name</th>
                        <th>Challan Date</th>
                        <th>Employee Contrib.</th>
                        <th>Employer Contrib.</th>
                        <th>Status</th>
                        <th>Submission Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $displayContractors = $filterStatus !== '' || $filterVendor !== ''
                    ? array_values(array_filter($allContractors, fn($c) => isset($submittedMap[$c['id']])))
                    : $allContractors;

                if (empty($displayContractors)):
                ?>
                <tr><td colspan="9" style="text-align:center;padding:32px;color:#64748b;"><i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i> No records found.</td></tr>
                <?php else: ?>
                <?php foreach ($displayContractors as $i => $contractor):
                    $sub = $submittedMap[$contractor['id']] ?? null;
                    $submitted = $sub !== null;
                    $status = $sub['status'] ?? '';
                    $statusCls = '';
                    switch ($status) {
                        case 'approved':  $statusCls = 'badge-success'; break;
                        case 'rejected':  $statusCls = 'badge-danger'; break;
                        case 'submitted': $statusCls = 'badge-warning'; break;
                        default:          $statusCls = 'badge-gray'; break;
                    }
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($contractor['vendor_code']) ?></code></td>
                    <td><strong><?= htmlspecialchars($contractor['contractor_name']) ?></strong></td>
                    <td><?= $submitted && $sub['challan_date'] ? date('d M Y', strtotime($sub['challan_date'])) : '—' ?></td>
                    <td><?= $submitted && $sub['employee_contribution'] ? '₹'.number_format((float)$sub['employee_contribution'], 2) : '—' ?></td>
                    <td><?= $submitted && $sub['employer_contribution'] ? '₹'.number_format((float)$sub['employer_contribution'], 2) : '—' ?></td>
                    <td>
                        <?php if ($submitted): ?>
                            <span class="badge <?= $statusCls ?>"><?= strtoupper($status) ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger">NOT SUBMITTED</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($submitted): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Submitted</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($submitted): ?>
                        <a href="../contractor/esi_contribution.php?contractor_id=<?= $contractor['id'] ?>" class="btn btn-sm btn-outline">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php else: ?>
                        <span style="font-size:11px;color:#94a3b8;">Awaiting submission</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);font-size:13px;background:var(--card-bg,#fff);color:var(--text-primary);box-sizing:border-box;transition:.2s; }
    .form-control:focus { outline:none;border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.1); }
    </style>
    <?php
}

$pageTitle = 'ESI Contribution Monitor';
renderLayout($pageTitle, 'renderContent', $role, $_SESSION['name'] ?? '');
?>
