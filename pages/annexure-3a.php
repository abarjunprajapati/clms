<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contractor Info – Representative & Supervisor Declaration</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-file-contract"></i></div>
    <div>
      <div class="topbar-title">Contractor Info – Representative & Supervisor Declaration</div>
      <div class="topbar-subtitle">Contractor Portal · Submission Form</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="annexure-2a.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-arrow-left"></i> Annexure 2/A</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<div class="stepper-wrapper">
  <div class="stepper">
    <div class="step completed"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-label">Login</div></div>
    <div class="step completed"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-label">Annexure 2/A</div></div>
    <div class="step active"><div class="step-icon">3</div><div class="step-label">Contractor Info</div></div>
    <div class="step"><div class="step-icon">4</div><div class="step-label">Welfare Verify</div></div>
    <div class="step"><div class="step-icon">5</div><div class="step-label">Enrolment</div></div>
    <div class="step"><div class="step-icon">6</div><div class="step-label">Gate Pass</div></div>
  </div>
</div>

<div class="page-container">
  <div class="page-header">
    <div class="page-title">Contractor Info — Representative & Supervisor Declaration</div>
    <div class="page-subtitle">Declare all authorised representatives and supervisors who will manage welfare compliance at the work site.</div>
  </div>

  <!-- Section A: Contractor Reference -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-building"></i> Contractor Reference</div>
    </div>
    <div class="card-body">
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Contractor Name <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" value="Ravi Constructions Pvt. Ltd." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">SAP Code <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" value="CNT-2024-0842" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Contract Number <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" value="PWD/2024/CNT/0187" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Work Location <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" value="Pune–Mumbai Expressway, Km 42" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Annexure 2/A Reference</label>
          <input class="form-control sap-field" value="ANN-2025-0842-2A" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Date of Submission</label>
          <input class="form-control sap-field" value="03-04-2025" disabled />
        </div>
      </div>
    </div>
  </div>

  <!-- Section B: Authorised Representatives Removed, handled in Contractor Registration -->

  <!-- Section C: Supervisors -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-hard-hat"></i> Section C — Site Supervisors</div>
      <button class="btn btn-primary btn-sm" onclick="addSupervisor()"><i class="fas fa-plus"></i> Add Supervisor</button>
    </div>
    <div class="card-body">
      <div id="supervisorList">
        <!-- Supervisor 1 -->
        <div class="sup-entry" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:16px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <strong style="color:var(--success);font-size:13px"><i class="fas fa-hard-hat"></i> Supervisor #1</strong>
            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);padding:3px 8px;font-size:11px"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Full Name <span class="required">*</span></label>
              <input class="form-control" placeholder="Full name" />
            </div>
            <div class="form-group">
              <label class="form-label">Qualification <span class="required">*</span></label>
              <select class="form-control">
                <option>Diploma – Civil</option>
                <option>Diploma – Electrical</option>
                <option>B.E. / B.Tech – Civil</option>
                <option>ITI Certified</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Experience (Years) <span class="required">*</span></label>
              <input class="form-control" type="number" placeholder="e.g., 5" />
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Number <span class="required">*</span></label>
              <input class="form-control" type="tel" placeholder="+91 XXXXX XXXXX" />
            </div>
            <div class="form-group">
              <label class="form-label">Aadhaar Number <span class="required">*</span></label>
              <input class="form-control" placeholder="XXXX XXXX XXXX" />
            </div>
            <div class="form-group">
              <label class="form-label">Area of Supervision</label>
              <input class="form-control" placeholder="e.g., Foundation Work, Km 40-42" />
            </div>
          </div>
        </div>

        <div class="sup-entry" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:16px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <strong style="color:var(--success);font-size:13px"><i class="fas fa-hard-hat"></i> Supervisor #2</strong>
            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);padding:3px 8px;font-size:11px"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Full Name <span class="required">*</span></label>
              <input class="form-control" placeholder="Full name" />
            </div>
            <div class="form-group">
              <label class="form-label">Qualification <span class="required">*</span></label>
              <select class="form-control">
                <option>ITI Certified</option>
                <option>Diploma – Civil</option>
                <option>B.E. / B.Tech – Civil</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Experience (Years) <span class="required">*</span></label>
              <input class="form-control" type="number" placeholder="e.g., 3" />
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Number <span class="required">*</span></label>
              <input class="form-control" type="tel" placeholder="+91 XXXXX XXXXX" />
            </div>
            <div class="form-group">
              <label class="form-label">Aadhaar Number <span class="required">*</span></label>
              <input class="form-control" placeholder="XXXX XXXX XXXX" />
            </div>
            <div class="form-group">
              <label class="form-label">Area of Supervision</label>
              <input class="form-control" placeholder="e.g., Electrical work, site B" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Section D: Welfare Compliance -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-balance-scale"></i> Section D — Welfare Compliance Declaration</div>
    </div>
    <div class="card-body">
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Welfare Officer Name (if any)</label>
          <input class="form-control" placeholder="Full name" />
        </div>
        <div class="form-group">
          <label class="form-label">Welfare Officer Mobile</label>
          <input class="form-control" type="tel" placeholder="+91 XXXXX XXXXX" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Amenities Provided at Work Site <span class="required">*</span></label>
        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px" id="amenitiesCheckboxes">
          <label class="check-item"><input type="checkbox" /> Drinking Water</label>
          <label class="check-item"><input type="checkbox" /> Rest Shed</label>
          <label class="check-item"><input type="checkbox" /> First Aid Box</label>
          <label class="check-item"><input type="checkbox" /> Toilets (Male)</label>
          <label class="check-item"><input type="checkbox" /> Toilets (Female)</label>
          <label class="check-item"><input type="checkbox" /> Canteen</label>
          <label class="check-item"><input type="checkbox" /> Crèche</label>
          <label class="check-item"><input type="checkbox" /> Uniform & PPE</label>
          <label class="check-item"><input type="checkbox" /> ID Cards</label>
        </div>
        <style>.check-item{display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;padding:6px 10px;border:1px solid var(--gray-200);border-radius:6px;}</style>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:40px">
    <a href="annexure-2a.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back: Annexure 2/A</a>
    <button class="btn btn-outline" onclick="saveDraft3a()"><i class="fas fa-save"></i> Save Draft</button>
    <button class="btn btn-primary" onclick="submit3a()"><i class="fas fa-paper-plane"></i> Submit Contractor Info</button>
  </div>
</div>

<div id="modal3a" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-body" style="text-align:center;padding:36px 28px">
      <div style="width:70px;height:70px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <h2 style="font-size:20px;font-weight:700;margin-bottom:8px">Contractor Info Submitted!</h2>
      <p style="font-size:13px;color:var(--gray-500);margin-bottom:24px">Ref: <strong>ANN-2025-0842-3A</strong> · Welfare team notified via Email & SMS.</p>
      <div style="display:flex;gap:10px;justify-content:center">
        <a href="welfare-verification.php" class="btn btn-primary"><i class="fas fa-eye"></i> View Welfare Verification</a>
        <button class="btn btn-outline" onclick="hideModal('modal3a')">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/utils.js"></script>
<script src="../js/navigation.js"></script>
<script>
  let supCount = 2;
  function addSupervisor() {
    supCount++;
    const html = `<div class="sup-entry" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <strong style="color:var(--success);font-size:13px"><i class="fas fa-hard-hat"></i> Supervisor #${supCount}</strong>
        <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);padding:3px 8px;font-size:11px" onclick="this.closest('.sup-entry').remove()"><i class="fas fa-trash"></i></button>
      </div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Full Name <span class="required">*</span></label><input class="form-control" placeholder="Full name" /></div>
        <div class="form-group"><label class="form-label">Qualification</label><select class="form-control"><option>Diploma</option><option>ITI</option><option>B.E.</option></select></div>
        <div class="form-group"><label class="form-label">Mobile <span class="required">*</span></label><input class="form-control" type="tel" placeholder="+91" /></div>
        <div class="form-group"><label class="form-label">Aadhaar <span class="required">*</span></label><input class="form-control" placeholder="XXXX XXXX XXXX" /></div>
      </div>
    </div>`;
    document.getElementById('supervisorList').insertAdjacentHTML('beforeend', html);
  }
  // Utilities
  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
      position:fixed;top:20px;right:20px;background:${type==='success'?'#10b981':'#ef4444'};color:white;
      padding:12px 20px;border-radius:8px;font-weight:600;z-index:9999;transform:translateX(400px);transition:0.3s;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.transform = 'translateX(400px)'; setTimeout(() => toast.remove(), 300); }, 10);
  }

  function showModal(id) { document.getElementById(id).style.display = 'flex'; document.body.style.overflow = 'hidden'; }
  function hideModal(id) { document.getElementById(id).style.display = 'none'; document.body.style.overflow = ''; }

  function saveDraft3a() { showToast('Draft saved locally (API later)', 'info'); }

  async function submit3a() {
    const appId = localStorage.getItem('application_id');
    console.log("📦 Retrieved application_id:", appId);

    if (!appId || appId === "null") {
      alert("❌ Application ID missing. Submit Contractor Registration first.");
      return;
    }

    // Collect sup_data  
    const sups = [];

    document.querySelectorAll('.sup-entry').forEach(entry => {
      const inputs = entry.querySelectorAll('input');
      const selects = entry.querySelectorAll('select');

      const name = inputs[0]?.value || '';
      const qualification = selects[0]?.value || '';
      const experience = inputs[1]?.value || '';
      const mobile = inputs[2]?.value || '';
      const aadhaar = inputs[3]?.value || '';

      if (name) {
        sups.push({
          name,
          qualification,
          experience,
          mobile,
          aadhaar
        });
      }
    });

    // Collect amenities
    const amenities = [];
    document.querySelectorAll('#amenitiesCheckboxes input[type="checkbox"]:checked').forEach(cb => {
      amenities.push(cb.parentNode.textContent.trim());
    });

    // Static fields
    const formData = {
      application_id: appId,
      sup_data: sups,
      amenities: amenities,
      welfare_officer_name: document.querySelector('input[placeholder*="Full name"]')?.value || ''
    };

    if (sups.length === 0) {
      showToast('Add at least 1 supervisor', 'danger');
      return;
    }

    try {
      showToast('Submitting Contractor Info...', 'info');
      const result = await window.apiFetch('save_annexure3a.php', {
        method: 'POST',
        body: JSON.stringify(formData)
      });

      console.log("API RESPONSE:", result);
      if (result.success) {
        document.querySelector('#modal3a h2').textContent = 'Contractor Info Submitted!';
        document.querySelector('#modal3a p').innerHTML = `Ref: <strong>${result.data?.ref_id || result.ref_id}</strong><br>${result.data?.sup_count || result.sup_count || 0} supervisors saved.`;
        showModal('modal3a');
      } else {
        showToast('Submit failed: ' + (result.error || 'Unknown'), 'danger');
      }
    } catch (error) {
      showToast('Network error', 'danger');
      console.error(error);
    }
  }

</script>
</body>
</html>

