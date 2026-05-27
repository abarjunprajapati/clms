<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Frontline Officer';

function renderContent() {
    global $conn;
    
    // Quick summary for today
    $today = date('Y-m-d');
    
    $total_entries = db_count($conn, "SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = '$today' AND check_in IS NOT NULL");
    $total_exits = db_count($conn, "SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = '$today' AND check_out IS NOT NULL");
    $currently_inside = $total_entries - $total_exits; // Approximation based on today
    
    // Entries by contractor
    $query_contractor = "
        SELECT c.contractor_name, COUNT(a.id) as count
        FROM attendance a
        JOIN workmen w ON a.workman_id = w.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE DATE(a.check_in) = '$today' AND a.check_in IS NOT NULL
        GROUP BY w.contractor_id
        ORDER BY count DESC
    ";
    $contractor_stats = db_fetch_all($conn, $query_contractor);

    ?>
    <div class="content-header">
      <h2 class="page-title"><i class="fas fa-chart-line text-primary"></i> Daily Gate Reports</h2>
      <p class="page-subtitle">Summary of today's movement metrics.</p>
    </div>

    <div class="row" style="display: flex; gap: 20px;">
        <!-- Overview Card -->
        <div class="col" style="flex: 1;">
            <div class="card glass">
                <div class="card-header bg-primary text-white">
                    <div class="card-title text-white"><i class="fas fa-info-circle"></i> Today's Summary</div>
                </div>
                <div class="card-body">
                    <ul class="list-group" style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 15px; border-bottom: 1px solid rgba(0,0,0,0.1); display: flex; justify-content: space-between;">
                            <strong>Total Entries Logged</strong>
                            <span class="badge badge-success" style="font-size: 14px;"><?= $total_entries ?></span>
                        </li>
                        <li style="padding: 15px; border-bottom: 1px solid rgba(0,0,0,0.1); display: flex; justify-content: space-between;">
                            <strong>Total Exits Logged</strong>
                            <span class="badge badge-danger" style="font-size: 14px;"><?= $total_exits ?></span>
                        </li>
                        <li style="padding: 15px; display: flex; justify-content: space-between;">
                            <strong>Currently Inside (Est.)</strong>
                            <span class="badge badge-info" style="font-size: 14px;"><?= $currently_inside ?></span>
                        </li>
                    </ul>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-outline-primary btn-block" style="width: 100%;" onclick="window.print()">
                            <i class="fas fa-print"></i> Print End of Shift Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contractor Breakdown -->
        <div class="col" style="flex: 2;">
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-building"></i> Entries by Contractor</div>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Contractor Name</th>
                                <th>Workers Entered Today</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($contractor_stats)): ?>
                            <tr><td colspan="2" class="text-center">No entries recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($contractor_stats as $stat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stat['contractor_name'] ?? 'Unknown / Direct') ?></td>
                                    <td><strong><?= $stat['count'] ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}

renderLayout("Gate Reports", 'renderContent', $role, $name);

