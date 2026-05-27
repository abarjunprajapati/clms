<?php
/**
 * Workflow Helper Functions for CLMS
 * Handles stage transitions: PIO → Welfare → AOC → Final Approval → Completed
 */

function workflow_ensure_tables($conn) {
    // Create application_workflow table
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
        UNIQUE KEY uq_application_workflow_application_id (application_id),
        KEY idx_application_workflow_stage (current_stage),
        KEY idx_application_workflow_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create permanent_passes table
    $conn->query("CREATE TABLE IF NOT EXISTS permanent_passes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50),
        worker_name VARCHAR(100),
        trade VARCHAR(100),
        contractor VARCHAR(100),
        pass_number VARCHAR(50),
        issue_date DATE,
        valid_till DATE,
        status VARCHAR(20) DEFAULT 'active',
        KEY idx_permanent_pass_application_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create remarks_history table
    $conn->query("CREATE TABLE IF NOT EXISTS remarks_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL,
        remark TEXT,
        created_by VARCHAR(50),
        action_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_remarks_app_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create documents table
    $conn->query("CREATE TABLE IF NOT EXISTS documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL,
        doc_name VARCHAR(100),
        doc_type VARCHAR(50),
        file_path VARCHAR(255),
        status VARCHAR(20) DEFAULT 'pending',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_docs_app_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create verification_checklist table
    $conn->query("CREATE TABLE IF NOT EXISTS verification_checklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL,
        item_name VARCHAR(255),
        is_done TINYINT(1) DEFAULT 0,
        remarks TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_checklist_app_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create annexure3a table if it doesn't exist (used for supervisors/representatives)
    $conn->query("CREATE TABLE IF NOT EXISTS annexure3a (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT,
        application_id VARCHAR(50),
        supervisor_name VARCHAR(100),
        representative_name VARCHAR(100),
        designation VARCHAR(100),
        qualification VARCHAR(100),
        experience VARCHAR(50),
        mobile VARCHAR(20),
        aadhaar VARCHAR(20),
        amenities TEXT,
        ref_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ann3a_app_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Advance application to next workflow stage
 * 
 * @param mysqli $conn Database connection
 * @param string $application_id The application ID
 * @param string $current_stage Current stage (pio/welfare/aoc/final)
 * @param string $status New status (approved/rejected)
 * @return bool True if successful
 */
function workflow_advance_stage($conn, $application_id, $current_stage, $status) {
    // Define stage progression: pio → welfare → aoc → final → completed
    $stage_progression = [
        'pio' => 'welfare',
        'welfare' => 'aoc',
        'aoc' => 'final',
        'final' => 'completed'
    ];
    
    $next_stage = isset($stage_progression[$current_stage]) ? $stage_progression[$current_stage] : null;
    
    if ($status === 'approved' && $next_stage) {
        // Advance to next stage
        $stmt = $conn->prepare("
            UPDATE application_workflow 
            SET current_stage = ?, 
                " . $current_stage . "_status = 'approved',
                overall_status = 'pending',
                updated_at = NOW()
            WHERE application_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ss', $next_stage, $application_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    } elseif ($status === 'rejected') {
        // Reject at any stage marks overall as rejected
        $stmt = $conn->prepare("
            UPDATE application_workflow 
            SET " . $current_stage . "_status = 'rejected',
                overall_status = 'rejected',
                updated_at = NOW()
            WHERE application_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('s', $application_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    }
    
    return false;
}

/**
 * Get current stage of an application
 */
function workflow_get_stage($conn, $application_id) {
    $stmt = $conn->prepare("SELECT * FROM application_workflow WHERE application_id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }
    return null;
}

/**
 * Ensure application exists in workflow table, create if not
 */
function workflow_ensure_application($conn, $application_id) {
    // Try to insert if not exists
    $stmt = $conn->prepare("
        INSERT INTO application_workflow (application_id, current_stage, pio_status)
        SELECT ?, 'pio', 'pending'
        WHERE NOT EXISTS (SELECT 1 FROM application_workflow WHERE application_id = ?)
    ");
    if ($stmt) {
        $stmt->bind_param('ss', $application_id, $application_id);
        $stmt->execute();
        $stmt->close();
    }
}

function workflow_seed_application($conn, $application_id) {
    workflow_ensure_application($conn, $application_id);
}

function workflow_log($conn, $application_id, $remarks, $user, $action) {
    $stmt = $conn->prepare("INSERT INTO remarks_history (application_id, remark, created_by, action_type) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('ssss', $application_id, $remarks, $user, $action);
        $stmt->execute();
        $stmt->close();
    }
}

function workflow_create_permanent_passes($conn, $application_id) {
    $contractor = 'Contractor';
    $stmt = $conn->prepare("SELECT contractor_name FROM annexure2a WHERE ref_id = ? OR application_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $application_id, $application_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $contractor = isset($row['contractor_name']) ? $row['contractor_name'] : $contractor;
        $stmt->close();
    }

    $workers = [];
    $result = $conn->query("SHOW TABLES LIKE 'training_results'");
    if ($result && $result->num_rows > 0) {
        $res = $conn->query("SELECT name AS worker_name, trade FROM training_results WHERE LOWER(result) = 'qualified' AND LOWER(attendance_status) = 'present' LIMIT 50");
        while ($res && ($row = $res->fetch_assoc())) {
            $workers[] = $row;
        }
    }

    if (count($workers) === 0) {
        $workers[] = ['worker_name' => 'Approved Worker', 'trade' => 'General'];
    }

    $created = 0;
    foreach ($workers as $worker) {
        $exists = $conn->prepare("SELECT id FROM permanent_passes WHERE application_id = ? AND worker_name = ? LIMIT 1");
        $worker_name = isset($worker['worker_name']) ? $worker['worker_name'] : (isset($worker['name']) ? $worker['name'] : 'Approved Worker');
        $trade = isset($worker['trade']) ? $worker['trade'] : 'General';
        $exists->bind_param('ss', $application_id, $worker_name);
        $exists->execute();
        $hasPass = $exists->get_result()->fetch_assoc();
        $exists->close();
        if ($hasPass) continue;

        $pass_number = 'GP-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $stmt = $conn->prepare("
            INSERT INTO permanent_passes (application_id, worker_name, trade, contractor, pass_number, issue_date, valid_till)
            VALUES (?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
        ");
        if ($stmt) {
            $stmt->bind_param('sssss', $application_id, $worker_name, $trade, $contractor, $pass_number);
            if ($stmt->execute()) $created++;
            $stmt->close();
        }
    }

    return $created;
}

