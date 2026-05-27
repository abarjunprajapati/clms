<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';

function renderContent() {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? 0;
    $contractor = db_single($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    if (!$contractor) {
        echo '<div class="content-header"><h2>No contractor record found for your account.</h2></div>';
        return;
    }
    $cid = intval($contractor['id']);
    $vendor_code = htmlspecialchars($contractor['vendor_code']);

    // Fetch annexure2a history
    $annex_history = db_fetch_all($conn, "SELECT annexure2a_id, status, reason, updated_at FROM contractor_annexure2a_history WHERE contractor_id = ? ORDER BY updated_at DESC", 'i', [$cid]);

    // Fetch contractor status history
    $status_history = db_fetch_all($conn, "SELECT contractor_id, status, reason, pdf_path, created_at FROM contractor_status_history WHERE contractor_id = ? ORDER BY created_at DESC", 'i', [$cid]);

    // Fetch welfare approval documents
    $docs = db_fetch_all($conn, "SELECT id, doc_type, file_path, original_name, status, uploaded_at FROM contractor_documents WHERE contractor_id = ? ORDER BY uploaded_at DESC", 'i', [$cid]);

    ?>
    <div class="content-header">
        <h2 class="page-title">Welfare Actions</h2>
        <p class="page-subtitle">History of welfare approvals, rejections and supporting documents for vendor code: <strong><?= $vendor_code ?></strong></p>
    </div>

    <div class="card glass" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">Contractor Registration Welfare Actions</div></div>
        <div class="card-body">
            <?php if (empty($annex_history)): ?>
                <div class="text-center" style="padding:20px">No welfare actions recorded yet.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>When</th><th>Action</th><th>Reason</th></tr></thead>
                    <tbody>
                    <?php foreach ($annex_history as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['updated_at']) ?></td>
                            <td><?= strtoupper(htmlspecialchars($h['status'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($h['reason'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">Contractor Status History</div></div>
        <div class="card-body">
            <?php if (empty($status_history)): ?>
                <div class="text-center" style="padding:20px">No contractor status changes recorded yet.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>When</th><th>Status</th><th>Reason</th><th>Document</th></tr></thead>
                    <tbody>
                    <?php foreach ($status_history as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['created_at']) ?></td>
                            <td><?= strtoupper(htmlspecialchars($s['status'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($s['reason'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($s['pdf_path'])): ?>
                                    <a href="<?= htmlspecialchars($s['pdf_path']) ?>" target="_blank">View PDF</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">Approval Documents</div></div>
        <div class="card-body">
            <?php if (empty($docs)): ?>
                <div class="text-center" style="padding:20px">No documents uploaded.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>When</th><th>Type</th><th>File</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($docs as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['uploaded_at']) ?></td>
                            <td><?= htmlspecialchars($d['doc_type']) ?></td>
                            <td><?php if (!empty($d['file_path'])): ?><a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank"><?= htmlspecialchars($d['original_name'] ?: basename($d['file_path'])) ?></a><?php else: ?>-<?php endif; ?></td>
                            <td><?= htmlspecialchars($d['status'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php
}

renderLayout('Welfare Actions', 'renderContent', $role, $name);
?>
