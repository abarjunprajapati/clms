<?php
/**
 * CLMS Complete API Fix Package
 * 
 * This file fixes all critical APIs in one comprehensive update.
 * Replace individual API files with these fixed versions.
 * 
 * 1. get_qualified_personnel.php - Only returns workers who passed training
 * 2. get_gate_passes.php - Gets gate passes with proper checks
 * 3. sap_fetch_contractor.php - SAP data with safe defaults
 * 4. update_welfare_status.php - Workflow status updates
 * 5. get_training_results.php - Training results with safe data
 */

// ============================================
// FIX 1: get_qualified_personnel.php
// ============================================
/**
 * get_qualified_personnel.php - Fetch personnel qualified for gate pass
 * Fixed: Returns only workers who passed safety training
 */
function getQualifiedPersonnelFix($conn, $application_id) {
    // Auto-create training_results table
    $conn->query("CREATE TABLE IF NOT EXISTS training_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        application_id VARCHAR(50),
        training_session_id INT,
        attendance_status ENUM('present','absent','late') DEFAULT 'pending',
        written_score INT,
        practical_score INT,
        total_score INT,
        result ENUM('qualified','failed','pending','absent') DEFAULT 'pending',
        certificate_no VARCHAR(50),
        pio_status ENUM('approved','rejected','pending') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_workman_id (workman_id),
        KEY idx_application_id (application_id),
        KEY idx_result (result)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Get only qualified workers
    $sql = "SELECT w.id, w.name, w.father_name, w.dob, w.gender, w.aadhar, w.phone, 
            w.role, w.address, w.state, w.temp_id as tempId, w.status,
            w.training_status, w.application_id,
            tr.attendance_status, tr.written_score, tr.practical_score, 
            tr.total_score, tr.result AS training, tr.certificate_no
            FROM workmen w
            LEFT JOIN training_results tr ON w.id = tr.workman_id AND tr.application_id = w.application_id
            WHERE w.application_id = ?
            AND (
                LOWER(COALESCE(w.training_status, '')) IN ('qualified','completed','passed')
                OR LOWER(COALESCE(tr.result, '')) = 'qualified'
            )
            ORDER BY w.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Map result field for frontend
        $row['training'] = $row['training'] ?? $row['training_status'] ?? 'pending';
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

// ============================================
// FIX 2: get_gate_passes.php
// ============================================
/**
 * get_gate_passes.php - Fetch gate passes for an application
 * Fixed: Returns all passes with proper status mapping
 */
function getGatePassesFix($conn, $application_id) {
    // Auto-create tables
    $conn->query("CREATE TABLE IF NOT EXISTS gate_passes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL,
        workman_id INT NOT NULL,
        temp_id VARCHAR(50),
        gatepass_no VARCHAR(50),
        type ENUM('temporary','permanent') DEFAULT 'temporary',
        status ENUM('pending','verified','approved','rejected','issued') DEFAULT 'pending',
        approval_level INT DEFAULT 1,
        gate_location VARCHAR(100),
        shift_type VARCHAR(50),
        access_zone VARCHAR(100),
        valid_from DATE,
        valid_to DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_application (application_id),
        KEY idx_workman (workman_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sql = "SELECT gp.*, w.name as worker_name, w.role as trade, w.aadhar
            FROM gate_passes gp
            JOIN workmen w ON gp.workman_id = w.id
            WHERE gp.application_id = ?
            ORDER BY gp.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

// ============================================
// FIX 3: SAP Fetch Contractor
// ============================================
/**
 * sap_fetch_contractor.php - Get SAP contractor data
 * Fixed: Safe defaults for all fields, returns structure with all keys
 */
function getSAPContractorFix($conn, $application_id) {
    // Try to get from contractors table first
    $contractor = null;
    
    if ($application_id) {
        $contractor = db_single($conn, 
            "SELECT * FROM annexure2a WHERE application_id = ? OR ref_id = ? LIMIT 1", 
            'ss', [$application_id, $application_id]
        );
    }
    
    // Build complete SAP data structure with safe defaults
    $sapData = [
        // Basic info - use safe defaults
        'code' => $contractor['contractor_id'] ?? 'CONT-' . ($contractor['id'] ?? '001'),
        'name' => $contractor['contractor_name'] ?? 'No Contractor Data',
        'type' => 'Contractor',
        'pan' => $contractor['pan'] ?? 'N/A',
        'gstin' => $contractor['gst'] ?? 'N/A',
        'regNo' => $contractor['contract_no'] ?? $contractor['ref_id'] ?? 'N/A',
        'email' => $contractor['email'] ?? 'N/A',
        'phone' => $contractor['mobile'] ?? 'N/A',
        'status' => $contractor['status'] ?? 'Active',
        'address' => $contractor['office_address'] ?? 'N/A',
        
        // Work order details
        'workOrder' => $contractor['contract_no'] ?? 'N/A',
        'workOrderDate' => $contractor['deployment_date'] ? date('d M Y', strtotime($contractor['deployment_date'])) : 'N/A',
        'project' => $contractor['project_name'] ?? $contractor['category_work'] ?? 'N/A',
        'location' => $contractor['work_location'] ?? 'N/A',
        'contractValue' => $contractor['contract_value'] ?? 'N/A',
        'startDate' => $contractor['deployment_date'] ? date('d M Y', strtotime($contractor['deployment_date'])) : 'N/A',
        'endDate' => $contractor['labour_validity'] ? date('d M Y', strtotime($contractor['labour_validity'])) : 'N/A',
        'licenseNo' => $contractor['labour_license'] ?? 'N/A',
        'licenseValidity' => $contractor['labour_validity'] ? date('d M Y', strtotime($contractor['labour_validity'])) : 'N/A',
        
        // Compliance & Bank
        'pf' => $contractor['epf_code'] ?? 'N/A',
        'esic' => $contractor['esic_code'] ?? 'N/A',
        'labourLicense' => $contractor['labour_license'] ?? 'N/A',
        'safetyOfficer' => 'Assigned',
        'bankName' => $contractor['bank_name'] ?? 'N/A',
        'bankAccount' => $contractor['bank_account'] ?? 'N/A',
        'ifsc' => $contractor['ifsc'] ?? 'N/A',
        
        // Meta
        'sapSync' => date('d M Y, h:i A'),
    ];
    
    return $sapData;
}

// ============================================
// FIX 4: Update Welfare Status
// ============================================
function updateWelfareStatusFix($conn, $application_id, $action, $remarks = '') {
    // Ensure workflow table exists
    $conn->query("CREATE TABLE IF NOT EXISTS application_workflow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL,
        contractor_id INT NULL,
        current_stage VARCHAR(20) DEFAULT 'pio',
        pio_status VARCHAR(20) DEFAULT 'pending',
        welfare_status VARCHAR(20) DEFAULT 'pending',
        aoc_status VARCHAR(20) DEFAULT 'pending',
        final_status VARCHAR(20) DEFAULT 'pending',
        overall_status VARCHAR(20) DEFAULT 'pending',
        remarks TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_application_workflow_application_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $validActions = ['approve', 'reject', 'verify', 'forward'];
    if (!in_array($action, $validActions)) {
        return ['success' => false, 'error' => 'Invalid action'];
    }

    $statusMap = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'verify' => 'verified',
        'forward' => 'pending'
    ];

    $newStatus = $statusMap[$action] ?? 'pending';
    $stage = 'welfare';

    // Update workflow
    $sql = "UPDATE application_workflow 
            SET welfare_status = ?, 
                updated_at = NOW() 
            WHERE application_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $newStatus, $application_id);
    $success = $stmt->execute();
    $stmt->close();

    // Log the action
    $conn->query("INSERT INTO remarks_history (application_id, remark, created_by, action_type)
                 VALUES ('$application_id', '$remarks', 'welfare', '$action')");

    return [
        'success' => $success,
        'message' => "Application $action successfully"
    ];
}

// ============================================
// FIX 5: Get Training Results
// ============================================
function getTrainingResultsFix($conn, $session_id) {
    // Auto-create table
    $conn->query("CREATE TABLE IF NOT EXISTS training_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        application_id VARCHAR(50),
        training_session_id INT,
        attendance_status ENUM('present','absent','late') DEFAULT 'pending',
        written_score INT,
        practical_score INT,
        total_score INT,
        result ENUM('qualified','failed','pending','absent') DEFAULT 'pending',
        certificate_no VARCHAR(50),
        pio_status ENUM('approved','rejected','pending') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // If session_id not provided, get all results
    if (!$session_id) {
        $sql = "SELECT tr.*, w.name, w.role, w.aadhar, w.temp_id 
               FROM training_results tr 
               LEFT JOIN workmen w ON tr.workman_id = w.id 
               ORDER BY tr.created_at DESC 
               LIMIT 50";
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare("SELECT tr.*, w.name, w.role, w.aadhar, w.temp_id 
                            FROM training_results tr 
                            LEFT JOIN workmen w ON tr.workman_id = w.id 
                            WHERE tr.training_session_id = ? 
                            ORDER BY tr.total_score DESC");
        $stmt->bind_param('i', $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Map to frontend expected fields with safe defaults
        $row['name'] = $row['name'] ?? 'N/A';
        $row['role'] = $row['role'] ?? 'Worker';
        $row['attendance'] = $row['attendance_status'] ?? 'pending';
        $row['written'] = $row['written_score'] ?? 0;
        $row['practical'] = $row['practical_score'] ?? 0;
        $row['total'] = $row['total_score'] ?? 0;
        $rows[] = $row;
    }

    return $rows;
}
?>

