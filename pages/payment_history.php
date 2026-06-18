<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/payment_flow.php';

$contractor = null;
$paymentRequests = [];
if (!empty($_SESSION['user_id'])) {
    $contractor = clms_get_current_contractor_for_payment(
        $conn,
        (int)$_SESSION['user_id'],
        $_SESSION['contractor_id'] ?? ($_SESSION['vendor_code'] ?? '')
    );
    if ($contractor) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment History / Requests</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <style>
    .payment-page { max-width: 1040px; margin: 0 auto; padding: 28px 16px; }
    .payment-hero {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 18px;
      margin-bottom: 20px;
      padding: 18px;
      border: 1px solid #dbe4ef;
      border-radius: 8px;
      background: #ffffff;
      box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
    }
    .payment-title-block {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }
    .payment-title-icon {
      width: 46px;
      height: 46px;
      flex: 0 0 46px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      color: #ffffff;
      background: #1e293b;
      box-shadow: 0 10px 24px rgba(30, 41, 59, .18);
    }
    .payment-title {
      margin: 0;
      color: #0f172a;
      font-size: 23px;
      line-height: 1.2;
      font-weight: 800;
    }
    .payment-subtitle {
      color: #64748b;
      font-size: 13px;
      margin-top: 5px;
      font-weight: 600;
    }
    .payment-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 14px 35px rgba(15, 23, 42, .07); overflow: hidden; }
    .payment-head { padding: 18px 20px; border-bottom: 1px solid #e5edf6; display:flex; justify-content:space-between; gap:16px; align-items:center; }
    .payment-head h1 { margin:0; font-size: 17px; color:#0f172a; font-weight:800; }
    .payment-body { padding: 20px; }
    .badge-pay { padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; text-transform:uppercase; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 5px; letter-spacing: .03em; }
    .badge-pending { background:#fef3c7; color:#92400e; border-color: #fde68a; }
    .badge-paid { background:#d1fae5; color:#065f46; border-color: #a7f3d0; }
    .badge-expired { background:#fee2e2; color:#991b1b; border-color: #fecaca; }
    .request-list { display:grid; gap:10px; margin-bottom:18px; }
    .request-item { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; padding:12px 14px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; text-decoration:none; color:#111827; box-shadow: 0 8px 20px rgba(15, 23, 42, .04); transition: .2s; }
    .request-item:hover { transform: translateY(-1px); border-color:#93c5fd; background:#f8fbff; box-shadow:0 8px 22px rgba(15,23,42,.08); }
    .request-meta { font-size:12px; color:#64748b; margin-top:3px; }
    .request-amount { font-weight:900; color:#0f766e; text-align:right; font-size: 16px; }
    .btn-full { width:100%; justify-content:center; }
    body.payment-screen {
      background:
        linear-gradient(180deg, #eef4fb 0, #f8fafc 235px),
        #f8fafc;
    }
  </style>
</head>
<body class="payment-screen">
<div class="payment-page">
  <div class="payment-hero">
    <div class="payment-title-block">
      <div class="payment-title-icon"><i class="fas fa-history"></i></div>
      <div>
        <h1 class="payment-title">Payment History & Requests</h1>
        <div class="payment-subtitle">View and track all previous safety training fee payment requests.</div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <a href="payment.php" class="btn btn-outline"><i class="fas fa-credit-card"></i> Make Payment</a>
      <a href="contractor/dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
  </div>

  <section class="payment-card">
    <div class="payment-head">
      <h1>All Payment Requests</h1>
      <span style="font-size:12px;color:#64748b;font-weight:700;"><?= count($paymentRequests) ?> request(s) found</span>
    </div>
    <div class="payment-body">
      <?php if (empty($paymentRequests)): ?>
        <div style="color:#64748b;font-size:14px;text-align:center;padding:40px 0;">
          <i class="fas fa-folder-open" style="font-size:36px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
          No payment requests found.
        </div>
      <?php else: ?>
        <div class="request-list">
          <?php foreach ($paymentRequests as $pr): ?>
            <?php
              $prStatus = strtolower((string)$pr['status']);
              $prWorkers = clms_training_payment_workers($conn, (int)$pr['id']);
              $workerNames = array_slice(array_map(function($w) { return $w['name'] ?? 'Worker'; }, $prWorkers), 0, 3);
              $moreWorkers = max(0, count($prWorkers) - count($workerNames));
              $namesText = implode(', ', $workerNames) . ($moreWorkers > 0 ? " +{$moreWorkers} more" : '');
              
              $prExpired = !empty($pr['link_expires_at']) && strtotime($pr['link_expires_at']) < time() && !in_array($prStatus, ['paid', 'verified'], true);
              $prBadgeClass = $prExpired ? 'badge-expired' : (in_array($prStatus, ['paid', 'verified'], true) ? 'badge-paid' : 'badge-pending');
              $prBadgeText = $prExpired ? 'Expired' : (in_array($prStatus, ['pending', 'link_sent', 'gateway_created', 'submitted'], true) ? 'Pending' : ucfirst($prStatus));
            ?>
            <a class="request-item" href="payment.php?token=<?= urlencode($pr['payment_token']) ?>">
              <div>
                <strong><?= htmlspecialchars($pr['payment_ref']) ?></strong>
                <span class="badge-pay <?= $prBadgeClass ?>" style="margin-left:8px;"><?= htmlspecialchars($prBadgeText) ?></span>
                <div class="request-meta">
                  <?= (int)$pr['worker_count'] ?> worker(s)<?= $namesText ? ' | ' . htmlspecialchars($namesText) : '' ?>
                  <br>
                  <span style="font-size:11px;color:#94a3b8;">Created: <?= date('d M Y h:i A', strtotime($pr['created_at'])) ?></span>
                </div>
              </div>
              <div class="request-amount">Rs. <?= number_format((float)$pr['total_amount'], 2) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
</body>
</html>
