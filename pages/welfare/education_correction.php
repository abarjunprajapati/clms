<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/education_flow.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

$educationOptions = clms_get_education_options($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_worker';

    if ($action === 'add_flow') {
        $skill = clms_normalize_flow_skill($_POST['flow_skill_category'] ?? '');
        $qualification = trim($_POST['flow_qualification'] ?? '');
        $jobProfile = trim($_POST['flow_job_profile'] ?? '');

        if ($skill === '' || $qualification === '' || $jobProfile === '') {
            $_SESSION['error'] = 'Skill category, qualification and job profile are required.';
            header('Location: education_correction.php#flowMaster');
            exit;
        }

        clms_ensure_education_flow_table($conn);
        $maxSort = db_single($conn, "SELECT COALESCE(MAX(sort_order), 0) + 10 AS next_sort FROM education_job_profiles");
        $sortOrder = (int)($maxSort['next_sort'] ?? 10);
        db_execute(
            $conn,
            "INSERT INTO education_job_profiles (skill_category, qualification, job_profile, sort_order, is_active)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE is_active = 1, sort_order = VALUES(sort_order), updated_at = NOW()",
            'sssi',
            [$skill, $qualification, $jobProfile, $sortOrder]
        );

        $_SESSION['success'] = 'Education flow option added successfully.';
        header('Location: education_correction.php#flowMaster');
        exit;
    }

    if ($action === 'delete_flow') {
        $flowId = (int)($_POST['flow_id'] ?? 0);
        if ($flowId > 0) {
            clms_ensure_education_flow_table($conn);
            db_execute($conn, "UPDATE education_job_profiles SET is_active = 0, updated_at = NOW() WHERE id = ?", 'i', [$flowId]);
            $_SESSION['success'] = 'Education job profile set as inactive.';
        }
        header('Location: education_correction.php#flowMaster');
        exit;
    }

    $worker_id = (int)($_POST['worker_id'] ?? 0);
    $education = trim($_POST['education'] ?? '');
    $skill = clms_flow_skill_for_workmen($_POST['skill_category'] ?? '');
    $nature_of_work = trim($_POST['nature_of_work'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$worker_id || $education === '' || $skill === '' || $nature_of_work === '') {
        $_SESSION['error'] = 'Worker, education, skill category and job profile are required.';
        header('Location: education_correction.php' . ($worker_id ? '?id=' . $worker_id : ''));
        exit;
    }

    $worker = db_single(
        $conn,
        "SELECT w.*, c.work_order_no FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.id = ?",
        'i',
        [$worker_id]
    );

    if (!$worker) {
        $_SESSION['error'] = 'Worker not found.';
        header('Location: education_correction.php');
        exit;
    }

    $old = [
        'education' => $worker['education'] ?? '',
        'skill' => $worker['skill'] ?? '',
        'skill_category' => $worker['skill_category'] ?? '',
        'nature_of_work' => $worker['nature_of_work'] ?? '',
        'trade' => $worker['trade'] ?? '',
    ];

    db_execute(
        $conn,
        "UPDATE workmen
         SET education = ?, skill = ?, skill_category = ?, nature_of_work = ?, trade = ?, updated_at = NOW()
         WHERE id = ?",
        'sssssi',
        [$education, $skill, $skill, $nature_of_work, $nature_of_work, $worker_id]
    );

    $new = [
        'education' => $education,
        'skill' => $skill,
        'skill_category' => $skill,
        'nature_of_work' => $nature_of_work,
        'trade' => $nature_of_work,
    ];
    $details = 'Education correction for worker #' . $worker_id . ' (' . ($worker['name'] ?? '') . '). Remarks: ' . $remarks;
    db_execute(
        $conn,
        "INSERT INTO audit_logs (user_id, action, module, old_value, new_value, remarks, ip_address)
         VALUES (?, 'worker_education_updated', 'workmen', ?, ?, ?, ?)",
        'issss',
        [
            (int)($_SESSION['user_id'] ?? 0),
            json_encode($old, JSON_UNESCAPED_SLASHES),
            json_encode($new, JSON_UNESCAPED_SLASHES),
            $details,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]
    );

    $_SESSION['success'] = 'Worker education details updated successfully.';
    header('Location: education_correction.php?id=' . $worker_id);
    exit;
}

function renderContent() {
    global $conn, $educationOptions;

    $selectedId = (int)($_GET['id'] ?? 0);
    $flowRows = clms_get_education_flow_rows($conn);
    $workers = db_fetch_all($conn, "
        SELECT w.id, w.temp_id, w.name, w.aadhaar, w.education, w.skill, w.skill_category,
               w.nature_of_work, w.trade, w.status, c.contractor_name
        FROM workmen w
        LEFT JOIN contractors c ON w.contractor_id = c.id
        ORDER BY w.updated_at DESC, w.created_at DESC
    ");

    $selected = null;
    if ($selectedId) {
        $selected = db_single($conn, "
            SELECT w.*, c.contractor_name
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE w.id = ?
        ", 'i', [$selectedId]);
    }
    ?>
    <style>
      .education-layout { display:grid; grid-template-columns:minmax(0, 1.25fr) minmax(360px, .75fr); gap:20px; align-items:start; }
      .worker-meta { color:var(--text-muted,#64748b); font-size:12px; margin-top:3px; }
      .edit-panel { position:sticky; top:90px; }
      .form-grid { display:grid; gap:14px; }
      .flow-master-grid { display:grid; grid-template-columns:minmax(260px, .45fr) minmax(0, .55fr); gap:18px; align-items:start; margin-bottom:20px; }
      .flow-add-grid { display:grid; gap:12px; }
      .flow-master-list { display:grid; gap:8px; max-height:360px; overflow:auto; padding-right:4px; }
      .flow-master-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; background:#fff; }
      .flow-master-main strong { display:block; color:#0f172a; font-size:13px; }
      .flow-master-main span { display:block; color:#64748b; font-size:12px; margin-top:3px; }
      .correction-note { font-size:12px; color:#64748b; line-height:1.45; margin:0 0 14px; }
      @media (max-width: 1000px) { .education-layout, .flow-master-grid { grid-template-columns:1fr; } .edit-panel { position:static; } }
    </style>

    <div class="content-header">
      <div>
        <h2 class="page-title">Education Job Profile</h2>
        <!-- <p class="page-subtitle">Update Annexure 4A education, skill category and job profile before welfare approval.</p> -->
      </div>
    </div>

    <div class="card glass" id="flowMaster">
      <div class="card-header">
        <div>
          <div class="card-title">Education Job Profile Master</div>
          <!-- <p class="correction-note" style="margin:6px 0 0;">Add options here to make them available in contractor Annexure 4A enrollment and welfare correction.</p> -->
        </div>
      </div>
      <div class="card-body">
        <div class="flow-master-grid">
          <form method="POST" class="flow-add-grid">
            <input type="hidden" name="action" value="add_flow">
            <div class="form-group">
              <label class="form-label required">Skill Category</label>
              <select class="form-control" name="flow_skill_category" required>
                <option value="">Select category</option>
                <option value="Skilled">Skilled</option>
                <option value="Semi-Skilled">Semi-Skilled</option>
                <option value="Unskilled">Unskilled</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label required">Education / Qualification</label>
              <input class="form-control" name="flow_qualification" list="qualificationList" placeholder="e.g. Diploma" required>
              <datalist id="qualificationList">
                <?php foreach (array_keys($educationOptions) as $qualification): ?>
                  <option value="<?= htmlspecialchars($qualification) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="form-group">
              <label class="form-label required">Job Profile / Nature of Work</label>
              <input class="form-control" name="flow_job_profile" placeholder="e.g. Crane Operator" required>
            </div>
            <button class="btn btn-primary" type="submit">
              Add Job Profile
            </button>
          </form>

          <div class="flow-master-list">
            <?php foreach ($flowRows as $row): ?>
              <div class="flow-master-row">
                <div class="flow-master-main">
                  <strong><?= htmlspecialchars($row['job_profile'] ?? '') ?></strong>
                  <span><?= htmlspecialchars($row['skill_category'] ?? '') ?> | <?= htmlspecialchars($row['qualification'] ?? '') ?></span>
                </div>
                <form method="POST" onsubmit="return confirm('Set this education job profile as inactive?');">
                  <input type="hidden" name="action" value="delete_flow">
                  <input type="hidden" name="flow_id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-sm btn-outline" type="submit" title="Remove">
                    Inactive
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="education-layout">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-users"></i> Enrolled Workers</div>
        </div>
        <div class="card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Contractor</th>
                <th>Education / Skill</th>
                <th>Job Profile</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($workers as $w): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($w['name'] ?? '') ?></strong>
                    <div class="worker-meta">
                      <?= htmlspecialchars($w['temp_id'] ?: 'No Temp ID') ?> | Aadhaar: <?= htmlspecialchars($w['aadhaar'] ?: 'N/A') ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($w['contractor_name'] ?? 'N/A') ?></td>
                  <td>
                    <?= htmlspecialchars($w['education'] ?: 'Not set') ?>
                    <div class="worker-meta"><?= htmlspecialchars(($w['skill_category'] ?: $w['skill']) ?: 'Not set') ?></div>
                  </td>
                  <td><?= htmlspecialchars(($w['nature_of_work'] ?: $w['trade']) ?: 'Not set') ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline" href="education_correction.php?id=<?= (int)$w['id'] ?>">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card glass edit-panel">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-pen-to-square"></i> Correction Form</div>
        </div>
        <div class="card-body">
          <?php if (!$selected): ?>
            <p class="correction-note">Select a worker from the table to correct enrolment education details.</p>
          <?php else: ?>
            <p class="correction-note">
              Editing: <strong><?= htmlspecialchars($selected['name'] ?? '') ?></strong><br>
              Contractor: <?= htmlspecialchars($selected['contractor_name'] ?? 'N/A') ?>
            </p>
            <form method="POST" class="form-grid" id="educationCorrectionForm">
              <input type="hidden" name="action" value="update_worker">
              <input type="hidden" name="worker_id" value="<?= (int)$selected['id'] ?>">

              <div class="form-group">
                <label class="form-label required">Education / Qualification</label>
                <select class="form-control" name="education" id="educationSelect" required>
                  <option value="">Select qualification</option>
                  <?php foreach ($educationOptions as $qualification => $meta): ?>
                    <option value="<?= htmlspecialchars($qualification) ?>" data-skill="<?= htmlspecialchars($meta['skill']) ?>" <?= (($selected['education'] ?? '') === $qualification) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($qualification) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label required">Skill Category</label>
                <select class="form-control" name="skill_category" id="skillSelect" required>
                  <?php $selectedSkill = clms_normalize_flow_skill(($selected['skill_category'] ?? '') ?: ($selected['skill'] ?? '')); ?>
                  <?php foreach (['Skilled', 'Semi-Skilled', 'Unskilled'] as $skill): ?>
                    <option value="<?= $skill ?>" <?= ($selectedSkill === $skill) ? 'selected' : '' ?>>
                      <?= $skill ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label required">Job Profile / Nature of Work</label>
                <select class="form-control" name="nature_of_work" id="jobSelect" data-current="<?= htmlspecialchars(($selected['nature_of_work'] ?: $selected['trade']) ?? '') ?>" required>
                  <option value="">Select job profile</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea class="form-control" name="remarks" rows="3" placeholder="Reason for correction"></textarea>
              </div>

              <button class="btn btn-primary" type="submit">
                <i class="fas fa-save"></i> Update Education
              </button>
              <a class="btn btn-outline" href="view_workman.php?id=<?= (int)$selected['id'] ?>">
                <i class="fas fa-eye"></i> View Worker
              </a>
            </form>

            <script>
              const educationFlow = <?= json_encode($educationOptions, JSON_UNESCAPED_SLASHES) ?>;
              const educationSelect = document.getElementById('educationSelect');
              const skillSelect = document.getElementById('skillSelect');
              const jobSelect = document.getElementById('jobSelect');

              function renderJobs() {
                const selected = educationSelect.value;
                const current = jobSelect.dataset.current || '';
                const jobs = educationFlow[selected]?.jobs || [];
                jobSelect.innerHTML = '<option value="">Select job profile</option>';
                jobs.forEach(job => {
                  const option = document.createElement('option');
                  option.value = job;
                  option.textContent = job;
                  if (job === current) option.selected = true;
                  jobSelect.appendChild(option);
                });
                if (educationFlow[selected]?.skill) {
                  skillSelect.value = educationFlow[selected].skill;
                }
              }

              educationSelect.addEventListener('change', () => {
                jobSelect.dataset.current = '';
                renderJobs();
              });
              renderJobs();
            </script>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}

renderLayout('Education Job Profile', 'renderContent', $role, $name);
?>
