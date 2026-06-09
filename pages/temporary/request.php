<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $passes = db_fetch_all($conn, "SELECT * FROM temporary_passes ORDER BY created_at DESC LIMIT 50");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-clock" style="color:#6366f1;margin-right:10px;"></i> Temporary Workforce Pass</h2>
        <!-- <p class="page-subtitle">Manage short-term external workforce entries and auto-expiry.</p> -->
      </div>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="document.getElementById('addPassModal').style.display='flex'"><i class="fas fa-plus"></i> New Pass Request</button>
      </div>
    </div>

    <div class="card glass" style="padding:0;overflow:hidden;">
        <table class="table">
            <thead>
                <tr>
                    <th>Workman Name</th>
                    <th>Purpose</th>
                    <th>Valid From</th>
                    <th>Valid To</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($passes)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5;">No temporary passes found.</td></tr>
                <?php else: ?>
                    <?php foreach($passes as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['workman_name']) ?></td>
                            <td><?= htmlspecialchars($p['purpose']) ?></td>
                            <td><?= $p['valid_from'] ?></td>
                            <td><?= $p['valid_to'] ?></td>
                            <td><span class="status-pill status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td>
                                <button class="btn-icon"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Simple Modal for Demo -->
    <div id="addPassModal" class="glass" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div class="card" style="width:500px; background:#1e293b; border:1px solid rgba(255,255,255,0.1); padding:24px;">
            <h3>New Temporary Pass</h3>
            <form action="../../api/temporary/create_pass.php" method="POST">
                <div style="margin-bottom:15px;">
                    <label>Workman Name</label>
                    <input type="text" name="workman_name" class="form-control" required style="width:100%;">
                </div>
                <div style="margin-bottom:15px;">
                    <label>Purpose</label>
                    <input type="text" name="purpose" class="form-control" required style="width:100%;">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                    <div>
                        <label>Valid From</label>
                        <input type="date" name="valid_from" class="form-control" required>
                    </div>
                    <div>
                        <label>Valid To</label>
                        <input type="date" name="valid_to" class="form-control" required>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addPassModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

renderLayout('Temporary Workforce Pass', 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
