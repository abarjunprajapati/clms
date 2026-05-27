<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    $month = $_GET['month'] ?? date('Y-m');
    $month_start = $month . '-01';
    $month_end   = date('Y-m-t', strtotime($month_start));
    $active_tab = $_GET['tab'] ?? 'daily';

    // Base query for attendance joined with workmen to ensure contractor restriction
    $attendance = $c_id ? db_fetch_all($conn,
        "SELECT a.*, w.name as worker_name, w.trade, w.acc_number
         FROM sap_attendance a
         JOIN workmen w ON a.acc_no = w.acc_number
         WHERE w.contractor_id = ? AND a.attendance_date BETWEEN ? AND ?
         ORDER BY a.attendance_date DESC, a.in_time DESC",
        'iss', [$c_id, $month_start, $month_end]) : [];

    // Summaries for cards
    $present_count = count(array_unique(array_column($attendance, 'acc_no')));
    $avg_hours = 0;
    if (count($attendance) > 0) {
        $total_mins = 0;
        foreach($attendance as $at) {
            if ($at['working_hours']) {
                $parts = explode(':', $at['working_hours']);
                $total_mins += ($parts[0] * 60) + ($parts[1] ?? 0);
            }
        }
        $avg_hours = round($total_mins / (count($attendance) * 60), 1);
    }

    ?>
    <style>
        .nav-tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .tab-btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: 0.2s; border: 1px solid transparent; background: transparent; color: var(--text-muted); }
        .tab-btn.active { background: rgba(59,130,246,0.1); color: #3b82f6; border-color: rgba(59,130,246,0.2); }
        .tab-btn:hover:not(.active) { background: rgba(255,255,255,0.05); }
        .muster-table th, .muster-table td { padding: 6px 4px !important; text-align: center !important; font-size: 11px !important; min-width: 25px; }
        .status-p { color: #10b981; font-weight: bold; }
        .status-a { color: #ef4444; }
        .status-w { color: #3b82f6; }
        .status-h { color: #f59e0b; }
    </style>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-calendar-check" style="color:#3b82f6;margin-right:10px;"></i> Attendance & Compliance</h2>
        <!-- <p class="page-subtitle">SAP-synced attendance, productivity analytics, and statutory validation.</p> -->
      </div>
      <div style="display:flex;gap:10px;align-items:center;">
        <input type="month" id="monthPicker" class="form-control" style="width:180px;" value="<?= $month ?>">
        <button class="btn btn-outline" onclick="exportCSV()"><i class="fas fa-download"></i> Export</button>
      </div>
    </div>

    <div class="nav-tabs">
        <button class="tab-btn <?= $active_tab == 'daily' ? 'active' : '' ?>" onclick="switchTab('daily')">Daily Attendance</button>
        <button class="tab-btn <?= $active_tab == 'muster' ? 'active' : '' ?>" onclick="switchTab('muster')">Muster Roll</button>
        <button class="tab-btn <?= $active_tab == 'productivity' ? 'active' : '' ?>" onclick="switchTab('productivity')">Productivity</button>
        <button class="tab-btn <?= $active_tab == 'compliance' ? 'active' : '' ?>" onclick="switchTab('compliance')">PF/ESI Validation</button>
    </div>

    <?php if ($active_tab == 'daily'): ?>
        <!-- Daily View -->
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
          <div class="stat-card glass">
            <div class="stat-icon" style="background:rgba(59,130,246,.12);color:#3b82f6"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $present_count ?></div>
            <div class="stat-label">Unique Workers Present</div>
          </div>
          <div class="stat-card glass">
            <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $avg_hours ?>h</div>
            <div class="stat-label">Avg. Daily Work Hours</div>
          </div>
          <div class="stat-card glass">
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fas fa-sync"></i></div>
            <div class="stat-value"><?= count(array_filter($attendance, function($x) { return $x['sap_sync_status'] == 'SYNCED'; })) ?></div>
            <div class="stat-label">SAP Synced Records</div>
          </div>
        </div>

        <div class="card glass">
            <div class="card-header"><div class="card-title">Attendance Logs (<?= $month ?>)</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table" id="attTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Worker</th>
                            <th>ACC No</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attendance as $r): ?>
                        <tr>
                            <td><?= date('d M', strtotime($r['attendance_date'])) ?></td>
                            <td style="font-weight:600"><?= htmlspecialchars($r['worker_name']) ?></td>
                            <td><code><?= htmlspecialchars($r['acc_no']) ?></code></td>
                            <td><?= $r['in_time'] ? date('H:i', strtotime($r['in_time'])) : '-' ?></td>
                            <td><?= $r['out_time'] ? date('H:i', strtotime($r['out_time'])) : '-' ?></td>
                            <td><?= $r['working_hours'] ?: '-' ?></td>
                            <td><span class="badge <?= $r['punch_status'] == 'OUT' ? 'badge-success' : 'badge-warning' ?>"><?= $r['punch_status'] ?: 'IN' ?></span></td>
                            <td><small style="opacity:0.7"><?= str_replace('_', ' ', $r['sync_source']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab == 'muster'): ?>
        <!-- Muster Roll View (PAHDWOH) -->
        <div class="card glass" style="overflow-x:auto">
            <div class="card-header"><div class="card-title">Monthly Muster Roll - <?= date('F Y', strtotime($month_start)) ?></div></div>
            <div class="card-body" style="padding:0">
                <?php
                $workers = db_fetch_all($conn, "SELECT id, name, acc_number FROM workmen WHERE contractor_id = ? AND status != 'blocked'", 'i', [$c_id]);
                $days_in_month = date('t', strtotime($month_start));
                
                // Map attendance for fast lookup
                $att_map = [];
                foreach($attendance as $a) {
                    $day = (int)date('d', strtotime($a['attendance_date']));
                    $att_map[$a['acc_no']][$day] = 'P'; // Present
                }
                ?>
                <table class="data-table muster-table">
                    <thead>
                        <tr>
                            <th style="text-align:left !important; min-width:150px">Worker Name</th>
                            <?php for($d=1; $d<=$days_in_month; $d++): ?>
                                <th><?= $d ?></th>
                            <?php endfor; ?>
                            <th>P</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($workers as $w): 
                            $p_count = 0;
                            ?>
                        <tr>
                            <td style="text-align:left !important; font-weight:600"><?= htmlspecialchars($w['name']) ?></td>
                            <?php for($d=1; $d<=$days_in_month; $d++): 
                                $status = $att_map[$w['acc_number']][$d] ?? '-';
                                $class = '';
                                if ($status == 'P') { $class = 'status-p'; $p_count++; }
                                
                                // Mock Weekly Off (Sundays)
                                $date_str = "$month-" . sprintf("%02d", $d);
                                if (date('N', strtotime($date_str)) == 7) {
                                    $status = ($status == 'P') ? 'P' : 'WO';
                                    $class = ($status == 'P') ? 'status-p' : 'status-w';
                                }
                            ?>
                                <td class="<?= $class ?>"><?= $status ?></td>
                            <?php endfor; ?>
                            <td style="font-weight:bold"><?= $p_count ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab == 'productivity'): ?>
        <!-- Productivity Reports -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Worker Productivity (Hours vs Shift)</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Total Days</th>
                            <th>Total Hours</th>
                            <th>Avg. Efficiency</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prod_data = [];
                        foreach($attendance as $a) {
                            if (!$a['working_hours']) continue;
                            $parts = explode(':', $a['working_hours']);
                            $mins = ($parts[0] * 60) + ($parts[1] ?? 0);
                            if (!isset($prod_data[$a['acc_no']])) {
                                $prod_data[$a['acc_no']] = ['name' => $a['worker_name'], 'mins' => 0, 'days' => 0];
                            }
                            $prod_data[$a['acc_no']]['mins'] += $mins;
                            $prod_data[$a['acc_no']]['days']++;
                        }

                        foreach($prod_data as $pd):
                            $avg_hrs = $pd['mins'] / ($pd['days'] * 60);
                            $efficiency = round(($avg_hrs / 8) * 100); // 8 hour shift
                            $badge = $efficiency >= 90 ? 'badge-success' : ($efficiency >= 70 ? 'badge-warning' : 'badge-danger');
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($pd['name']) ?></td>
                            <td><?= $pd['days'] ?></td>
                            <td><?= round($pd['mins']/60, 1) ?>h</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px">
                                    <div style="flex:1; height:6px; background:rgba(255,255,255,0.1); border-radius:3px">
                                        <div style="width:<?= min(100, $efficiency) ?>%; height:100%; background:<?= $efficiency >= 90 ? '#10b981' : ($efficiency >= 70 ? '#f59e0b' : '#ef4444') ?>; border-radius:3px"></div>
                                    </div>
                                    <span style="font-size:12px; font-weight:600"><?= $efficiency ?>%</span>
                                </div>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= $efficiency >= 90 ? 'EXCELLENT' : ($efficiency >= 70 ? 'GOOD' : 'LOW') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab == 'compliance'): ?>
        <!-- PF/ESI Validation -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Statutory Compliance Validation (Days Worked)</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Days Worked</th>
                            <th>Required (Min)</th>
                            <th>ESI Status</th>
                            <th>PF Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $workers = db_fetch_all($conn, "SELECT name, acc_number FROM workmen WHERE contractor_id = ? AND status != 'blocked'", 'i', [$c_id]);
                        foreach($workers as $w):
                            $worked = count(array_unique(array_map(function($x) { return $x['attendance_date']; }, array_filter($attendance, function($x) use ($w) { return $x['acc_no'] == $w['acc_number']; }))));
                            $min_req = 15; // Example threshold
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($w['name']) ?></td>
                            <td><?= $worked ?> Days</td>
                            <td><?= $min_req ?> Days</td>
                            <td><span class="badge <?= $worked >= $min_req ? 'badge-success' : 'badge-warning' ?>"><?= $worked >= $min_req ? 'VALIDATED' : 'SHORTFALL' ?></span></td>
                            <td><span class="badge <?= $worked >= $min_req ? 'badge-success' : 'badge-warning' ?>"><?= $worked >= $min_req ? 'VALIDATED' : 'SHORTFALL' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <script>
    function switchTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.location.href = url.toString();
    }
    
    function filterMonth() {
        const m = document.getElementById('monthPicker').value;
        if (m) {
            const url = new URL(window.location.href);
            url.searchParams.set('month', m);
            window.location.href = url.toString();
        }
    }
    
    document.getElementById('monthPicker').addEventListener('change', filterMonth);
    </script>
    <?php
}

renderLayout("Attendance & Compliance", 'renderContent', $role, $name);

