<?php
// pages/welfare/check_esi_compliance.php
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

    $filterMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
    $filterYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $filterVendor = isset($_GET['vendor_code']) ? trim($_GET['vendor_code']) : '';

    $monthYear = "$filterYear-" . str_pad($filterMonth, 2, '0', STR_PAD_LEFT);

    // Fetch ESI submissions matching the filters
    $where = "c.type = 'esi' AND c.month_year = '$monthYear'";
    $params = [];
    $types = '';

    if ($filterVendor !== '') {
        $where .= " AND (con.vendor_code LIKE ? OR con.contractor_name LIKE ?)";
        $params[] = '%' . $filterVendor . '%';
        $params[] = '%' . $filterVendor . '%';
        $types .= 'ss';
    }

    $sql = "
        SELECT c.*, con.vendor_code, con.contractor_name 
        FROM compliance c
        JOIN contractors con ON c.contractor_id = con.id
        WHERE $where
        ORDER BY con.contractor_name ASC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($types && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $submissions = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $submissions[] = $row;
    }

    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June',
        '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    ?>
    <style>
        .filter-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .filter-row {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group {
            margin: 0;
            flex-grow: 1;
            min-width: 150px;
        }
        .bulk-actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .bulk-btn-group {
            display: flex;
            gap: 10px;
        }
        .error-tooltip {
            font-size: 11px;
            color: #ef4444;
            margin-top: 4px;
            white-space: pre-line;
            max-width: 320px;
        }
    </style>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-shield-check" style="color:#6366f1;margin-right:10px;"></i> Check ESI Compliance</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px">Verify ESI compliance and generate vendor mismatch reports.</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-card glass">
        <form method="GET" class="filter-row">
            <div class="filter-group" style="max-width:160px;">
                <label class="form-label">Month</label>
                <select name="month" class="form-control">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $filterMonth === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="max-width:120px;">
                <label class="form-label">Year</label>
                <select name="year" class="form-control">
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                <a href="check_esi_compliance.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar">
        <div>
            <span style="font-size: 13px; font-weight: 700; color: #475569;">
                <i class="fas fa-tasks" style="color:#6366f1; margin-right:6px;"></i> 
                <span id="selected_count">0</span> vendors selected
            </span>
        </div>
        <div class="bulk-btn-group">
            <button class="btn btn-sm btn-outline" onclick="downloadBulkReport(1)" style="font-size:12px;">
                <i class="fas fa-file-excel" style="color:#10b981;"></i> Report 1: Vendor Workers Details
            </button>
            <button class="btn btn-sm btn-outline" onclick="downloadBulkReport(2)" style="font-size:12px;">
                <i class="fas fa-file-excel" style="color:#ef4444;"></i> Report 2: ESI Compliance Mismatches
            </button>
        </div>
    </div>

    <!-- Submissions Table -->
    <div class="card glass">
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="select_all_vendors" onchange="toggleSelectAll(this)" style="transform: scale(1.2); cursor: pointer;">
                        </th>
                        <th style="width: 60px;">S.No</th>
                        <th>Vendor Code</th>
                        <th>Vendor Name</th>
                        <th>Wage Month</th>
                        <th>Challan No.</th>
                        <th>Total Amount</th>
                        <th>Validation Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="vendor_submissions_list">
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:30px; color:#64748b;">No ESI submissions found for the selected month.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $idx = 1;
                        foreach ($submissions as $row): 
                            $status = $row['status'] ?? 'pending';
                            $valStatus = $row['validation_status'] ?? 'pending';
                            $valBadgeClass = $valStatus === 'passed' ? 'badge-success' : ($valStatus === 'mismatch' ? 'badge-danger' : 'badge-warning');
                            $monthStr = $months[str_pad($filterMonth, 2, '0', STR_PAD_LEFT)] . ' ' . $filterYear;
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="vendor-checkbox" value="<?= $row['id'] ?>" onchange="onVendorSelectChange()" style="transform: scale(1.2); cursor: pointer;">
                                </td>
                                <td><?= $idx++ ?></td>
                                <td><code><?= htmlspecialchars($row['vendor_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($row['contractor_name']) ?></strong></td>
                                <td><?= $monthStr ?></td>
                                <td><code><?= htmlspecialchars($row['challan_number'] ?? '-') ?></code></td>
                                <td>₹<?= number_format((float)$row['amount'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $valBadgeClass ?>"><?= strtoupper($valStatus) ?></span>
                                    <?php if (!empty($row['validation_errors'])): ?>
                                        <div class="error-tooltip">
                                            <?= htmlspecialchars($row['validation_errors']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="../../uploads/compliance/<?= rawurlencode($row['file_path']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 8px; font-size:11px;">
                                            <i class="fas fa-file-pdf"></i> View PDF
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
            master.checked = total.length > 0 && checked.length === total.length;
            master.indeterminate = checked.length > 0 && checked.length < total.length;
        }

        function downloadBulkReport(reportType) {
            const checked = document.querySelectorAll('.vendor-checkbox:checked');
            if (checked.length === 0) {
                Swal.fire('No Selection', 'Please select at least one vendor compliance record.', 'warning');
                return;
            }
            const ids = Array.from(checked).map(cb => cb.value).join(',');
            window.open('../../api/welfare/export_esi_compliance.php?report_type=' + reportType + '&compliance_ids=' + ids);
        }
    </script>
    <?php
}

renderLayout("Check ESI Compliance", 'renderContent', $role, $name);
?>
