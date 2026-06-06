<!-- Standard Safety Modals (PDF Page 19-24) -->

<!-- 1. Schedule Batch Modal -->
<div id="scheduleModal" class="modal-backdrop hidden">
  <div class="modal-content glass" style="max-width:600px; padding:0; border: none; overflow: hidden;">
    <div class="modal-header" style="background: var(--primary); color: white; padding: 20px 24px;">
      <h3 style="margin:0; font-size:18px;"><i class="fas fa-calendar-alt" style="margin-right:10px;"></i> Assign Training Batch</h3>
      <button class="btn-close" onclick="closeModal('scheduleModal')" style="color: white; opacity: 0.8;">&times;</button>
    </div>
    
    <div class="modal-body" style="padding:28px;">
      <!-- Worker Context Box -->
      <div id="scheduleWorkerInfo" style="margin-bottom: 25px;"></div>

      <form id="scheduleForm">
        <input type="hidden" id="scheduleReqId" name="request_id">
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div class="form-group">
            <label class="form-label required"><i class="fas fa-calendar-day"></i> Training Date</label>
            <input type="date" class="form-control" name="scheduled_date" required min="<?= date('Y-m-d') ?>" style="border-radius: 12px; height: 45px;">
          </div>
          <div class="form-group">
            <label class="form-label required"><i class="fas fa-map-marker-alt"></i> Training Hall / Venue</label>
            <select class="form-control" name="scheduled_venue" required style="border-radius: 12px; height: 45px;">
              <option value="Safety Induction Hall A">Safety Induction Hall A</option>
              <option value="Training Center - Block B">Training Center - Block B</option>
              <option value="Main Conference Hall">Main Conference Hall</option>
              <option value="On-Site Briefing Zone">On-Site Briefing Zone</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div class="form-group">
            <label class="form-label required"><i class="fas fa-hashtag"></i> Batch Number</label>
            <input type="text" class="form-control" name="batch_number" required placeholder="e.g. BATCH-2024-05" style="border-radius: 12px; height: 45px;">
          </div>
          <div class="form-group">
            <label class="form-label required"><i class="fas fa-user-tie"></i> Assigned Instructor</label>
            <input type="text" class="form-control" name="instructor" required placeholder="Name of Instructor" style="border-radius: 12px; height: 45px;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required"><i class="fas fa-clock"></i> Select Session Slot</label>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:8px;">
            <label class="slot-card">
              <input type="radio" name="scheduled_shift" value="morning" checked required>
              <div class="slot-content">
                <i class="fas fa-sun"></i>
                <div>
                  <strong>Morning Batch</strong>
                  <small>09:00 AM - 01:00 PM</small>
                </div>
              </div>
            </label>
            <label class="slot-card">
              <input type="radio" name="scheduled_shift" value="evening">
              <div class="slot-content">
                <i class="fas fa-moon"></i>
                <div>
                  <strong>Evening Batch</strong>
                  <small>02:00 PM - 06:00 PM</small>
                </div>
              </div>
            </label>
          </div>
        </div>

        <div class="form-group" style="margin-top:20px;">
          <label class="form-label"><i class="fas fa-info-circle"></i> Invitation Remarks (Sent to Contractor)</label>
          <textarea class="form-control" name="safety_remarks" rows="3" placeholder="Special instructions for the batch..." style="border-radius: 12px;"></textarea>
        </div>

        <div style="margin-top:30px; display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" class="btn btn-outline" onclick="closeModal('scheduleModal')" style="padding: 12px 24px; border-radius: 12px;">Cancel</button>
          <button type="submit" class="btn btn-primary" id="scheduleSubmitBtn" style="padding: 12px 24px; border-radius: 12px; background: var(--primary);">
            <i class="fas fa-paper-plane"></i> Send Training Invitation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function safetyScheduleAlert(title, message, type = 'info') {
  if (window.Swal && Swal.fire) {
    return Swal.fire(title, message, type);
  }
  alert(message ? `${title}: ${message}` : title);
  return Promise.resolve();
}

document.getElementById('scheduleForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('scheduleSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

  const form = e.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('../../api/safety/schedule_training.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    const text = await res.text();
    let result;
    try {
      result = JSON.parse(text);
    } catch (parseErr) {
      throw new Error(`Server returned HTTP ${res.status}: ${text.slice(0, 220) || 'Invalid response'}`);
    }
    if (!res.ok) {
      throw new Error(result.error || `Server returned HTTP ${res.status}`);
    }
    if (result.success) {
      closeModal('scheduleModal');
      await safetyScheduleAlert('Training Scheduled', result.message || 'Invitation sent to contractor.', 'success');
      location.reload();
    } else {
      await safetyScheduleAlert('Scheduling Failed', result.error || 'Unable to schedule training.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Training Invitation';
    }
  } catch (err) {
    await safetyScheduleAlert('Scheduling Failed', err.message || 'Network error. Try again.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Training Invitation';
  }
});

function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>

<style>
  .slot-card { cursor: pointer; position: relative; }
  .slot-card input { display: none; }
  .slot-content { 
    border: 2px solid #e2e8f0; border-radius: 15px; padding: 15px; display: flex; align-items: center; gap: 12px; transition: 0.3s;
  }
  .slot-content i { font-size: 20px; color: #94a3b8; }
  .slot-content strong { display: block; font-size: 14px; }
  .slot-content small { color: #64748b; font-size: 11px; }
  
  .slot-card input:checked + .slot-content { 
    border-color: var(--primary); background: rgba(37, 99, 235, 0.05);
  }
  .slot-card input:checked + .slot-content i { color: var(--primary); }
  .slot-card:hover .slot-content { border-color: var(--primary); opacity: 0.8; }

  .form-group { margin-bottom: 15px; }
  .form-label { font-weight: 600; font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 6px; }
  .required::after { content: ' *'; color: red; }
</style>
