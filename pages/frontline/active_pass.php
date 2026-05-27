<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    // Fetch active passes that are currently inside today
    $today = date('Y-m-d');
    $query = "
        SELECT a.check_in as entry_time, w.name, g.acc_card_number as gatepass_no, w.trade, c.contractor_name, g.pass_type
        FROM attendance a
        JOIN workmen w ON a.workman_id = w.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN gate_passes g ON w.id = g.workman_id AND g.status = 'approved'
        WHERE DATE(a.check_in) = '$today' AND a.check_in IS NOT NULL AND a.check_out IS NULL
        ORDER BY a.check_in DESC
    ";
    
    $active_workers = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-id-badge text-info"></i> Active Workers Inside</h2>
      <p class="page-subtitle">Real-time view of workers currently checked into the premises.</p>
    </div>

    <div class="card glass">
        <div class="card-header">
            <div class="card-title">Currently Inside: <?= count($active_workers) ?></div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Gate Pass No.</th>
                        <th>Pass Type</th>
                        <th>Contractor</th>
                        <th>Trade</th>
                        <th>Entry Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_workers)): ?>
                    <tr><td colspan="6" class="text-center">No workers currently inside.</td></tr>
                    <?php else: ?>
                        <?php foreach($active_workers as $w): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($w['gatepass_no'] ?? 'N/A') ?></code></td>
                            <td>
                                <?php if($w['pass_type'] == 'permanent'): ?>
                                    <span class="badge badge-success">Permanent (ACC)</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Temporary</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($w['contractor_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($w['trade'] ?? 'N/A') ?></td>
                            <td><?= date('H:i:s', strtotime($w['entry_time'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Active Pass List", 'renderContent', $role, $name);

