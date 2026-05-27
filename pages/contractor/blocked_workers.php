<?php
require_once '../../include/auth.php';
checkAuth(['contractor']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    if (!$c_id) {
        echo '<div class="alert alert-warning">Complete your registration first.</div>';
        return;
    }

    // Fetch blocked workers
    $blocked_workers = db_fetch_all($conn, "
        SELECT id, name, aadhaar, acc_number, blocked_source, status 
        FROM workmen 
        WHERE is_blocked = 1 AND contractor_id = ?
        ORDER BY updated_at DESC
    ", 'i', [$c_id]);

    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-slash text-danger"></i> Blocked Workers</h2>
        <p class="page-subtitle">View workers whose gate passes have been suspended or revoked by authorities.</p>
      </div>
    </div>

    <div class="card glass">
        <div class="card-header">
            <div class="card-title text-danger">Currently Blocked List</div>
        </div>
        <div class="card-body p-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Aadhaar</th>
                        <th>ACC Number</th>
                        <th>Blocked By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($blocked_workers as $w): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($w['name']) ?></td>
                        <td><code><?= substr($w['aadhaar'], 0, 4) . ' **** ****' ?></code></td>
                        <td><code><?= htmlspecialchars($w['acc_number'] ?? 'N/A') ?></code></td>
                        <td><span class="badge badge-warning"><?= strtoupper($w['blocked_source'] ?? 'ADMIN') ?></span></td>
                        <td><span class="badge badge-danger">BLOCKED</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick="requestUnblock(<?= $w['id'] ?>)"><i class="fas fa-envelope"></i> Request Unblock</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($blocked_workers)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success" style="font-size:24px; opacity:0.5; display:block; margin-bottom:10px;"></i> All clear. You have no blocked workers.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle"></i>
        <div>Blocked workers cannot punch IN/OUT at the gate. If a worker is blocked by Safety (e.g., violation), you must contact the Safety Department to request unblocking.</div>
    </div>

    <script>
    function requestUnblock(id) {
        const reason = prompt("Enter justification for unblocking this worker:");
        if(!reason) return;
        alert("Unblock request sent to the concerned department. (Simulation)");
        // In a real scenario, this would call an API to log an unblock request in a ticketing table.
    }
    </script>
    <?php
}

renderLayout('Blocked Workers', 'renderContent', $role, $name);
?>
