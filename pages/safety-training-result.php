<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Safety Training Results</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-chart-bar"></i></div>
    <div>
      <div class="topbar-title">Safety Training Results</div>
      <div class="topbar-subtitle">Contractor Portal · Safety Module</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="safety-training-approval.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<div class="page-container">
  <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between">
    <div>
      <div class="page-title">Safety Training Result Processing</div>
      <div class="page-subtitle">Reference: STR-2025-0843 · Training Date: 10 Apr 2025 · Venue: Zone A Safety Centre</div>
    </div>
    <div>
      <span class="badge badge-success" style="font-size:13px;padding:6px 14px"><i class="fas fa-check-circle"></i> Training Completed</span>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-users"></i></div>
      <div class="stat-value" id="totalParticipants">...</div><div class="stat-label">Total Participants</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <div class="stat-value" id="totalPassed" style="color:var(--success)">...</div><div class="stat-label">Passed (≥70%)</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times-circle"></i></div>
      <div class="stat-value" id="totalFailed" style="color:var(--danger)">...</div><div class="stat-label">Failed (<70%)</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-percentage"></i></div>
      <div class="stat-value" id="passRate">...</div><div class="stat-label">Pass Rate</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-star"></i></div>
      <div class="stat-value" id="avgScore">...</div><div class="stat-label">Average Score</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Result Chart -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Result Distribution</div></div>
      <div class="card-body" style="height:260px">
        <canvas id="resultChart"></canvas>
      </div>
    </div>

    <!-- Score Distribution -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Score Range Distribution</div></div>
      <div class="card-body" style="height:260px">
        <canvas id="scoreChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Results Table -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list"></i> Individual Results</div>
      <div style="display:flex;gap:8px">
        <input class="form-control" style="width:200px" placeholder="Search workman..." />
        <select class="form-control" style="width:150px" onchange="filterResults(this.value)">
          <option value="all">All Results</option>
          <option value="pass">Passed</option>
          <option value="fail">Failed</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table" id="resultsTable">
        <thead>
          <tr>
            <th>S.No.</th>
            <th>Temp ID</th>
            <th>Name</th>
            <th>Trade</th>
            <th>Score (%)</th>
            <th>Result</th>
            <th>Certificate</th>
            <th>Gate Pass Eligible</th>
          </tr>
        </thead>
        <tbody>
        <tbody id="resultsTableBody">
          <tr><td colspan="8" style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading results...</td></tr>
        </tbody>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Failed Workmen – Retraining Option -->
  <div class="card" style="margin-bottom:20px;border-color:var(--danger)">
    <div class="card-header" style="background:#fef2f2">
      <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Failed Workmen – 7 Workmen</div>
      <button class="btn btn-warning btn-sm" onclick="requestRetraining()"><i class="fas fa-redo"></i> Request Retraining</button>
    </div>
    <div class="card-body">
      <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <span>Failed workmen are NOT eligible for gate pass. They must retake the safety training and pass before a gate pass can be issued. Retraining request can be submitted below.</span>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
      <div id="failedWorkmenList" style="display:flex;flex-wrap:wrap;gap:8px">
        <span class="text-muted">Loading failed workmen...</span>
      </div>
      </div>
    </div>
  </div>

  <!-- Next Step -->
  <div class="alert alert-success">
    <i class="fas fa-check-circle" style="font-size:18px"></i>
    <div>
      <strong>41 Workmen Qualified!</strong> Passed workmen are now eligible to apply for a Gate Pass as per Annexure 6/A.
      <div style="margin-top:8px">
        <a href="gatepass-6a.php" class="btn btn-success"><i class="fas fa-door-open"></i> Proceed to Gate Pass Request (Annexure 6/A)</a>
      </div>
    </div>
  </div>
</div>

<script src="../js/navigation.js"></script>
<script>
  let resultsData = [];
  let resultChart, scoreChart;

  async function loadResults() {
    const tbody = document.getElementById('resultsTableBody');
    const sessionId = new URLSearchParams(window.location.search).get('session_id') || 'STR-2025-0843';
    
    try {
      const res = await fetch(`/clms/api/get_training_results.php?session_id=${encodeURIComponent(sessionId)}`);
      const json = await res.json();
      
      if (json.success && json.data.length > 0) {
        resultsData = json.data;
        renderUI(resultsData);
      } else {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:60px;color:var(--gray-400);"><i class="fas fa-inbox" style="font-size:32px;"></i><br>No results found for this session.</td></tr>';
      }
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--danger);">Error loading results.</td></tr>';
    }
  }

  function renderUI(data) {
    const tbody = document.getElementById('resultsTableBody');
    const failedList = document.getElementById('failedWorkmenList');
    
    let passedCount = 0;
    let totalScore = 0;
    let scores = [0,0,0,0,0,0]; // ranges

    tbody.innerHTML = data.map((r, i) => {
      const score = parseInt(r.total) || 0;
      const isPass = r.result.toLowerCase() === 'pass';
      if (isPass) passedCount++;
      totalScore += score;
      
      // score distribution
      if (score < 50) scores[0]++;
      else if (score < 60) scores[1]++;
      else if (score < 70) scores[2]++;
      else if (score < 80) scores[3]++;
      else if (score < 90) scores[4]++;
      else scores[5]++;

      const badgeClass = isPass ? 'badge-success' : 'badge-danger';
      const icon = isPass ? 'check' : 'times';
      const color = isPass ? 'var(--success)' : 'var(--danger)';

      return `<tr class="${isPass ? 'pass-row' : 'fail-row'}">
        <td>${i+1}</td>
        <td>${r.workman_id || '—'}</td>
        <td>${r.name}</td>
        <td>${r.trade}</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="width:60px;height:6px;background:var(--gray-200);border-radius:3px">
              <div style="width:${score}%;height:100%;background:${color};border-radius:3px"></div>
            </div>${score}%
          </div>
        </td>
        <td><span class="badge ${badgeClass}"><i class="fas fa-${icon}"></i> ${r.result.toUpperCase()}</span></td>
        <td>${isPass ? '<button class="btn btn-sm btn-outline"><i class="fas fa-certificate"></i> Download</button>' : '<span class="text-muted">—</span>'}</td>
        <td><span class="badge ${badgeClass}"><i class="fas fa-${icon}"></i> ${isPass ? 'Eligible' : 'Not Eligible'}</span></td>
      </tr>`;
    }).join('');

    // Update Stats
    const total = data.length;
    document.getElementById('totalParticipants').textContent = total;
    document.getElementById('totalPassed').textContent = passedCount;
    document.getElementById('totalFailed').textContent = total - passedCount;
    document.getElementById('passRate').textContent = ((passedCount/total)*100).toFixed(1) + '%';
    document.getElementById('avgScore').textContent = (totalScore/total).toFixed(1) + '%';

    // Update Failed List
    const failedRows = data.filter(r => r.result.toLowerCase() !== 'pass');
    failedList.innerHTML = failedRows.map(r => `<span class="badge badge-danger">${r.workman_id} – ${r.name} (${r.total})</span>`).join('');
    if (failedRows.length === 0) failedList.innerHTML = '<span class="text-success">All workmen passed!</span>';

    updateCharts(passedCount, total - passedCount, scores);
  }

  function updateCharts(passed, failed, scores) {
    // Pie Chart
    if (resultChart) resultChart.destroy();
    resultChart = new Chart(document.getElementById('resultChart'), {
      type: 'doughnut',
      data: {
        labels: [`Passed (${passed})`, `Failed (${failed})`],
        datasets: [{ data: [passed, failed], backgroundColor: ['#10b981', '#ef4444'], borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Bar Chart
    if (scoreChart) scoreChart.destroy();
    scoreChart = new Chart(document.getElementById('scoreChart'), {
      type: 'bar',
      data: {
        labels: ['<50%', '50-59%', '60-69%', '70-79%', '80-89%', '90-100%'],
        datasets: [{
          label: 'Workmen',
          data: scores,
          backgroundColor: ['#ef4444', '#ef4444', '#ef4444', '#10b981', '#10b981', '#10b981'],
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 5 } } }
      }
    });
  }

  function filterResults(val) {
    document.querySelectorAll('.pass-row').forEach(r => r.style.display = (val === 'fail') ? 'none' : '');
    document.querySelectorAll('.fail-row').forEach(r => r.style.display = (val === 'pass') ? 'none' : '');
  }

  function requestRetraining() {
    showToast('Retraining request submitted for failed workmen. Safety team notified.', 'info');
  }

  document.addEventListener('DOMContentLoaded', loadResults);
</script>
</body>
</html>

