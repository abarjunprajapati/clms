<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['customer']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Customer';

function renderContent() {
    global $conn;
    $customer_code = $_SESSION['customer_code'] ?? '';
    if (empty($customer_code)) {
        echo '<div class="content-header"><h2>No customer code found in session.</h2></div>';
        return;
    }

    // History of Customer Registration actions for this customer
    $hist_sql = "SELECT annexure3a_id, vendor_code, work_order_no, status, reason, updated_at
                 FROM contractor_annexure3a_history
                 WHERE customer_code = ?
                 ORDER BY updated_at DESC";
    $annex_history = db_fetch_all($conn, $hist_sql, 's', [$customer_code]);

    // Documents related to Customer Registration for this customer
    $docs_sql = "SELECT d.* , a.vendor_code, a.work_order_no
                 FROM contractor_documents d
                 JOIN contractor_annexure3a a ON a.id = d.annexure3a_id
                 WHERE a.customer_code = ?
                 ORDER BY d.uploaded_at DESC";
    $docs = db_fetch_all($conn, $docs_sql, 's', [$customer_code]);

    ?>
    <div class="content-header">
        <h2 class="page-title">Welfare Actions </h2>
        <p class="page-subtitle">History of welfare actions and documents for your customer code: <strong><?= htmlspecialchars($customer_code) ?></strong></p>
    </div>

    <div class="card glass" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title"> Action History</div></div>
        <div class="card-body">
            <?php if (empty($annex_history)): ?>
                <div class="text-center" style="padding:20px">No  actions recorded yet.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>When</th><th>Vendor Code</th><th>WO</th><th>Action</th><th>Reason</th></tr></thead>
                    <tbody>
                    <?php foreach ($annex_history as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['updated_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($h['vendor_code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($h['work_order_no'] ?? '') ?></td>
                            <td><?= strtoupper(htmlspecialchars($h['status'] ?? '')) ?></td>
                            <td><?= nl2br(htmlspecialchars($h['reason'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Documents</div></div>
        <div class="card-body">
            <?php if (empty($docs)): ?>
                <div class="text-center" style="padding:20px">No documents found.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>When</th><th>Vendor Code</th><th>WO</th><th>Type</th><th>File</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($docs as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['uploaded_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($d['vendor_code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($d['work_order_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($d['doc_type'] ?? '') ?></td>
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

renderLayout('Welfare Actions - 3A', 'renderContent', $role, $name);
?>
