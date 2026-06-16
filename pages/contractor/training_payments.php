<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once '../../include/payment_flow.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;
    clms_ensure_payment_flow($conn);

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    $paymentRequests = $c_id ? db_fetch_all(
        $conn,
        "SELECT * FROM training_payment_requests WHERE contractor_id = ? ORDER BY id DESC",
        'i',
        [(int)$c_id]
    ) : [];

    $paymentWorkersMap = [];
    if ($c_id && !empty($paymentRequests)) {
        $pwRows = db_fetch_all($conn,
            "SELECT pw.payment_request_id, w.name, w.temp_id
             FROM training_payment_request_workers pw
             JOIN workmen w ON pw.workman_id = w.id
             JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
             WHERE pr.contractor_id = ?",
            'i', [(int)$c_id]
        );
        foreach ($pwRows as $row) {
            $paymentWorkersMap[$row['payment_request_id']][] = htmlspecialchars($row['name']) . ($row['temp_id'] ? " (" . htmlspecialchars($row['temp_id']) . ")" : "");
        }
    }
    ?>

    <div class="content-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <div>
        <h2 class="page-title"><i class="fas fa-credit-card" style="color:#8b5cf6;margin-right:10px;"></i> Safety Training Payments History</h2>
      </div>
      <div>
        <a class="btn btn-outline" href="training_request.php">
          <i class="fas fa-arrow-left"></i> Back to Training Requests
        </a>
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete <a href="annexure-2a.php">Contractor Registration</a> first.</div></div>
    <?php return; endif; ?>

    <div class="card glass">
      <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div class="card-title"><i class="fas fa-history"></i> Payments History</div>
        <span class="badge badge-gray"><?= count($paymentRequests) ?> Records</span>
      </div>
      <div class="card-body" style="padding:15px; display:grid; gap:12px;">
        <?php if (empty($paymentRequests)): ?>
        <div style="padding:40px; text-align:center; color:var(--text-muted);">
          <i class="fas fa-credit-card" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
          <p>No payment requests found.</p>
        </div>
        <?php else: ?>
          <?php foreach ($paymentRequests as $pay): 
            $payStatus = strtolower((string)$pay['status']);
            $payExpired = !empty($pay['link_expires_at']) && strtotime($pay['link_expires_at']) < time() && $payStatus !== 'paid';
            $payBadge = $payExpired ? 'badge-danger' : ($payStatus === 'paid' ? 'badge-success' : 'badge-warning');
            $payText = $payExpired ? 'EXPIRED' : strtoupper(str_replace('_', ' ', $payStatus));
            $workersList = $paymentWorkersMap[$pay['id']] ?? [];
          ?>
          <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:12px; display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">
            <div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-weight:700; font-size:15px;">Rs. <?= number_format((float)$pay['total_amount'], 2) ?></span>
                <span class="badge <?= $payBadge ?>" style="font-size:10px; padding:3px 6px;"><?= htmlspecialchars($payText) ?></span>
              </div>
              <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                Ref: <code><?= htmlspecialchars($pay['payment_ref']) ?></code>
                <?php if (!empty($pay['link_expires_at'])): ?>
                  | Valid till: <?= htmlspecialchars(date('d M Y h:i A', strtotime($pay['link_expires_at']))) ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($workersList)): ?>
                <div style="font-size:11px; margin-top:6px; color:var(--text-primary);">
                  <strong>Workmen (<?= count($workersList) ?>):</strong> <?= implode(', ', $workersList) ?>
                </div>
              <?php endif; ?>
            </div>
            <div style="display:flex; gap:8px;">
              <?php if ($payStatus !== 'paid' && !$payExpired): ?>
                <a class="btn btn-sm btn-primary" href="payment.php?token=<?= urlencode($pay['payment_token']) ?>">
                  <i class="fas fa-credit-card"></i> Pay Fee
                </a>
              <?php elseif ($payStatus === 'paid'): ?>
                <a class="btn btn-sm btn-outline" href="payment.php?token=<?= urlencode($pay['payment_token']) ?>">
                  <i class="fas fa-eye"></i> Receipt
                </a>
              <?php endif; ?>
              <a class="btn btn-sm btn-outline" href="../payments/download_training_invoice.php?token=<?= urlencode($pay['payment_token']) ?>">
                <i class="fas fa-file-invoice"></i> GST Invoice
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

renderLayout("Safety Payments History", 'renderContent', $role, $name);
?>
