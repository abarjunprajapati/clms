<?php
require_once __DIR__ . '/../include/session.php';

$currentRole = get_normalized_role() ?? 'guest';
$userName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Access Denied — Contractor Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #0f2447 0%, #1a3c6e 50%, #2563a8 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }
    .denied-card {
      background: rgba(255,255,255,0.95);
      color: #1e293b;
      border-radius: 20px;
      padding: 48px 40px;
      max-width: 480px;
      width: 90%;
      text-align: center;
      box-shadow: 0 24px 64px rgba(0,0,0,0.3);
    }
    .denied-icon {
      width: 80px; height: 80px;
      background: #fee2e2;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 24px;
      font-size: 36px;
      color: #ef4444;
    }
    .denied-title { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
    .denied-text { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
    .denied-role {
      display: inline-block;
      background: #e2e8f0;
      color: #475569;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      margin-bottom: 24px;
    }
    .btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 24px; border-radius: 10px;
      font-size: 14px; font-weight: 600;
      text-decoration: none; border: none; cursor: pointer;
      transition: all 0.2s;
    }
    .btn-primary { background: #2563a8; color: #fff; }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-light { background: #f1f5f9; color: #475569; margin-left: 8px; }
    .btn-light:hover { background: #e2e8f0; }
  </style>
</head>
<body>
  <div class="denied-card">
    <div class="denied-icon"><i class="fas fa-shield-alt"></i></div>
    <div class="denied-title">Access Denied</div>
    <div class="denied-text">
      Sorry, <strong><?= htmlspecialchars($userName) ?></strong>.<br>
      Your current role <span class="denied-role"><?= htmlspecialchars($currentRole) ?></span>
      does not have permission to view this page.
    </div>
    <div>
      <a href="<?= isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '../index.php' ?>" class="btn btn-light">
        <i class="fas fa-arrow-left"></i> Go Back
      </a>
      <a href="../index.php" class="btn btn-primary">
        <i class="fas fa-home"></i> Dashboard
      </a>
    </div>
  </div>
</body>
</html>


