<?php
// pages/welfare/lwf_compliance_checking.php
require_once '../../include/session.php';

$role = $_SESSION['role'] ?? '';
if ($role === 'contractor') {
    header('Location: ' . BASE_URL . 'pages/contractor/lwf_compliance.php');
    exit;
}

require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    error_log('LWF checking schema init failed: ' . $e->getMessage());
}

function renderContent() {
    global $conn, $role;

    $selectedHalf = $_GET['half'] ?? 'first';
    $selectedYear = intval($_GET['year'] ?? date('Y'));
    $selectedContractors = isset($_GET['contractors']) ? array_map('intval', (array)$_GET['contractors']) : [];
    $searchQuery = trim($_GET['search'] ?? '');

    if ($selectedHalf === 'first') {
        $startDate = "$selectedYear-01-01"; $endDate = "$selectedYear-06-30";
        $halfLabel = "First Half $selectedYear";
    } else {
        $startDate = "$selectedYear-07-01"; $endDate = "$selectedYear-12-31";
        $halfLabel = "Second Half $selectedYear";
    }

    // Get latest LWF rate (latest value applicable)
    $latestRate = db_single($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC LIMIT 1");
    $empC  = (float)($latestRate['employee_contribution'] ?? 45);
    $emplC = (float)($latestRate['employer_contribution'] ?? 45);

    // All approved contractors (optionally filtered by search)
    $allContractors = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");

    if ($role === 'contractor') {
        $c_user_id = (int)($_SESSION['user_id'] ?? 0);
        $myContractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? LIMIT 1", 'i', [$c_user_id]);
        $myCid = (int)($myContractor['id'] ?? -1);
        $allContractors = array_filter($allContractors, fn($c) => $c['id'] == $myCid);
    }

    // Apply search filter to contractor list for display
    $filteredForDisplay = $searchQuery
        ? array_filter($allContractors, fn($c) => stripos($c['contractor_name'], $searchQuery) !== false || stripos($c['vendor_code'], $searchQuery) !== false)
        : $allContractors;

    // Build contractor data for selected contractors
    $contractorData = [];
    $queryIds = !empty($selectedContractors)
        ? $selectedContractors
        : array_column($allContractors, 'id');

    foreach ($queryIds as $cid) {
        $con = null;
        foreach ($allContractors as $ac) { if ($ac['id'] == $cid) { $con = $ac; break; } }
        if (!$con) continue;

        $workerCountRow = db_single($conn, "
            SELECT COUNT(*) AS eligible_count FROM (
                SELECT w.id 
                FROM workmen w
                JOIN attendance a ON w.id = a.workman_id
                WHERE w.contractor_id = ? 
                  AND w.status NOT IN ('draft','pending','inactive','blocked')
                  AND DATE(a.check_in) BETWEEN ? AND ? 
                  AND LOWER(a.status) IN ('present','p')
                GROUP BY w.id
                HAVING COUNT(DISTINCT DATE(a.check_in)) >= 15
            ) as subq
        ", 'iss', [$cid, $startDate, $endDate]);
        
        $workerCount = (int)($workerCountRow['eligible_count'] ?? 0);

        $dueAmount = $workerCount * ($empC + $emplC);

        $lwfRecord = db_single($conn, "SELECT * FROM compliance_lwf WHERE contractor_id = ? AND period_half = ? AND period_year = ?", 'isi', [$cid, $selectedHalf, $selectedYear]);

        $paidAmount = (float)($lwfRecord['paid_amount'] ?? 0);
        $mismatch   = $dueAmount - $paidAmount;

        $contractorData[] = [
            'id'          => $cid,
            'vendor_code' => $con['vendor_code'],
            'name'        => $con['contractor_name'],
            'worker_count'=> $workerCount,
            'emp_c'       => $empC,
            'empl_c'      => $emplC,
            'due_amount'  => $dueAmount,
            'paid_amount' => $paidAmount,
            'mismatch'    => $mismatch,
            'proof_path'  => $lwfRecord['payment_proof_path'] ?? null,
        ];
    }
    ?>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-clipboard-check" style="color:#8b5cf6;margin-right:10px;"></i> Labour Welfare Fund (LWF)</h2>
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load</button>
                </div>
            </form>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:280px 1fr; gap:20px; align-items:start;">

        <!-- Left: Contract Selection Panel -->
        <div class="card glass">
            <div class="card-body" style="padding:14px;">
                <!-- Search Contract -->
                <div style="margin-bottom:12px;">
                    <input type="text" id="contractSearch" class="form-control" placeholder="Search Contract..." value="<?= htmlspecialchars($searchQuery) ?>" oninput="filterContracts(this.value)" style="width:100%; box-sizing:border-box;">
                </div>

                <!-- Select All -->
                <div style="margin-bottom:8px; padding:6px 0; border-bottom:1px solid var(--border-color);">
                    <label style="display:flex; gap:8px; align-items:center; cursor:pointer; font-size:13px; font-weight:600;">
                        <input type="checkbox" id="selectAll" onchange="toggleAll(this)" checked>
                        <span>Select All</span>
                    </label>
                </div>

                <!-- Contract List -->
                <div id="contractList" style="max-height:400px; overflow-y:auto;">
                    <?php $idx = 1; foreach ($allContractors as $ac): ?>
                    <label class="contract-item" data-name="<?= strtolower(htmlspecialchars($ac['contractor_name'])) ?>" data-code="<?= strtolower(htmlspecialchars($ac['vendor_code'])) ?>"
                        style="display:flex; gap:8px; align-items:center; cursor:pointer; font-size:12px; padding:6px 4px; border-radius:4px; margin-bottom:2px;">
                        <input type="checkbox" class="contract-cb" name="contractors[]" value="<?= $ac['id'] ?>" <?= empty($selectedContractors) || in_array($ac['id'], $selectedContractors) ? 'checked' : '' ?>>
                        <span><?= $idx++ ?>. <?= htmlspecialchars($ac['contractor_name']) ?> <span style="color:#94a3b8;">(<?= htmlspecialchars($ac['vendor_code']) ?>)</span></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Actions + Report Table -->
        <div>
            <!-- Download Button -->
            <div style="margin-bottom:16px;">
                <button class="btn btn-primary" onclick="downloadChecking()">
                    <i class="fas fa-file-excel"></i> Download LWF Dues * Checking in XL
                </button>
            </div>

            <!-- Report Table -->
            <div class="card glass">
                <div class="card-header"><div class="card-title">LWF Dues – <?= $halfLabel ?></div></div>
                <div class="card-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Vendor Code</th>
                                <th>Vendor Name</th>
                                <th>Worker (≥15 days)</th>
                                <th>Employee Contribution (₹)</th>
                                <th>Employer Contribution (₹)</th>
                                <th>Total (₹)</th>
                                <th>Upload Payment Proof</th>
                                <th>Mismatch Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contractorData)): ?>
                            <tr><td colspan="9" style="text-align:center;padding:30px;color:#64748b;">No data found.</td></tr>
                            <?php else: ?>
                            <?php foreach ($contractorData as $i => $cd): ?>
                            <?php
                                $empTotal  = $cd['emp_c']  * $cd['worker_count'];
                                $emplTotal = $cd['empl_c'] * $cd['worker_count'];
                                $mismatch  = $cd['mismatch'];
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($cd['vendor_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($cd['name']) ?></strong></td>
                                <td><?= $cd['worker_count'] ?></td>
                                <td>₹<?= number_format($empTotal, 2) ?> <small style="color:#64748b;">(₹<?= $cd['emp_c'] ?> × <?= $cd['worker_count'] ?>)</small></td>
                                <td>₹<?= number_format($emplTotal, 2) ?> <small style="color:#64748b;">(₹<?= $cd['empl_c'] ?> × <?= $cd['worker_count'] ?>)</small></td>
                                <td style="font-weight:700; color:#7c3aed;">₹<?= number_format($cd['due_amount'], 2) ?></td>
                                <td>
                                    <?php if ($cd['proof_path']): ?>
                                    <a href="../../uploads/compliance/<?= rawurlencode($cd['proof_path']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 8px;font-size:11px;"><i class="fas fa-file-pdf"></i> View</a>
                                    <?php else: ?>
                                    <span style="color:#94a3b8;font-size:12px;">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:700; color:<?= $mismatch > 0 ? '#ef4444' : '#10b981' ?>;">
                                    <?php if ($mismatch > 0): ?>
                                        ₹<?= number_format($cd['due_amount'], 0) ?> − ₹<?= number_format($cd['paid_amount'], 0) ?> = ₹<?= number_format($mismatch, 2) ?> <span style="font-size:11px;">(Not Complied)</span>
                                    <?php else: ?>
                                        –
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
    </div>

    <style>
    .form-group { margin-bottom:14px; }
    .form-control { padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;transition:.2s; }
    .contract-item:hover { background:rgba(139,92,246,0.06); }
    </style>

    <script>
    function toggleAll(cb) {
        document.querySelectorAll('.contract-cb').forEach(c => c.checked = cb.checked);
    }

    function filterContracts(val) {
        val = val.toLowerCase();
        document.querySelectorAll('.contract-item').forEach(item => {
            const name = item.dataset.name || '';
            const code = item.dataset.code || '';
            item.style.display = (name.includes(val) || code.includes(val)) ? '' : 'none';
        });
    }

    function getSelectedContractorIds() {
        return Array.from(document.querySelectorAll('.contract-cb:checked')).map(c => c.value);
    }

    function downloadChecking() {
        const half = '<?= $selectedHalf ?>';
        const year = '<?= $selectedYear ?>';
        const ids  = getSelectedContractorIds();
        if (!ids.length) { alert('Please select at least one contractor.'); return; }
        let url = `../../api/welfare/download_lwf_checking.php?half=${half}&year=${year}`;
        ids.forEach(id => url += `&contractors[]=${id}`);
        window.location.href = url;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const allCbs = document.querySelectorAll('.contract-cb');
        const selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = Array.from(allCbs).every(c => c.checked);
        allCbs.forEach(cb => cb.addEventListener('change', () => {
            if (selectAll) selectAll.checked = Array.from(allCbs).every(c => c.checked);
        }));
    });
    </script>
    <?php
}

$pageTitle = 'LWF Compliance Checking';
renderLayout($pageTitle, 'renderContent', $role, $_SESSION['name'] ?? '');
?>
