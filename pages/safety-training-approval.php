<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Safety Training – Executing Officer Approval</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-user-shield"></i></div>
    <div>
      <div class="topbar-title">Executing Officer – Safety Training Approval</div>
      <div class="topbar-subtitle">Safety Portal · Executing Officer Role</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-notif"><i class="fas fa-bell" style="font-size:18px"></i><div class="notif-badge">3</div></div>
    <div class="topbar-user">
      <div class="user-avatar">EO</div>
      <div><div style="font-size:13px;font-weight:600">A.K. Mishra</div><div style="font-size:11px;opacity:0.7">Executing Officer</div></div>
    </div>
  </div>
</div>

<div class="layout-wrapper">
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Safety Module</div>
      <a href="safety-training-approval.php" class="sidebar-item active"><i class="fas fa-hard-hat"></i> Training Requests</a>
      <a href="safety-training-result.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Training Results</a>
      <a href="notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> Notifications</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Gate Pass</div>
      <a href="gatepass-6a.php" class="sidebar-item"><i class="fas fa-door-open"></i> Gate Pass 6/A</a>
      <a href="../index.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="page-header">
      <div class="page-title">Safety Training Approval Panel</div>
      <div class="page-subtitle">Review and approve safety training requests submitted by contractors. Send confirmation and schedule training batches.</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-inbox"></i></div>
        <div class="stat-value">6</div><div class="stat-label">Pending Requests</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-value">14</div><div class="stat-label">Approved (This Month)</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-users"></i></div>
        <div class="stat-value">320</div><div class="stat-label">Workmen Trained</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-percentage"></i></div>
        <div class="stat-value">87%</div><div class="stat-label">Pass Rate</div>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-list"></i> Safety Training Requests</div>
        <select class="form-control" style="width:180px">
          <option>All</option>
          <option>Pending Approval</option>
          <option>Approved</option>
          <option>Rejected</option>
        </select>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ref No.</th>
              <th>Contractor</th>
              <th>Training Type</th>
              <th>Participants</th>
              <th>Preferred Date</th>
              <th>Fee Paid</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>STR-2025-0843</strong></td>
              <td>Ravi Constructions</td>
              <td>Basic Safety Induction</td>
              <td>48</td>
              <td>10 Apr 2025</td>
              <td><span class="badge badge-success"><i class="fas fa-check"></i> Paid</span></td>
              <td><span class="badge badge-warning">Pending</span></td>
              <td><button class="btn btn-primary btn-sm" onclick="showApprovalPanel('STR-2025-0843','Ravi Constructions',48)"><i class="fas fa-gavel"></i> Decide</button></td>
            </tr>
            <tr>
              <td><strong>STR-2025-0840</strong></td>
              <td>National Highway Builders</td>
              <td>Height Safety</td>
              <td>30</td>
              <td>08 Apr 2025</td>
              <td><span class="badge badge-success"><i class="fas fa-check"></i> Paid</span></td>
              <td><span class="badge badge-warning">Pending</span></td>
              <td><button class="btn btn-primary btn-sm" onclick="showApprovalPanel('STR-2025-0840','NHB',30)"><i class="fas fa-gavel"></i> Decide</button></td>
            </tr>
            <tr>
              <td><strong>STR-2025-0835</strong></td>
              <td>Pioneer Civil Works</td>
              <td>Fire Safety</td>
              <td>20</td>
              <td>05 Apr 2025</td>
              <td><span class="badge badge-danger"><i class="fas fa-times"></i> Pending</span></td>
              <td><span class="badge badge-orange">Fee Awaited</span></td>
              <td><button class="btn btn-outline btn-sm"><i class="fas fa-clock"></i> Wait</button></td>
            </tr>
            <tr>
              <td><strong>STR-2025-0828</strong></td>
              <td>Apex Road Construction</td>
              <td>Basic Safety Induction</td>
              <td>25</td>
              <td>03 Apr 2025</td>
              <td><span class="badge badge-success"><i class="fas fa-check"></i> Paid</span></td>
              <td><span class="badge badge-success">Approved</span></td>
              <td><span class="text-muted text-sm">Scheduled: 03 Apr</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approval Panel -->
    <div id="approvalPanel" style="display:none">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-gavel"></i> Approval Decision — <span id="stRefNo">STR-2025-0843</span></div>
          <button class="btn btn-outline btn-sm" onclick="document.getElementById('approvalPanel').style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
            <div>
              <div class="info-row"><span class="info-label">Contractor</span><span class="info-value" id="stContractor">Ravi Constructions</span></div>
              <div class="info-row"><span class="info-label">Training Type</span><span class="info-value">Basic Safety Induction</span></div>
              <div class="info-row"><span class="info-label">Participants</span><span class="info-value" id="stParticipants">48</span></div>
              <div class="info-row"><span class="info-label">Preferred Date</span><span class="info-value">10 Apr 2025</span></div>
            </div>
            <div>
              <div class="info-row"><span class="info-label">Fee Status</span><span class="info-value"><span class="badge badge-success">Paid – ₹24,000</span></span></div>
              <div class="info-row"><span class="info-label">Venue Requested</span><span class="info-value">Safety Training Centre, Zone A</span></div>
              <div class="info-row"><span class="info-label">Batch Size</span><span class="info-value">Max 30 per batch</span></div>
              <div class="info-row"><span class="info-label">Batches Required</span><span class="info-value">2</span></div>
            </div>
          </div>

          <div class="section-divider"><span>Schedule Training</span></div>

          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Confirmed Training Date <span class="required">*</span></label>
              <input class="form-control" type="date" value="2025-04-10" />
            </div>
            <div class="form-group">
              <label class="form-label">Time Slot</label>
              <select class="form-control">
                <option>09:00 AM – 01:00 PM</option>
                <option>Full Day</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Assigned Trainer</label>
              <select class="form-control">
                <option>Mr. Sanjay Joshi – Safety Officer</option>
                <option>Ms. Kavita Rao – HSE Lead</option>
                <option>Mr. Prashant Verma – Safety Expert</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Venue Confirmed</label>
              <select class="form-control">
                <option>Safety Training Centre – Zone A</option>
                <option>Mobile Unit – Site</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Batch 1 Count</label>
              <input class="form-control" type="number" value="30" />
            </div>
            <div class="form-group">
              <label class="form-label">Batch 2 Count</label>
              <input class="form-control" type="number" value="18" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Approval Remarks</label>
            <textarea class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn btn-success" onclick="approveST()"><i class="fas fa-check"></i> Approve & Schedule Training</button>
            <button class="btn btn-danger" onclick="rejectST()"><i class="fas fa-times"></i> Reject Request</button>
            <button class="btn btn-outline" onclick="document.getElementById('approvalPanel').style.display='none'">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/navigation.js"></script>
<script>
  function showApprovalPanel(ref, contractor, participants) {
    document.getElementById('stRefNo').textContent = ref;
    document.getElementById('stContractor').textContent = contractor;
    document.getElementById('stParticipants').textContent = participants;
    document.getElementById('approvalPanel').style.display = 'block';
    document.getElementById('approvalPanel').scrollIntoView({ behavior: 'smooth' });
  }
  function approveST() {
    showToast('Training approved & scheduled! Contractor notified via Email, SMS & Push notification.', 'success');
    document.getElementById('approvalPanel').style.display = 'none';
    setTimeout(() => window.location.href = 'safety-training-result.php', 1800);
  }
  function rejectST() {
    showToast('Training request rejected. Contractor notified.', 'danger');
    document.getElementById('approvalPanel').style.display = 'none';
  }
</script>
</body>
</html>

