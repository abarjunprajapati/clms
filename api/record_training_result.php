<?php
/**
 * record_training_result.php
 * Record training results (pass/fail) for each worker
 * Gate pass must ONLY be enabled if result = 'qualified'
 * Returns: { success: true, data: [...] }
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    
    $session_id = $input['session_id'] ?? null;
    $workman_id = $input['workman_id'] ?? null;
    $application_id = $input['application_id'] ?? null;
    $attendance = trim($input['attendance'] ?? 'present');
    $result = trim($input['result'] ?? '');
    $theory_score = intval($input['theory_score'] ?? 0);
    $practical_score = intval($input['practical_score'] ?? 0);
    $certificate_no = trim($input['certificate_no'] ?? '');
    
    // Validation
    if (!$session_id && !$application_id) {
        apiError('session_id or application_id is required', 400);
    }
    if (!$workman_id) {
        apiError('workman_id is required', 400);
    }
    if (!$result) {
        apiError('result is required (qualified/failed/pending)', 400);
    }
    
    // Validate result values
    $valid_results = ['qualified', 'failed', 'pending'];
    if (!in_array(strtolower($result), $valid_results)) {
        apiError('Invalid result. Must be: qualified, failed, or pending', 400);
    }
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // STRICT RULE: Application must be approved to schedule training
    require_once __DIR__ . '/RuleEngine.php';
    $appStatusStmt = $conn->prepare("SELECT overall_status FROM application_workflow WHERE application_id = ?");
    $appStatus = 'unknown';
    if ($appStatusStmt) {
        $appStatusStmt->bind_param('s', $application_id);
        $appStatusStmt->execute();
        $res = $appStatusStmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $appStatus = $row['overall_status'];
        }
        $appStatusStmt->close();
    }
    
    // Fallback: Check annexure2a if workflow table doesn't have it
    if ($appStatus === 'unknown') {
        $appStatusStmt2 = $conn->prepare("SELECT workflow_status FROM annexure2a WHERE application_id = ?");
        if ($appStatusStmt2) {
            $appStatusStmt2->bind_param('s', $application_id);
            $appStatusStmt2->execute();
            $res = $appStatusStmt2->get_result();
            if ($row = $res->fetch_assoc()) {
                $appStatus = $row['workflow_status'];
            }
            $appStatusStmt2->close();
        }
    }
    
    $allowedTrainingStatuses = ['approved', 'workmen_added', 'safety_pending'];
    if (!in_array($appStatus, $allowedTrainingStatuses)) {
        apiError("Training results can only be recorded for workers belonging to an application in " . implode('/', $allowedTrainingStatuses) . ". Current status: $appStatus", 400);
    }
    
    $conn->begin_transaction();
    
    // Get session info
    $sessionInfo = null;
    if ($session_id) {
        $sessionStmt = $conn->prepare("SELECT * FROM training_sessions WHERE id = ?");
        if ($sessionStmt) {
            $sessionStmt->bind_param('s', $session_id);
            $sessionStmt->execute();
            $sessionResult = $sessionStmt->get_result();
            $sessionInfo = $sessionResult->fetch_assoc();
            $sessionStmt->close();
        }
    }
    
    // Check if record exists
    $existingStmt = $conn->prepare("
        SELECT id FROM training_results 
        WHERE workman_id = ? AND (training_session_id = ? OR application_id = ?)
        LIMIT 1
    ");
    
    $existingId = null;
    if ($existingStmt) {
        $existingStmt->bind_param('iss', $workman_id, $session_id ?? '', $application_id ?? '');
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        if ($row = $existingResult->fetch_assoc()) {
            $existingId = $row['id'];
        }
        $existingStmt->close();
    }
    
    // Calculate total score
    $total_score = $theory_score + $practical_score;
    
    // Determine pass/fail (qualified if >= 50 and attendance = present)
    $final_result = strtolower($result);
    if ($final_result === 'qualified' || ($attendance === 'present' && $total_score >= 50)) {
        $final_result = 'qualified';
    } else {
        $final_result = 'failed';
    }
    
    if ($existingId) {
        // Update existing record
        $updateStmt = $conn->prepare("
            UPDATE training_results 
            SET attendance_status = ?,
                result = ?,
                theory_score = ?,
                practical_score = ?,
                total_score = ?,
                certificate_no = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($updateStmt) {
            $updateStmt->bind_param('ssiiissi', $attendance, $final_result, $theory_score, $practical_score, $total_score, $certificate_no, $existingId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("
            INSERT INTO training_results 
            (application_id, workman_id, training_session_id, attendance_status, result, theory_score, practical_score, total_score, certificate_no, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($insertStmt) {
            $insertStmt->bind_param('ssissiiss', $application_id ?? '', $workman_id, $session_id ?? '', $attendance, $final_result, $theory_score, $practical_score, $total_score, $certificate_no);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }
    
    // Update workman's training_status - ONLY set to 'PASS' if result is qualified
    if ($final_result === 'qualified') {
        $workmanStatus = 'PASS';
        $eligibility = 'ELIGIBLE';
        $valid_till = date('Y-m-d', strtotime('+1 year'));
    } else {
        $workmanStatus = 'FAIL';
        $eligibility = 'NOT ELIGIBLE';
        $valid_till = null;
    }
    
    $workmanStmt = $conn->prepare("
        UPDATE workmen 
        SET training_status = ?, 
            eligibility_status = ?,
            training_valid_till = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($workmanStmt) {
        $workmanStmt->bind_param('sssi', $workmanStatus, $eligibility, $valid_till, $workman_id);
        $workmanStmt->execute();
        $workmanStmt->close();
    }
    
    // Update session enrolled_count
    if ($session_id) {
        $countStmt = $conn->prepare("
            UPDATE training_sessions 
            SET enrolled_count = COALESCE(enrolled_count, 0) + 1
            WHERE id = ?
        ");
        if ($countStmt) {
            $countStmt->bind_param('s', $session_id);
            $countStmt->execute();
            $countStmt->close();
        }
    }
    
    // Update application workflow status using WorkflowEngine
    if ($application_id) {
        require_once __DIR__ . '/WorkflowEngine.php';
        WorkflowEngine::performAction($conn, $application_id, 'complete_training', 'safety', $_SESSION['user_id'] ?? 0, 'Training completed');
    }
    
    $conn->commit();
    
    // Return success
    $resultData = [
        'workman_id' => $workman_id,
        'session_id' => $session_id ?? $sessionInfo['id'] ?? null,
        'application_id' => $application_id,
        'attendance' => $attendance,
        'result' => $final_result,
        'theory_score' => $theory_score,
        'practical_score' => $practical_score,
        'total_score' => $total_score,
        'certificate_no' => $certificate_no,
        'training_status' => $workmanStatus,
        'gatepass_eligible' => ($final_result === 'qualified')
    ];
    
    apiSuccess($resultData, 'Training result recorded successfully');
    
} catch (Exception $e) {
    if ($conn && $conn->in_transaction) {
        $conn->rollback();
    }
    apiError($e->getMessage(), 500);
}
?>

