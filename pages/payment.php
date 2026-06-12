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
$autoPay = (int)($_GET['auto_pay'] ?? 0) === 1;
$selectedWorkerId = (int)($_GET['selected_worker_id'] ?? $_GET['worker_id'] ?? 0);
$request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
$paymentRequests = [];
$contractor = null;
$pendingWorkers = [];
$pendingTotal = 0;
$feePerWorker = clms_training_fee_per_worker($conn);
if (!empty($_SESSION['user_id'])) {
    $contractor = clms_get_current_contractor_for_payment(
        $conn,
        (int)$_SESSION['user_id'],
        $_SESSION['contractor_id'] ?? ($_SESSION['vendor_code'] ?? '')
    );
    if ($contractor) {
        payment_sync_missing_worker_requests($conn, (int)$contractor['id'], (int)$_SESSION['user_id']);
        $pendingWorkers = clms_pending_safety_fee_workers($conn, (int)$contractor['id']);
        foreach ($pendingWorkers as $pendingWorker) {
            $pendingTotal += (float)$pendingWorker['safety_fee'];
        }
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
$bookingWorkerId = count($workers) === 1 ? (int)($workers[0]['id'] ?? 0) : 0;
$bookingUrl = 'contractor/book_safety_training.php' . ($bookingWorkerId > 0 ? '?worker_id=' . urlencode((string)$bookingWorkerId) : '');
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
    .pending-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .summary-tile { border:1px solid #dbe4ef; border-radius:8px; padding:14px; background:#f8fafc; }
    .summary-tile span { display:block; font-size:12px; color:#64748b; font-weight:800; text-transform:uppercase; }
    .summary-tile strong { display:block; margin-top:5px; color:#111827; font-size:20px; }
    .pending-tools { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .pending-tools input { width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:10px 11px; font-size:13px; box-sizing:border-box; }
    .pending-table-wrap { overflow:auto; border:1px solid #dbe4ef; border-radius:8px; }
    .pending-table { width:100%; border-collapse:collapse; min-width:780px; }
    .pending-table th, .pending-table td { padding:11px 12px; border-bottom:1px solid #edf2f7; text-align:left; font-size:13px; vertical-align:middle; }
    .pending-table th { background:#f8fafc; color:#475569; font-weight:900; font-size:12px; text-transform:uppercase; }
    .pending-table tr:last-child td { border-bottom:0; }
    .pending-table input[type="checkbox"] { width:17px; height:17px; cursor:pointer; }
    .worker-code { font-weight:900; color:#1d4ed8; }
    .live-total { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-top:14px; padding:14px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:8px; }
    .live-total strong { color:#0f172a; }
    .live-total .total-amount { font-size:22px; color:#0f766e; font-weight:900; }
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
    .payment-page {
      max-width: 1180px;
      padding: 30px 18px 42px;
    }
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
    .payment-card {
      border-color: #e2e8f0;
      box-shadow: 0 14px 35px rgba(15, 23, 42, .07);
    }
    .payment-head {
      background: #ffffff;
      padding: 17px 20px;
    }
    .payment-head h1 {
      display: flex;
      align-items: center;
      gap: 9px;
      font-size: 17px;
      color: #0f172a;
      font-weight: 800;
    }
    .payment-body { padding: 20px; }
    .summary-tile {
      position: relative;
      min-height: 92px;
      background: #ffffff;
      border-color: #e2e8f0;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }
    .summary-tile::after {
      content: '';
      position: absolute;
      right: 14px;
      top: 14px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #3b82f6;
      box-shadow: 0 0 0 5px #dbeafe;
    }
    .summary-tile:nth-child(2)::after {
      background: #0f766e;
      box-shadow: 0 0 0 5px #ccfbf1;
    }
    .summary-tile:nth-child(3)::after {
      background: #f59e0b;
      box-shadow: 0 0 0 5px #fef3c7;
    }
    .summary-tile strong { font-size: 22px; letter-spacing: 0; }
    .pending-tools {
      grid-template-columns: repeat(4, minmax(150px, 1fr));
      padding: 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #f8fafc;
    }
    .pending-tools input {
      background: #ffffff;
      border-color: #dbe4ef;
      font-weight: 600;
      color: #334155;
    }
    .pending-tools input:focus {
      outline: none;
      border-color: #1e293b;
      box-shadow: 0 0 0 3px rgba(30, 41, 59, .10);
    }
    .pending-table-wrap {
      border-color: #e2e8f0;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, .65);
    }
    .pending-table th {
      background: #f8fafc;
      color: #475569;
      white-space: nowrap;
      letter-spacing: .04em;
    }
    .pending-table td { color: #334155; }
    .pending-table tbody tr:hover td { background: #f8fafc; }
    .worker-code {
      display: inline-flex;
      align-items: center;
      min-height: 26px;
      padding: 3px 8px;
      border-radius: 6px;
      background: #eff6ff;
      color: #1d4ed8;
    }
    .badge-pay {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      letter-spacing: .03em;
      border: 1px solid transparent;
    }
    .badge-pending { border-color: #fde68a; }
    .badge-paid { border-color: #a7f3d0; }
    .badge-expired { border-color: #fecaca; }
    .live-total {
      position: sticky;
      bottom: 12px;
      border-color: #c7d2fe;
      background: #ffffff;
      box-shadow: 0 16px 38px rgba(15, 23, 42, .13);
    }
    .request-item {
      border-color: #e2e8f0;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }
    .request-item:hover {
      transform: translateY(-1px);
      border-color: #93c5fd;
      background: #f8fbff;
    }
    .request-item.active {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px #dbeafe;
    }
    .amount { color: #0f766e; letter-spacing: 0; }
    .kv:last-child { border-bottom: 0; }
    .worker-row {
      align-items: center;
      border-bottom-color: #eef2f7;
    }
    .worker-row code {
      border-radius: 6px;
      background: #f1f5f9;
      color: #334155;
      padding: 4px 7px;
      font-weight: 800;
    }
    .pay-modal { backdrop-filter: blur(3px); }
    .pay-modal-dialog { border-radius: 8px; }
    .qr-box { border-color: #cbd5e1; box-shadow: 0 10px 24px rgba(15, 23, 42, .08); }
    body.payment-screen {
      background:
        linear-gradient(180deg, #eef4fb 0, #f8fafc 235px),
        #f8fafc;
    }
    @media (max-width: 860px) {
      .payment-grid { grid-template-columns:1fr; }
      .pending-summary, .pending-tools { grid-template-columns:1fr; }
      .payment-hero { align-items: stretch; flex-direction: column; }
      .payment-hero .btn { width: 100%; justify-content: center; }
      .live-total { position: static; }
    }
    @media (max-width: 560px) {
      .payment-page { padding: 16px 12px 28px; }
      .payment-title-block { align-items: flex-start; }
      .payment-title-icon { width: 40px; height: 40px; flex-basis: 40px; }
      .payment-title { font-size: 19px; }
      .payment-card, .payment-hero { border-radius: 8px; }
      .payment-head, .payment-body { padding: 15px; }
      .live-total .btn { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body class="payment-screen">
<div class="payment-page">
  <div class="payment-hero">
    <div class="payment-title-block">
      <div class="payment-title-icon"><i class="fas fa-shield-halved"></i></div>
      <div>
        <h1 class="payment-title">Safety Induction Fee Payment</h1>
        <div class="payment-subtitle">Review pending PWO workers, select fees, and complete payment securely.</div>
      </div>
    </div>
    <a href="contractor/dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>

  <?php if (!$request): ?>
    <section class="payment-card">
      <div class="payment-head">
        <h1>Pending Safety Fee Payment</h1>
        <span style="font-size:12px;color:#64748b;font-weight:800;">PWO workers only</span>
      </div>
      <div class="payment-body">
        <div class="pending-summary">
          <div class="summary-tile"><span>Total Pending Workers</span><strong><?= count($pendingWorkers) ?></strong></div>
          <div class="summary-tile"><span>Total Pending Amount</span><strong>Rs. <?= number_format((float)$pendingTotal, 2) ?></strong></div>
          <div class="summary-tile"><span>Fee Per Worker</span><strong>Rs. <?= number_format((float)$feePerWorker, 2) ?></strong></div>
        </div>

        <div class="pending-tools">
          <input type="text" id="filterProject" placeholder="Filter Project">
          <input type="text" id="filterContractor" placeholder="Filter Contractor">
          <input type="text" id="filterWorkOrder" placeholder="Filter Work Order">
          <input type="text" id="filterEnrollment" placeholder="Filter Enrollment No / Date">
        </div>

        <?php if (!$pendingWorkers): ?>
          <div class="pay-alert"><i class="fas fa-circle-info"></i><span>No pending safety fee payment found.</span></div>
        <?php else: ?>
          <div class="pending-table-wrap">
            <table class="pending-table" id="pendingWorkersTable">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllWorkers" aria-label="Select all workers"></th>
                  <th>Workmen ID</th>
                  <th>Name</th>
                  <th>Work Order</th>
                  <th>Enrollment No</th>
                  <th>Date</th>
                  <th>Safety Fee</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingWorkers as $worker): ?>
                  <?php
                    $isPreselected = $selectedWorkerId > 0 && $selectedWorkerId === (int)$worker['id'];
                    $dateText = !empty($worker['enrollment_date']) ? date('d M Y', strtotime($worker['enrollment_date'])) : '';
                  ?>
                  <tr
                    data-project="<?= htmlspecialchars(strtolower($worker['project_name'] ?? '')) ?>"
                    data-contractor="<?= htmlspecialchars(strtolower(($contractor['contractor_name'] ?? '') . ' ' . ($contractor['vendor_name'] ?? '') . ' ' . ($contractor['vendor_code'] ?? ''))) ?>"
                    data-work-order="<?= htmlspecialchars(strtolower($worker['work_order_no'] ?? '')) ?>"
                    data-enrollment="<?= htmlspecialchars(strtolower(($worker['application_no'] ?? '') . ' ' . $dateText)) ?>"
                  >
                    <td><input type="checkbox" class="worker-pay-check" value="<?= (int)$worker['id'] ?>" data-fee="<?= htmlspecialchars((string)$worker['safety_fee']) ?>" <?= $isPreselected ? 'checked' : '' ?>></td>
                    <td><span class="worker-code"><?= htmlspecialchars($worker['worker_code'] ?? ('W' . $worker['id'])) ?></span></td>
                    <td><strong><?= htmlspecialchars($worker['name'] ?? 'Worker') ?></strong></td>
                    <td><?= htmlspecialchars($worker['work_order_no'] ?? '') ?></td>
                    <td><?= htmlspecialchars($worker['application_no'] ?? '') ?></td>
                    <td><?= htmlspecialchars($dateText) ?></td>
                    <td>Rs. <?= number_format((float)$worker['safety_fee'], 2) ?></td>
                    <td><span class="badge-pay badge-pending">Pending</span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="live-total">
            <div>
              <strong>Selected Workers : <span id="selectedWorkerCount">0</span></strong>
              <div style="font-size:12px;color:#1d4ed8;margin-top:3px;">Individual payment ke liye ek worker select karein, bulk payment ke liye multiple workers tick karein.</div>
            </div>
            <div>
              <div style="font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase;">Total Amount</div>
              <div class="total-amount">Rs. <span id="selectedTotalAmount">0.00</span></div>
            </div>
            <button class="btn btn-primary" type="button" id="paySelectedWorkersBtn" disabled>
              <i class="fas fa-credit-card"></i> Pay Selected
            </button>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($paymentRequests): ?>
      <section class="payment-card" style="margin-top:18px;">
        <div class="payment-head">
          <h1>Recent Payment Requests</h1>
          <span style="font-size:12px;color:#64748b;font-weight:700;"><?= count($paymentRequests) ?> request(s)</span>
        </div>
        <div class="payment-body">
          <div class="request-list">
            <?php foreach ($paymentRequests as $pr): ?>
              <?php
                $prStatus = strtolower((string)$pr['status']);
                $prExpired = !empty($pr['link_expires_at']) && strtotime($pr['link_expires_at']) < time() && !in_array($prStatus, ['paid', 'verified'], true);
                $prBadgeClass = $prExpired ? 'badge-expired' : (in_array($prStatus, ['paid', 'verified'], true) ? 'badge-paid' : 'badge-pending');
                $prBadgeText = $prExpired ? 'Expired' : (in_array($prStatus, ['pending', 'link_sent', 'gateway_created', 'submitted'], true) ? 'Pending' : ucfirst($prStatus));
              ?>
              <a class="request-item" href="payment.php?token=<?= urlencode($pr['payment_token']) ?>">
                <div>
                  <strong><?= htmlspecialchars($pr['payment_ref']) ?></strong>
                  <span class="badge-pay <?= $prBadgeClass ?>" style="margin-left:8px;"><?= htmlspecialchars($prBadgeText) ?></span>
                  <div class="request-meta"><?= (int)$pr['worker_count'] ?> worker(s)</div>
                </div>
                <div class="request-amount">Rs. <?= number_format((float)$pr['total_amount'], 2) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
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
                $prBadgeText = $prExpired ? 'Expired' : (in_array($prStatus, ['pending', 'link_sent', 'gateway_created', 'submitted'], true) ? 'Pending' : ucfirst($prStatus));
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
            $badgeText = $isExpired ? 'Expired' : (in_array($status, ['pending', 'link_sent', 'gateway_created', 'submitted'], true) ? 'Pending' : ucfirst($status));
          ?>
          <span class="badge-pay <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
        </div>
        <div class="payment-body">
          <?php if (!$gatewayReady && !$isExpired && $status !== 'paid'): ?>
            <div class="pay-alert"><i class="fas fa-key"></i><span>Payment gateway keys are not configured yet. Demo QR payment is available when provider is set to demo_qr.</span></div>
          <?php elseif ($isExpired): ?>
            <div class="pay-alert"><i class="fas fa-clock"></i><span>This payment link has expired. Please generate a fresh safety fee payment request.</span></div>
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
            <?php if ($status === 'paid'): ?>
              <a class="btn btn-primary btn-full" href="<?= htmlspecialchars($bookingUrl) ?>">
                <i class="fas fa-calendar-check"></i> Safety Training & Seat Booking
              </a>
            <?php endif; ?>
            <button class="btn btn-primary btn-full" id="payBtn" <?= (!$gatewayReady || $isExpired || in_array($status, ['paid'], true)) ? 'disabled' : '' ?>>
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
            <div class="qr-placeholder" title="Payment QR"></div>
          <?php endif; ?>
        </div>
        <strong id="demoMerchant"><?= htmlspecialchars($demoDetails['merchant_name']) ?></strong>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">UPI: <code id="demoUpi"><?= htmlspecialchars($demoDetails['upi_id']) ?></code></div>
        <div style="font-size:22px;font-weight:900;color:#0f766e;margin-top:8px;">Rs. <?= number_format((float)$request['total_amount'], 2) ?></div>
      </div>
      <label>Payment Reference / UTR</label>
      <input type="text" id="payerReference" placeholder="Example: DEMO123456 / UTR number" required>
      <label>Note</label>
      <textarea id="payerNote" rows="2" placeholder="Optional payment note"></textarea>
      <button class="btn btn-primary btn-full" id="submitDemoPaymentBtn" type="button" onclick="submitDemoPayment()">
        <i class="fas fa-check"></i> Confirm Safety Fee Payment
      </button>
      <div style="font-size:12px;color:#64748b;text-align:center;">After payment success, continue with Safety Training & Seat Booking.</div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const workerChecks = Array.from(document.querySelectorAll('.worker-pay-check'));
const selectedWorkerCount = document.getElementById('selectedWorkerCount');
const selectedTotalAmount = document.getElementById('selectedTotalAmount');
const selectAllWorkers = document.getElementById('selectAllWorkers');
const paySelectedWorkersBtn = document.getElementById('paySelectedWorkersBtn');
const pendingRows = Array.from(document.querySelectorAll('#pendingWorkersTable tbody tr'));

function visiblePendingRows() {
  return pendingRows.filter(row => row.style.display !== 'none');
}

function updateSelectedTotal() {
  const selected = workerChecks.filter(input => input.checked && input.closest('tr')?.style.display !== 'none');
  const total = selected.reduce((sum, input) => sum + Number(input.dataset.fee || 0), 0);
  if (selectedWorkerCount) selectedWorkerCount.textContent = selected.length;
  if (selectedTotalAmount) selectedTotalAmount.textContent = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  if (paySelectedWorkersBtn) paySelectedWorkersBtn.disabled = selected.length === 0;
  if (selectAllWorkers) {
    const visible = visiblePendingRows();
    const visibleChecks = visible.map(row => row.querySelector('.worker-pay-check')).filter(Boolean);
    selectAllWorkers.checked = visibleChecks.length > 0 && visibleChecks.every(input => input.checked);
    selectAllWorkers.indeterminate = visibleChecks.some(input => input.checked) && !selectAllWorkers.checked;
  }
}

function applyPendingFilters() {
  const project = (document.getElementById('filterProject')?.value || '').trim().toLowerCase();
  const contractor = (document.getElementById('filterContractor')?.value || '').trim().toLowerCase();
  const workOrder = (document.getElementById('filterWorkOrder')?.value || '').trim().toLowerCase();
  const enrollment = (document.getElementById('filterEnrollment')?.value || '').trim().toLowerCase();
  pendingRows.forEach(row => {
    const show = (!project || row.dataset.project.includes(project))
      && (!contractor || row.dataset.contractor.includes(contractor))
      && (!workOrder || row.dataset.workOrder.includes(workOrder))
      && (!enrollment || row.dataset.enrollment.includes(enrollment));
    row.style.display = show ? '' : 'none';
    if (!show) {
      const check = row.querySelector('.worker-pay-check');
      if (check) check.checked = false;
    }
  });
  updateSelectedTotal();
}

workerChecks.forEach(input => input.addEventListener('change', updateSelectedTotal));
if (selectAllWorkers) {
  selectAllWorkers.addEventListener('change', () => {
    visiblePendingRows().forEach(row => {
      const check = row.querySelector('.worker-pay-check');
      if (check) check.checked = selectAllWorkers.checked;
    });
    updateSelectedTotal();
  });
}
['filterProject', 'filterContractor', 'filterWorkOrder', 'filterEnrollment'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', applyPendingFilters);
});
if (paySelectedWorkersBtn) {
  paySelectedWorkersBtn.addEventListener('click', async () => {
    const workerIds = workerChecks
      .filter(input => input.checked && input.closest('tr')?.style.display !== 'none')
      .map(input => Number(input.value))
      .filter(Boolean);
    if (!workerIds.length) {
      alert('Please select at least one pending worker.');
      return;
    }
    paySelectedWorkersBtn.disabled = true;
    paySelectedWorkersBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Payment';
    try {
      const res = await fetch('../api/payments/create_selected_training_payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ worker_ids: workerIds })
      });
      const result = await res.json();
      if (!result.success || !result.payment || !result.payment.payment_link) {
        alert(result.message || 'Payment request generate nahi ho pa raha.');
        paySelectedWorkersBtn.disabled = false;
        paySelectedWorkersBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Selected';
        return;
      }
      const joiner = result.payment.payment_link.indexOf('?') >= 0 ? '&' : '?';
      window.location.href = result.payment.payment_link + joiner + 'auto_pay=1';
    } catch (err) {
      alert('Unable to generate payment request.');
      paySelectedWorkersBtn.disabled = false;
      paySelectedWorkersBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Selected';
    }
  });
}
updateSelectedTotal();

const payBtn = document.getElementById('payBtn');
const autoPay = <?= $autoPay ? 'true' : 'false' ?>;
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
if (autoPay && payBtn && !payBtn.disabled) {
  window.setTimeout(() => payBtn.click(), 250);
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
    alert(result.message || (result.success ? 'Payment successful.' : 'Payment failed.'));
    if (result.success) window.location.href = <?= json_encode($bookingUrl) ?>;
  } catch (err) {
    alert('Unable to submit payment details.');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-check"></i> Confirm Safety Fee Payment';
}
</script>
</body>
</html>
