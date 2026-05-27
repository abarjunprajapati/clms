<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $work_orders = db_fetch_all($conn, "
        SELECT wo.*, c.customer_name, v.vendor_name 
        FROM work_orders wo
        LEFT JOIN sap_customer_master c ON c.customer_code = wo.customer_code
        LEFT JOIN sap_vendor_master v ON v.vendor_code = wo.vendor_code
        ORDER BY wo.id DESC
    ");

    $customers = db_fetch_all($conn, "SELECT customer_code, customer_name FROM sap_customer_master ORDER BY customer_name");
    $vendors = db_fetch_all($conn, "SELECT vendor_code, vendor_name FROM sap_vendor_master ORDER BY vendor_name");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-handshake" style="color:#6366f1;margin-right:10px;"></i> Work Order Mapping</h2>
        <!-- <p class="page-subtitle">Link Customers to Vendors via Work Orders to enable portal visibility and auto-fill.</p> -->
      </div>
      <button class="btn btn-primary" onclick="showAddModal()"><i class="fas fa-plus"></i> New Mapping</button>
    </div>

    <div class="card glass">
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Work Order</th>
                        <th>Customer</th>
                        <th>Contractor</th>
                        <th>Project / Dept</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($work_orders as $wo): ?>
                    <tr id="row-<?= $wo['id'] ?>">
                        <td><strong><?= htmlspecialchars($wo['work_order_no']) ?></strong></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($wo['customer_name'] ?: 'N/A') ?></div>
                            <code style="font-size:11px;"><?= $wo['customer_code'] ?></code>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($wo['vendor_name'] ?: 'N/A') ?></div>
                            <code style="font-size:11px;"><?= $wo['vendor_code'] ?></code>
                        </td>
                        <td>
                            <div style="font-size:13px;"><?= htmlspecialchars($wo['project_name']) ?></div>
                            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($wo['department']) ?></div>
                        </td>
                        <td>
                            <span class="badge badge-<?= $wo['wo_status']=='ACTIVE'?'success':'danger' ?>" style="cursor:pointer;" onclick="toggleStatus(<?= $wo['id'] ?>)">
                                <?= $wo['wo_status'] ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline text-danger" onclick="deleteMapping(<?= $wo['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD MAPPING MODAL -->
    <div id="mappingModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card glass" style="width:500px; padding:25px;">
            <div class="card-header">
                <div class="card-title">Create New Mapping</div>
                <button class="btn-close" onclick="hideModal()">&times;</button>
            </div>
            <form id="mappingForm">
                <div class="form-group mb-3">
                    <label>Work Order Number</label>
                    <input type="text" name="work_order_no" class="form-control" required placeholder="e.g. WO-2026-001">
                </div>
                <div class="form-group mb-3">
                    <label>Customer</label>
                    <select name="customer_code" class="form-control" required>
                        <option value="">Select Customer</option>
                        <?php foreach($customers as $c): ?>
                        <option value="<?= $c['customer_code'] ?>"><?= htmlspecialchars($c['customer_name']) ?> (<?= $c['customer_code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Vendor / Contractor</label>
                    <select name="vendor_code" class="form-control" required>
                        <option value="">Select Vendor</option>
                        <?php foreach($vendors as $v): ?>
                        <option value="<?= $v['vendor_code'] ?>"><?= htmlspecialchars($v['vendor_name']) ?> (<?= $v['vendor_code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group mb-3">
                        <label>Project Name</label>
                        <input type="text" name="project_name" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group mb-3">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                    <button type="button" class="btn btn-outline" onclick="hideModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Mapping</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showAddModal() { document.getElementById('mappingModal').style.display = 'flex'; }
    function hideModal() { document.getElementById('mappingModal').style.display = 'none'; }

    document.getElementById('mappingForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const data = Object.fromEntries(fd.entries());
        data.action = 'create';

        const res = await fetch('../../api/admin/save_work_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const r = await res.json();
        if(r.success) {
            alert(r.message);
            location.reload();
        } else {
            alert(r.message);
        }
    };

    async function toggleStatus(id) {
        const res = await fetch('../../api/admin/save_work_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action:'toggle_status', id})
        });
        const r = await res.json();
        if(r.success) location.reload();
    }

    async function deleteMapping(id) {
        if(!confirm('Delete this mapping?')) return;
        const res = await fetch('../../api/admin/save_work_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action:'delete', id})
        });
        const r = await res.json();
        if(r.success) document.getElementById('row-'+id).remove();
    }
    </script>
    <?php
}

renderLayout("Work Order Mapping", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
