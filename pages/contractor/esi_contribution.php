<?php
// pages/contractor/esi_contribution.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

ensureComplianceSchema($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    if (!$c_id) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>';
        return;
    }

    // Fetch ESI submissions from compliance table
    $history = db_fetch_all($conn, "
        SELECT c.*, ce.challan_date, ce.employer_contribution, ce.employee_contribution 
        FROM compliance c
        LEFT JOIN compliance_esi ce ON ce.compliance_id = c.id
        WHERE c.contractor_id = ? AND c.type = 'esi'
        ORDER BY c.month_year DESC
    ", 'i', [$c_id]);

    $months = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
    ];
    ?>
    <style>
        .esi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .flow-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
            border: 1px solid #f59e0b;
            border-left: 5px solid #d97706;
            color: #b45309;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.05);
        }
        .form-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
        .span-2 { grid-column: span 2; }
        .error-list {
            margin: 8px 0 0 0;
            padding-left: 20px;
            font-size: 11px;
            color: #ef4444;
            text-align: left;
        }
        .error-list li {
            margin-bottom: 3px;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex !important; }
        .modal-box {
            background: #fff;
            border-radius: 14px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; line-height: 1; }
        .modal-body { padding: 24px; max-height: 75vh; overflow-y: auto; }
        .modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px; }
        .details-expander {
            cursor: pointer;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }
        .details-expander:hover { text-decoration: underline; }
        .hidden-details { display: none; margin-top: 8px; border-top: 1px dashed #e2e8f0; padding-top: 8px; }
    </style>

    <!-- Prominent Muster Roll Sign & Upload Warning -->
    <div class="flow-warning">
        <i class="fas fa-exclamation-circle" style="font-size:20px; color:#d97706;"></i>
        <div>When muster roll is downloaded, sign & upload. ESI days and wages are verified against approved Muster Roll attendance records.</div>
    </div>

    <div class="esi-header">
        <div>
            <h2 class="page-title"><i class="fas fa-hospital" style="color:#ef4444;margin-right:10px;"></i> ESI Contribution</h2>
            <div style="font-size:12px;color:#64748b;margin-top:4px">Manage ESI templates, make portal payments, and upload contribution receipts.</div>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" onclick="openUploadModal()"><i class="fas fa-upload"></i> Upload ESI Challan</button>
        </div>
    </div>

    <!-- Actions and Template Generation Card -->
    <div class="card glass" style="margin-bottom: 24px;">
        <div class="card-header">
            <div class="card-title">ESI Contribution Management</div>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div class="form-group" style="margin:0; width:220px;">
                    <label class="form-label required" style="font-size:11px;">Select Wage Month</label>
                    <input type="month" id="wage_month_select" class="form-control" value="<?= date('Y-m', strtotime('-1 month')) ?>">
                </div>
                <div style="margin-top:20px;">
                    <button class="btn btn-outline" onclick="downloadESITemplate()"><i class="fas fa-download"></i> Download Monthly Contribution of ESI (Excel)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submissions History Card -->
    <div class="card glass">
        <div class="card-header">
            <div class="card-title">ESI Submission & Compliance History</div>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px;">S.No</th>
                        <th>Wage Month</th>
                        <th>Challan No.</th>
                        <th>Challan Date</th>
                        <th>Total Contribution</th>
                        <th>Validation</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:30px; color:#64748b;">No ESI contribution submissions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $idx = 1;
                        foreach ($history as $row): 
                            $status = $row['status'] ?? 'pending';
                            $badgeClass = ['verified' => 'badge-success', 'rejected' => 'badge-danger', 'pending' => 'badge-warning'][$status] ?? 'badge-gray';
                            $valStatus = $row['validation_status'] ?? 'pending';
                            $valBadgeClass = $valStatus === 'passed' ? 'badge-success' : ($valStatus === 'mismatch' ? 'badge-danger' : 'badge-warning');
                            
                            $my = explode('-', $row['month_year']);
                            $monthText = isset($months[$my[1]]) ? ($months[$my[1]] . ' ' . $my[0]) : $row['month_year'];
                        ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td><strong><?= htmlspecialchars($monthText) ?></strong></td>
                                <td><code><?= htmlspecialchars($row['challan_number'] ?? '-') ?></code></td>
                                <td><?= $row['challan_date'] ? date('d M Y', strtotime($row['challan_date'])) : '-' ?></td>
                                <td><strong>₹<?= number_format((float)$row['amount'], 2) ?></strong></td>
                                <td>
                                    <span class="badge <?= $valBadgeClass ?>"><?= strtoupper($valStatus) ?></span>
                                    <?php if (!empty($row['validation_errors'])): ?>
                                        <br>
                                        <div class="details-expander" onclick="toggleDetails(this)">
                                            <i class="fas fa-chevron-circle-down"></i> View Mismatches
                                        </div>
                                        <div class="hidden-details">
                                            <ul class="error-list">
                                                <?php 
                                                $errs = explode("\n", $row['validation_errors']);
                                                foreach ($errs as $err) {
                                                    if (trim($err) !== '') {
                                                        echo "<li>" . htmlspecialchars($err) . "</li>";
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= strtoupper($status) ?></span></td>
                                <td><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="../../uploads/compliance/<?= rawurlencode($row['file_path']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 8px; font-size:11px;">
                                            <i class="fas fa-file-pdf"></i> View Challan
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

    <!-- Upload Challan Modal -->
    <div id="uploadChallanModal" class="modal-overlay" onclick="handleOverlayClick(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-invoice" style="color:#ef4444;margin-right:6px"></i> Upload ESI Contribution Challan</h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <form id="esiContributionForm">
                <input type="hidden" name="type" value="esi">
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group span-2">
                            <label class="form-label required">Contribution Month</label>
                            <input type="month" class="form-control" name="contribution_month" id="modal_wage_month" required onchange="fetchMonthDetails(this.value)">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Challan Number</label>
                            <input type="text" class="form-control" name="challan_no" required placeholder="ESI Challan Ref No.">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Challan Date</label>
                            <input type="date" class="form-control" name="challan_date" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">No. of Employees</label>
                            <input type="number" class="form-control" name="employees_count" id="esi_employees" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Total Gross Wages (₹)</label>
                            <input type="number" class="form-control" name="gross_wages" id="esi_gross" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Employer Contribution (3.25% - ₹)</label>
                            <input type="number" class="form-control" name="employer_contribution" id="esi_employer" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Employee Contribution (0.75% - ₹)</label>
                            <input type="number" class="form-control" name="employee_contribution" id="esi_employee" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label required">Total Contribution (4% - ₹)</label>
                            <input type="number" class="form-control" name="total_contribution" id="esi_total" required readonly style="background:#f1f5f9; cursor:not-allowed; font-weight:700; color:#10b981;">
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label required">Upload Monthly Contribution Details PDF (From ESI Portal)</label>
                            <input type="file" class="form-control" name="challan_file" accept=".pdf" required>
                            <small class="form-hint">Upload the PDF downloaded from the ESI site after payment.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="esiSubmitBtn">Submit Challan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDetails(expander) {
            const container = expander.nextElementSibling;
            const icon = expander.querySelector('i');
            if (container.style.display === 'block') {
                container.style.display = 'none';
                icon.className = 'fas fa-chevron-circle-down';
            } else {
                container.style.display = 'block';
                icon.className = 'fas fa-chevron-circle-up';
            }
        }

        function downloadESITemplate() {
            const monthVal = document.getElementById('wage_month_select').value;
            if (!monthVal) {
                Swal.fire('Error', 'Please select a wage month first.', 'error');
                return;
            }
            window.open('../../api/contractor/download_esi_contribution.php?month_year=' + monthVal);
        }

        function openUploadModal() {
            const monthVal = document.getElementById('wage_month_select').value;
            document.getElementById('modal_wage_month').value = monthVal;
            document.getElementById('uploadChallanModal').classList.add('show');
            fetchMonthDetails(monthVal);
        }

        function closeUploadModal() {
            document.getElementById('uploadChallanModal').classList.remove('show');
        }

        function handleOverlayClick(e) {
            if (e.target.id === 'uploadChallanModal') closeUploadModal();
        }

        async function fetchMonthDetails(monthVal) {
            if (!monthVal) return;
            const contractorId = <?= json_encode($c_id) ?>;
            try {
                const response = await fetch('../../api/compliance/validate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ contractor_id: contractorId, month_year: monthVal })
                });
                const result = await response.json();
                if (result.success && result.data) {
                    const data = result.data;
                    const wageTotal = parseFloat(data.wage_total) || 0;
                    const workerCount = parseInt(data.worker_count) || 0;

                    document.getElementById('esi_employees').value = workerCount;
                    document.getElementById('esi_gross').value = wageTotal.toFixed(2);
                    document.getElementById('esi_employer').value = (wageTotal * 0.0325).toFixed(2);
                    document.getElementById('esi_employee').value = (wageTotal * 0.0075).toFixed(2);
                    document.getElementById('esi_total').value = (wageTotal * 0.04).toFixed(2);
                }
            } catch (err) {
                console.error('Error fetching details:', err);
            }
        }

        document.getElementById('esiContributionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('esiSubmitBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing & Parsing PDF...';

            const fd = new FormData(this);
            try {
                const res = await fetch('../../api/contractor/save_compliance.php', {
                    method: 'POST',
                    body: fd
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Submitted!',
                        text: 'ESI Contribution Challan uploaded and verified successfully.',
                        confirmButtonColor: '#1e3a8a'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Mismatch / Error',
                        text: result.message || 'Validation failed.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (err) {
                Swal.fire('Error', 'Network or server error occurred.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
    <?php
}

renderLayout("ESI Contribution", 'renderContent', $role, $name);
?>
