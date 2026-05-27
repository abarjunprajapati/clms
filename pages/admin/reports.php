<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $reportCategories = [
        'workforce' => [
            'label' => 'Workforce Reports', 'icon' => 'fa-users', 'color' => '#6366f1',
            'reports' => [
                ['Contractor-wise Worker Count', 'contractors', "SELECT c.contractor_name, COUNT(w.id) as workers FROM contractors c LEFT JOIN workmen w ON w.contractor_id = c.id GROUP BY c.id ORDER BY workers DESC"],
                ['Department-wise Deployment', 'workmen', "SELECT department, COUNT(*) as count FROM workmen WHERE department IS NOT NULL GROUP BY department"],
                ['Trade-wise Distribution', 'workmen', "SELECT trade, COUNT(*) as count FROM workmen WHERE trade IS NOT NULL GROUP BY trade"],
                ['Worker Transfer (NOC) Report', 'noc_requests', "SELECT * FROM noc_requests ORDER BY created_at DESC"],
            ]
        ],
        'safety' => [
            'label' => 'Safety & Training Reports', 'icon' => 'fa-hard-hat', 'color' => '#059669',
            'reports' => [
                ['Training Pass/Fail Summary', 'training_results', "SELECT result, COUNT(*) as count FROM training_results GROUP BY result"],
                ['Safety Training Failed Workers', 'training_results', "SELECT tr.*, w.name FROM training_results tr JOIN workmen w ON tr.workman_id = w.id WHERE tr.result = 'fail'"],
                ['Biometric Enrollment Report', 'acc_attendance_map', "SELECT biometric_status, COUNT(*) as count FROM acc_attendance_map GROUP BY biometric_status"],
            ]
        ],
        'pass' => [
            'label' => 'Gate Pass Reports', 'icon' => 'fa-id-card', 'color' => '#0284c7',
            'reports' => [
                ['Expired Pass Report', 'gate_passes', "SELECT g.*, w.name FROM gate_passes g JOIN workmen w ON g.workman_id = w.id WHERE g.valid_to < CURDATE() AND g.status = 'active'"],
                ['Active Pass Summary', 'gate_passes', "SELECT pass_type, status, COUNT(*) as count FROM gate_passes GROUP BY pass_type, status"],
            ]
        ],
        'compliance' => [
            'label' => 'Compliance Reports', 'icon' => 'fa-shield-alt', 'color' => '#f59e0b',
            'reports' => [
                ['Compliance Pending Contractors', 'compliance', "SELECT DISTINCT c.contractor_name FROM contractors c WHERE c.id NOT IN (SELECT contractor_id FROM compliance WHERE month_year = DATE_FORMAT(CURDATE(),'%Y-%m'))"],
                ['Compliance Rejection Summary', 'compliance', "SELECT c.contractor_name, comp.type, comp.month_year, comp.validation_errors FROM compliance comp JOIN contractors c ON comp.contractor_id = c.id WHERE comp.status = 'rejected'"],
            ]
        ],
        'blocking' => [
            'label' => 'Blocking Reports', 'icon' => 'fa-ban', 'color' => '#ef4444',
            'reports' => [
                ['Blocked Workers Report', 'workmen', "SELECT w.name, w.aadhaar, c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.status = 'blocked'"],
                ['Blocked Contractors Report', 'contractors', "SELECT contractor_name, block_reason, blocked_at FROM contractors WHERE is_blocked = 1 OR status = 'blocked'"],
                ['Block/Unblock History', 'contractor_block_history', "SELECT * FROM contractor_block_history ORDER BY created_at DESC LIMIT 100"],
            ]
        ],
        'attendance' => [
            'label' => 'SAP Attendance & Productivity Reports', 'icon' => 'fa-calendar-check', 'color' => '#10b981',
            'reports' => [
                ['Contractor Wise Attendance', 'sap_attendance', "SELECT contractor_name, COUNT(id) as total_present, SUM(TIME_TO_SEC(working_hours))/3600 as total_hours FROM sap_attendance WHERE attendance_date = CURDATE() GROUP BY contractor_name"],
                ['Worker Wise Attendance', 'sap_attendance', "SELECT acc_no, worker_name, attendance_date, in_time, out_time, working_hours, sap_sync_status FROM sap_attendance ORDER BY attendance_date DESC LIMIT 500"],
                ['Monthly Attendance Summary', 'sap_attendance', "SELECT attendance_date, COUNT(id) as total_present FROM sap_attendance WHERE MONTH(attendance_date) = MONTH(CURDATE()) GROUP BY attendance_date ORDER BY attendance_date DESC"],
                ['Productivity (Under-Hours) Report', 'sap_attendance', "SELECT acc_no, worker_name, contractor_name, working_hours FROM sap_attendance WHERE out_time IS NOT NULL AND TIME_TO_SEC(working_hours) < 28800 ORDER BY working_hours ASC LIMIT 100"],
                ['SAP Sync Failures', 'sap_attendance', "SELECT * FROM sap_attendance WHERE sap_sync_status = 'FAILED'"],
            ]
        ],
    ];
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-chart-bar" style="color:#6366f1;margin-right:10px;"></i> Report Center</h2>
        <!-- <p class="page-subtitle">Comprehensive system reports organized by category. Click to view or export.</p> -->
      </div>
      <div><a href="data_export.php" class="btn btn-primary"><i class="fas fa-file-export"></i> Bulk Export</a></div>
    </div>

    <?php foreach($reportCategories as $catKey => $cat): ?>
    <div class="card glass" style="margin-bottom:20px;border-left:4px solid <?= $cat['color'] ?>;">
      <div class="card-header">
        <div class="card-title" style="font-size:15px;"><i class="fas <?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>;"></i> <?= $cat['label'] ?></div>
      </div>
      <div class="card-body" style="padding:10px 20px;">
        <?php foreach($cat['reports'] as $idx => $rpt): ?>
        <div class="report-item" style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(0,0,0,0.04);">
          <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-file-alt" style="color:<?= $cat['color'] ?>;opacity:0.6;"></i>
            <span style="font-weight:500;font-size:13px;"><?= $rpt[0] ?></span>
          </div>
          <button class="btn btn-sm btn-outline" onclick="viewReport('<?= $catKey ?>_<?= $idx ?>', this)"><i class="fas fa-eye"></i> Preview</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Report Preview Modal -->
    <div id="reportModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px;">
      <div class="modal-content glass" style="background:#fff; border-radius:16px; width:100%; max-width:1000px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.2);">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
          <h3 id="reportTitle" style="margin:0; font-size:18px; font-weight:700; color:#1e293b;">Report Preview</h3>
          <button onclick="closeReportModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <div class="modal-body" id="reportData" style="padding:20px; overflow-y:auto; flex-grow:1;">
          <div style="text-align:center; padding:40px; color:#64748b;">
            <i class="fas fa-spinner fa-spin" style="font-size:30px; margin-bottom:10px;"></i>
            <p>Loading report data...</p>
          </div>
        </div>
        <div class="modal-footer" style="padding:15px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px;">
          <button class="btn btn-outline" onclick="closeReportModal()">Close</button>
          <button class="btn btn-primary" id="btnExport" onclick="exportReport()"><i class="fas fa-download"></i> Download CSV</button>
        </div>
      </div>
    </div>

    <script>
    let currentReportData = [];
    let currentReportTitle = '';

    async function viewReport(id, btn) {
      const originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
      
      const modal = document.getElementById('reportModal');
      const body = document.getElementById('reportData');
      const title = document.getElementById('reportTitle');
      
      modal.style.display = 'flex';
      body.innerHTML = '<div style="text-align:center; padding:40px; color:#64748b;"><i class="fas fa-spinner fa-spin" style="font-size:30px; margin-bottom:10px;"></i><p>Fetching data...</p></div>';

      try {
        const res = await fetch('../../api/admin/get_report.php?id=' + id);
        const json = await res.json();
        
        if (json.success) {
          currentReportData = json.data;
          currentReportTitle = json.title;
          title.textContent = json.title;
          renderReportTable(json.data);
        } else {
          body.innerHTML = '<div class="alert alert-danger">' + (json.message || 'Failed to load report') + '</div>';
        }
      } catch (e) {
        body.innerHTML = '<div class="alert alert-danger">Network error: ' + e.message + '</div>';
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
      }
    }

    function renderReportTable(data) {
      const body = document.getElementById('reportData');
      if (!data || data.length === 0) {
        body.innerHTML = '<div style="text-align:center; padding:40px; color:#64748b;"><i class="fas fa-info-circle" style="font-size:30px; margin-bottom:10px;"></i><p>No data found for this report.</p></div>';
        return;
      }

      const headers = Object.keys(data[0]);
      let html = '<table class="data-table" style="width:100%; font-size:13px;"><thead><tr>';
      headers.forEach(h => html += `<th style="text-align:left; padding:12px; background:#f8fafc; border-bottom:2px solid #e2e8f0; text-transform:capitalize;">${h.replace(/_/g, ' ')}</th>`);
      html += '</tr></thead><tbody>';
      
      data.forEach(row => {
        html += '<tr>';
        headers.forEach(h => html += `<td style="padding:10px 12px; border-bottom:1px solid #f1f5f9;">${row[h] === null ? '<span style="opacity:0.3;">N/A</span>' : row[h]}</td>`);
        html += '</tr>';
      });
      
      html += '</tbody></table>';
      body.innerHTML = html;
    }

    function closeReportModal() {
      document.getElementById('reportModal').style.display = 'none';
    }

    function exportReport() {
      if (!currentReportData || currentReportData.length === 0) return;
      
      const headers = Object.keys(currentReportData[0]);
      let csv = headers.join(',') + '\n';
      
      currentReportData.forEach(row => {
        csv += headers.map(h => {
          let val = row[h] === null ? '' : String(row[h]);
          val = val.replace(/"/g, '""');
          return `"${val}"`;
        }).join(',') + '\n';
      });
      
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.setAttribute('hidden', '');
      a.setAttribute('href', url);
      a.setAttribute('download', currentReportTitle.replace(/\s+/g, '_') + '.csv');
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }
    
    // Close on escape
    window.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeReportModal();
    });
    </script>
    <?php
}

renderLayout("Report Center", 'renderContent', $_SESSION['role'], $_SESSION['name']);

