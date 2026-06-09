<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    // Fetch expired passes
    $today = date('Y-m-d');
    $query = "
        SELECT g.acc_card_number as pass_no, g.valid_to, g.pass_type, w.name, c.contractor_name
        FROM gate_passes g
        JOIN workmen w ON g.workman_id = w.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE g.valid_to < '$today' AND g.status = 'approved'
        ORDER BY g.valid_to DESC
        LIMIT 100
    ";
    
    $expired_passes = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-exclamation-triangle text-warning"></i> Expired Pass Alerts</h2>
      <p class="page-subtitle">Passes that have crossed their validity date and should be confiscated if seen.</p>
    </div>

    <div class="card glass border-warning">
        <div class="card-header bg-warning text-dark">
            <div class="card-title"><i class="fas fa-calendar-times"></i> Recently Expired Passes</div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Gate Pass No.</th>
                        <th>Pass Type</th>
                        <th>Contractor</th>
                        <th>Expired On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expired_passes)): ?>
                    <tr><td colspan="6" class="text-center">No expired active passes found.</td></tr>
                    <?php else: ?>
                        <?php foreach($expired_passes as $w): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($w['pass_no']) ?></code></td>
                            <td><?= $w['pass_type'] == 'perm' ? 'Permanent' : 'Temporary' ?></td>
                            <td><?= htmlspecialchars($w['contractor_name'] ?? 'Unknown') ?></td>
                            <td><span class="text-danger"><?= date('d M Y', strtotime($w['valid_to'])) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="alert('Record pass confiscation in physical register.')">Confiscate Pass</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Expired Pass Alerts", 'renderContent', $role, $name);

