<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/payment_flow.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    clms_ensure_payment_flow($conn);
    $demo = clms_demo_payment_details($conn);
    $payments = db_fetch_all(
        $conn,
        "SELECT pr.*, c.contractor_name, c.vendor_name
         FROM training_payment_requests pr
         LEFT JOIN contractors c ON c.id = pr.contractor_id
         ORDER BY FIELD(pr.status, 'submitted', 'link_sent', 'gateway_created', 'pending', 'paid'), pr.created_at DESC
         LIMIT 100"
    );
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-credit-card" style="color:#0f766e;margin-right:10px;"></i> Payment Gateway / QR Control</h2>
  </div>
</div>

<div style="display:grid;grid-template-columns:380px 1fr;gap:18px;align-items:start;">
  <section class="card glass">
    <div class="card-header"><div class="card-title">Demo QR Gateway</div></div>
    <div class="card-body">
      <form id="paymentSettingsForm" enctype="multipart/form-data">
        <input type="hidden" name="payment_gateway_provider" value="demo_qr">
        <div class="form-group">
          <label class="form-label">Merchant Name</label>
          <input class="form-control" name="payment_demo_merchant_name" value="<?= htmlspecialchars($demo['merchant_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">UPI ID / Payment Handle</label>
          <input class="form-control" name="payment_demo_upi_id" value="<?= htmlspecialchars($demo['upi_id']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Fee Per Worker</label>
          <input class="form-control" type="number" step="0.01" min="0" name="training_fee_per_worker" value="<?= htmlspecialchars(clms_training_fee_per_worker($conn)) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">GST Percent</label>
          <input class="form-control" type="number" step="0.01" min="0" name="training_payment_gst_percent" value="<?= htmlspecialchars(clms_training_payment_gst_percent($conn)) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Link Validity Hours</label>
          <input class="form-control" type="number" step="1" min="1" name="training_payment_link_valid_hours" value="<?= htmlspecialchars(clms_training_payment_link_hours($conn)) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Upload QR Image</label>
          <input class="form-control" type="file" name="payment_qr" accept=".png,.jpg,.jpeg,.webp">
        </div>
        <?php if (!empty($demo['qr_url'])): ?>
        <div style="text-align:center;margin:12px 0;">
          <img src="<?= htmlspecialchars($demo['qr_url']) ?>" alt="Current QR" style="max-width:180px;border:1px solid #dbe4ef;border-radius:8px;padding:8px;background:#fff;">
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px;word-break:break-all;"><?= htmlspecialchars($demo['qr_url']) ?></div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning" style="font-size:12px;margin:10px 0;">
          <i class="fas fa-qrcode"></i>
          <div>No QR uploaded yet. Upload QR image and save settings.</div>
        </div>
        <?php endif; ?>
        <button class="btn btn-primary" style="width:100%;" type="submit" id="savePaymentSettingsBtn"><i class="fas fa-save"></i> Save Gateway Settings</button>
      </form>
    </div>
  </section>

  <section class="card glass">
    <div class="card-header">
      <div class="card-title">Training Payment Requests</div>
      <span class="badge badge-gray"><?= count($payments) ?> Records</span>
    </div>
    <div class="card-body" style="padding:0;overflow:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Ref</th>
            <th>Contractor</th>
            <th>Amount</th>
            <th>Status</th>
            <th>UTR / Reference</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$payments): ?>
          <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--text-muted);">No payment requests.</td></tr>
          <?php endif; ?>
          <?php foreach ($payments as $p):
            $status = strtolower((string)$p['status']);
            $badge = $status === 'paid' ? 'badge-success' : ($status === 'submitted' ? 'badge-info' : 'badge-warning');
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($p['payment_ref']) ?></strong><br>
              <small><?= htmlspecialchars($p['invoice_no'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($p['contractor_name'] ?: ($p['vendor_name'] ?? '')) ?></td>
            <td><strong>Rs. <?= number_format((float)$p['total_amount'], 2) ?></strong><br><small><?= (int)$p['worker_count'] ?> worker(s)</small></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $status))) ?></span></td>
            <td>
              <?= htmlspecialchars($p['payer_reference'] ?: '-') ?>
              <?php if (!empty($p['contractor_payment_note'])): ?><br><small><?= htmlspecialchars($p['contractor_payment_note']) ?></small><?php endif; ?>
            </td>
            <td>
              <?php if ($status === 'submitted'): ?>
              <button class="btn btn-sm btn-success" onclick="verifyPayment(<?= (int)$p['id'] ?>, 'approve')"><i class="fas fa-check"></i> Verify</button>
              <button class="btn btn-sm btn-outline" onclick="verifyPayment(<?= (int)$p['id'] ?>, 'reject')"><i class="fas fa-times"></i> Reject</button>
              <?php else: ?>
              <a class="btn btn-sm btn-outline" target="_blank" href="../payment.php?token=<?= urlencode($p['payment_token']) ?>"><i class="fas fa-eye"></i> View</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<style>
  .form-group { margin-bottom:12px; }
  .form-label { display:block;font-size:13px;font-weight:700;margin-bottom:6px; }
  .form-control { width:100%;padding:10px 12px;border-radius:8px;border:1.5px solid var(--border-color);box-sizing:border-box; }
  .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:700;box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981;color:white; } .toast-error { background:#ef4444;color:white; }
  @media(max-width:980px){ div[style*="grid-template-columns:380px"]{grid-template-columns:1fr!important;} }
</style>

<script>
document.getElementById('paymentSettingsForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('savePaymentSettingsBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving';
  try {
    const res = await fetch('../../api/welfare/update_payment_gateway.php', {
      method: 'POST',
      body: new FormData(e.target)
    });
    const result = await res.json();
    showToast(result.message || (result.success ? 'Saved.' : 'Failed.'), result.success ? 'success' : 'error');
    if (result.success) setTimeout(() => location.reload(), 900);
  } catch (err) {
    showToast('Unable to save payment settings.', 'error');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-save"></i> Save Gateway Settings';
});

async function verifyPayment(paymentId, action) {
  const remarks = prompt(action === 'approve' ? 'Verification remarks:' : 'Rejection reason:') || '';
  if (action === 'reject' && !remarks.trim()) return;
  try {
    const res = await fetch('../../api/welfare/verify_training_payment.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ payment_id: paymentId, action, remarks })
    });
    const result = await res.json();
    showToast(result.message || (result.success ? 'Done.' : 'Failed.'), result.success ? 'success' : 'error');
    if (result.success) setTimeout(() => location.reload(), 900);
  } catch (err) {
    showToast('Unable to update payment.', 'error');
  }
}

function showToast(msg, type) {
  const t = document.createElement('div');
  t.className = 'toast-msg toast-' + type;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php
}

renderLayout('Payment Gateway', 'renderContent', $role, $name);
