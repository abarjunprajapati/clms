<?php
require_once '../../include/auth.php';
checkAuth(['pass_user', 'welfare_user', 'welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function nocTransferColumnExists($conn, $table, $column) {
    return db_count(
        $conn,
        "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        'ss',
        [$table, $column]
    ) > 0;
}

function nocTransferTryQuery($conn, $sql) {
    try {
        return (bool)$conn->query($sql);
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[NOC_TRANSFER_PAGE] ' . date('c') . ' - ' . $e->getMessage() . ' | ' . $sql . "\n", FILE_APPEND);
        return false;
    }
}

function nocTransferEnsureSchema($conn) {
    nocTransferTryQuery($conn, "CREATE TABLE IF NOT EXISTS worker_transfer_logs (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        from_contractor_id INT NOT NULL,
        to_contractor_id INT DEFAULT NULL,
        noc_id INT DEFAULT NULL,
        noc_reference VARCHAR(100) DEFAULT NULL,
        transfer_type VARCHAR(20) DEFAULT 'noc',
        status VARCHAR(20) DEFAULT 'completed',
        approved_by INT DEFAULT NULL,
        remarks TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'workman_id' => "ALTER TABLE worker_transfer_logs ADD workman_id INT NOT NULL",
        'from_contractor_id' => "ALTER TABLE worker_transfer_logs ADD from_contractor_id INT NOT NULL",
        'to_contractor_id' => "ALTER TABLE worker_transfer_logs ADD to_contractor_id INT DEFAULT NULL",
        'noc_id' => "ALTER TABLE worker_transfer_logs ADD noc_id INT DEFAULT NULL",
        'noc_reference' => "ALTER TABLE worker_transfer_logs ADD noc_reference VARCHAR(100) DEFAULT NULL",
        'transfer_type' => "ALTER TABLE worker_transfer_logs ADD transfer_type VARCHAR(20) DEFAULT 'noc'",
        'status' => "ALTER TABLE worker_transfer_logs ADD status VARCHAR(20) DEFAULT 'completed'",
        'approved_by' => "ALTER TABLE worker_transfer_logs ADD approved_by INT DEFAULT NULL",
        'remarks' => "ALTER TABLE worker_transfer_logs ADD remarks TEXT DEFAULT NULL",
        'created_at' => "ALTER TABLE worker_transfer_logs ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($columns as $column => $sql) {
        if (!nocTransferColumnExists($conn, 'worker_transfer_logs', $column)) {
            nocTransferTryQuery($conn, $sql);
        }
    }
}

function renderContent() {
    global $conn;

    nocTransferEnsureSchema($conn);

    $transfers = db_fetch_all($conn, "
        SELECT wt.*, w.name as worker_name, w.aadhaar as aadhaar_no,
               c1.contractor_name as from_name, c2.contractor_name as to_name,
               u.name as processed_by_name
        FROM worker_transfer_logs wt
        JOIN workmen w ON wt.workman_id = w.id
        JOIN contractors c1 ON wt.from_contractor_id = c1.id
        JOIN contractors c2 ON wt.to_contractor_id = c2.id
        LEFT JOIN users u ON wt.approved_by = u.id
        ORDER BY wt.created_at DESC
    ");

    $active_workers = db_fetch_all($conn, "
        SELECT w.id, w.name, w.aadhaar as aadhaar_no, c.contractor_name, w.contractor_id
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE w.status IN ('active', 'permanent_active', 'temporary_issued', 'acc_generated', 'verified')
        ORDER BY w.name ASC
    ");

    $all_contractors = db_fetch_all($conn, "SELECT id, contractor_name FROM contractors WHERE status IN ('active', 'approved') OR status IS NULL ORDER BY contractor_name ASC");
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-exchange-alt" style="color:#6366f1;margin-right:10px;"></i> Worker Transfer & NOC Control</h2>
        <!-- <p class="page-subtitle">Manage worker movement between contractors with digital NOC verification.</p> -->
    </div>
    <button class="btn btn-primary" onclick="openTransferModal()"><i class="fas fa-plus"></i> Initiate Transfer</button>
</div>

<div class="card glass">
    <div class="card-header"><div class="card-title">Transfer History</div></div>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>From Contractor</th>
                    <th>To Contractor</th>
                    <th>Transfer Date</th>
                    <th>NOC No.</th>
                    <th>Processed By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transfers as $t): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($t['worker_name']) ?></strong><br><small><?= $t['aadhaar_no'] ?></small></td>
                    <td><?= htmlspecialchars($t['from_name']) ?></td>
                    <td><span class="text-success"><?= htmlspecialchars($t['to_name']) ?></span></td>
                    <td><?= date('d-M-Y', strtotime($t['created_at'])) ?></td>
                    <td><code><?= htmlspecialchars($t['noc_reference'] ?: ($t['noc_id'] ?: 'AUTO-GEN')) ?></code></td>
                    <td><?= htmlspecialchars($t['processed_by_name'] ?? 'System') ?></td>
                    <td><span class="badge badge-success"><?= strtoupper(htmlspecialchars($t['status'] ?: 'completed')) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($transfers)): ?>
                <tr><td colspan="7" class="text-center" style="padding:30px;color:#64748b;">No NOC transfer records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="noc-modal" aria-hidden="true">
    <div class="noc-modal__dialog">
        <div class="noc-modal__header">
            <h3><i class="fas fa-random"></i> Initiate Worker Transfer</h3>
            <button type="button" class="noc-modal__close" onclick="closeTransferModal()" aria-label="Close">&times;</button>
        </div>
        <form id="transferForm">
            <div class="form-group">
                <label class="form-label required">Select Worker</label>
                <select name="worker_id" class="form-control" required onchange="updateFromContractor(this)">
                    <option value="">-- Select --</option>
                    <?php foreach($active_workers as $w): ?>
                        <option value="<?= $w['id'] ?>" data-cid="<?= $w['contractor_id'] ?>" data-cname="<?= htmlspecialchars($w['contractor_name']) ?>">
                            <?= htmlspecialchars($w['name']) ?> (<?= $w['aadhaar_no'] ?>) - <?= htmlspecialchars($w['contractor_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Current Contractor</label>
                <input type="text" id="current_contractor_display" class="form-control" readonly style="background:#f1f5f9;">
                <input type="hidden" name="from_contractor_id" id="from_contractor_id">
            </div>
            <div class="form-group">
                <label class="form-label required">New (To) Contractor</label>
                <select name="to_contractor_id" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php foreach($all_contractors as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">NOC / Reference No.</label>
                <input type="text" name="noc_reference" class="form-control" required placeholder="Enter NOC document reference">
            </div>
            <div class="form-group">
                <label class="form-label">Transfer Reason</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <div class="noc-modal__footer">
                <button type="button" class="btn btn-outline" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveTransferBtn">Process Transfer</button>
            </div>
        </form>
    </div>
</div>

<style>
.noc-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(3px);
    padding: 28px 16px;
    overflow-y: auto;
}
.noc-modal.is-open {
    display: flex;
    align-items: flex-start;
    justify-content: center;
}
.noc-modal__dialog {
    width: min(640px, 100%);
    margin: 24px auto;
    background: #fff;
    border: 1px solid #dbe4ef;
    border-radius: 12px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}
.noc-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    border-bottom: 1px solid #e5edf6;
}
.noc-modal__header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: #1f2937;
}
.noc-modal__close {
    width: 34px;
    height: 34px;
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}
.noc-modal__close:hover {
    background: #f8fafc;
}
#transferForm {
    padding: 20px 22px 22px;
}
#transferForm .form-group {
    margin-bottom: 14px;
}
#transferForm .form-control {
    width: 100%;
    min-height: 38px;
    box-sizing: border-box;
}
#transferForm textarea.form-control {
    min-height: 76px;
    resize: vertical;
}
.noc-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 8px;
}
body.noc-modal-open {
    overflow: hidden;
}
@media (max-width: 640px) {
    .noc-modal {
        padding: 12px;
    }
    .noc-modal__dialog {
        margin: 8px auto;
    }
    .noc-modal__header,
    #transferForm {
        padding-left: 16px;
        padding-right: 16px;
    }
    .noc-modal__footer {
        flex-direction: column-reverse;
    }
    .noc-modal__footer .btn {
        width: 100%;
    }
}
</style>

<script>
function openTransferModal() {
    const modal = document.getElementById('transferModal');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('noc-modal-open');
}
function closeTransferModal() {
    const modal = document.getElementById('transferModal');
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('noc-modal-open');
}

function updateFromContractor(select) {
    const opt = select.options[select.selectedIndex];
    if (opt.value) {
        document.getElementById('current_contractor_display').value = opt.getAttribute('data-cname');
        document.getElementById('from_contractor_id').value = opt.getAttribute('data-cid');
    }
}

document.getElementById('transferForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveTransferBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    const formData = new FormData(e.target);
    try {
        const resp = await fetch('../../api/welfare/process_transfer.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            }
        });
        const raw = await resp.text();
        let res = {};
        try {
            res = raw ? JSON.parse(raw) : {};
        } catch(parseError) {
            res = { success: false, message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : 'Server returned an empty response.' };
        }
        if(res.success) {
            alert('Worker transferred successfully!');
            location.reload();
        } else {
            alert(res.message || 'Error processing transfer');
            btn.disabled = false;
            btn.innerHTML = 'Process Transfer';
        }
    } catch(err) {
        alert('Network error while processing transfer.');
        btn.disabled = false;
        btn.innerHTML = 'Process Transfer';
    }
});
</script>
<?php
}
renderLayout('Worker Transfer', 'renderContent', $role, $name);
?>
