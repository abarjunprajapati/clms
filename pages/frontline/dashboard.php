<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    // Quick Stats for Dashboard
    $today = date('Y-m-d');
    
    // Active workers currently inside (present and no exit_time or marked present)
    // For simplicity, let's just count attendance today with entry but no exit
    $active_inside = db_count($conn, "SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = '$today' AND check_in IS NOT NULL AND check_out IS NULL");
    
    // Total entries today
    $total_entries = db_count($conn, "SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = '$today' AND check_in IS NOT NULL");
    
    // Total exits today
    $total_exits = db_count($conn, "SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = '$today' AND check_out IS NOT NULL");
    
    // Blocked attempts (let's assume we log them in audit_log or similar)
    $blocked_attempts = db_count($conn, "SELECT COUNT(*) c FROM audit_logs WHERE DATE(created_at) = '$today' AND action = 'gate_entry_rejected'");

    ?>
    <div class="content-header">
      <h2 class="page-title">Frontline Execution Desk</h2>
      <p class="page-subtitle">Real-time gate monitoring and security enforcement.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(99,102,241,0.1);color:#6366f1"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $active_inside ?></div>
        <div class="stat-label">Currently Inside</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(34,197,94,0.1);color:#22c55e"><i class="fas fa-sign-in-alt"></i></div>
        <div class="stat-value"><?= $total_entries ?></div>
        <div class="stat-label">Total Entries (Today)</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(234,179,8,0.1);color:#eab308"><i class="fas fa-sign-out-alt"></i></div>
        <div class="stat-value"><?= $total_exits ?></div>
        <div class="stat-label">Total Exits (Today)</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:#ef4444"><i class="fas fa-user-shield"></i></div>
        <div class="stat-value"><?= $blocked_attempts ?></div>
        <div class="stat-label">Blocked Attempts (Today)</div>
      </div>
    </div>

    <div class="row" style="margin-top: 20px; display: flex; gap: 20px;">
        <div class="col" style="flex: 1;">
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-qrcode"></i> Quick Actions</div>
                </div>
                <div class="card-body" style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="entry_validation.php" class="btn btn-success" style="flex: 1; text-align: center; padding: 20px; font-size: 16px;"><i class="fas fa-sign-in-alt fa-2x" style="margin-bottom: 10px; display: block;"></i> Gate Entry</a>
                    <a href="exit_validation.php" class="btn btn-danger" style="flex: 1; text-align: center; padding: 20px; font-size: 16px;"><i class="fas fa-sign-out-alt fa-2x" style="margin-bottom: 10px; display: block;"></i> Gate Exit</a>
                    <a href="../demo-punch-machine.php" target="_blank" class="btn btn-primary" style="flex: 1; text-align: center; padding: 20px; font-size: 16px; background:#6366f1; border:none;"><i class="fas fa-fingerprint fa-2x" style="margin-bottom: 10px; display: block;"></i> Biometric Punch</a>
                </div>
            </div>
        </div>
        <div class="col" style="flex: 1;">
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-exclamation-triangle"></i> System Alerts</div>
                </div>
                <div class="card-body">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <!-- Alerts would be fetched via JS or pre-rendered here -->
                        <li style="padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1);"><i class="fas fa-info-circle text-info"></i> Shift 1 active. Check valid timings.</li>
                        <li style="padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1);"><i class="fas fa-shield-alt text-warning"></i> Biometric terminal online.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php
}

renderLayout("Frontline Dashboard", 'renderContent', $role, $name);

