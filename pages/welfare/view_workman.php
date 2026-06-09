<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        echo "<div class='alert alert-danger'>Invalid Request: Workman ID missing.</div>";
        return;
    }
    
    // Worker + Enrollment + Training
    $query = "
    SELECT w.*, c.contractor_name, tr.training_type, tr.status as request_status
    FROM workmen w
    LEFT JOIN contractors c ON w.contractor_id = c.id
    LEFT JOIN training_requests tr ON w.id = tr.workman_id
    WHERE w.id = ?
    ";
    
    $data = db_single($conn, $query, 'i', [$id]);
    
    if (!$data) {
        echo "<div class='alert alert-danger'>Worker not found.</div>";
        return;
    }
    ?>
    <div class="content-header">
      <h2 class="page-title">Worker Profile & Action Center</h2>
      <p class="page-subtitle">Welfare Department - Approve Enrollment and Safety Training</p>
    </div>

    <div style="display:grid;grid-template-columns:1.25fr 0.75fr;gap:24px;">
        <!-- Details Card -->
        <div class="card glass">
            <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Worker Profile Details</div></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Personal Information</h4>
                        <table class="data-table">
                            <tr><th>Name</th><td><?= htmlspecialchars($data['name'] ?? 'N/A') ?></td></tr>
                            <tr><th>Father's Name</th><td><?= htmlspecialchars($data['father_name'] ?? 'N/A') ?></td></tr>
                            <tr><th>Gender / DOB</th><td><?= htmlspecialchars($data['gender'] ?? 'N/A') ?> / <?= htmlspecialchars($data['dob'] ?? 'N/A') ?></td></tr>
                            <tr><th>Aadhaar</th><td><code><?= htmlspecialchars($data['aadhaar'] ?? 'N/A') ?></code></td></tr>
                            <tr><th>Mobile</th><td><?= htmlspecialchars($data['mobile'] ?? 'N/A') ?></td></tr>
                            <tr><th>Emergency</th><td><?= htmlspecialchars($data['emergency_contact'] ?? 'N/A') ?></td></tr>
                        </table>
                    </div>
                    <div>
                        <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Employment & Statutory</h4>
                        <table class="data-table">
                            <tr><th>Contractor</th><td><?= htmlspecialchars($data['contractor_name'] ?? 'N/A') ?></td></tr>
                            <tr><th>Trade / Skill</th><td><?= htmlspecialchars($data['trade'] ?? 'N/A') ?> / <?= htmlspecialchars($data['skill'] ?? 'N/A') ?></td></tr>
                            <tr><th>PF No / UAN</th><td><?= htmlspecialchars($data['pf_no'] ?: 'N/A') ?> / <?= htmlspecialchars($data['uan_number'] ?: 'N/A') ?></td></tr>
                            <tr><th>ESI No</th><td><?= htmlspecialchars($data['esic_number'] ?: 'N/A') ?></td></tr>
                            <tr><th>Bank / IFSC</th><td><?= htmlspecialchars($data['bank_account'] ?: 'N/A') ?> / <?= htmlspecialchars($data['ifsc'] ?: 'N/A') ?></td></tr>
                        </table>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Worker Documents (Annexure 4A / 6A)</h4>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                        <?php 
                        // 1. Collect docs from workmen table (Enrollment 4A)
                        $displayDocs = [
                            'Aadhaar (4A)' => $data['aadhaar_doc'] ? ['path' => "../../uploads/workers/" . $data['aadhaar_doc'], 'type' => 'enrollment'] : null,
                            'Medical (4A)' => $data['medical_doc'] ? ['path' => "../../uploads/workers/" . $data['medical_doc'], 'type' => 'enrollment'] : null,
                            'Police Verification (4A)' => $data['police_doc'] ? ['path' => "../../uploads/workers/" . $data['police_doc'], 'type' => 'enrollment'] : null,
                            'Insurance (4A)' => $data['insurance_doc'] ? ['path' => "../../uploads/workers/" . $data['insurance_doc'], 'type' => 'enrollment'] : null,
                            'Education (4A)' => $data['educational_doc'] ? ['path' => "../../uploads/workers/" . $data['educational_doc'], 'type' => 'enrollment'] : null,
                            'Photo' => $data['photo'] ? ['path' => "../../uploads/workers/" . $data['photo'], 'type' => 'enrollment'] : null,
                        ];

                        // 2. Fetch docs from documents table (Gate Pass 6A)
                        $otherDocsRes = $conn->query("SELECT document_type, file_path FROM documents WHERE workman_id = $id");
                        while($otherDocsRes && $row = $otherDocsRes->fetch_assoc()) {
                            $displayDocs[$row['document_type']] = [
                                'path' => "../../uploads/documents/" . $row['file_path'],
                                'type' => 'gatepass'
                            ];
                        }

                        foreach($displayDocs as $label => $doc): 
                            if ($doc === null && strpos($label, '(4A)') === false) continue; // Only show missing for core 4A docs
                        ?>
                            <div class="doc-card" style="padding:10px;border:1px solid #eee;border-radius:8px;background:#f8fafc;display:flex;flex-direction:column;gap:8px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-size:11px;font-weight:600;"><?= htmlspecialchars($label) ?></span>
                                    <?php if($doc && isset($doc['type'])): ?>
                                        <span class="badge" style="font-size:9px;padding:2px 6px;<?= $doc['type']==='gatepass' ? 'background:#dcfce7;color:#166534' : 'background:#f1f5f9;color:#475569' ?>">
                                            <?= $doc['type']==='gatepass' ? '6A Upload' : '4A Upload' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if($doc): ?>
                                    <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank" class="btn btn-sm btn-outline" style="width:100%;text-align:center;">
                                        <i class="fas fa-external-link-alt"></i> View Document
                                    </a>
                                <?php else: ?>
                                    <div class="text-danger" style="font-size:11px;text-align:center;padding:5px;border:1px dashed #fecaca;border-radius:4px;">
                                        <i class="fas fa-exclamation-triangle"></i> Missing
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Card -->
        <div>
            <!-- Status Sidebar -->
            <div class="card glass" style="margin-bottom:24px;">
                <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Workflow Status</div></div>
                <div class="card-body">
                    <div class="info-row" style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span>Temporary ID:</span>
                        <code class="text-primary"><?= htmlspecialchars($data['temp_id'] ?: 'Pending') ?></code>
                    </div>
                    <div class="info-row" style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span>Enrollment:</span>
                        <span class="badge <?= (strtolower($data['status']??'')==='verified'||strtolower($data['status']??'')==='approved')?'badge-success':'badge-warning' ?>">
                            <?= strtoupper($data['status'] ?: 'Pending') ?>
                        </span>
                    </div>
                    <div class="info-row" style="display:flex;justify-content:space-between;">
                        <span>Safety Training:</span>
                        <span class="badge <?= (strtolower($data['training_status']??'')==='pass'||strtolower($data['training_status']??'')==='passed')?'badge-success':'badge-warning' ?>">
                            <?= strtoupper($data['training_status'] ?: 'Pending') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Enrollment Approval -->
            <div class="card glass" style="margin-bottom:24px;">
                <div class="card-header"><div class="card-title"><i class="fas fa-clipboard-check"></i> Enrollment Action</div></div>
                <div class="card-body">
                    <p style="margin-bottom:15px;font-size:13px;color:var(--gray-600)">Verify that all documents are uploaded and details match the PDF requirements.</p>
                    <form method="POST" action="update_enrollment.php">
                        <input type="hidden" name="worker_id" value="<?= $data['id'] ?>">
                        <div style="display:flex;gap:10px;">
                            <button class="btn btn-success" style="flex:1" name="action" value="approve"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn btn-danger" style="flex:1" name="action" value="reject"><i class="fas fa-times"></i> Reject</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Training Approval -->
            <div class="card glass">
                <div class="card-header"><div class="card-title"><i class="fas fa-hard-hat"></i> Training Action</div></div>
                <div class="card-body">
                    <p style="margin-bottom:15px;font-size:13px;color:var(--gray-600)">Mark results after safety orientation is completed by the Safety User.</p>
                    <form method="POST" action="update_training.php">
                        <input type="hidden" name="worker_id" value="<?= $data['id'] ?>">
                        <div style="display:flex;gap:10px;">
                            <button class="btn btn-success" style="flex:1" name="action" value="pass"><i class="fas fa-certificate"></i> Pass</button>
                            <button class="btn btn-danger" style="flex:1" name="action" value="fail"><i class="fas fa-ban"></i> Fail</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

renderLayout("Worker Details", 'renderContent', $role, $name);
?>

