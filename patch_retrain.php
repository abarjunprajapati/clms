<?php
$f = 'c:/xampp/htdocs/CLMS1/pages/safety/retraining_requests.php';
$c = file_get_contents($f);

// Change UI text
$c = str_replace('Safety Enrollment Approval', 'Re-Training Inbox', $c);
$c = str_replace('Approve enrollment', 'Approve retraining', $c);
$c = str_replace('Reject enrollment', 'Reject retraining', $c);
$c = str_replace('Approve Enrollment', 'Approve Retraining', $c);

// API endpoint
$c = str_replace('review_enrollment.php', 'review_retraining.php', $c);

// DB fix at top
$c = preg_replace('/require_once __DIR__ \. \'\/\.\.\/\.\.\/include\/layout\.php\';/', "require_once __DIR__ . '/../../include/layout.php';\n@mysqli_query(\$conn, \"UPDATE training_requests SET status='pending_safety' WHERE status='pending' AND source='contractor_re_enroll'\");", $c);

// Batch Select
$c = str_replace(
    '$batchSelect = "tcb.batch_number AS pre_batch_number,',
    '$batchSelect = "tcb.id AS pre_batch_id, tcb.batch_number AS pre_batch_number,',
    $c
);

// Batch Join - Add preferred date
$batchJoinFind = "SELECT tb_asg.id \n                    FROM training_class_batches tb_asg \n                    WHERE tb_asg.batch_number = tr.batch_number \n                      AND LOWER(COALESCE(tb_asg.status, '')) IN ('open', 'scheduled', 'active')\n                    LIMIT 1\n                ),";
$batchJoinReplace = $batchJoinFind . "\n                (\n                    SELECT tb_pref.id \n                    FROM training_class_batches tb_pref \n                    WHERE tb_pref.training_date = tr.preferred_date \n                      AND LOWER(TRIM(tb_pref.language_name)) = LOWER(TRIM(COALESCE(NULLIF(w.training_booking_language, ''), w.safety_language, '')))\n                      AND LOWER(COALESCE(tb_pref.status, '')) IN ('open', 'scheduled', 'active')\n                    LIMIT 1\n                ),";
$c = str_replace($batchJoinFind, $batchJoinReplace, $c);

// Filter logic
$c = preg_replace(
    '/WHERE LOWER\(COALESCE\(tr\.status, \'\'\)\) IN \(\'pending_safety\', \'welfare_pending\'\)\s*AND LOWER\(COALESCE\(w\.execution_training_status, \'\'\)\) = \'approved\'\s*AND LOWER\(COALESCE\(w\.safety_enrollment_status, \'pending\'\)\) <> \'approved\'/',
    "WHERE LOWER(COALESCE(tr.status, '')) IN ('pending_safety', 'welfare_pending')\n              AND tr.source = 'contractor_re_enroll'\n              AND LOWER(COALESCE(w.execution_training_status, '')) = 'approved'",
    $c
);

// Group batches
$c = str_replace(
    "'$bnKey' => [\n                'batch_number' =>",
    "'$bnKey' => [\n                'batch_id' => \$req['pre_batch_id'] ?? null,\n                'batch_number' =>",
    $c
);

// JS fixes
$c = str_replace(
    "onclick=\"reviewBatchGroup(<?= \$workerIdsJson ?>, 'approved', '<?= addslashes(\$group['label']) ?>')\"",
    "onclick=\"reviewBatchGroup(<?= \$workerIdsJson ?>, 'approved', '<?= addslashes(\$group['label']) ?>', <?= \$group['batch_id'] ? (int)\$group['batch_id'] : 'null' ?>)\"",
    $c
);
$c = str_replace(
    "onclick=\"reviewSafetyEnrollment(<?= (int)\$approval['workman_id'] ?>, 'approved')\"",
    "onclick=\"reviewSafetyEnrollment(<?= (int)\$approval['workman_id'] ?>, 'approved', <?= \$group['batch_id'] ? (int)\$group['batch_id'] : 'null' ?>)\"",
    $c
);

// Rewrite script tag
$scriptStart = strpos($c, '<script>');
if ($scriptStart !== false) {
    $c = substr($c, 0, $scriptStart) . '<script>
// ── Single worker approval ──────────────────────────────────────────────────
async function reviewSafetyEnrollment(workmanId, decision, batchId = null) {
  const rejecting = decision === \'rejected\';
  const prompt = await Swal.fire({
    icon: rejecting ? \'warning\' : \'question\',
    title: rejecting ? \'Reject retraining?\' : \'Approve retraining?\',
    text: rejecting
      ? \'The enrollment will return to the contractor for correction and resubmission.\'
      : \'The worker will be released for Safety training scheduling.\',
    input: \'textarea\',
    inputLabel: rejecting ? \'Correction / rejection remarks\' : \'Approval remarks (optional)\',
    inputPlaceholder: rejecting ? \'Clearly mention what the contractor must correct.\' : \'Enter remarks if required\',
    showCancelButton: true,
    confirmButtonText: rejecting ? \'Reject & Return\' : \'Approve Retraining\',
    confirmButtonColor: rejecting ? \'#dc2626\' : \'#16a34a\',
    inputValidator: value => rejecting && !String(value || \'\').trim() ? \'Rejection remarks are required.\' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI([workmanId], decision, String(prompt.value || \'\').trim(), batchId);
}

// ── Approve/Reject entire batch group ───────────────────────────────────────
async function reviewBatchGroup(workmanIds, decision, batchLabel, batchId = null) {
  const rejecting  = decision === \'rejected\';
  const count      = workmanIds.length;
  const prompt = await Swal.fire({
    icon: rejecting ? \'warning\' : \'question\',
    title: rejecting
      ? `Reject all ${count} retraining(s) in "${batchLabel}"?`
      : `Approve all ${count} retraining(s) in "${batchLabel}"?`,
    html: rejecting
      ? `All selected workers in <strong>${batchLabel}</strong> will be returned to their contractors for correction.`
      : `All <strong>${count} workers</strong> in batch <strong>${batchLabel}</strong> will be released for Safety training scheduling.`,
    input: \'textarea\',
    inputLabel: rejecting ? \'Correction / rejection remarks\' : \'Approval remarks (optional)\',
    inputPlaceholder: rejecting ? \'Clearly mention what the contractor must correct.\' : \'Enter remarks if required\',
    showCancelButton: true,
    confirmButtonText: rejecting ? `Reject ${count} Worker(s)` : `Approve ${count} Worker(s)`,
    confirmButtonColor: rejecting ? \'#dc2626\' : \'#16a34a\',
    cancelButtonText: \'Cancel\',
    inputValidator: value => rejecting && !String(value || \'\').trim() ? \'Rejection remarks are required.\' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, String(prompt.value || \'\').trim(), batchId);
}

// ── Multi-select floating panel ──────────────────────────────────────────────
async function reviewSafetyEnrollmentBatch(decision) {
  const selectedBoxes = document.querySelectorAll(\'.worker-checkbox:checked\');
  const workmanIds    = Array.from(selectedBoxes).map(cb => parseInt(cb.value));
  if (workmanIds.length === 0) {
    Swal.fire(\'No Selection\', \'Please select at least one worker.\', \'warning\');
    return;
  }
  const rejecting = decision === \'rejected\';
  const prompt = await Swal.fire({
    icon: rejecting ? \'warning\' : \'question\',
    title: rejecting ? `Reject ${workmanIds.length} retraining(s)?` : `Approve ${workmanIds.length} retraining(s)?`,
    text: rejecting
      ? \'Selected enrollments will return to the contractor for correction and resubmission.\'
      : \'Selected workers will be released for Safety training scheduling.\',
    input: \'textarea\',
    inputLabel: rejecting ? \'Correction / rejection remarks\' : \'Approval remarks (optional)\',
    inputPlaceholder: rejecting ? \'Clearly mention what the contractor must correct.\' : \'Enter remarks if required\',
    showCancelButton: true,
    confirmButtonText: rejecting ? \'Reject & Return Selected\' : \'Approve Selected\',
    confirmButtonColor: rejecting ? \'#dc2626\' : \'#16a34a\',
    inputValidator: value => rejecting && !String(value || \'\').trim() ? \'Rejection remarks are required.\' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, String(prompt.value || \'\').trim());
}

// ── Shared API call ───────────────────────────────────────────────────────────
async function doEnrollmentAPI(workmanIds, decision, remarks, batchId = null) {
  Swal.fire({
    title: \'Processing...\',
    text: \'Please wait while we update selected enrollments.\',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });
  try {
    const response = await fetch(\'../../api/safety/review_retraining.php\', {
      method: \'POST\',
      headers: { \'Content-Type\': \'application/json\', \'X-CSRF-Token\': window.CLMS_CSRF_TOKEN || \'\' },
      body: JSON.stringify({ workman_ids: workmanIds, decision, remarks, batch_id: batchId })
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || \'Unable to update enrollment approvals.\');
    await Swal.fire({
      icon: \'success\',
      title: decision === \'approved\' ? \'Approved!\' : \'Rejected!\',
      text: result.message,
      confirmButtonColor: \'#1e3a8a\'
    });
    location.reload();
  } catch (error) {
    Swal.fire(\'Action Failed\', error.message || \'Server response could not be processed.\', \'error\');
  }
}
</script>
<?php
}

renderLayout(\'Re-Training Inbox\', \'renderContent\', $role, $name);
?>';
}

file_put_contents($f, $c);
echo "Done replacing JS.";
?>
