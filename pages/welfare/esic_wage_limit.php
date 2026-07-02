<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

// Ensure settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default if not exists
$conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('esic_daily_wage_limit', '827')");

function renderContent() {
    global $conn;

    $success = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['esic_limit'])) {
        $limit = (float)$_POST['esic_limit'];
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'esic_daily_wage_limit'");
        $stmt->bind_param("s", $limit);
        $stmt->execute();
        $success = 'ESIC Daily Wage Limit updated successfully!';
    }

    $res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'esic_daily_wage_limit'");
    $current_limit = $res->fetch_assoc()['setting_value'] ?? '827';
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-cog" style="color:#6366f1;margin-right:10px;"></i> ESIC Wage Limit Settings</h2>
        <p class="page-subtitle">Configure the maximum ESIC daily wage coverage limit as per Government rules.</p>
      </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><div><?= htmlspecialchars($success) ?></div></div>
    <?php endif; ?>

    <div class="card glass" style="max-width:500px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-indian-rupee-sign"></i> Update Limit</div></div>
        <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:16px;">
                <i class="fas fa-info-circle"></i>
                <div>As per the Workmen Compensation Policy requirements (Point f), this limit is referenced when contractors declare whether their daily wages exceed the ESIC coverage limit.</div>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label required">ESIC Daily Wage Coverage Limit (Rs)</label>
                    <input type="number" step="0.01" class="form-control" name="esic_limit" value="<?= htmlspecialchars($current_limit) ?>" required>
                    <small class="form-hint">Default is Rs. 827 per day (Eg. Rs. 827*26/day as per document). Contractors will see this when uploading Workmen Compensation Policy.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Limit</button>
            </form>
        </div>
    </div>

    <?php
}

renderLayout('ESIC Wage Limit Settings', 'renderContent', $role, $name);
?>
