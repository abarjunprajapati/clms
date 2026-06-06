<?php
// api/safety/schedule_training.php
// Safety schedules a training request → sends back to contractor for confirmation
ob_start();
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json; charset=utf-8');

function safety_schedule_json($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    error_log('[schedule_training] Uncaught: ' . $e->getMessage());
    safety_schedule_json(['success' => false, 'error' => $e->getMessage()]);
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[schedule_training] Fatal: ' . $error['message']);
        safety_schedule_json(['success' => false, 'error' => 'Fatal server error: ' . $error['message']]);
    }
});

safety_schedule_ensure_schema($conn);

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    safety_schedule_json(['success' => false, 'error' => 'Invalid JSON input']);
}
$req_id        = (int)($data['request_id'] ?? 0);
$scheduled_date  = $data['scheduled_date'] ?? '';
$scheduled_shift = $data['scheduled_shift'] ?? '';
$scheduled_venue = $data['scheduled_venue'] ?? '';
$scheduled_time  = $data['scheduled_time'] ?? '';
$safety_remarks  = $data['safety_remarks'] ?? '';
$batch_number    = $data['batch_number'] ?? '';
$instructor      = $data['instructor'] ?? '';

if (!$req_id || !$scheduled_date || !$scheduled_shift || !$scheduled_venue) {
    $missing = [];
    if (!$req_id) $missing[] = 'request_id';
    if (!$scheduled_date) $missing[] = 'scheduled_date';
    if (!$scheduled_shift) $missing[] = 'scheduled_shift';
    if (!$scheduled_venue) $missing[] = 'scheduled_venue';
    
    file_put_contents(__DIR__ . '/../../logs/training_debug.log', date('Y-m-d H:i:s') . ' - Missing fields: ' . implode(', ', $missing) . ' | Data: ' . json_encode($data) . "\n", FILE_APPEND);
    
    safety_schedule_json(['success' => false, 'error' => 'Required fields missing: ' . implode(', ', $missing)]);
}

$safety_user_id = $_SESSION['user_id'] ?? 0;

$conn->begin_transaction();
try {
    // 1. Update training request
    db_execute($conn,
        "UPDATE training_requests SET
            scheduled_date=?, scheduled_shift=?, scheduled_venue=?, scheduled_time=?,
            safety_remarks=?, batch_number=?, instructor=?, scheduled_by=?, status='scheduled', updated_at=NOW()
        WHERE id=?",
        'sssssssii',
        [$scheduled_date, $scheduled_shift, $scheduled_venue, $scheduled_time, $safety_remarks, $batch_number, $instructor, $safety_user_id, $req_id]
    );

    // 1b. Update workmen table status (PDF Synchronization)
    db_execute($conn, 
        "UPDATE workmen SET safety_training_status = 'TRAINING_SCHEDULED', training_status = 'scheduled' WHERE id = (SELECT workman_id FROM training_requests WHERE id = ?)", 
        'i', [$req_id]
    );

    // 2. Find or Create Session in training_schedule
    // Mapping shift to time if not provided
    $final_time = $scheduled_time ?: ($scheduled_shift === 'morning' ? '09:00:00' : '14:00:00');
    
    // Normalize venue/instructor to prevent duplicates due to whitespace
    $scheduled_venue = trim($scheduled_venue);
    $instructor = trim($instructor);

    $session = db_single($conn, 
        "SELECT id FROM training_schedule
         WHERE session_date = ?
           AND LOWER(TRIM(location)) = LOWER(TRIM(?))
           AND session_time = ?
           AND LOWER(COALESCE(session_status, 'open')) <> 'cancelled'", 
        'sss', [$scheduled_date, $scheduled_venue, $final_time]
    );

    if (!$session) {
        // Create new session - Using mysqli_query to ensure we can get insert_id easily
        $session_id = safety_schedule_insert_row($conn, 'training_schedule', [
            'session_date' => $scheduled_date,
            'session_time' => $final_time,
            'location' => $scheduled_venue,
            'capacity' => 30,
            'trainer_name' => $instructor,
            'batch_number' => $batch_number,
            'training_type' => 'induction',
            'session_status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } else {
        $session_id = $session['id'];
        // Update existing session batch/instructor if changed
        db_execute($conn, "UPDATE training_schedule SET batch_number = ?, trainer_name = ? WHERE id = ?", 'ssi', [$batch_number, $instructor, $session_id]);
    }

    if (!$session_id) {
        throw new Exception("Could not determine session ID.");
    }

    db_execute(
        $conn,
        "UPDATE training_requests SET scheduled_session_id = ? WHERE id = ?",
        'ii',
        [$session_id, $req_id]
    );

    // Worker is not mapped to the batch yet. Mapping happens only after contractor confirmation.
    db_execute(
        $conn,
        "UPDATE training_schedule
         SET enrolled_count = (
             SELECT COUNT(*)
             FROM training_session_workers tsw
             JOIN training_requests tr ON tr.id = tsw.training_request_id
             WHERE tsw.session_id = ? AND tr.status = 'contractor_confirmed'
         )
         WHERE id = ?",
        'ii',
        [$session_id, $session_id]
    );

    // 4. Notify contractor
    $req = db_single($conn, "SELECT tr.*, w.name as worker_name, c.user_id as contractor_user_id FROM training_requests tr JOIN workmen w ON tr.workman_id = w.id LEFT JOIN contractors c ON tr.contractor_id = c.id WHERE tr.id=?", 'i', [$req_id]);
    if ($req && $req['contractor_user_id'] && safety_schedule_table_exists($conn, 'notifications')) {
        $shift_label = $scheduled_shift === 'morning' ? 'Morning (8 AM – 12 PM)' : 'Evening (2 PM – 6 PM)';
        $msg = "Training for {$req['worker_name']} has been scheduled on " . date('d M Y', strtotime($scheduled_date)) . " ({$shift_label}) at {$scheduled_venue}. Please confirm your attendance.";
        try {
            db_execute($conn, "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,'training_scheduled',0)", 'is', [$req['contractor_user_id'], $msg]);
        } catch (Exception $ignored) {
            error_log('[schedule_training] Notification skipped: ' . $ignored->getMessage());
        }
    }

    $conn->commit();
    safety_schedule_json(['success' => true, 'message' => 'Training schedule sent to contractor for confirmation. Worker will appear in the batch after confirmation.']);
} catch (Exception $e) {
    $conn->rollback();
    safety_schedule_json(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

function safety_schedule_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safety_schedule_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function safety_schedule_column_meta($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

function safety_schedule_ensure_column($conn, $table, $column, $definition) {
    if (!safety_schedule_table_exists($conn, $table) || safety_schedule_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        throw new Exception("DB column `$table.$column` missing and auto-create failed: " . mysqli_error($conn));
    }
}

function safety_schedule_ensure_schema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_schedule (
        id INT NOT NULL AUTO_INCREMENT,
        session_date DATE NULL,
        session_time TIME NULL,
        location VARCHAR(255) NULL,
        capacity INT DEFAULT 30,
        enrolled_count INT DEFAULT 0,
        trainer_name VARCHAR(100) NULL,
        batch_number VARCHAR(50) NULL,
        training_type VARCHAR(50) DEFAULT 'induction',
        session_status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_session_workers (
        id INT NOT NULL AUTO_INCREMENT,
        session_id INT NOT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NULL,
        attendance_status VARCHAR(20) DEFAULT 'pending',
        result VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'safety_remarks' => 'TEXT NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_by' => 'INT NULL',
        'scheduled_session_id' => 'INT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        safety_schedule_ensure_column($conn, 'training_requests', $column, $definition);
    }

    foreach ([
        'session_date' => 'DATE NULL',
        'session_time' => 'TIME NULL',
        'location' => 'VARCHAR(255) NULL',
        'capacity' => 'INT DEFAULT 30',
        'enrolled_count' => 'INT DEFAULT 0',
        'trainer_name' => 'VARCHAR(100) NULL',
        'batch_number' => 'VARCHAR(50) NULL',
        'training_type' => "VARCHAR(50) DEFAULT 'induction'",
        'session_status' => "VARCHAR(50) DEFAULT 'open'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        safety_schedule_ensure_column($conn, 'training_schedule', $column, $definition);
    }

    foreach ([
        'session_id' => 'INT NOT NULL',
        'workman_id' => 'INT NOT NULL',
        'training_request_id' => 'INT NULL',
        'attendance_status' => "VARCHAR(20) DEFAULT 'pending'",
        'result' => "VARCHAR(20) DEFAULT 'pending'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        safety_schedule_ensure_column($conn, 'training_session_workers', $column, $definition);
    }

    foreach (['training_schedule', 'training_session_workers'] as $table) {
        $meta = safety_schedule_column_meta($conn, $table, 'id');
        if ($meta && stripos($meta['Extra'] ?? '', 'auto_increment') === false) {
            @mysqli_query($conn, "ALTER TABLE `$table` MODIFY id INT NOT NULL AUTO_INCREMENT");
        }
    }

    if (safety_schedule_table_exists($conn, 'workmen')) {
        safety_schedule_ensure_column($conn, 'workmen', 'safety_training_status', "VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        safety_schedule_ensure_column($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
    }
}

function safety_schedule_filter_row($conn, $table, $row) {
    $safeTable = str_replace('`', '``', $table);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable`");
    $cols = [];
    if ($res) while ($c = mysqli_fetch_assoc($res)) $cols[$c['Field']] = true;
    return array_intersect_key($row, $cols);
}

function safety_schedule_next_id($conn, $table) {
    $safeTable = str_replace('`', '``', $table);
    $res = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 next_id FROM `$safeTable`");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return (int)($row['next_id'] ?? 1);
}

function safety_schedule_insert_row($conn, $table, $row) {
    $row = safety_schedule_filter_row($conn, $table, $row);
    $idMeta = safety_schedule_column_meta($conn, $table, 'id');
    if ($idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') === false && !isset($row['id'])) {
        $row = ['id' => safety_schedule_next_id($conn, $table)] + $row;
    }
    $cols = array_keys($row);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("$table insert prepare failed: " . $conn->error);
    $values = array_values($row);
    $bind = [str_repeat('s', count($values))];
    foreach ($values as $i => $value) {
        $bind[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("$table insert failed: " . $error);
    }
    $id = (int)$stmt->insert_id;
    $stmt->close();
    return $id ?: (int)($row['id'] ?? 0);
}
?>
