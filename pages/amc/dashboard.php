<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'customer']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Aggregated Stats
    $total_tickets = db_count($conn, "SELECT COUNT(*) FROM amc_tickets");
    $open_tickets = db_count($conn, "SELECT COUNT(*) FROM amc_tickets WHERE status = 'open'");
    $breached_tickets = db_count($conn, "SELECT COUNT(*) FROM amc_tickets WHERE status = 'open' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"); // Simple breach logic
    $avg_resolution = db_single($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_res FROM amc_tickets WHERE status = 'resolved'")['avg_res'] ?? 0;

    $tickets = db_fetch_all($conn, "SELECT t.*, c.contract_number FROM amc_tickets t JOIN amc_contracts c ON t.contract_id = c.id ORDER BY t.created_at DESC LIMIT 20");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-handshake" style="color:#6366f1;margin-right:10px;"></i> AMC & SLA Governance</h2>
        <!-- <p class="page-subtitle">Real-time monitoring of support tickets, SLA performance, and vendor compliance.</p> -->
      </div>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="document.getElementById('ticketModal').style.display='flex'"><i class="fas fa-plus"></i> Raise Ticket</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $total_tickets ?></div>
        <div style="font-size:12px;opacity:0.6;">Total Tickets</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $open_tickets ?></div>
        <div style="font-size:12px;opacity:0.6;">Open Tickets</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $breached_tickets ?></div>
        <div style="font-size:12px;opacity:0.6;">SLA Breached</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#10b981;"><?= round($avg_resolution, 1) ?>h</div>
        <div style="font-size:12px;opacity:0.6;">Avg Resolution</div>
      </div>
    </div>

    <div class="card glass" style="padding:0;overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:16px;">Active Support Tickets</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Severity</th>
                        <th>Subject</th>
                        <th>Contract</th>
                        <th>Status</th>
                        <th>Raised At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;opacity:0.5;">No active tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach($tickets as $t): ?>
                            <tr>
                                <td>#<?= $t['id'] ?></td>
                                <td><span class="badge badge-<?= $t['severity'] == 'S1' ? 'danger' : ($t['severity'] == 'S2' ? 'warning' : 'primary') ?>"><?= $t['severity'] ?></span></td>
                                <td><?= htmlspecialchars($t['subject']) ?></td>
                                <td><?= htmlspecialchars($t['contract_number']) ?></td>
                                <td><span class="status-pill status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td><?= date('d M, H:i', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <?php if ($t['status'] == 'open'): ?>
                                        <button class="btn-icon" title="Pause SLA" style="color:#f59e0b;"><i class="fas fa-pause"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Raise Ticket Modal -->
    <div id="ticketModal" class="glass" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div class="card" style="width:550px; background:#1e293b; border:1px solid rgba(255,255,255,0.1); padding:24px; border-radius:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Raise Support Ticket</h3>
                <button onclick="document.getElementById('ticketModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="../../api/amc/create_ticket.php" method="POST">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Select Contract</label>
                    <select name="contract_id" class="form-control" style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                        <?php
                        $contracts = db_fetch_all($conn, "SELECT id, contract_number FROM amc_contracts");
                        foreach($contracts as $c) echo "<option value='{$c['id']}'>{$c['contract_number']}</option>";
                        ?>
                    </select>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Severity</label>
                    <div style="display:flex; gap:10px;">
                        <label style="flex:1; border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:8px; text-align:center; cursor:pointer;">
                            <input type="radio" name="severity" value="S1" required> <span style="color:#ef4444;">S1</span>
                        </label>
                        <label style="flex:1; border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:8px; text-align:center; cursor:pointer;">
                            <input type="radio" name="severity" value="S2" checked> <span style="color:#f59e0b;">S2</span>
                        </label>
                        <label style="flex:1; border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:8px; text-align:center; cursor:pointer;">
                            <input type="radio" name="severity" value="S3"> <span style="color:#6366f1;">S3</span>
                        </label>
                    </div>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Subject</label>
                    <input type="text" name="subject" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Description</label>
                    <textarea name="description" class="form-control" rows="4" style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Create Ticket</button>
            </form>
        </div>
    </div>
    <?php
}

renderLayout('AMC & SLA Governance', 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
