<?php
require_once __DIR__ . '/../training_venue_master.php';
require_once __DIR__ . '/../training_type_master.php';
$safetyTrainingVenueRows = isset($conn) ? clms_get_training_venue_rows($conn, true) : [];
$safetyTrainingTypeRows = isset($conn) ? clms_get_training_type_rows($conn, true) : [];
$safetyTrainingVenues = array_values(array_map(function($row) {
    return $row['venue_name'];
}, $safetyTrainingVenueRows));
$safetyTrainingTypes = array_values(array_map(function($row) {
    return $row['type_name'];
}, $safetyTrainingTypeRows));
?>

<script>
window.safetyTrainingVenues = <?= json_encode($safetyTrainingVenues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.safetyTrainingTypes = <?= json_encode($safetyTrainingTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function safetyScheduleAlert(title, message, type = 'info') {
  if (window.Swal && Swal.fire) {
    return Swal.fire(title, message, type);
  }
  alert(message ? `${title}: ${message}` : title);
  return Promise.resolve();
}

async function submitSafetySchedule(data) {
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
  if (!res.ok || !result.success) {
    throw new Error(result.error || `Server returned HTTP ${res.status}`);
  }
  return result;
}
</script>

<style>
  .schedule-swal-popup { width: min(680px, calc(100vw - 24px)) !important; }
  .schedule-swal-html { text-align:left; margin:0; }
  .schedule-worker-box { border:1px solid #dbe4ef; background:#f8fafc; border-radius:10px; padding:12px; margin-bottom:14px; display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  .schedule-worker-box small { display:block; color:#64748b; font-size:10px; font-weight:800; text-transform:uppercase; margin-bottom:2px; }
  .schedule-worker-box b { color:#0f172a; font-size:13px; }
  .schedule-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .schedule-form-group { margin-bottom:12px; }
  .schedule-form-group label { display:block; font-size:12px; font-weight:800; color:#475569; margin-bottom:6px; }
  .schedule-form-group label.required::after { content:' *'; color:#dc2626; }
  .schedule-form-group input, .schedule-form-group select, .schedule-form-group textarea { width:100%; box-sizing:border-box; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:13px; color:#0f172a; background:#fff; }
  .schedule-slot-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  .schedule-slot { display:flex; align-items:center; gap:8px; border:1px solid #cbd5e1; border-radius:8px; padding:10px; cursor:pointer; }
  .schedule-slot input { width:auto; }
  @media (max-width:640px) { .schedule-worker-box, .schedule-form-grid, .schedule-slot-grid { grid-template-columns:1fr; } }
</style>
