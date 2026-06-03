<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user', 'pass_user', 'welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function accReturnQueueEnsureSchema($conn) {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS worker_transfer_logs (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            workman_id INT NOT NULL,
            from_contractor_id INT NOT NULL,
            to_contractor_id INT DEFAULT NULL,
            noc_reference VARCHAR(100) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            approved_by INT DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[ACC_RETURN_QUEUE] ' . date('c') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

function renderContent() {
    global $conn;
    accReturnQueueEnsureSchema($conn);
    $rows = [];
    $r = @mysqli_query($conn, "SELECT DISTINCT w.id, w.name, w.acc_number, w.acc_card_number, w.application_no, w.status, c.contractor_name,
                                      CASE
                                        WHEN w.status = 'acc_return_pending' THEN 'Relieving requested'
                                        WHEN w.status IN ('permanent_active', 'active') AND EXISTS (
                                            SELECT 1 FROM worker_transfer_logs wt
                                            WHERE wt.workman_id = w.id AND COALESCE(wt.status, 'completed') IN ('completed', 'approved')
                                        ) THEN 'NOC transfer completed'
                                        WHEN EXISTS (
                                            SELECT 1 FROM gate_passes gp
                                            WHERE gp.workman_id = w.id AND gp.status = 'blocked' AND COALESCE(gp.remarks, '') LIKE '%transfer%'
                                        ) THEN 'Transferred pass blocked'
                                        ELSE 'ACC return required'
                                      END AS return_reason
                                FROM workmen w
                                LEFT JOIN contractors c ON w.contractor_id = c.id
                                WHERE COALESCE(w.acc_number, w.acc_card_number, '') <> ''
                                  AND w.status <> 'acc_returned'
                                  AND (
                                    w.status = 'acc_return_pending'
                                    OR EXISTS (
                                        SELECT 1 FROM worker_transfer_logs wt
                                        WHERE wt.workman_id = w.id AND COALESCE(wt.status, 'completed') IN ('completed', 'approved')
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM gate_passes gp
                                        WHERE gp.workman_id = w.id AND gp.status = 'blocked' AND COALESCE(gp.remarks, '') LIKE '%transfer%'
                                    )
                                  )
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
                <thead><tr><th style="width:50px;">S.No</th><th>ACC Number</th><th>Worker Name</th><th>Contractor</th><th>Reason</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af">No cards pending for return.</td></tr>
                    <?php else: $sno = 1; foreach($rows as $w): ?>
                    <tr class="acc-row">
                        <td><?= $sno++ ?></td>
                        <td><code><?= htmlspecialchars($w['acc_number'] ?: ($w['acc_card_number'] ?? '-')) ?></code></td>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars($w['contractor_name'] ?? '-') ?></td>
                        <td><span class="badge badge-warning"><?= htmlspecialchars($w['return_reason'] ?? 'ACC return required') ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="markReturned(<?= (int)$w['id'] ?>, '<?= htmlspecialchars($w['acc_number'] ?: ($w['acc_card_number'] ?? '')) ?>', '<?= htmlspecialchars($w['application_no'] ?? '') ?>')">
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
    async function markReturned(workerId, accNo, appId) {
        if (!confirm('Confirm receipt of physical ACC card ' + accNo + '?')) return;
        const res = await fetch('../../api/welfare/return_acc.php', {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            },
            body: JSON.stringify({ workman_id: workerId, acc_no: accNo, application_id: appId })
        });
        const raw = await res.text();
        let data = {};
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (err) {
            data = { success: false, message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : 'Server returned an empty response.' };
        }
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
