<?php
// pages/welfare/check_epf_compliance.php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

ensureComplianceSchema($conn);

function renderContent() {
    global $conn;

    $filterMonth = $_GET['month'] ?? date('m', strtotime('-1 month'));
    $filterYear = $_GET['year'] ?? date('Y');
    $filterVendor = isset($_GET['vendor_code']) ? trim($_GET['vendor_code']) : '';

    $monthYear = "$filterYear-" . str_pad($filterMonth, 2, '0', STR_PAD_LEFT);

    // Fetch all contractors in the system
    $where = "1=1";
    $params = [];
    $types = '';
    if ($filterVendor !== '') {
        $where .= " AND (con.vendor_code LIKE ? OR con.contractor_name LIKE ?)";
        $params[] = '%' . $filterVendor . '%';
        $params[] = '%' . $filterVendor . '%';
        $types .= 'ss';
    }

    $sql = "
        SELECT con.id AS contractor_id, con.vendor_code, con.contractor_name, con.vendor_name AS company_name,
               c.id AS compliance_id, c.validation_status, c.validation_errors, c.status AS compliance_status,
               c.wage_total, c.worker_count, c.pf_amount
        FROM contractors con
        LEFT JOIN compliance c ON c.contractor_id = con.id AND c.type = 'epf' AND c.month_year = ?
        WHERE $where
        ORDER BY con.contractor_name ASC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    $bindParams = array_merge([$monthYear], $params);
    $bindTypes = 's' . $types;
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $contractorsList = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $contractorsList[] = $row;
    }

    // High level summary stats
    $totalVendors = count($contractorsList);
    $submittedCount = 0;
    $compliedCount = 0;
    $failedCount = 0;

    foreach ($contractorsList as $c) {
        if ($c['compliance_id']) {
            $submittedCount++;
            if ($c['validation_status'] === 'passed') {
                $compliedCount++;
            } else {
                $failedCount++;
            }
        }
    }

    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June',
        '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];

    // Check if compliance check was run
    $checkedIds = isset($_GET['checked_vendors']) ? array_filter(array_map('intval', explode(',', $_GET['checked_vendors']))) : [];
    $comparisonResults = [];

    if (!empty($checkedIds)) {
        $idsImploded = implode(',', $checkedIds);
        
        // Fetch compliance records for the checked vendors
        $compQuery = db_fetch_all($conn, "
            SELECT c.*, con.vendor_code, con.contractor_name, con.vendor_name AS company_name
            FROM compliance c
            JOIN contractors con ON c.contractor_id = con.id
            WHERE con.id IN ($idsImploded) AND c.type = 'epf' AND c.month_year = ?
        ", 's', [$monthYear]);

        foreach ($compQuery as $sub) {
            $cId = (int)$sub['contractor_id'];
            
            // Get expected workmen
            $start = $monthYear . '-01';
            $end = date('Y-m-t', strtotime($start));
            
            $workers = db_fetch_all($conn, "
                SELECT id, name, uan_number, pf_no, acc_number, certified_wage_rate
                FROM workmen
                WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
            ", 'i', [$cId]);

            $attendance = [];
            if (!empty($workers)) {
                $attQuery = db_fetch_all($conn, "
                    SELECT workman_id, COUNT(DISTINCT DATE(check_in)) AS present_days
                    FROM attendance
                    WHERE workman_id IN (SELECT id FROM workmen WHERE contractor_id = ?)
                      AND DATE(check_in) BETWEEN ? AND ?
                      AND LOWER(status) IN ('present', 'p')
                    GROUP BY workman_id
                ", 'iss', [$cId, $start, $end]);
                foreach ($attQuery as $row) {
                    $attendance[(int)$row['workman_id']] = (int)$row['present_days'];
                }
            }

            // Get uploaded ECR records
            $ecrRecords = db_fetch_all($conn, "
                SELECT * FROM compliance_epf_records WHERE compliance_id = ?
            ", 'i', [(int)$sub['id']]);

            $matchedUans = [];

            // Map workmen
            foreach ($workers as $w) {
                $uan = trim((string)$w['uan_number']);
                if ($uan === '') {
                    $uan = trim((string)$w['pf_no']);
                }

                $days = $attendance[(int)$w['id']] ?? 0;
                $rate = (float)($w['certified_wage_rate'] ?? 0);
                $mrWage = $days * $rate; // Muster Roll Wage = Total Days Worked x Wage Rate

                // Find ECR record
                $ecrWage = 0.0;
                $rating = 'Complied';
                $status = 'Complied';
                
                $found = false;
                foreach ($ecrRecords as $rec) {
                    if ($rec['uan'] === $uan) {
                        $ecrWage = (float)$rec['epf_wages'];
                        $matchedUans[$rec['uan']] = true;
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    if (abs($ecrWage - $mrWage) > 1.0 && $ecrWage < $mrWage) {
                        $rating = 'Underpaid';
                        $status = 'Not Complied';
                    }
                } else {
                    $rating = 'Excluded';
                    $status = 'Not Complied';
                }

                $comparisonResults[] = [
                    'acc_no' => $w['acc_number'] ?: ('W-' . $w['id']),
                    'pf_no' => $w['pf_no'] ?: 'N/A',
                    'worker_name' => $w['name'],
                    'company_name' => $sub['contractor_name'], // Company name or contractor name
                    'vendor_code' => $sub['vendor_code'],
                    'mr_wage' => $mrWage,
                    'ecr_wage' => $ecrWage,
                    'diff' => $mrWage - $ecrWage,
                    'rating' => $rating,
                    'status' => $status
                ];
            }

            // Add excess ECR records (present in ECR but not in workmen list)
            foreach ($ecrRecords as $rec) {
                if (!isset($matchedUans[$rec['uan']])) {
                    $comparisonResults[] = [
                        'acc_no' => 'N/A',
                        'pf_no' => 'N/A',
                        'worker_name' => $rec['member_name'],
                        'company_name' => $sub['contractor_name'],
                        'vendor_code' => $sub['vendor_code'],
                        'mr_wage' => 0.00,
                        'ecr_wage' => (float)$rec['epf_wages'],
                        'diff' => - (float)$rec['epf_wages'],
                        'rating' => 'NA',
                        'status' => 'NA'
                    ];
                }
            }
        }
    }
    ?>

    <style>
        .filter-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 20px; }
        .filter-row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { margin: 0; flex-grow: 1; min-width: 150px; }
        .bulk-actions-bar { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 18px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .bulk-btn-group { display: flex; gap: 10px; }
        .comp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
        .comp-stat-card { border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; text-align: center; background: var(--card-bg); }
        .comp-stat-card span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); }
        .comp-stat-card h3 { font-size: 24px; font-weight: 800; margin: 4px 0 0; color: var(--text-primary); }
    </style>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-shield-check" style="color:#3b82f6;margin-right:10px;"></i> Check EPF Compliance</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px">Verify contractor EPF ECR compliance and identify wage discrepancies or excluded workers.</div>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="comp-stats-grid">
        <div class="comp-stat-card" style="border-left: 4px solid #64748b;">
            <span>Total Vendors</span>
            <h3><?= $totalVendors ?></h3>
        </div>
        <div class="comp-stat-card" style="border-left: 4px solid #3b82f6;">
            <span>Submitted ECR</span>
            <h3><?= $submittedCount ?></h3>
        </div>
        <div class="comp-stat-card" style="border-left: 4px solid #10b981;">
            <span>Complied Vendors</span>
            <h3><?= $compliedCount ?></h3>
        </div>
        <div class="comp-stat-card" style="border-left: 4px solid #ef4444;">
            <span>Failed Vendors</span>
            <h3><?= $failedCount ?></h3>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-card glass">
        <form method="GET" class="filter-row" id="filterForm">
            <div class="filter-group" style="max-width:160px;">
                <label class="form-label">Month</label>
                <select name="month" class="form-control" id="monthSelect">
                    <?php foreach ($months as $num => $mName): ?>
                        <option value="<?= $num ?>" <?= $filterMonth === $num ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="max-width:120px;">
                <label class="form-label">Year</label>
                <select name="year" class="form-control" id="yearSelect">
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group" style="min-width:250px;">
                <label class="form-label">Search Vendor Code / Name</label>
                <input type="text" name="vendor_code" class="form-control" placeholder="Vendor code or name..." value="<?= htmlspecialchars($filterVendor) ?>">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Search</button>
                <a href="check_epf_compliance.php" class="btn btn-outline">Reset/Clear</a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar">
        <div>
            <span style="font-size: 13px; font-weight: 700; color: #475569;">
                <i class="fas fa-tasks" style="color:#3b82f6; margin-right:6px;"></i> 
                <span id="selected_count">0</span> vendors selected
            </span>
        </div>
        <div class="bulk-btn-group">
            <button type="button" class="btn btn-sm btn-primary" onclick="runComplianceCheck()">
                <i class="fas fa-play"></i> Compliance Check
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="downloadSelectedReport()">
                <i class="fas fa-download"></i> Download
            </button>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="card glass">
        <div class="card-body" style="padding:0;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="select_all_vendors" onchange="toggleSelectAll(this)" style="transform: scale(1.2); cursor: pointer;">
                        </th>
                        <th style="width: 60px;">S.No</th>
                        <th>Vendor Code</th>
                        <th>Vendor Name</th>
                        <th>Wage Month</th>
                        <th>ECR Workers</th>
                        <th>Total EPF Amount</th>
                        <th>Validation Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contractorsList)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:20px; color:#64748b;">No vendors found matching search.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $idx = 1;
                        foreach ($contractorsList as $row): 
                            $valStatus = $row['validation_status'] ?? 'pending';
                            if (!$row['compliance_id']) {
                                $valStatus = 'Not Submitted';
                            }
                            $valBadgeClass = $valStatus === 'passed' ? 'badge-success' : ($valStatus === 'mismatch' ? 'badge-danger' : 'badge-warning');
                            $monthStr = $months[str_pad($filterMonth, 2, '0', STR_PAD_LEFT)] . ' ' . $filterYear;
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="vendor-checkbox" value="<?= (int)$row['contractor_id'] ?>" data-compliance-id="<?= (int)($row['compliance_id'] ?? 0) ?>" onchange="onVendorSelectChange()" style="transform: scale(1.2); cursor: pointer;">
                            </td>
                            <td><?= $idx++ ?></td>
                            <td><code><?= htmlspecialchars($row['vendor_code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['contractor_name']) ?></strong></td>
                            <td><?= $monthStr ?></td>
                            <td><?= $row['compliance_id'] ? (int)$row['worker_count'] : '-' ?></td>
                            <td><?= $row['compliance_id'] ? ('₹' . number_format((float)$row['pf_amount'], 2)) : '-' ?></td>
                            <td>
                                <span class="badge <?= $valBadgeClass ?>"><?= strtoupper($valStatus) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mismatches Report Grid section -->
    <?php if (!empty($checkedIds)): ?>
    <div class="card glass" style="margin-top: 24px;" id="resultsSection">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice"></i> EPF Compliance Verification Result - <?= htmlspecialchars($months[str_pad($filterMonth, 2, '0', STR_PAD_LEFT)] . ' ' . $filterYear) ?></div></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>ACC NO</th>
                        <th>Name of Worker</th>
                        <th>Vendor Code</th>
                        <th>Vendor name</th>
                        <th>Musterroll wage (Total days x Rate)</th>
                        <th>ECR EPF wage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comparisonResults)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#64748b;">No mismatch details to display.</td></tr>
                    <?php else: ?>
                        <?php foreach ($comparisonResults as $r): 
                            $statusBadge = $r['status'] === 'Complied' ? 'badge-success' : ($r['status'] === 'Not Complied' ? 'badge-danger' : 'badge-gray');
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['acc_no']) ?></code></td>
                            <td><strong><?= htmlspecialchars($r['worker_name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($r['vendor_code']) ?></code></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td>₹<?= number_format($r['mr_wage'], 2) ?></td>
                            <td>₹<?= number_format($r['ecr_wage'], 2) ?></td>
                            <td>
                                <span class="badge <?= $statusBadge ?>"><?= strtoupper($r['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleSelectAll(masterCb) {
            document.querySelectorAll('.vendor-checkbox').forEach(cb => {
                cb.checked = masterCb.checked;
            });
            onVendorSelectChange();
        }

        function onVendorSelectChange() {
            const checked = document.querySelectorAll('.vendor-checkbox:checked');
            const total = document.querySelectorAll('.vendor-checkbox');
            document.getElementById('selected_count').textContent = checked.length;
            
            const master = document.getElementById('select_all_vendors');
            if (master) {
                master.checked = total.length > 0 && checked.length === total.length;
                master.indeterminate = checked.length > 0 && checked.length < total.length;
            }
        }

        function runComplianceCheck() {
            const checked = document.querySelectorAll('.vendor-checkbox:checked');
            if (checked.length === 0) {
                Swal.fire('No Selection', 'Please select at least one vendor checkbox.', 'warning');
                return;
            }
            const ids = Array.from(checked).map(cb => cb.value).join(',');
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            
            const url = new URL(window.location.href);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            url.searchParams.set('checked_vendors', ids);
            window.location.href = url.toString();
        }

        function downloadSelectedReport() {
            const checked = document.querySelectorAll('.vendor-checkbox:checked');
            if (checked.length === 0) {
                Swal.fire('No Selection', 'Please select at least one vendor checkbox.', 'warning');
                return;
            }
            // Filter only to those who have compliance uploaded (compliance_id > 0)
            const compIds = Array.from(checked)
                .map(cb => Number(cb.dataset.complianceId))
                .filter(id => id > 0);

            if (compIds.length === 0) {
                Swal.fire('No ECR Uploaded', 'None of the selected vendors have uploaded an ECR for this month.', 'info');
                return;
            }

            const idsStr = compIds.join(',');
            window.open('../../api/welfare/export_epf_compliance.php?compliance_ids=' + idsStr);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Restore checkbox state if query string has checked_vendors
            const urlParams = new URLSearchParams(window.location.search);
            const checkedVal = urlParams.get('checked_vendors');
            if (checkedVal) {
                const ids = checkedVal.split(',');
                document.querySelectorAll('.vendor-checkbox').forEach(cb => {
                    if (ids.includes(cb.value)) {
                        cb.checked = true;
                    }
                });
                onVendorSelectChange();
                
                // Scroll to results
                const resultsSection = document.getElementById('resultsSection');
                if (resultsSection) {
                    resultsSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    </script>
    <?php
}

renderLayout("Check EPF Compliance", 'renderContent', $role, $name);
