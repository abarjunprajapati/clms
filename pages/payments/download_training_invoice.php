<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

$token = trim($_GET['token'] ?? '');
$request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
if (!$request) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

$contractor = db_single($conn, "SELECT contractor_name, vendor_name, gst_no, gst, address FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$request['contractor_id']]);
$workers = clms_training_payment_workers($conn, (int)$request['id']);
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="training-gst-invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $request['invoice_no']) . '.html"');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($request['invoice_no']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; color:#111827; margin:0; padding:28px; }
    .invoice { max-width:850px; margin:0 auto; border:1px solid #d1d5db; padding:28px; }
    .head { display:flex; justify-content:space-between; gap:20px; border-bottom:2px solid #111827; padding-bottom:16px; margin-bottom:20px; }
    h1 { margin:0; font-size:24px; }
    table { width:100%; border-collapse:collapse; margin-top:18px; }
    th, td { border:1px solid #d1d5db; padding:9px 10px; text-align:left; font-size:13px; }
    th { background:#f3f4f6; }
    .right { text-align:right; }
    .muted { color:#6b7280; font-size:12px; }
    .totals { width:360px; margin-left:auto; }
  </style>
</head>
<body>
  <div class="invoice">
    <div class="head">
      <div>
        <h1>GST Invoice</h1>
        <div class="muted">Safety Induction Training Fee</div>
      </div>
      <div class="right">
        <strong><?= htmlspecialchars($request['invoice_no']) ?></strong><br>
        Date: <?= htmlspecialchars(date('d/m/Y', strtotime($request['invoice_generated_at'] ?: $request['created_at']))) ?><br>
        Payment Ref: <?= htmlspecialchars($request['payment_ref']) ?>
      </div>
    </div>

    <table>
      <tr><th colspan="2">Bill To</th></tr>
      <tr><td>Contractor</td><td><?= htmlspecialchars($contractor['contractor_name'] ?: ($contractor['vendor_name'] ?? '')) ?></td></tr>
      <tr><td>GSTIN</td><td><?= htmlspecialchars($contractor['gst_no'] ?: ($contractor['gst'] ?? 'N/A')) ?></td></tr>
      <tr><td>Address</td><td><?= htmlspecialchars($contractor['address'] ?? '') ?></td></tr>
    </table>

    <table>
      <thead><tr><th>SL</th><th>Worker</th><th>Temporary ID</th><th class="right">Fee</th></tr></thead>
      <tbody>
        <?php $sl = 1; foreach ($workers as $worker): ?>
        <tr>
          <td><?= $sl++ ?></td>
          <td><?= htmlspecialchars($worker['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($worker['temp_id'] ?? '') ?></td>
          <td class="right"><?= number_format((float)$request['fee_per_worker'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <table class="totals">
      <tr><td>Subtotal</td><td class="right"><?= number_format((float)$request['subtotal_amount'], 2) ?></td></tr>
      <tr><td>GST <?= number_format((float)$request['gst_percent'], 2) ?>%</td><td class="right"><?= number_format((float)$request['gst_amount'], 2) ?></td></tr>
      <tr><th>Total Payable</th><th class="right">Rs. <?= number_format((float)$request['total_amount'], 2) ?></th></tr>
      <tr><td>Status</td><td class="right"><?= htmlspecialchars(strtoupper((string)$request['status'])) ?></td></tr>
    </table>

    <p class="muted">This invoice is system-generated for CLMS safety induction fee payment.</p>
  </div>
</body>
</html>
