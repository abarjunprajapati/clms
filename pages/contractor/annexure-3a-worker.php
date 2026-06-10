<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'workmen'; // representative, supervisor, workmen
$allowedTypes = ['contractor', 'representative', 'supervisor', 'workmen', 'workman'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'workmen';
}
$redirectParams = ['type' => $type];
if (!empty($_GET['aadhaar'])) {
    $redirectParams['aadhaar'] = $_GET['aadhaar'];
}
header('Location: enrolment-4a.php?' . http_build_query($redirectParams));
exit;

// Set display label based on type
$type_label = "Workmen";
if ($type === 'representative') $type_label = "Representative";
if ($type === 'supervisor') $type_label = "Supervisor";

function renderContent() {
    global $conn, $user_id, $type, $type_label;

    // Get contractor record
    $contractor = db_single($conn, "SELECT id, contractor_name, status, work_order_no FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    if (!$c_id) {
        echo '<div class="alert alert-danger">Contractor record not found. Please complete registration first.</div>';
        return;
    }
    
    // Fetch enrolled of this type
    $workers = db_fetch_all($conn, "
        SELECT * FROM workmen 
        WHERE contractor_id = ? AND worker_type = ?
        ORDER BY created_at DESC
    ", 'is', [$c_id, $type]);
    ?>
    <style>
    .square-tabs { display: flex; gap: 10px; padding: 20px 20px 0 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; overflow-x: auto; }
    .square-tab { background: #f1f5f9; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .square-tab.active { background: #6366f1; color: white; border-color: #6366f1; }
    .square-tab:hover:not(.active) { background: #e2e8f0; }
    
    .modal-tab-content { display:block; padding: 25px; }
    .modal-tab-content.hidden { display:none; }

    .form-grid-3 { display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; }
    .form-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:20px; }
    .form-group { margin-bottom: 18px; }
    .form-label { display:block;font-size:13px;font-weight:700;margin-bottom:8px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;}
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid var(--border-color);font-size:14px;box-sizing:border-box; transition: 0.2s; }
    .form-control:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 4px rgba(99,102,241,.1); }
    
    .doc-card { background:#f8fafc; border:1px solid var(--border-color); border-radius:12px; padding:15px; }
    .badge-status { font-size:10px; padding:3px 8px; border-radius:10px; font-weight:600; text-transform:uppercase; }
    
    .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
    </style>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-contract" style="color:#6366f1;margin-right:10px;"></i>  <?= $type_label ?> Registration</h2>
        <p class="page-subtitle">Register your <?= strtolower($type_label) ?> for site access and gate pass.</p>
      </div>
      <div style="display:flex; gap:10px;">
        <button class="btn btn-outline" id="btnBack"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
        <button class="btn btn-primary" id="btnOpenForm"><i class="fas fa-plus"></i> New <?= $type_label ?></button>
      </div>
    </div>

    <!-- Pass Limits Summary -->
    <div id="passLimitsWidget" style="margin-bottom:20px;"></div>

    <div id="listSection">
        <div class="card glass-card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title">Enrolled <?= $type_label ?></h3>
                <input type="text" id="searchWorker" class="form-control" style="width:250px;" placeholder="Search name or Aadhaar...">
            </div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Details</th>
                            <th>Aadhaar</th>
                            <th>Registration Date</th>
                            <th>Temp ID</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($workers as $w): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:40px; height:40px; background:#f1f5f9; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                                        <?php if($w['photo']): ?>
                                            <img src="../../uploads/workers/<?= $w['photo'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user" style="color:#cbd5e1;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($w['name']) ?></div>
                                        <div style="font-size:11px; color:#64748b;"><?= $w['gender'] ?> | <?= $w['mobile'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= $w['aadhaar'] ?></code></td>
                            <td><?= date('d M Y', strtotime($w['created_at'])) ?></td>
                            <td><code class="text-primary"><?= $w['temp_id'] ?? 'PENDING' ?></code></td>
                            <td>
                                <span class="badge-status" style="background:#fef3c7; color:#92400e;">Safety: <?= $w['training_status'] ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="viewWorker(<?= $w['id'] ?>)"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FORM SECTION -->
    <div id="formSection" style="display:none;">
        <div class="card glass-card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title"> New Registration</h3>
                <button class="btn btn-outline btn-sm" onclick="closeForm()"><i class="fas fa-times"></i></button>
            </div>
            <form id="annexure3AForm" enctype="multipart/form-data">
                <input type="hidden" name="worker_type" value="<?= $type ?>">
                <input type="hidden" name="contractor_id" value="<?= $c_id ?>">
                <input type="hidden" name="source" id="workerSource" value="MANUAL">

                <div class="square-tabs">
                    <button type="button" class="square-tab active" data-tab="1">1. Basic Details</button>
                    <button type="button" class="square-tab" data-tab="2">2. Address Details</button>
                    <button type="button" class="square-tab" data-tab="3">3. Employment</button>
                    <button type="button" class="square-tab" data-tab="4">4. Training</button>
                    <button type="button" class="square-tab" data-tab="5">5. Documents</button>
                </div>

                <!-- Section 1: Basic Details -->
                <div class="modal-tab-content" id="section-1">
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label required">Work Order No</label>
                            <input type="text" name="work_order_no" class="form-control" value="<?= htmlspecialchars($contractor['work_order_no'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Project No / Site</label>
                            <input type="text" name="project_no" class="form-control" placeholder="Enter Site Name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Pass Type</label>
                            <input type="text" name="pass_type" class="form-control" value="<?= $type_label ?> Pass" readonly style="background:#f8fafc;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Registration Date</label>
                            <input type="date" name="registration_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Aadhaar Card No</label>
                            <input type="text" name="aadhaar" id="aadhaarInput" class="form-control" maxlength="12" pattern="\d{12}" required placeholder="12 digit number">
                            <div id="aadhaarStatus" style="font-size:11px; margin-top:5px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="As per Aadhaar">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select</option>
                                <option>Male</option><option>Female</option><option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Date of Birth</label>
                            <input type="date" name="dob" id="dobInput" class="form-control" required>
                            <div id="ageStatus" style="font-size:11px; margin-top:5px; color:#dc2626; display:none;">Worker must be at least 18 years old.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Department</label>
                            <select name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php
                                $depts = db_fetch_all($conn, "SELECT dept_name FROM master_departments WHERE status = 'active' ORDER BY dept_name");
                                foreach($depts as $d) {
                                    echo '<option>'.htmlspecialchars($d['dept_name']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control" maxlength="10" required>
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label required">Email ID</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Address Details -->
                <div class="modal-tab-content hidden" id="section-2">
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Religion</label>
                            <input type="text" name="region" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Pincode</label>
                            <input type="text" name="pincode" class="form-control" maxlength="6" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Nationality</label>
                            <input type="text" name="nationality" class="form-control" value="Indian" required>
                        </div>
                        <div class="form-group span-3">
                            <label class="form-label required">Present Address</label>
                            <textarea name="present_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group span-3">
                            <label class="form-label required">Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">State</label>
                            <input type="text" name="state" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">District</label>
                            <input type="text" name="district" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select</option>
                                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                <option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>
                            </select>
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label">Educational Qualification</label>
                            <input type="text" name="qualification" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Previous Experience</label>
                            <input type="text" name="experience" class="form-control" placeholder="e.g. 5 Years">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Employment Details -->
                <div class="modal-tab-content hidden" id="section-3">
                    <div class="form-grid-3">
                        <div class="form-group span-2">
                            <label class="form-label required">Nature of Duty / Job</label>
                            <input type="text" name="nature_of_work" class="form-control" required placeholder="e.g. Welder, Fitter">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Mandatory PPE Issued?</label>
                            <select name="ppe_issued" class="form-control" required>
                                <option value="">Select</option>
                                <option value="YES">Yes - Issued</option>
                                <option value="NO">No - Pending</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Daily Wage Rate</label>
                            <input type="number" name="daily_wage_rate" class="form-control" required step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ESIC Number</label>
                            <input type="text" name="esic_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">EPF Number (UAN)</label>
                            <input type="text" name="uan_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Bank Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control" maxlength="11" required>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Training Details -->
                <div class="modal-tab-content hidden" id="section-4">
                    <div style="background:#f8fafc; padding:20px; border-radius:15px; border:1px dashed #cbd5e1; margin-bottom:20px;">
                        <p style="font-size:13px; color:#64748b; margin-bottom:15px;"><i class="fas fa-info-circle"></i> Training details will be updated by the Safety Department. If the worker has previously attended training at this site, please mention details below.</p>
                        
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Training Attended previously?</label>
                                <select name="safety_training_attended" class="form-control">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Safety Training Date</label>
                                <input type="date" name="safety_training_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Documents -->
                <div class="modal-tab-content hidden" id="section-5">
                    <div class="form-grid-3">
                        <div class="doc-card">
                            <label class="form-label required">Photo Upload</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" required>
                        </div>
                        <div class="doc-card">
                            <label class="form-label required">Aadhaar Card Copy</label>
                            <input type="file" name="aadhaar_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="doc-card">
                            <label class="form-label required">Medical Fitness Cert</label>
                            <input type="file" name="medical_fitness_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="doc-card">
                            <label class="form-label required">Police Clearance (PCC)</label>
                            <input type="file" name="police_clearance_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="doc-card">
                            <label class="form-label required">Insurance Policy</label>
                            <input type="file" name="insurance_policy_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="doc-card">
                            <label class="form-label">Qualification Cert</label>
                            <input type="file" name="qualification_file" class="form-control" accept=".pdf">
                        </div>
                        <div class="doc-card">
                            <label class="form-label">Experience Cert</label>
                            <input type="file" name="experience_file" class="form-control" accept=".pdf">
                        </div>
                    </div>
                </div>

                <div style="padding:20px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:12px; background:#f8fafc; border-radius:0 0 20px 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/passLimitValidator.js"></script>
    <script>
    const cid = <?= $c_id ?>;
    const type = '<?= $type ?>';

    async function updatePassLimits() {
        if (!cid) return;
        await PassLimitValidator.fetchLimits(cid);
        PassLimitValidator.renderSummary(document.getElementById('passLimitsWidget'));
    }

    document.getElementById('btnOpenForm').onclick = () => {
        // Limit Check before opening
        let limitType = 'Workman';
        if (type === 'supervisor') limitType = 'Supervisor';
        else if (type === 'representative') limitType = 'Representative';
        
        if (typeof PassLimitValidator !== 'undefined') {
            if (!PassLimitValidator.validate(limitType, 1)) return;
        }

        document.getElementById('listSection').style.display = 'none';
        document.getElementById('formSection').style.display = 'block';
    };

    function closeForm() {
        document.getElementById('formSection').style.display = 'none';
        document.getElementById('listSection').style.display = 'block';
        document.getElementById('annexure3AForm').reset();
    }

    document.querySelectorAll('.square-tab').forEach(tab => {
        tab.onclick = () => {
            document.querySelectorAll('.square-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.modal-tab-content').forEach(c => c.classList.add('hidden'));
            document.getElementById('section-' + tab.dataset.tab).classList.remove('hidden');
        };
    });

    document.getElementById('btnBack').onclick = () => window.location.href = 'dashboard.php';

    // Aadhaar Search Logic
    document.getElementById('aadhaarInput').onblur = async function() {
        const val = this.value.trim();
        if (val.length !== 12) return;

        const status = document.getElementById('aadhaarStatus');
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        status.style.color = '#6366f1';

        try {
            const resp = await fetch(`../../api/contractor/fetch_worker_aadhaar.php?aadhaar=${val}`);
            const res = await resp.json();
            if (res.success && res.data) {
                status.innerHTML = `<i class="fas fa-check-circle"></i> Found in ${res.data.source}. Auto-filling...`;
                status.style.color = '#10b981';
                document.getElementById('workerSource').value = res.data.source;
                
                // Fill fields
                const data = res.data;
                const form = document.getElementById('annexure3AForm');
                const fields = ['name', 'gender', 'mobile', 'email', 'pincode', 'nationality', 'present_address', 'permanent_address', 'state', 'district', 'qualification', 'experience', 'nature_of_work', 'daily_wage_rate', 'esic_number', 'uan_number', 'bank_account_number', 'ifsc_code'];
                
                fields.forEach(f => {
                    const input = form.querySelector(`[name="${f}"]`);
                    if (input && data[f]) {
                        input.value = data[f];
                        input.readOnly = true;
                        if (input.tagName === 'SELECT') {
                            input.style.pointerEvents = 'none';
                            input.style.background = '#f1f5f9';
                        }
                    }
                });
            } else {
                status.innerHTML = '<i class="fas fa-info-circle"></i> New Record';
                status.style.color = '#64748b';
                document.getElementById('workerSource').value = 'MANUAL';
            }
        } catch(e) {
            status.innerHTML = '';
        }
    };

    document.getElementById('annexure3AForm').onsubmit = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        const formData = new FormData(this);
        
        // Age validation
        const dob = document.getElementById('dobInput').value;
        if (dob) {
            const age = new Date().getFullYear() - new Date(dob).getFullYear();
            if (age < 18) {
                document.getElementById('ageStatus').style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Submit Customer Registration';
                return;
            }
        }
        
        // PPE validation
        if (formData.get('ppe_issued') === 'NO') {
            alert("Worker cannot be registered without mandatory PPE issuance.");
            btn.disabled = false;
            btn.innerHTML = 'Submit Customer Registration';
            return;
        }

        try {
            const resp = await fetch('../../api/contractor/save_annexure3a_worker.php', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();
            if (res.success) {
                alert('Success: ' + res.message);
                location.reload();
            } else {
                alert('Error: ' + res.message);
                btn.disabled = false;
                btn.innerHTML = 'Submit Customer Registration';
            }
        } catch(err) {
            alert('Server error. Please check console.');
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = 'Submit Customer Registration';
        }
    };

    updatePassLimits();
    </script>
    <?php
}

renderLayout("Customer Registration - $type_label Registration", 'renderContent', $role, $name);
?>
