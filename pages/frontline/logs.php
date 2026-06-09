<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    $today = date('Y-m-d');
    
    // Fetch logs
    $query = "
        SELECT a.check_in as entry_time, a.check_out as exit_time, w.name, g.acc_card_number as gatepass_no, c.contractor_name
        FROM attendance a
        JOIN workmen w ON a.workman_id = w.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN gate_passes g ON w.id = g.workman_id AND g.status = 'approved'
        WHERE DATE(a.check_in) = '$today'
        ORDER BY a.check_in DESC
        LIMIT 200
    ";
    
    $logs = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-history text-secondary"></i> Daily Entry/Exit Logs</h2>
      <p class="page-subtitle">Movement history for today.</p>
    </div>

    <div class="card glass">
        <div class="card-header">
            <div class="card-title">Movement Logs (Today)</div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Direction</th>
                        <th>Worker Name</th>
                        <th>Gate Pass No.</th>
                        <th>Contractor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center">No movement recorded today.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                        
                        <?php if ($log['exit_time']): ?>
                        <tr>
                            <td><?= date('H:i:s', strtotime($log['exit_time'])) ?></td>
                            <td><span class="badge badge-danger"><i class="fas fa-sign-out-alt"></i> EXIT</span></td>
                            <td><?= htmlspecialchars($log['name']) ?></td>
                            <td><code><?= htmlspecialchars($log['gatepass_no']) ?></code></td>
                            <td><?= htmlspecialchars($log['contractor_name'] ?? 'Unknown') ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($log['entry_time']): ?>
                        <tr>
                            <td><?= date('H:i:s', strtotime($log['entry_time'])) ?></td>
                            <td><span class="badge badge-success"><i class="fas fa-sign-in-alt"></i> ENTRY</span></td>
                            <td><?= htmlspecialchars($log['name']) ?></td>
                            <td><code><?= htmlspecialchars($log['gatepass_no']) ?></code></td>
                            <td><?= htmlspecialchars($log['contractor_name'] ?? 'Unknown') ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Entry/Exit Logs", 'renderContent', $role, $name);

