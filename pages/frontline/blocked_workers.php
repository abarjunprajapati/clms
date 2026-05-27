<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    // Fetch blocked workers
    $query = "
        SELECT b.reason, b.blocked_at, w.name, g.acc_card_number as gatepass_no, w.aadhaar as aadhar, c.contractor_name, u.name as blocked_by_name
        FROM worker_blocks b
        JOIN workmen w ON b.workman_id = w.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN users u ON b.blocked_by = u.id
        LEFT JOIN gate_passes g ON w.id = g.workman_id AND g.status = 'approved'
        ORDER BY b.blocked_at DESC
    ";
    
    $blocked_workers = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-user-slash text-danger"></i> Blocked Workers Watchlist</h2>
      <p class="page-subtitle">These workers must be strictly denied entry.</p>
    </div>

    <div class="card glass border-danger">
        <div class="card-header bg-danger text-white">
            <div class="card-title text-white"><i class="fas fa-ban"></i> Restricted Persons</div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Identifier</th>
                        <th>Contractor</th>
                        <th>Block Reason</th>
                        <th>Blocked By</th>
                        <th>Block Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blocked_workers)): ?>
                    <tr><td colspan="6" class="text-center">No blocked workers.</td></tr>
                    <?php else: ?>
                        <?php foreach($blocked_workers as $w): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><?= htmlspecialchars($w['gatepass_no'] ?: $w['aadhar']) ?></td>
                            <td><?= htmlspecialchars($w['contractor_name'] ?? 'Unknown') ?></td>
                            <td><span class="text-danger"><?= htmlspecialchars($w['reason']) ?></span></td>
                            <td><?= htmlspecialchars($w['blocked_by_name'] ?? 'System') ?></td>
                            <td><?= date('d M Y H:i', strtotime($w['blocked_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Blocked Workers Watchlist", 'renderContent', $role, $name);

