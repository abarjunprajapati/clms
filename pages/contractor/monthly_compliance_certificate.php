<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;
    ensureComplianceSchema($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_certificate'])) {
        $month_year = $_POST['month_year'] ?? '';
        $total_workmen = (int)($_POST['total_workmen'] ?? 0);
        $wages_paid = $_POST['wages_paid'] ?? '';
        $esi_paid = $_POST['esi_paid'] ?? 'No';
        $pf_paid = $_POST['pf_paid'] ?? 'No';
        $remarks = $_POST['remarks'] ?? '';

        if ($month_year && $c_id) {
            $period_from = $month_year . '-01';
            $period_to = date('Y-m-t', strtotime($period_from));
            $form_data = json_encode([
                'wages_paid' => $wages_paid,
                'esi_paid' => $esi_paid,
                'pf_paid' => $pf_paid,
                'remarks' => $remarks
            ]);
            $cert_date = date('Y-m-d');
            
            $stmt = $conn->prepare("INSERT INTO compliance_certificates (contractor_id, certificate_date, period_from, period_to, total_workmen, form_data, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?)");
            $stmt->bind_param("isssisi", $c_id, $cert_date, $period_from, $period_to, $total_workmen, $form_data, $user_id);
            $stmt->execute();
            echo '<div class="alert alert-success">Certificate submitted successfully for verification!</div>';
        }
    }

    $history = $c_id ? db_fetch_all($conn, "SELECT * FROM compliance_certificates WHERE contractor_id = ? ORDER BY period_from DESC", 'i', [$c_id]) : [];
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-certificate" style="color:#10b981;margin-right:10px;"></i> Monthly Compliance Certificate</h2>
        <p class="page-subtitle">Submit your monthly compliance declaration and download certificates.</p>
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

      <!-- Submission Form -->
      <div class="card glass">
        <div class="card-header"><div class="card-title">Submit New Certificate</div></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
                <label class="form-label required">Compliance Month</label>
                <input type="month" name="month_year" class="form-control" required max="<?= date('Y-m') ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Total Workmen Engaged</label>
                <input type="number" name="total_workmen" class="form-control" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Total Wages Paid (Rs)</label>
                <input type="number" name="wages_paid" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label required">ESI Contribution Remitted?</label>
                <select name="esi_paid" class="form-control" required>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                    <option value="NA">Not Applicable</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">PF Contribution Remitted?</label>
                <select name="pf_paid" class="form-control" required>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                    <option value="NA">Not Applicable</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Additional Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" name="submit_certificate" class="btn btn-primary w-100">Submit Declaration</button>
          </form>
        </div>
      </div>

      <!-- History -->
      <div class="card glass">
        <div class="card-header"><div class="card-title">Submission History</div></div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($history)): ?>
          <div style="text-align:center;padding:30px;color:#94a3b8;">No certificates submitted yet.</div>
          <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Workmen</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $h): ?>
              <tr>
                <td><?= date('F Y', strtotime($h['period_from'])) ?></td>
                <td><?= $h['total_workmen'] ?></td>
                <td>
                  <span class="badge <?= $h['status']==='verified'?'badge-success':($h['status']==='rejected'?'badge-danger':'badge-warning') ?>">
                    <?= ucfirst($h['status']) ?>
                  </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline" onclick="printCert(<?= $h['id'] ?>)" title="Download PDF"><i class="fas fa-file-pdf text-danger"></i></button>
                    <!-- <button class="btn btn-sm btn-outline" onclick="exportCert(<?= $h['id'] ?>)" title="Export Excel"><i class="fas fa-file-excel text-success"></i></button> -->
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Hidden Print Area -->
    <div id="printArea" style="display:none; padding:40px; background:white; color:black;">
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="margin:0; text-decoration:underline;">MONTHLY COMPLIANCE CERTIFICATE</h2>
            <h4 style="margin:5px 0;">Contractor: <?= htmlspecialchars($contractor['contractor_name']) ?></h4>
            <p>Code: <?= htmlspecialchars($contractor['vendor_code']) ?></p>
        </div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:30px;" border="1">
            <tr><th style="padding:10px;text-align:left;">Period</th><td id="p_month" style="padding:10px;"></td></tr>
            <tr><th style="padding:10px;text-align:left;">Total Workmen</th><td id="p_workmen" style="padding:10px;"></td></tr>
            <tr><th style="padding:10px;text-align:left;">Wages Paid</th><td id="p_wages" style="padding:10px;"></td></tr>
            <tr><th style="padding:10px;text-align:left;">ESI Remitted</th><td id="p_esi" style="padding:10px;"></td></tr>
            <tr><th style="padding:10px;text-align:left;">PF Remitted</th><td id="p_pf" style="padding:10px;"></td></tr>
        </table>
        <p>This is to certify that all statutory compliances for the aforementioned period have been strictly adhered to as per the statutory laws and regulations.</p>
        <div style="margin-top:50px; display:flex; justify-content:space-between;">
            <div>
                <p><strong>Status:</strong> <span id="p_status"></span></p>
                <p>Date: <span id="p_date"></span></p>
            </div>
            <div style="text-align:center;">
                <p>_______________________</p>
                <p>Authorized Signatory</p>
            </div>
        </div>
    </div>

    <script>
    const certHistory = <?= json_encode($history) ?>;
    
    function printCert(id) {
        const cert = certHistory.find(c => c.id == id);
        if (!cert) return;
        const data = JSON.parse(cert.form_data);
        
        document.getElementById('p_month').textContent = new Date(cert.period_from).toLocaleString('default', { month: 'long', year: 'numeric' });
        document.getElementById('p_workmen').textContent = cert.total_workmen;
        document.getElementById('p_wages').textContent = 'Rs. ' + data.wages_paid;
        document.getElementById('p_esi').textContent = data.esi_paid;
        document.getElementById('p_pf').textContent = data.pf_paid;
        document.getElementById('p_status').textContent = cert.status.toUpperCase();
        document.getElementById('p_date').textContent = new Date(cert.certificate_date).toLocaleDateString();
        
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Compliance Certificate</title></head><body style="font-family:sans-serif;">');
        printWindow.document.write(document.getElementById('printArea').innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    }
    </script>
    <?php
}
renderLayout('Monthly Compliance Certificate', 'renderContent', $role, $name);
?>
