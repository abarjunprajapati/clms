<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Officer';

function renderContent() {
    ?>
    <div class="card glass" style="padding: 100px 40px; text-align: center;">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; width: 80px; height: 80px; margin: 0 auto 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 32px;">
            <i class="fas fa-lock"></i>
        </div>
        <h2 style="font-weight: 800; margin-bottom: 12px;">Approval Desk Disabled</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 30px;">
            Customer Registration approval desk is currently disabled as per the new workflow requirements.
        </p>
        <a href="admin_dashboard.php" class="btn btn-primary btn-lg" style="padding: 12px 32px;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    <?php
}
renderLayout('Approve Customer Registration', 'renderContent', $role, $name);
?>

