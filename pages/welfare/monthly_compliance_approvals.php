<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

function renderContent() {
    global $conn;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $cert_id = (int)$_POST['cert_id'];
        $action = $_POST['action']; // 'verify' or 'reject'
        $remarks = $_POST['remarks'] ?? '';
        
        $status = ($action === 'verify') ? 'verified' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE compliance_certificates SET status = ?, remarks = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $remarks, $cert_id);
        $stmt->execute();
        
        echo '<div class="alert alert-success">Certificate status updated successfully!</div>';
    }

    $history = db_fetch_all($conn, "SELECT cc.*, c.contractor_name, c.vendor_code 
                                    FROM compliance_certificates cc 
                                    JOIN contractors c ON cc.contractor_id = c.id 
                                    ORDER BY cc.status = 'submitted' DESC, cc.period_from DESC");
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-certificate" style="color:#10b981;margin-right:10px;"></i> Monthly Compliance Certificates (Welfare)</h2>
        <p class="page-subtitle">Verify and approve monthly compliance declarations submitted by contractors.</p>
      </div>
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0;">
        <?php if (empty($history)): ?>
        <div style="text-align:center;padding:30px;color:#94a3b8;">No certificates found.</div>
        <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Contractor</th>
              <th>Period</th>
              <th>Workmen</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($h['contractor_name']) ?></strong><br>
                <small><?= htmlspecialchars($h['vendor_code']) ?></small>
              </td>
              <td><?= date('F Y', strtotime($h['period_from'])) ?></td>
              <td><?= $h['total_workmen'] ?></td>
              <td>
                <span class="badge <?= $h['status']==='verified'?'badge-success':($h['status']==='rejected'?'badge-danger':'badge-warning') ?>">
                  <?= ucfirst($h['status']) ?>
                </span>
              </td>
              <td>
                  <button class="btn btn-sm btn-outline" onclick="viewCert(<?= $h['id'] ?>)" title="View Details"><i class="fas fa-eye text-primary"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- View Modal -->
    <div id="certModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding: 24px; border-radius: 12px; width: 100%; max-width: 500px;">
            <h3 style="margin-top:0; margin-bottom: 20px;">Review Compliance Certificate</h3>
            <table class="table" style="width:100%; margin-bottom: 20px;">
                <tr><th>Contractor:</th><td id="m_contractor"></td></tr>
                <tr><th>Period:</th><td id="m_period"></td></tr>
                <tr><th>Total Workmen:</th><td id="m_workmen"></td></tr>
                <tr><th>Wages Paid:</th><td id="m_wages"></td></tr>
                <tr><th>ESI Remitted:</th><td id="m_esi"></td></tr>
                <tr><th>PF Remitted:</th><td id="m_pf"></td></tr>
                <tr><th>Contractor Remarks:</th><td id="m_cremarks"></td></tr>
            </table>
            
            <form method="POST">
                <input type="hidden" name="cert_id" id="m_cert_id">
                <div class="form-group" id="actionFormGroup">
                    <label class="form-label">Welfare Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Required if rejecting..."></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn" style="background:#f1f5f9; color:#475569;" onclick="document.getElementById('certModal').style.display='none'">Close</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger" id="btnReject">Reject</button>
                    <button type="submit" name="action" value="verify" class="btn btn-success" id="btnVerify">Verify</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const certHistory = <?= json_encode($history) ?>;
    
    function viewCert(id) {
        const cert = certHistory.find(c => c.id == id);
        if (!cert) return;
        const data = JSON.parse(cert.form_data);
        
        document.getElementById('m_cert_id').value = id;
        document.getElementById('m_contractor').textContent = cert.contractor_name;
        document.getElementById('m_period').textContent = new Date(cert.period_from).toLocaleString('default', { month: 'long', year: 'numeric' });
        document.getElementById('m_workmen').textContent = cert.total_workmen;
        document.getElementById('m_wages').textContent = 'Rs. ' + data.wages_paid;
        document.getElementById('m_esi').textContent = data.esi_paid;
        document.getElementById('m_pf').textContent = data.pf_paid;
        document.getElementById('m_cremarks').textContent = data.remarks || 'None';
        
        if (cert.status !== 'submitted') {
            document.getElementById('btnReject').style.display = 'none';
            document.getElementById('btnVerify').style.display = 'none';
        } else {
            document.getElementById('btnReject').style.display = 'block';
            document.getElementById('btnVerify').style.display = 'block';
        }

        document.getElementById('certModal').style.display = 'flex';
    }
    </script>
    <?php
}
renderLayout('Monthly Compliance Approvals', 'renderContent', $role, $name);
?>
