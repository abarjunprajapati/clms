<?php
require_once __DIR__ . '/../../include/auth.php';
// Adding fallback lowercase role for cached session.php issues
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = strtolower(trim($_SESSION['role']));
}
checkAuth(['welfare_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function renderContent() {
    global $conn;

    // Fixed configuration limits as per user instruction
    $TEMP_PASS_DAYS = 15;
    $PERM_PASS_DAYS = 90;

    // Search filter for future dates
    $search_date = $_GET['search_date'] ?? '';

    // Filter strictly by verified or approved workmen as per new workflow manual
    $where_clauses = ["w.status IN ('verified', 'approved')"]; 
    
    if (!empty($search_date)) {
        $where_clauses[] = "w.expected_joining_date = '" . mysqli_real_escape_string($conn, $search_date) . "'";
    } else {
        $where_clauses[] = "w.expected_joining_date <= CURDATE()";
    }

    $where_sql = implode(' AND ', $where_clauses);

    $sql = "SELECT w.id, w.aadhaar, w.skill_category, w.expected_joining_date, 
                   c.name as contractor_name,
                   (SELECT COUNT(*) FROM documents d 
                    JOIN gate_pass_document_masters gm ON d.document_type = gm.document_type
                    WHERE d.workman_id = w.id AND gm.category = 'pcc' AND d.status = 'approved') as pcc_approved_count
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE $where_sql
            ORDER BY w.expected_joining_date DESC";

    $workmen = db_fetch_all($conn, $sql);

    ?>
    <style>
        .computed-pass-to-dt { font-weight: bold; }
        .table th, .table td { vertical-align: middle; }
    </style>
    
    <div class="content-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h2 style="margin:0;">ACC no Generation & Hiring</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <form method="GET" style="display:flex; gap:10px; margin:0;">
                <input type="date" name="search_date" class="form-control" value="<?= htmlspecialchars($search_date) ?>" title="Search by future date" style="max-width:200px;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search_date)): ?>
                    <a href="acc_generation.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form id="hiringForm">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0 data-table" id="workmenTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl.No</th>
                                <th>Contractor</th>
                                <th>Aadhar No</th>
                                <th>Eligible Pass Type (Temp/Perm)</th>
                                <th>Worker Category</th>
                                <th>Preferred Joining Dt</th>
                                <th>Pass Till Dt</th>
                                <th>Pass From Dt</th>
                                <th>Pass To Dt</th>
                                <th class="text-center">Hiring <input type="checkbox" id="selectAll" class="form-check-input ms-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workmen)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">No workmen match the criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($workmen as $index => $w): 
                                    // Logic for Pass Type (Permanent if PCC uploaded and verified OK)
                                    $is_permanent = ($w['pcc_approved_count'] > 0);
                                    $pass_type = $is_permanent ? 'Permanent Pass' : 'Temporary Pass';
                                    $max_days = $is_permanent ? $PERM_PASS_DAYS : $TEMP_PASS_DAYS;
                                    
                                    // If expected joining date is not set, fallback to today
                                    $expected_dt = $w['expected_joining_date'] ?: date('Y-m-d');
                                    $current_dt = date('Y-m-d');
                                    
                                    // Joining Dt = Current Date
                                    // Pass From Dt = Current Date
                                    $pass_from_dt = $current_dt;

                                    // Pass Till Date = Preferred Joining Dt + Limit
                                    $pass_till_dt_timestamp = strtotime($expected_dt . " + $max_days days");
                                    $default_pass_till_dt = date('Y-m-d', $pass_till_dt_timestamp);
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($w['contractor_name']) ?></td>
                                    <td><?= htmlspecialchars($w['aadhaar']) ?></td>
                                    <td><span class="badge <?= $is_permanent ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $pass_type ?></span></td>
                                    <td><?= htmlspecialchars($w['skill_category']) ?></td>
                                    <td><?= htmlspecialchars($w['expected_joining_date']) ?></td>
                                    <td>
                                        <input type="date" 
                                               class="form-control form-control-sm pass-till-dt" 
                                               name="workmen[<?= $w['id'] ?>][pass_till_dt]" 
                                               value="<?= $default_pass_till_dt ?>"
                                               data-max-dt="<?= $default_pass_till_dt ?>"
                                               data-max-days="<?= $max_days ?>"
                                               max="<?= $default_pass_till_dt ?>"
                                               required>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y') ?>
                                        <input type="hidden" name="workmen[<?= $w['id'] ?>][pass_from_dt]" value="<?= $pass_from_dt ?>">
                                    </td>
                                    <td class="computed-pass-to-dt text-primary">
                                        <?= date('d/m/Y', $pass_till_dt_timestamp) ?>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-checkbox" name="workmen[<?= $w['id'] ?>][selected]" value="1" style="width:1.5rem;height:1.5rem;">
                                        <input type="hidden" name="workmen[<?= $w['id'] ?>][aadhaar]" value="<?= htmlspecialchars($w['aadhaar']) ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 mb-3 text-end pe-3">
                    <button type="button" id="btnHire" class="btn btn-success btn-lg shadow-sm" <?= empty($workmen) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle me-2"></i> Hiring
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- API Actions as per exact notes -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select All checkboxes
        const selectAll = document.getElementById('selectAll');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        // Pass Till Dt dynamic validation (Live updates "Pass To Dt" column)
        const passTillInputs = document.querySelectorAll('.pass-till-dt');
        passTillInputs.forEach(input => {
            input.addEventListener('change', function() {
                const maxDt = new Date(this.getAttribute('data-max-dt'));
                const selectedDt = new Date(this.value);
                
                if (selectedDt > maxDt) {
                    alert('Validity cannot exceed ' + this.getAttribute('data-max-days') + ' days from Preferred Joining Dt.');
                    this.value = this.getAttribute('data-max-dt');
                }
                
                // Update display Pass To Dt
                const tr = this.closest('tr');
                const passToCell = tr.querySelector('.computed-pass-to-dt');
                if (this.value) {
                    const parts = this.value.split('-');
                    passToCell.textContent = parts[2] + '/' + parts[1] + '/' + parts[0];
                }
            });
        });

        // Hiring Button Action
        document.getElementById('btnHire').addEventListener('click', function() {
            const selected = document.querySelectorAll('.row-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select workmen.');
                return;
            }

            let payload = {
                action: 'hiring',
                workmen: []
            };

            selected.forEach(cb => {
                const tr = cb.closest('tr');
                const id = cb.name.match(/\[(\d+)\]/)[1];
                const aadhaar = tr.querySelector('input[name="workmen['+id+'][aadhaar]"]').value;
                const passTill = tr.querySelector('.pass-till-dt').value;
                const passFrom = tr.querySelector('input[name="workmen['+id+'][pass_from_dt]"]').value;
                
                payload.workmen.push({
                    id: id,
                    aadhaar: aadhaar,
                    pass_from: passFrom,
                    pass_to: passTill
                });
            });

            // Disable button during processing
            const btn = document.getElementById('btnHire');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            btn.disabled = true;

            fetch('../../api/welfare/process_hiring.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Success: ' + data.message + '\n\nEmployee validity dt (Hiring action) -> 31/12/9999\nAfter Hiring:\n- Acc no generated\n- Hiring Dt\n- Reply to APP4S');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred during the hiring process.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    });
    </script>
    <?php
}

renderLayout("ACC Generation & Hiring", 'renderContent', $role, $name);
