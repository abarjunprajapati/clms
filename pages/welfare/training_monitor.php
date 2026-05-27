<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $sql = "
        SELECT 
            w.temp_id, 
            w.name, 
            c.contractor_name, 
            st.training_date, 
            st.result, 
            st.valid_till, 
            st.trainer_name
        FROM safety_training st
        JOIN workmen w ON st.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        
        UNION ALL
        
        SELECT 
            w.temp_id, 
            w.name, 
            c.contractor_name, 
            tr.created_at as training_date, 
            tr.result, 
            DATE_ADD(tr.created_at, INTERVAL 1 YEAR) as valid_till, 
            COALESCE(u.name, 'System') as trainer_name
        FROM training_results tr
        JOIN workmen w ON tr.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN users u ON tr.recorded_by = u.id
        WHERE tr.result IN ('pass', 'passed', 'qualified', 'completed')
          AND tr.workman_id NOT IN (SELECT workman_id FROM safety_training)
        
        ORDER BY training_date DESC
    ";
    $trainings = db_fetch_all($conn, $sql);
    ?>
    <div class="content-header">
      <h2 class="page-title">Safety Training Monitoring</h2>
      <!-- <p class="page-subtitle">Ensure only workers who pass safety training are allowed for gate pass issuance.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-graduation-cap"></i> Training Results Summary</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman ID</th>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Training Date</th>
              <th>Result</th>
              <th>Valid Till</th>
              <th>Trainer</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($trainings as $t): 
              $isSuccess = in_array(strtolower($t['result']), ['pass', 'passed', 'qualified', 'completed']);
              $res_class = $isSuccess ? 'badge-success' : 'badge-danger';
            ?>
            <tr>
              <td><code><?= htmlspecialchars($t['temp_id'] ?? 'N/A') ?></code></td>
              <td><strong><?= htmlspecialchars($t['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($t['contractor_name'] ?? 'N/A') ?></td>
              <td><?= date('d M Y', strtotime($t['training_date'] ?? 'now')) ?></td>
              <td><span class="badge <?= $res_class ?>"><?= strtoupper($t['result'] ?? '') ?></span></td>
              <td><?= ($t['valid_till'] ?? null) ? date('d M Y', strtotime($t['valid_till'])) : 'N/A' ?></td>
              <td><?= htmlspecialchars($t['trainer_name'] ?? 'System') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Training Monitoring", 'renderContent', $role, $name);

