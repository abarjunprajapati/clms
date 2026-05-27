<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;

    // Fetch productivity reports
    $reports = db_fetch_all($conn, "
        SELECT pr.*, d.dept_name 
        FROM productivity_reports pr
        LEFT JOIN master_departments d ON pr.dept_id = d.id
        WHERE pr.contractor_id = ? 
        ORDER BY pr.report_date DESC
    ", 'i', [$contractor_id]);

    // Fetch departments for the form
    $depts = db_fetch_all($conn, "SELECT id, dept_name FROM master_departments WHERE status='active'");
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-chart-line" style="color:#10b981;margin-right:10px;"></i> Productivity Reporting</h2>
        <p class="page-subtitle">Track and report work output against manpower deployed</p>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Report</button>
</div>

<div class="card glass">
    <div class="card-body">
        <table class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Report Date</th>
                    <th>Department</th>
                    <th>Work Description</th>
                    <th>Output Unit</th>
                    <th>Qty Produced</th>
                    <th>Manpower Used</th>
                    <th>Efficiency</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d-M-Y', strtotime($r['report_date'])) ?></td>
                    <td><?= htmlspecialchars($r['dept_name']) ?></td>
                    <td><?= htmlspecialchars($r['work_description']) ?></td>
                    <td><?= htmlspecialchars($r['output_unit']) ?></td>
                    <td><strong><?= $r['output_qty'] ?></strong></td>
                    <td><?= $r['manpower_deployed'] ?></td>
                    <td>
                        <?php 
                            $eff = ($r['manpower_deployed'] > 0) ? ($r['output_qty'] / $r['manpower_deployed']) : 0;
                            echo number_format($eff, 2) . ' / Man-day';
                        ?>
                    </td>
                    <td><span class="badge badge-success">Submitted</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for New Productivity Report -->
<div id="productivityModal" class="modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);">
    <div class="modal-content glass" style="background:white; margin:5% auto; padding:30px; width:500px; border-radius:20px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"><i class="fas fa-file-invoice"></i> Log Daily Productivity</h3>
            <span onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <form id="prodForm">
            <input type="hidden" name="contractor_id" value="<?= $contractor_id ?>">
            <div class="form-group">
                <label class="form-label required">Reporting Date</label>
                <input type="date" name="report_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Department</label>
                <select name="dept_id" class="form-control" required>
                    <option value="">-- Select Dept --</option>
                    <?php foreach($depts as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['dept_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Work Description</label>
                <input type="text" name="work_description" class="form-control" required placeholder="e.g. Painting, Welding, etc.">
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label required">Output Unit</label>
                    <input type="text" name="output_unit" class="form-control" required placeholder="Sqft / Pcs / MT">
                </div>
                <div class="form-group">
                    <label class="form-label required">Quantity</label>
                    <input type="number" name="output_qty" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label required">Manpower Deployed (Total)</label>
                <input type="number" name="manpower_deployed" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveProdBtn">Save Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() { document.getElementById('productivityModal').style.display = 'block'; }
function closeModal() { document.getElementById('productivityModal').style.display = 'none'; }

document.getElementById('prodForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveProdBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const formData = new FormData(e.target);
    try {
        const resp = await fetch('../../api/contractor/save_productivity.php', {
            method: 'POST',
            body: formData
        });
        const res = await resp.json();
        if(res.success) {
            alert('Productivity logged successfully!');
            location.reload();
        } else {
            alert(res.message || 'Error saving report');
            btn.disabled = false;
            btn.innerHTML = 'Save Report';
        }
    } catch(err) {
        alert('Network error');
        btn.disabled = false;
        btn.innerHTML = 'Save Report';
    }
});
</script>
<?php
}
renderLayout('Productivity Reports', 'renderContent', $role, $name);
?>
