<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/payment_flow.php';

function payment_sync_missing_worker_requests($conn, $contractorId, $userId = 0) {
    if (!$contractorId) return;
    clms_ensure_payment_flow($conn);
    $workers = db_fetch_all(
        $conn,
        "SELECT w.id
         FROM workmen w
         WHERE w.contractor_id = ?
           AND COALESCE(w.status, '') <> 'draft'
           AND COALESCE(w.execution_training_status, '') = 'pending_payment'
           AND NOT EXISTS (
               SELECT 1
               FROM training_payment_request_workers pw
               JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
               WHERE pw.workman_id = w.id
                 AND pr.status IN ('pending', 'link_sent', 'gateway_created', 'submitted', 'paid')
           )
         ORDER BY w.id ASC",
        'i',
        [(int)$contractorId]
    );
    foreach ($workers as $worker) {
        clms_create_training_payment_request($conn, (int)$contractorId, [(int)$worker['id']], (int)$userId, 'payment_page_recovery');
    }
}

$token = trim($_GET['token'] ?? '');
$request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
$paymentRequests = [];
if (!$request && !empty($_SESSION['user_id'])) {
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [(int)$_SESSION['user_id']]);
    if ($contractor) {
        payment_sync_missing_worker_requests($conn, (int)$contractor['id'], (int)$_SESSION['user_id']);
        $paymentRequests = db_fetch_all(
            $conn,
            "SELECT *
             FROM training_payment_requests
             WHERE contractor_id = ?
             ORDER BY
                FIELD(status, 'submitted', 'link_sent', 'gateway_created', 'pending', 'paid') ASC,
                COALESCE(updated_at, created_at) DESC,
                id DESC",
            'i',
            [(int)$contractor['id']]
        );
        $request = db_single(
            $conn,
            "SELECT *
             FROM training_payment_requests
             WHERE contractor_id = ?
             ORDER BY
                CASE WHEN status IN ('paid', 'verified') THEN 1 ELSE 0 END ASC,
                FIELD(status, 'submitted', 'link_sent', 'gateway_created', 'pending', 'paid') ASC,
                COALESCE(updated_at, created_at) DESC,
                id DESC
             LIMIT 1",
            'i',
            [(int)$contractor['id']]
        );
        $token = $request['payment_token'] ?? '';
    }
} elseif (!empty($_SESSION['user_id'])) {
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [(int)$_SESSION['user_id']]);
    if ($contractor) {
        payment_sync_missing_worker_requests($conn, (int)$contractor['id'], (int)$_SESSION['user_id']);
        $paymentRequests = db_fetch_all(
            $conn,
            "SELECT *
             FROM training_payment_requests
             WHERE contractor_id = ?
             ORDER BY
                FIELD(status, 'submitted', 'link_sent', 'gateway_created', 'pending', 'paid') ASC,
                COALESCE(updated_at, created_at) DESC,
                id DESC",
            'i',
            [(int)$contractor['id']]
        );
    }
}

$gatewayReady = clms_payment_gateway_configured($conn);
$workers = $request ? clms_training_payment_workers($conn, (int)$request['id']) : [];
$isExpired = $request && !empty($request['link_expires_at']) && strtotime($request['link_expires_at']) < time() && !in_array($request['status'], ['paid', 'verified'], true);
$demoDetails = clms_demo_payment_details($conn, $request);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Safety Training Payment</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <style>
    .payment-page { max-width: 1040px; margin: 0 auto; padding: 28px 16px; }
    .payment-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 18px; align-items: start; }
    .payment-card { background: #fff; border: 1px solid #dbe4ef; border-radius: 8px; box-shadow: 0 10px 28px rgba(15,23,42,.08); overflow: hidden; }
    .payment-head { padding: 18px 20px; border-bottom: 1px solid #e5edf6; display:flex; justify-content:space-between; gap:16px; align-items:center; }
    .payment-head h1 { margin:0; font-size: 20px; color:#1f2937; }
    .payment-body { padding: 20px; }
    .kv { display:flex; justify-content:space-between; gap:16px; padding:10px 0; border-bottom:1px solid #edf2f7; font-size:14px; }
    .kv span:first-child { color:#64748b; font-weight:700; }
    .amount { font-size: 34px; line-height: 1; font-weight: 900; color:#0f766e; margin: 6px 0 2px; }
    .badge-pay { padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; text-transform:uppercase; }
    .badge-pending { background:#fef3c7; color:#92400e; }
    .badge-paid { background:#d1fae5; color:#065f46; }
    .badge-expired { background:#fee2e2; color:#991b1b; }
    .worker-row { display:grid; grid-template-columns:1fr auto; gap:10px; padding:10px 0; border-bottom:1px solid #edf2f7; }
    .worker-row strong { color:#1f2937; }
    .worker-row small { display:block; color:#64748b; margin-top:3px; }
    .pay-alert { display:flex; gap:10px; padding:12px 14px; border-radius:8px; background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; font-size:13px; font-weight:700; margin-bottom:16px; }
    .pay-actions { display:grid; gap:10px; margin-top:16px; }
    .request-list { display:grid; gap:10px; margin-bottom:18px; }
    .request-item { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; padding:12px 14px; border:1px solid #dbe4ef; border-radius:8px; background:#fff; text-decoration:none; color:#111827; }
    .request-item:hover { border-color:#94a3b8; box-shadow:0 8px 22px rgba(15,23,42,.08); }
    .request-item.active { border-color:#2563eb; background:#eff6ff; }
    .request-meta { font-size:12px; color:#64748b; margin-top:3px; }
    .request-amount { font-weight:900; color:#0f766e; text-align:right; }
    .btn-full { width:100%; justify-content:center; }
    .pay-modal { display:none; position:fixed; inset:0; z-index:3000; background:rgba(15,23,42,.58); padding:18px; overflow:auto; }
    .pay-modal.is-open { display:flex; align-items:flex-start; justify-content:center; }
    .pay-modal-dialog { width:min(520px,100%); background:#fff; border-radius:10px; border:1px solid #dbe4ef; margin-top:28px; box-shadow:0 24px 60px rgba(15,23,42,.25); overflow:hidden; }
    .pay-modal-head { padding:16px 18px; border-bottom:1px solid #e5edf6; display:flex; justify-content:space-between; align-items:center; gap:14px; }
    .pay-modal-head h2 { margin:0; font-size:18px; color:#111827; }
    .pay-modal-head button { width:34px; height:34px; border:1px solid #dbe4ef; background:#fff; border-radius:8px; font-size:22px; cursor:pointer; }
    .qr-box { width:220px; height:220px; margin:0 auto 12px; border:1px solid #dbe4ef; border-radius:8px; display:flex; align-items:center; justify-content:center; background:#fff; overflow:hidden; }
    .qr-box img { width:100%; height:100%; object-fit:contain; }
    .qr-placeholder { width:180px; height:180px; background:repeating-linear-gradient(45deg,#111827 0 8px,#fff 8px 16px); opacity:.18; border-radius:6px; }
    .modal-form { display:grid; gap:10px; padding:18px; }
    .modal-form label { font-size:13px; font-weight:700; color:#334155; }
    .modal-form input, .modal-form textarea { width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:14px; box-sizing:border-box; }
    @media (max-width: 860px) { .payment-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body style="background:#f8fafc;">
<div class="payment-page">
  <div style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
    <div>
      <h1 style="margin:0;font-size:24px;color:#111827;">Safety Induction Fee Payment</h1>
      <div style="font-size:13px;color:#64748b;margin-top:4px;">Temporary ID training fee and GST invoice</div>
    </div>
    <a href="contractor/dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>

  <?php if (!$request): ?>
    <div class="payment-card"><div class="payment-body">
      <div class="pay-alert"><i class="fas fa-circle-info"></i><span>No payment request found. Please use the payment link sent after enrolment/training request.</span></div>
    </div></div>
  <?php else: ?>
    <?php if (count($paymentRequests) > 1): ?>
      <section class="payment-card" style="margin-bottom:18px;">
        <div class="payment-head">
          <h1>All Payment Requests</h1>
          <span style="font-size:12px;color:#64748b;font-weight:700;"><?= count($paymentRequests) ?> request(s)</span>
        </div>
        <div class="payment-body">
          <div class="request-list">
            <?php foreach ($paymentRequests as $pr): ?>
              <?php
                $prStatus = strtolower((string)$pr['status']);
                $prWorkers = clms_training_payment_workers($conn, (int)$pr['id']);
                $workerNames = array_slice(array_map(function($w) { return $w['name'] ?? 'Worker'; }, $prWorkers), 0, 3);
                $moreWorkers = max(0, count($prWorkers) - count($workerNames));
                $namesText = implode(', ', $workerNames) . ($moreWorkers > 0 ? " +{$moreWorkers} more" : '');
                $isCurrent = (int)($request['id'] ?? 0) === (int)$pr['id'];
                $prExpired = !empty($pr['link_expires_at']) && strtotime($pr['link_expires_at']) < time() && !in_array($prStatus, ['paid', 'verified'], true);
                $prBadgeClass = $prExpired ? 'badge-expired' : ($prStatus === 'paid' ? 'badge-paid' : 'badge-pending');
                $prBadgeText = $prExpired ? 'Expired' : ucfirst($prStatus);
              ?>
              <a class="request-item <?= $isCurrent ? 'active' : '' ?>" href="payment.php?token=<?= urlencode($pr['payment_token']) ?>">
                <div>
                  <strong><?= htmlspecialchars($pr['payment_ref']) ?></strong>
                  <span class="badge-pay <?= $prBadgeClass ?>" style="margin-left:8px;"><?= htmlspecialchars($prBadgeText) ?></span>
                  <div class="request-meta"><?= (int)$pr['worker_count'] ?> worker(s)<?= $namesText ? ' | ' . htmlspecialchars($namesText) : '' ?></div>
                </div>
                <div class="request-amount">Rs. <?= number_format((float)$pr['total_amount'], 2) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
          <div style="font-size:12px;color:#64748b;">Jis worker/payment ki fee pay karni hai, us request ko select karke Pay Online karein.</div>
        </div>
      </section>
    <?php endif; ?>
    <div class="payment-grid">
      <section class="payment-card">
        <div class="payment-head">
          <h1>Payment Request</h1>
          <?php
            $status = strtolower((string)$request['status']);
            $badgeClass = $isExpired ? 'badge-expired' : ($status === 'paid' ? 'badge-paid' : 'badge-pending');
            $badgeText = $isExpired ? 'Expired' : ucfirst($status);
          ?>
          <span class="badge-pay <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
        </div>
        <div class="payment-body">
          <?php if (!$gatewayReady && !$isExpired && $status !== 'paid'): ?>
            <div class="pay-alert"><i class="fas fa-key"></i><span>Payment gateway keys are not configured yet. Demo QR payment is available when provider is set to demo_qr.</span></div>
          <?php elseif ($status === 'submitted'): ?>
            <div class="pay-alert"><i class="fas fa-hourglass-half"></i><span>Payment reference submitted. Welfare verification is pending.</span></div>
          <?php elseif ($isExpired): ?>
            <div class="pay-alert"><i class="fas fa-clock"></i><span>This payment link has expired. Please ask Welfare/Admin to regenerate the link.</span></div>
          <?php endif; ?>

          <div style="color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;">Amount Payable</div>
          <div class="amount">Rs. <?= number_format((float)$request['total_amount'], 2) ?></div>
          <div style="font-size:12px;color:#64748b;">Ref: <?= htmlspecialchars($request['payment_ref']) ?></div>

          <div style="margin-top:16px;">
            <div class="kv"><span>Workers</span><strong><?= (int)$request['worker_count'] ?></strong></div>
            <div class="kv"><span>Fee / worker</span><strong>Rs. <?= number_format((float)$request['fee_per_worker'], 2) ?></strong></div>
            <div class="kv"><span>Subtotal</span><strong>Rs. <?= number_format((float)$request['subtotal_amount'], 2) ?></strong></div>
            <div class="kv"><span>GST <?= number_format((float)$request['gst_percent'], 2) ?>%</span><strong>Rs. <?= number_format((float)$request['gst_amount'], 2) ?></strong></div>
            <div class="kv"><span>Valid Till</span><strong><?= htmlspecialchars(date('d M Y h:i A', strtotime($request['link_expires_at']))) ?></strong></div>
          </div>

          <div class="pay-actions">
            <button class="btn btn-primary btn-full" id="payBtn" <?= (!$gatewayReady || $isExpired || in_array($status, ['paid', 'submitted'], true)) ? 'disabled' : '' ?>>
              <i class="fas fa-credit-card"></i> Pay Online
            </button>
            <a class="btn btn-outline btn-full" href="payments/download_training_invoice.php?token=<?= urlencode($token) ?>">
              <i class="fas fa-file-invoice"></i> Download GST Invoice
            </a>
          </div>
        </div>
      </section>

      <aside class="payment-card">
        <div class="payment-head"><h1>Workers</h1></div>
        <div class="payment-body">
          <?php if (!$workers): ?>
            <div style="color:#64748b;font-size:13px;">No linked workers.</div>
          <?php else: foreach ($workers as $worker): ?>
            <div class="worker-row">
              <div>
                <strong><?= htmlspecialchars($worker['name'] ?? 'Worker') ?></strong>
                <small><?= htmlspecialchars($worker['worker_type'] ?? '') ?></small>
              </div>
              <code><?= htmlspecialchars($worker['temp_id'] ?? '') ?></code>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</div>

<?php if ($request): ?>
<div class="pay-modal" id="demoPayModal" aria-hidden="true">
  <div class="pay-modal-dialog">
    <div class="pay-modal-head">
      <h2>Demo QR Payment</h2>
      <button type="button" onclick="closeDemoPay()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-form">
      <div style="text-align:center;">
        <div class="qr-box" id="qrBox">
          <?php if (!empty($demoDetails['qr_url'])): ?>
            <img src="<?= htmlspecialchars($demoDetails['qr_url']) ?>" alt="Payment QR">
          <?php else: ?>
            <div class="qr-placeholder" title="Upload QR from Welfare Payment Gateway page"></div>
          <?php endif; ?>
        </div>
        <strong id="demoMerchant"><?= htmlspecialchars($demoDetails['merchant_name']) ?></strong>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">UPI: <code id="demoUpi"><?= htmlspecialchars($demoDetails['upi_id']) ?></code></div>
        <div style="font-size:22px;font-weight:900;color:#0f766e;margin-top:8px;">Rs. <?= number_format((float)$request['total_amount'], 2) ?></div>
      </div>
      <label>Payment Reference / UTR</label>
      <input type="text" id="payerReference" placeholder="Example: DEMO123456 / UTR number" required>
      <label>Note</label>
      <textarea id="payerNote" rows="2" placeholder="Optional note for Welfare verification"></textarea>
      <button class="btn btn-primary btn-full" id="submitDemoPaymentBtn" type="button" onclick="submitDemoPayment()">
        <i class="fas fa-paper-plane"></i> Submit For Welfare Verification
      </button>
      <div style="font-size:12px;color:#64748b;text-align:center;">Demo flow: scan/pay offline, enter reference, Welfare verifies from payment desk.</div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const payBtn = document.getElementById('payBtn');
if (payBtn) {
  payBtn.addEventListener('click', async () => {
    payBtn.disabled = true;
    payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating order';
    try {
      const res = await fetch('../api/payments/create_training_order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ token: <?= json_encode($token) ?> })
      });
      const result = await res.json();
      if (!result.success) {
        alert(result.message || 'Payment gateway is not ready.');
        payBtn.disabled = false;
        payBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Online';
        return;
      }
      if (result.checkout_mode === 'demo_qr') {
        openDemoPay(result.demo || {});
        return;
      }
      alert('Gateway order created. Provider checkout wiring will be enabled for the selected provider.');
    } catch (err) {
      alert('Unable to start payment.');
      payBtn.disabled = false;
      payBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Online';
    }
  });
}

function openDemoPay(details) {
  const modal = document.getElementById('demoPayModal');
  if (!modal) return;
  if (details.merchant_name) document.getElementById('demoMerchant').textContent = details.merchant_name;
  if (details.upi_id) document.getElementById('demoUpi').textContent = details.upi_id;
  if (details.qr_url) {
    document.getElementById('qrBox').innerHTML = `<img src="${details.qr_url}" alt="Payment QR">`;
  }
  modal.classList.add('is-open');
  modal.setAttribute('aria-hidden', 'false');
}

function closeDemoPay() {
  const modal = document.getElementById('demoPayModal');
  if (!modal) return;
  modal.classList.remove('is-open');
  modal.setAttribute('aria-hidden', 'true');
}

async function submitDemoPayment() {
  const btn = document.getElementById('submitDemoPaymentBtn');
  const payerReference = document.getElementById('payerReference').value.trim();
  if (!payerReference) {
    alert('Payment reference / UTR is required.');
    return;
  }
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';
  try {
    const res = await fetch('../api/payments/submit_demo_payment.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        token: <?= json_encode($token) ?>,
        payer_reference: payerReference,
        note: document.getElementById('payerNote').value
      })
    });
    const result = await res.json();
    alert(result.message || (result.success ? 'Submitted.' : 'Submission failed.'));
    if (result.success) location.reload();
  } catch (err) {
    alert('Unable to submit payment details.');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit For Welfare Verification';
}
</script>
</body>
</html>
