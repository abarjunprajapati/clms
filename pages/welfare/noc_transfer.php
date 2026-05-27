<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function renderContent() {
    global $conn;

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
        WHERE w.status = 'active'
    ");

    $all_contractors = db_fetch_all($conn, "SELECT id, contractor_name FROM contractors WHERE status='active'");
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
                    <td><code><?= $t['noc_id'] ?: 'AUTO-GEN' ?></code></td>
                    <td><?= htmlspecialchars($t['processed_by_name'] ?? 'System') ?></td>
                    <td><span class="badge badge-success">COMPLETED</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);">
    <div class="modal-content glass" style="background:white; margin:5% auto; padding:30px; width:600px; border-radius:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"><i class="fas fa-random"></i> Initiate Worker Transfer</h3>
            <span onclick="closeTransferModal()" style="cursor:pointer; font-size:24px;">&times;</span>
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
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="btn btn-outline" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveTransferBtn">Process Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTransferModal() { document.getElementById('transferModal').style.display = 'block'; }
function closeTransferModal() { document.getElementById('transferModal').style.display = 'none'; }

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
            body: formData
        });
        const res = await resp.json();
        if(res.success) {
            alert('Worker transferred successfully!');
            location.reload();
        } else {
            alert(res.message || 'Error processing transfer');
            btn.disabled = false;
            btn.innerHTML = 'Process Transfer';
        }
    } catch(err) {
        alert('Network error');
        btn.disabled = false;
        btn.innerHTML = 'Process Transfer';
    }
});
</script>
<?php
}
renderLayout('Worker Transfer', 'renderContent', $role, $name);
?>
