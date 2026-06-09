<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'];

function renderContent() {
    global $conn;
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Muster Roll logic: Fetch workers and their attendance for the month
    $query = "SELECT w.name, c.contractor_name, COUNT(a.id) as days_present 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.user_id 
              LEFT JOIN attendance a ON w.id = a.workman_id AND MONTH(a.check_in) = $month AND YEAR(a.check_in) = $year
              GROUP BY w.id";
    $results = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Muster Roll Report</h2>
      <p class="page-subtitle">Monthly attendance summary per workman.</p>
    </div>

    <div class="card glass" style="margin-bottom:20px">
      <div class="card-body">
        <form method="GET" style="display:flex; gap:10px; align-items:flex-end">
          <div class="form-group">
            <label class="form-label">Month</label>
            <select name="month" class="form-control">
              <?php for($m=1; $m<=12; $m++): ?>
                <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <select name="year" class="form-control">
              <option value="2026" selected>2026</option>
              <option value="2025">2025</option>
            </select>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Export PDF</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card glass">
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Month</th>
              <th>Days Present</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($results as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['contractor_name']) ?></td>
              <td><?= date('F Y', mktime(0,0,0,$month, 1, $year)) ?></td>
              <td><strong><?= $r['days_present'] ?></strong> / 30</td>
              <td><span class="badge badge-success">Verified</span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Muster Roll Report", 'renderContent', $role, $name);

