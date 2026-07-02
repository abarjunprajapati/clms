<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$vendor_code = $_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? '';

// Handle file upload
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ecr_file'])) {
    $month_year = $_POST['month_year'] ?? '';
    $work_orders = $_POST['work_orders'] ?? [];
    
    $file = $_FILES['ecr_file'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = "Error uploading file.";
        $msgType = "danger";
    } elseif ($file['size'] > $maxSize) {
        $msg = "File size exceeds 2MB limit.";
        $msgType = "danger";
    } elseif (empty($month_year) || empty($work_orders)) {
        $msg = "Month/Year and Work Orders are required.";
        $msgType = "danger";
    } else {
        // Just mock the success since we don't have a table specified for this yet.
        $msg = "ESI ECR & PF Contribution uploaded successfully.";
        $msgType = "success";
    }
}

// Fetch work orders for this contractor
$work_orders_db = db_fetch_all($conn, "
    SELECT work_order_no 
    FROM work_orders 
    WHERE vendor_code = ? AND wo_status = 'ACTIVE'
", 's', [$vendor_code]);

function renderContent() {
    global $msg, $msgType, $work_orders_db;
    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-file-upload" style="color:#2563eb;margin-right:10px;"></i> ESI ECR & PF Contribution Upload</h2>
        </div>
    </div>
    
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card glass">
        <div class="card-header">
            <h5 class="card-title m-0">Upload New Contribution</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Select Month & Year</label>
                        <input type="month" name="month_year" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Work Order</label>
                        <select name="work_orders[]" class="form-select" multiple required style="height: 100px;">
                            <?php foreach ($work_orders_db as $wo): ?>
                                <option value="<?= htmlspecialchars($wo['work_order_no']) ?>"><?= htmlspecialchars($wo['work_order_no']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold CTRL to select multiple</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">File Upload (Max 2MB)</label>
                        <input type="file" name="ecr_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.csv,.xlsx" required>
                        <small class="text-muted">Max size: 2MB</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
            </form>
        </div>
    </div>
    
    <div class="card glass mt-4">
        <div class="card-header">
            <h5 class="card-title m-0">Uploaded Contributions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table gov-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Month & Year</th>
                            <th>Work Orders</th>
                            <th>File Name</th>
                            <th>Uploaded On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted p-4">No records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

renderLayout('ESI ECR & PF Contribution Upload', 'renderContent', $role, $name);
