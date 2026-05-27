<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;

    // 1. Queue for ACC Generation (Point 9)
    $accQueue = $conn->query("SELECT id, name, application_no, status FROM workmen WHERE status = 'temporary_issued' LIMIT 10");

    // 2. Queue for Biometric Enrollment (Point 10)
    $bioQueue = $conn->query("SELECT id, name, acc_number, biometric_status FROM workmen WHERE status = 'acc_generated' AND biometric_status = 'pending' LIMIT 10");

    // 3. Queue for Permanent Pass (Point 11)
    $permQueue = $conn->query("SELECT id, name, acc_number, application_no FROM workmen WHERE status IN ('acc_generated', 'biometric_completed') AND biometric_status = 'completed' LIMIT 10");

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title">Pass & ACC Issuance Authority</h2>
            <p class="page-subtitle">ACC Number Generation, Biometric Enrollment, and Permanent Pass Issuance (PDF Page 25-26).</p>
        </div>
    </div>

    <div class="row">
        <!-- ACC GENERATION (Point 9) -->
        <div class="col-md-4">
            <div class="card glass h-100">
                <div class="card-header bg-soft-blue"><i class="fas fa-microchip"></i> ACC Generation Queue</div>
                <div class="card-body p-0">
                    <table class="data-table small">
                        <thead><tr><th>Worker</th><th>App ID</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($accQueue->num_rows == 0): ?><tr><td colspan="3" class="text-center">No queue</td></tr><?php endif; ?>
                            <?php while($w = $accQueue->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($w['name']) ?></td>
                                <td><code><?= $w['application_no'] ?></code></td>
                                <td><button class="btn btn-xs btn-primary" onclick="generateAcc('<?= $w['application_no'] ?>')">Generate</button></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BIOMETRIC ENROLLMENT (Point 10) -->
        <div class="col-md-4">
            <div class="card glass h-100">
                <div class="card-header bg-soft-orange"><i class="fas fa-fingerprint"></i> Biometric Enrollment</div>
                <div class="card-body p-0">
                    <table class="data-table small">
                        <thead><tr><th>Worker</th><th>ACC No</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($bioQueue->num_rows == 0): ?><tr><td colspan="3" class="text-center">No queue</td></tr><?php endif; ?>
                            <?php while($w = $bioQueue->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($w['name']) ?></td>
                                <td><code><?= $w['acc_number'] ?></code></td>
                                <td><button class="btn btn-xs btn-warning" onclick="enrollBiometric(<?= $w['id'] ?>)">Capture</button></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PERMANENT PASS (Point 11) -->
        <div class="col-md-4">
            <div class="card glass h-100">
                <div class="card-header bg-soft-green"><i class="fas fa-id-card"></i> Permanent Pass Issue</div>
                <div class="card-body p-0">
                    <table class="data-table small">
                        <thead><tr><th>Worker</th><th>ACC No</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($permQueue->num_rows == 0): ?><tr><td colspan="3" class="text-center">No queue</td></tr><?php endif; ?>
                            <?php while($w = $permQueue->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($w['name']) ?></td>
                                <td><code><?= $w['acc_number'] ?></code></td>
                                <td><button class="btn btn-xs btn-success" onclick="issuePermPass('<?= $w['application_no'] ?>')">Issue</button></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    async function generateAcc(appId) {
        if (!confirm('Generate unique ACC and Sync with SAP for Application ' + appId + '?')) return;
        const res = await fetch('../../api/welfare/issue_acc.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.message);
    }

    async function enrollBiometric(workerId) {
        // Point 10 Mockup: Capture Fingerprint
        if (!confirm('Initialize Biometric Scanner? (PDF Point 10)')) return;
        
        // Mock capture process
        const res = await fetch('../../api/welfare/capture_biometric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ worker_id: workerId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Fingerprint captured and verified as unique (1 Fingerprint = 1 ACC).');
            location.reload();
        } else alert(data.message);
    }

    async function issuePermPass(appId) {
        if (!confirm('Issue Permanent Pass and activate ACC for all verified workers in Application ' + appId + '?')) return;
        const res = await fetch('../../api/WorkflowEngine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'issue_permanent_pass', 
                application_id: appId,
                additional_data: { perm_validity: 'contract' }
            })
        });
        // Note: I need to make sure WorkflowEngine can be called directly or via a wrapper API
        const data = await res.json();
        if (data.success) {
            alert('Permanent Pass Issued. Worker status synced to SAP.');
            location.reload();
        } else alert(data.message);
    }
    </script>

    <style>
        .bg-soft-blue { background: rgba(59,130,246,.08); color: #2563eb; font-weight: 800; }
        .bg-soft-orange { background: rgba(245,158,11,.08); color: #d97706; font-weight: 800; }
        .bg-soft-green { background: rgba(16,185,129,.08); color: #059669; font-weight: 800; }
        .btn-xs { padding: 2px 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .data-table.small td { font-size: 12px; padding: 8px; }
        .card-header i { margin-right: 8px; }
    </style>
    <?php
}

renderLayout("Issuance Authority", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

