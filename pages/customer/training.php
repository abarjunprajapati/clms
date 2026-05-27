<?php
/**
 * Customer Safety Training
 */

require_once '../../include/auth.php';
checkAuth(['customer']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if (empty($customer_code)) {
    die('<div class="alert alert-danger">Invalid session</div>');
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'request_training') {
        $training_type = $_POST['training_type'] ?? '';
        $preferred_date = $_POST['preferred_date'] ?? '';
        $location = $_POST['location'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        
        if (!empty($training_type) && !empty($preferred_date)) {
            db_execute($conn, "
                INSERT INTO training_requests (customer_code, training_type, preferred_date, location, remarks, status, requested_by, requested_at)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
            ", 'sssssi', [$customer_code, $training_type, $preferred_date, $location, $remarks, $user_id]);
            
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Training request submitted!</div>';
        }
    }
}

// Get training requests
$training_requests = db_fetch_all($conn, "
    SELECT * FROM training_requests WHERE customer_code = ? ORDER BY requested_at DESC
", 's', [$customer_code]);

function renderContent() {
    global $training_requests, $message;
    
    ?>
    <div class="container-lg py-5">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-graduation-cap"></i> Safety Training Requests</h2>
                <p class="text-muted">Request and track safety training for workers</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trainingModal">
                    <i class="fas fa-plus"></i> Request Training
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Requests Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Training Requests</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Training Type</th>
                            <th>Preferred Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($training_requests)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No training requests</td></tr>
                        <?php else: ?>
                            <?php foreach ($training_requests as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['training_type']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($t['preferred_date'])) ?></td>
                                    <td><?= htmlspecialchars($t['location'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $t['status'] === 'completed' ? 'success' : ($t['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($t['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($t['requested_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Request Training Modal -->
    <div class="modal fade" id="trainingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Safety Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Training Type <span class="text-danger">*</span></label>
                            <select name="training_type" class="form-select" required>
                                <option value="">-- Select Type --</option>
                                <option value="Safety-I">Safety Level I</option>
                                <option value="Safety-II">Safety Level II</option>
                                <option value="First-Aid">First Aid</option>
                                <option value="Fire-Safety">Fire Safety</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preferred Date <span class="text-danger">*</span></label>
                            <input type="date" name="preferred_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="request_training" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
}

renderLayout(
    ['title' => 'Customer Training', 'active_menu' => 'training'],
    'renderContent',
    $role,
    $name
);
?>
