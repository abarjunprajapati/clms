<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $enrollments = db_fetch_all($conn, "SELECT w.*, c.contractor_name, w.temp_id as enrollment_temp_id, w.status as enrollment_status 
                                        FROM workmen w 
                                        JOIN contractors c ON w.contractor_id = c.id 
                                        ORDER BY w.created_at DESC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Enrollment Monitoring</h2>
      <!-- <p class="page-subtitle">Track workmen enrollment and temporary ID generation status.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-id-badge"></i> Workmen Enrollment Status</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Trade / Skill</th>
              <th>Temporary ID</th>
              <th>Biometric</th>
              <th>Enrollment Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($enrollments as $w): 
              $s = $w['enrollment_status'];
              if ($s === 'approved') $status_class = 'badge-success';
              elseif ($s === 'rejected') $status_class = 'badge-danger';
              else $status_class = 'badge-warning';
            ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($w['name']) ?></strong>
                <div style="font-size:11px;color:var(--gray-500)">Aadhar: <?= htmlspecialchars($w['aadhaar']) ?></div>
              </td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><?= htmlspecialchars($w['trade']) ?> / <?= htmlspecialchars($w['skill']) ?></td>
              <td><code><?= htmlspecialchars($w['enrollment_temp_id'] ?: 'Not Generated') ?></code></td>
              <td>
                <span class="text-success"><i class="fas fa-fingerprint"></i> Done</span>
              </td>
              <td><span class="badge <?= $status_class ?>"><?= ucfirst($w['enrollment_status'] ?: 'Pending') ?></span></td>
              <td>
                <a href="view_workman.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                <?php if (($_SESSION['role'] ?? '') === 'welfare_admin' || ($_SESSION['role'] ?? '') === 'super_admin'): ?>
                  <a href="education_correction.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-graduation-cap"></i> Education
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Enrollment Monitoring", 'renderContent', $role, $name);
