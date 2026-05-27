<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user', 'welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $rows = [];
    $r = @mysqli_query($conn, "SELECT w.id, w.name, w.acc_number, w.application_no, c.contractor_name 
                                FROM workmen w 
                                LEFT JOIN contractors c ON w.contractor_id = c.id 
                                WHERE w.status = 'acc_return_pending' 
                                ORDER BY w.name");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) $rows[] = $row; }
    ?>
    <div class="content-header">
        <h2 class="page-title"><i class="fas fa-undo" style="color:#7c3aed;margin-right:8px"></i>ACC Card Return Queue</h2>
        <!-- <p class="page-subtitle">Track physical card collection for relieved workers.</p> -->
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Pending Physical Card Returns</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead><tr><th style="width:50px;">S.No</th><th>ACC Number</th><th>Worker Name</th><th>Contractor</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:#9ca3af">No cards pending for return.</td></tr>
                    <?php else: $sno = 1; foreach($rows as $w): ?>
                    <tr class="acc-row">
                        <td><?= $sno++ ?></td>
                        <td><code><?= htmlspecialchars($w['acc_number'] ?? '-') ?></code></td>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars($w['contractor_name'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="markReturned('<?= htmlspecialchars($w['acc_number'] ?? '') ?>', '<?= htmlspecialchars($w['application_no'] ?? '') ?>')">
                                <i class="fas fa-hand-holding"></i> Received Card
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    async function markReturned(accNo, appId) {
        if (!confirm('Confirm receipt of physical ACC card ' + accNo + '?')) return;
        const res = await fetch('../../api/welfare/return_acc.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ acc_no: accNo, application_id: appId })
        });
        const data = await res.json();
        if (data.success) {
            alert('ACC Card returned successfully. Worker lifecycle moved to Relieved.');
            location.reload();
        } else alert(data.message);
    }
    </script>
    <?php
}
renderLayout("ACC Return Queue", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>


