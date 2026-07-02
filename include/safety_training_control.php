<?php

require_once __DIR__ . '/training_venue_master.php';
require_once __DIR__ . '/training_type_master.php';
require_once __DIR__ . '/training_flow.php';

function clms_safety_table_exists($conn, $table) {
    $safe = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_safety_column_exists($conn, $table, $column) {
    if (!clms_safety_table_exists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_safety_ensure_column($conn, $table, $column, $definition) {
    if (!clms_safety_table_exists($conn, $table) || clms_safety_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function clms_safety_schedule_email_value($value, $width) {
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    if ($value === '') $value = '-';
    if (strlen($value) > $width) {
        $value = substr($value, 0, max(0, $width - 3)) . '...';
    }
    return str_pad($value, $width);
}

function clms_safety_schedule_email_line(array $columns) {
    $parts = array();
    foreach ($columns as $column) {
        $parts[] = clms_safety_schedule_email_value($column[0], (int)$column[1]);
    }
    return implode(' | ', $parts);
}

function clms_safety_send_batch_schedule_emails($conn, array $batch, array $workers, $finalTime) {
    if (empty($workers)) {
        return array('attempted' => 0, 'sent' => 0, 'failed' => 0);
    }

    if (!function_exists('sendEmailNotification')) {
        $helperPath = dirname(__DIR__) . '/api/helpers.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
    if (!function_exists('sendEmailNotification')) {
        error_log('[SAFETY_SCHEDULE_EMAIL] sendEmailNotification helper unavailable');
        return array('attempted' => 0, 'sent' => 0, 'failed' => 0);
    }

    $byContractor = array();
    foreach ($workers as $worker) {
        $contractorId = (int)($worker['contractor_id'] ?? 0);
        if ($contractorId <= 0) continue;
        if (!isset($byContractor[$contractorId])) $byContractor[$contractorId] = array();
        $byContractor[$contractorId][] = $worker;
    }

    $summary = array('attempted' => 0, 'sent' => 0, 'failed' => 0);
    $trainingDate = !empty($batch['training_date']) ? date('d M Y', strtotime($batch['training_date'])) : '-';
    $timeText = substr((string)$finalTime, 0, 5);
    $sessionText = strtoupper((string)($batch['session_name'] ?? ''));
    $shiftText = $sessionText === 'AN' ? 'AN / Evening' : 'FN / Morning';

    foreach ($byContractor as $contractorId => $contractorWorkers) {
        $contractor = db_single(
            $conn,
            "SELECT c.id, c.contractor_name, c.vendor_name, c.vendor_code,
                    c.email AS contractor_email, c.email_address,
                    u.email AS user_email, u.name AS user_name,
                    svm.email_address AS sap_email_address,
                    svm.vendor_name AS sap_vendor_name
             FROM contractors c
             LEFT JOIN users u ON u.id = c.user_id OR u.contractor_id = c.vendor_code
             LEFT JOIN sap_vendor_master svm ON TRIM(svm.vendor_code) = TRIM(c.vendor_code)
             WHERE c.id = ? OR TRIM(c.vendor_code) = TRIM(?)
             LIMIT 1",
            'is',
            array($contractorId, (string)$contractorId)
        );
        if (!$contractor && clms_safety_table_exists($conn, 'sap_vendor_master')) {
            $contractor = db_single(
                $conn,
                "SELECT NULL AS id, vendor_name AS contractor_name, vendor_name,
                        vendor_code, NULL AS contractor_email, NULL AS email_address,
                        NULL AS user_email, NULL AS user_name,
                        email_address AS sap_email_address,
                        vendor_name AS sap_vendor_name
                 FROM sap_vendor_master
                 WHERE TRIM(vendor_code) = TRIM(?)
                 LIMIT 1",
                's',
                array((string)$contractorId)
            );
        }
        if (!$contractor) {
            error_log('[SAFETY_SCHEDULE_EMAIL] Contractor lookup failed for contractor_id/vendor_code=' . $contractorId);
            continue;
        }

        $recipientEmail = '';
        foreach (array($contractor['sap_email_address'] ?? '', $contractor['contractor_email'] ?? '', $contractor['email_address'] ?? '', $contractor['user_email'] ?? '') as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $recipientEmail = $candidate;
                break;
            }
        }
        if ($recipientEmail === '') {
            error_log('[SAFETY_SCHEDULE_EMAIL] No valid email for contractor_id/vendor_code=' . $contractorId);
            continue;
        }

        $contractorName = trim((string)(($contractor['contractor_name'] ?? '') ?: (($contractor['vendor_name'] ?? '') ?: (($contractor['sap_vendor_name'] ?? '') ?: (($contractor['user_name'] ?? '') ?: 'Contractor')))));
        $vendorCode = trim((string)($contractor['vendor_code'] ?? ''));
        $h = function($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $rowsHtml = '';
        $serial = 1;
        foreach ($contractorWorkers as $worker) {
            $rowsHtml .= '<tr>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;text-align:center;">' . $h($serial++) . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;white-space:nowrap;">' . $h($worker['temp_id'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;">' . $h($worker['name'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;white-space:nowrap;">' . $h($worker['aadhaar'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;">' . $h($worker['department'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;">' . $h($worker['trade'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;text-align:center;font-weight:700;white-space:nowrap;">' . $h($worker['token_number'] ?? '-') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #d9e2ec;text-align:center;">' . $h($worker['attempt_no'] ?? '-') . '</td>'
                . '</tr>';
        }

        $subject = 'CLMS: Safety Training Batch Scheduled - ' . ($batch['batch_number'] ?? 'Batch');
        $message = '<!doctype html><html><body style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">'
            . '<div style="max-width:980px;margin:0 auto;padding:20px;">'
            . '<div style="background:#ffffff;border:1px solid #e1e7ef;border-radius:8px;padding:20px;">'
            . '<p style="margin:0 0 14px 0;">Dear <strong>' . $h($contractorName) . '</strong>,</p>'
            . '<p style="margin:0 0 18px 0;">Safety training has been finalized/scheduled for the following workmen.</p>'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 18px 0;width:100%;max-width:720px;">'
            . '<tr><td style="padding:5px 0;font-weight:700;width:150px;">Batch No</td><td style="padding:5px 0;">' . $h($batch['batch_number'] ?? '-') . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Training Date</td><td style="padding:5px 0;">' . $h($trainingDate) . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Session</td><td style="padding:5px 0;">' . $h($shiftText) . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Time</td><td style="padding:5px 0;">' . $h($timeText !== '' ? $timeText : '-') . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Venue</td><td style="padding:5px 0;">' . $h(($batch['venue_name'] ?? '') ?: '-') . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Training Type</td><td style="padding:5px 0;">' . $h(($batch['training_type'] ?? '') ?: '-') . '</td></tr>'
            . '<tr><td style="padding:5px 0;font-weight:700;">Trainer</td><td style="padding:5px 0;">' . $h(($batch['instructor_name'] ?? '') ?: '-') . '</td></tr>'
            . ($vendorCode !== '' ? '<tr><td style="padding:5px 0;font-weight:700;">Vendor Code</td><td style="padding:5px 0;">' . $h($vendorCode) . '</td></tr>' : '')
            . '</table>'
            . '<div style="font-weight:700;margin:0 0 8px 0;">Workmen Details</div>'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px;">'
            . '<thead><tr style="background:#eef4ff;color:#0f2f5f;">'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:center;">S.No</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:left;">Temp ID</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:left;">Name</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:left;">Aadhaar</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:left;">Department</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:left;">Trade</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:center;">Token</th>'
            . '<th style="padding:9px 10px;border:1px solid #cbd8ea;text-align:center;">Attempt</th>'
            . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
            . '<p style="margin:18px 0 0 0;">Regards,<br>CLMS</p>'
            . '</div></div></body></html>';
        $summary['attempted']++;
        try {
            $result = sendEmailNotification($recipientEmail, $subject, $message, 'training_batch_scheduled', $contractorName);
            if (!empty($result['success'])) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
                error_log('[SAFETY_SCHEDULE_EMAIL] ' . ($result['message'] ?? 'Email send failed'));
            }
        } catch (Throwable $emailError) {
            $summary['failed']++;
            error_log('[SAFETY_SCHEDULE_EMAIL] ' . $emailError->getMessage());
        }
    }

    return $summary;
}
function clms_safety_ensure_index($conn, $table, $indexName, $sql) {
    if (!clms_safety_table_exists($conn, $table)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeIndex = mysqli_real_escape_string($conn, $indexName);
    $res = mysqli_query($conn, "SHOW INDEX FROM `$safeTable` WHERE Key_name = '$safeIndex'");
    if ($res && mysqli_num_rows($res) > 0) return;
    @mysqli_query($conn, $sql);
}

function clms_safety_master_status($status) {
    return strtolower(trim((string)$status)) === 'active' ? 'active' : 'inactive';
}

function clms_safety_validate_master_dates($fromDate, $toDate, $status = 'active') {
    $fromDate = trim((string)$fromDate) ?: date('Y-m-d');
    $toDate = trim((string)$toDate) ?: '9999-12-31';
    if (strtotime($fromDate) === false || strtotime($toDate) === false) {
        throw new InvalidArgumentException('Please enter valid From Date and To Date.');
    }
    if ($toDate < $fromDate) {
        throw new InvalidArgumentException('To Date cannot be earlier than From Date.');
    }
    if (clms_safety_master_status($status) === 'active' && $toDate < date('Y-m-d')) {
        throw new InvalidArgumentException('Previous/expired date record cannot be set Active.');
    }
    return array($fromDate, $toDate);
}

function clms_safety_set_master_status($conn, $table, $id, $status) {
    $allowedTables = array(
        'training_venue_masters',
        'safety_instructor_masters',
        'training_language_masters',
        'training_fee_masters',
    );
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid Safety Master.');
    }
    $status = clms_safety_master_status($status);
    if ($status === 'active') {
        $row = db_single($conn, "SELECT from_date, to_date FROM `$table` WHERE id = ? LIMIT 1", 'i', array((int)$id));
        if (!$row) throw new RuntimeException('Master record not found.');
        clms_safety_validate_master_dates($row['from_date'] ?? '', $row['to_date'] ?? '', 'active');
    }
    db_execute($conn, "UPDATE `$table` SET status = ?, updated_at = NOW() WHERE id = ?", 'si', array($status, (int)$id));
}

function clms_safety_expire_master_rows($conn) {
    $today = date('Y-m-d');
    foreach (array('training_venue_masters', 'safety_instructor_masters', 'training_language_masters', 'training_fee_masters') as $table) {
        if (clms_safety_table_exists($conn, $table) && clms_safety_column_exists($conn, $table, 'to_date')) {
            @db_execute($conn, "UPDATE `$table` SET status = 'inactive', updated_at = NOW() WHERE LOWER(COALESCE(status, '')) = 'active' AND to_date < ?", 's', array($today));
        }
    }
    if (clms_safety_table_exists($conn, 'master_training_types')) {
        @db_execute($conn, "UPDATE master_training_types SET status = 'inactive' WHERE LOWER(COALESCE(status, '')) = 'active' AND to_date < ?", 's', array($today));
    }
    if (clms_safety_table_exists($conn, 'training_class_batches')) {
        // 1. Expire past-date batches
        @db_execute($conn, "UPDATE training_class_batches SET status = 'inactive', updated_at = NOW() WHERE training_date < ? AND LOWER(COALESCE(status, '')) IN ('draft', 'open', 'scheduled', 'active')", 's', array($today));

        // 2. Expire today's batches where session time has already passed:
        //    - If time_to is set and is less than current time â†’ expired
        //    - If time_to is NULL and session_name is 'FN' (Forenoon) and current time > 13:00 â†’ expired
        //    - If time_to is NULL and session_name is 'AN' (Afternoon/Evening) and current time > 18:00 â†’ expired
        @mysqli_query($conn, "
            UPDATE training_class_batches
            SET status = 'inactive', updated_at = NOW()
            WHERE training_date = CURDATE()
              AND LOWER(COALESCE(status, '')) IN ('draft', 'open', 'scheduled', 'active')
              AND (
                  (time_to IS NOT NULL AND time_to < CURTIME())
                  OR (time_to IS NULL AND UPPER(COALESCE(session_name, 'FN')) = 'FN' AND CURTIME() > '13:00:00')
                  OR (time_to IS NULL AND UPPER(COALESCE(session_name, 'FN')) = 'AN' AND CURTIME() > '18:00:00')
              )
        ");
    }
}


function clms_safety_ensure_master_tables($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS safety_instructor_masters (
        id INT NOT NULL AUTO_INCREMENT,
        instructor_code VARCHAR(30) NULL,
        instructor_name VARCHAR(150) NOT NULL,
        mobile VARCHAR(20) NULL,
        email VARCHAR(120) NULL,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_instructor_name (instructor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_language_masters (
        id INT NOT NULL AUTO_INCREMENT,
        language_name VARCHAR(80) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        sort_order INT DEFAULT 0,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_language (language_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_fee_masters (
        id INT NOT NULL AUTO_INCREMENT,
        fee_source VARCHAR(20) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function clms_safety_ensure_control_schema($conn) {
    clms_ensure_training_venue_masters($conn);
    clms_ensure_training_type_master($conn);
    clms_safety_ensure_master_tables($conn);

    clms_safety_ensure_column($conn, 'training_venue_masters', 'venue_code', 'VARCHAR(30) NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'seats', 'INT NOT NULL DEFAULT 35');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    clms_safety_ensure_column($conn, 'training_venue_masters', 'created_by', 'INT NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'created_at', 'DATETIME NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'updated_at', 'DATETIME NULL');
    @mysqli_query($conn, "UPDATE training_venue_masters SET seats = 35 WHERE seats IS NULL OR seats <= 0");
    @mysqli_query($conn, "UPDATE training_venue_masters SET venue_code = CONCAT('LOC', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(venue_code), '') = ''");
    clms_safety_ensure_index($conn, 'training_venue_masters', 'uq_training_venue_code', "ALTER TABLE training_venue_masters ADD UNIQUE KEY uq_training_venue_code (venue_code)");
    clms_safety_ensure_column($conn, 'workmen', 'safety_language', 'VARCHAR(50) NULL');
    clms_safety_ensure_column($conn, 'workmen', 'training_booking_language', 'VARCHAR(50) NULL');
    clms_safety_ensure_column($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
    clms_safety_ensure_column($conn, 'workmen', 'safety_training_status', "VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
    clms_safety_ensure_column($conn, 'workmen', 'eligibility_status', "VARCHAR(50) DEFAULT 'NOT ELIGIBLE'");
    clms_safety_ensure_column($conn, 'workmen', 'training_valid_till', 'DATE NULL');
    clms_safety_ensure_column($conn, 'workmen', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'mobile', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'email', 'VARCHAR(120) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'instructor_code', 'VARCHAR(30) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'created_by', 'INT NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'created_at', 'DATETIME NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'updated_at', 'DATETIME NULL');
    @mysqli_query($conn, "UPDATE safety_instructor_masters SET instructor_code = CONCAT('INS', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(instructor_code), '') = ''");
    clms_safety_ensure_index($conn, 'safety_instructor_masters', 'uq_instructor_code', "ALTER TABLE safety_instructor_masters ADD UNIQUE KEY uq_instructor_code (instructor_code)");
    foreach (array(
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_session_id' => 'INT NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'scheduled_by' => 'INT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_requests', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS safety_instructor_masters (
        id INT NOT NULL AUTO_INCREMENT,
        instructor_code VARCHAR(30) NULL,
        instructor_name VARCHAR(150) NOT NULL,
        mobile VARCHAR(20) NULL,
        email VARCHAR(120) NULL,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_instructor_name (instructor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'mobile', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'email', 'VARCHAR(120) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    @mysqli_query($conn, "UPDATE safety_instructor_masters SET instructor_code = CONCAT('INS', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(instructor_code), '') = ''");
    clms_safety_ensure_index($conn, 'safety_instructor_masters', 'uq_instructor_code', "ALTER TABLE safety_instructor_masters ADD UNIQUE KEY uq_instructor_code (instructor_code)");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_language_masters (
        id INT NOT NULL AUTO_INCREMENT,
        language_name VARCHAR(80) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        sort_order INT DEFAULT 0,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_language (language_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_language_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_language_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    clms_safety_ensure_column($conn, 'training_language_masters', 'sort_order', 'INT DEFAULT 0');
    clms_safety_ensure_column($conn, 'training_language_masters', 'created_by', 'INT NULL');
    clms_safety_ensure_column($conn, 'training_language_masters', 'created_at', 'DATETIME NULL');
    clms_safety_ensure_column($conn, 'training_language_masters', 'updated_at', 'DATETIME NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_fee_masters (
        id INT NOT NULL AUTO_INCREMENT,
        fee_source VARCHAR(20) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_fee_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_fee_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    clms_safety_ensure_column($conn, 'training_fee_masters', 'created_by', 'INT NULL');
    clms_safety_ensure_column($conn, 'training_fee_masters', 'created_at', 'DATETIME NULL');
    clms_safety_ensure_column($conn, 'training_fee_masters', 'updated_at', 'DATETIME NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_class_batches (
        id INT NOT NULL AUTO_INCREMENT,
        batch_token VARCHAR(6) NOT NULL,
        batch_number VARCHAR(50) NOT NULL,
        training_date DATE NOT NULL,
        venue_id INT NULL,
        venue_name VARCHAR(300) NOT NULL,
        capacity INT NOT NULL DEFAULT 35,
        emergency_seats INT NOT NULL DEFAULT 5,
        language_id INT NULL,
        language_name VARCHAR(80) NOT NULL,
        session_name VARCHAR(20) NOT NULL,
        time_from TIME NULL,
        time_to TIME NULL,
        training_type_id INT NULL,
        training_type VARCHAR(100) NOT NULL,
        instructor_id INT NULL,
        instructor_name VARCHAR(150) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_batch_token (batch_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_class_batches', 'emergency_seats', 'INT NOT NULL DEFAULT 5');
    @mysqli_query($conn, "UPDATE training_class_batches SET emergency_seats = 5 WHERE emergency_seats = 0 AND capacity >= 5");
    @mysqli_query($conn, "
        UPDATE training_class_batches b
        JOIN training_venue_masters v ON v.id = b.venue_id
        SET b.capacity = v.seats + b.emergency_seats
        WHERE b.emergency_seats > 0
          AND b.capacity = v.seats
    ");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_schedule (
        id INT NOT NULL AUTO_INCREMENT,
        session_date DATE NULL,
        session_time TIME NULL,
        location VARCHAR(255) NULL,
        capacity INT DEFAULT 30,
        enrolled_count INT DEFAULT 0,
        trainer_name VARCHAR(100) NULL,
        batch_number VARCHAR(50) NULL,
        training_type VARCHAR(100) DEFAULT 'Safety Induction',
        session_status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'session_date' => 'DATE NULL',
        'session_time' => 'TIME NULL',
        'location' => 'VARCHAR(255) NULL',
        'capacity' => 'INT DEFAULT 30',
        'enrolled_count' => 'INT DEFAULT 0',
        'trainer_name' => 'VARCHAR(100) NULL',
        'batch_number' => 'VARCHAR(50) NULL',
        'training_type' => "VARCHAR(100) DEFAULT 'Safety Induction'",
        'session_status' => "VARCHAR(50) DEFAULT 'open'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_schedule', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_batch_workers (
        id INT NOT NULL AUTO_INCREMENT,
        batch_id INT NOT NULL,
        training_request_id INT NOT NULL,
        workman_id INT NOT NULL,
        ticked TINYINT(1) NOT NULL DEFAULT 1,
        token_number VARCHAR(6) NULL,
        training_token VARCHAR(20) NULL,
        attempt_no INT NOT NULL DEFAULT 1,
        status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
        scheduled_at DATETIME NULL,
        created_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_batch_workman (batch_id, workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_batch_workers', 'token_number', 'VARCHAR(6) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'training_token', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'external_reference', 'VARCHAR(100) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'scheduled_at', 'DATETIME NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_session_workers (
        id INT NOT NULL AUTO_INCREMENT,
        session_id INT NOT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NULL,
        attendance_status VARCHAR(20) DEFAULT 'pending',
        result VARCHAR(20) DEFAULT 'pending',
        valid_till DATE NULL,
        remarks TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'session_id' => 'INT NOT NULL',
        'workman_id' => 'INT NOT NULL',
        'training_request_id' => 'INT NULL',
        'attendance_status' => "VARCHAR(20) DEFAULT 'pending'",
        'result' => "VARCHAR(20) DEFAULT 'pending'",
        'valid_till' => 'DATE NULL',
        'theory_score' => 'INT DEFAULT 0',
        'practical_score' => 'INT DEFAULT 0',
        'total_score' => 'INT DEFAULT 0',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_session_workers', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_session_workers ADD UNIQUE KEY uq_training_request (training_request_id)");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_results (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        application_no VARCHAR(100) NULL,
        result VARCHAR(20) NOT NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'workman_id' => 'INT NOT NULL',
        'application_no' => 'VARCHAR(100) NULL',
        'training_request_id' => 'INT NULL',
        'training_token' => 'VARCHAR(20) NULL',
        'attendance_status' => "VARCHAR(30) DEFAULT 'present'",
        'total_score' => 'INT NULL',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'result' => 'VARCHAR(20) NOT NULL',
        'recorded_by' => 'INT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_results', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS batch_reschedule_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        original_date DATE NOT NULL,
        original_venue_id INT NULL,
        original_venue_name VARCHAR(150) NULL,
        original_session VARCHAR(10) NULL,
        rescheduled_date DATE NOT NULL,
        rescheduled_venue_id INT NULL,
        rescheduled_venue_name VARCHAR(150) NULL,
        rescheduled_session VARCHAR(10) NULL,
        rescheduled_by INT NULL,
        rescheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach (array('Malayalam', 'English', 'Kannada', 'Tamil', 'Hindi') as $idx => $language) {
        db_execute($conn, "INSERT IGNORE INTO training_language_masters (language_name, status, sort_order, created_at, updated_at) VALUES (?, 'active', ?, NOW(), NOW())", 'si', array($language, ($idx + 1) * 10));
    }
    if (db_count($conn, "SELECT COUNT(*) FROM training_fee_masters") === 0) {
        foreach (array(array('PWO', 100.00), array('PO', 0.00), array('SO', 0.00)) as $fee) {
            db_execute($conn, "INSERT IGNORE INTO training_fee_masters (fee_source, amount, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())", 'sd', array($fee[0], $fee[1]));
        }
    }
    clms_safety_expire_master_rows($conn);
}

function clms_safety_generate_batch_token($conn) {
    for ($i = 0; $i < 20; $i++) {
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!db_single($conn, "SELECT id FROM training_class_batches WHERE batch_token = ? LIMIT 1", 's', array($token))) return $token;
    }
    return substr((string)time(), -6);
}

function clms_safety_generate_batch_number($conn, $trainingDate) {
    $stamp = date('Ymd', strtotime($trainingDate));
    $prefix = 'B' . $stamp;
    $row = db_single($conn, "SELECT COUNT(*) + 1 AS next_no FROM training_class_batches WHERE batch_number LIKE ?", 's', array($prefix . '%'));
    return $prefix . str_pad((string)(int)($row['next_no'] ?? 1), 3, '0', STR_PAD_LEFT);
}

function clms_safety_generate_training_token($trainingDate, $counter) {
    global $conn;
    $year = date('Y', strtotime($trainingDate ?: 'now'));
    
    // Generate a random 5-digit token and ensure uniqueness
    for ($i = 0; $i < 100; $i++) {
        $randNo = rand(10000, 99999);
        $token = 'TRN' . $year . $randNo;
        
        if ($conn) {
            $check = db_single($conn, "SELECT COUNT(*) AS c FROM training_batch_workers WHERE training_token = ?", 's', array($token));
            if ((int)($check['c'] ?? 0) === 0) {
                return $token;
            }
        } else {
            return $token;
        }
    }
    return 'TRN' . $year . str_pad((string)max(1, (int)$counter), 5, '0', STR_PAD_LEFT);
}

function clms_safety_generate_unique_token_number($conn) {
    for ($i = 0; $i < 1000; $i++) {
        $randVal = rand(100000, 999999);
        $token = str_pad((string)$randVal, 6, '0', STR_PAD_LEFT);
        if ($conn) {
            $check = db_single($conn, "SELECT COUNT(*) AS c FROM training_batch_workers WHERE token_number = ?", 's', array($token));
            if ((int)($check['c'] ?? 0) === 0) {
                return $token;
            }
        } else {
            return $token;
        }
    }
    return str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function clms_safety_contractors_name_sql($conn, $alias) {
    $parts = array();
    foreach (array('contractor_name', 'vendor_name', 'name') as $col) {
        if (clms_safety_column_exists($conn, 'contractors', $col)) $parts[] = "$alias.`$col`";
    }
    $parts[] = "CONCAT('Contractor #', $alias.id)";
    return 'COALESCE(' . implode(', ', $parts) . ')';
}

function clms_safety_contractors_code_sql($conn, $alias) {
    $parts = array();
    foreach (array('contractor_code', 'vendor_code', 'vendor_id') as $col) {
        if (clms_safety_column_exists($conn, 'contractors', $col)) $parts[] = "$alias.`$col`";
    }
    $parts[] = "CONCAT('C-', $alias.id)";
    return 'COALESCE(' . implode(', ', $parts) . ')';
}

function clms_safety_batch_candidates($conn, $batchId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) return array();

    $contractorName = clms_safety_contractors_name_sql($conn, 'c');
    $contractorCode = clms_safety_contractors_code_sql($conn, 'c');
    $workerCreatedExpr = clms_safety_column_exists($conn, 'workmen', 'created_at') ? 'w.created_at' : 'tr.created_at';
    $attempts30Expr = clms_training_attempts_30_sql('w.id');

    return db_fetch_all($conn, "
        SELECT
            tr.id AS training_request_id,
            tr.requested_date,
            tr.created_at AS request_created_at,
            $workerCreatedExpr AS enrolment_date,
            tr.status AS request_status,
            w.id AS workman_id,
            w.name,
            w.aadhaar,
            w.temp_id,
            w.department,
            w.trade,
            w.work_order_no,
            COALESCE(NULLIF(TRIM(w.training_booking_language), ''), NULLIF(TRIM(w.safety_language), ''), ?) AS safety_language,
            w.contractor_id,
            $contractorCode AS contractor_code,
            $contractorName AS contractor_name,
            COALESCE(tr.contractor_confirmed, 0) AS contractor_confirmed,
            COALESCE(tbw.ticked, 0) AS ticked,
            tbw.token_number,
            tbw.training_token,
            COALESCE(tbw.attempt_no, $attempts30Expr + 1) AS attempt_no,
            tbw.status AS batch_worker_status,
            EXISTS (
                SELECT 1
                FROM training_session_workers tsw
                WHERE tsw.training_request_id = tr.id
                  AND (
                      LOWER(COALESCE(tsw.attendance_status, 'pending')) NOT IN ('pending', '')
                      OR LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pending', '')
                  )
            ) AS training_started,
            CASE
                WHEN COALESCE(tbw.ticked, 0) = 1
                 AND (
                    LOWER(COALESCE(tbw.status, '')) IN ('scheduled', 'completed')
                    OR LOWER(COALESCE(tr.status, '')) IN ('contractor_confirmed', 'passed', 'failed', 'training_passed', 'training_failed')
                    OR COALESCE(tr.contractor_confirmed, 0) = 1
                    OR EXISTS (
                        SELECT 1
                        FROM training_session_workers tsw_lock
                        WHERE tsw_lock.training_request_id = tr.id
                          AND (
                              LOWER(COALESCE(tsw_lock.attendance_status, 'pending')) NOT IN ('pending', '')
                              OR LOWER(COALESCE(tsw_lock.result, 'pending')) NOT IN ('pending', '')
                          )
                    )
                 )
                THEN 1 ELSE 0
            END AS locked_for_schedule
        FROM training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        LEFT JOIN contractors c ON c.id = COALESCE(tr.contractor_id, w.contractor_id)
        LEFT JOIN training_batch_workers tbw ON tbw.batch_id = ? AND tbw.training_request_id = tr.id
        WHERE (
              (
                  tr.id = ?
                  AND LOWER(TRIM(COALESCE(NULLIF(TRIM(w.training_booking_language), ''), TRIM(w.safety_language), ?))) = LOWER(TRIM(?))
                  AND (tbw.id IS NOT NULL OR LOWER(COALESCE(w.safety_enrollment_status, 'pending')) = 'approved')
              )
              OR (
                  (
                      tbw.id IS NOT NULL
                      OR LOWER(COALESCE(tr.status, 'pending')) IN ('pending', 'welfare_pending', 'failed', 'training_failed')
                  )
                  AND (
                      tbw.id IS NOT NULL
                      OR LOWER(TRIM(COALESCE(NULLIF(TRIM(w.training_booking_language), ''), TRIM(w.safety_language), ?))) = LOWER(TRIM(?))
                  )
                  AND (
                      LOWER(COALESCE(w.safety_enrollment_status, 'pending')) = 'approved'
                      OR (tbw.id IS NOT NULL AND LOWER(COALESCE(tbw.status, 'draft')) IN ('scheduled', 'completed', 'finalized'))
                  )
              )
          )
          AND NOT EXISTS (
              SELECT 1
              FROM training_batch_workers used
              WHERE used.workman_id = tr.workman_id
                AND used.batch_id <> ?
                AND used.ticked = 1
                AND LOWER(COALESCE(used.status, 'scheduled')) IN ('draft', 'scheduled', 'completed')
          )
          AND (
              tbw.id IS NOT NULL
              OR tr.id = (
                  SELECT MAX(tr2.id)
                  FROM training_requests tr2
                  WHERE tr2.workman_id = tr.workman_id
              )
          )
        ORDER BY COALESCE(DATE($workerCreatedExpr), tr.requested_date, DATE(tr.created_at)) ASC, tr.id ASC
    ", 'siissssi', array($batch['language_name'], $batchId, (int)$forceRequestId, $batch['language_name'], $batch['language_name'], $batch['language_name'], $batch['language_name'], $batchId));
}

function clms_safety_batch_existing_rows($conn, $batchId) {
    return db_fetch_all(
        $conn,
        "SELECT tbw.training_request_id, tbw.workman_id, COALESCE(tbw.ticked, 0) AS ticked,
                COALESCE(tbw.status, '') AS batch_worker_status,
                COALESCE(tr.status, '') AS request_status,
                COALESCE(tr.contractor_confirmed, 0) AS contractor_confirmed,
                EXISTS (
                    SELECT 1
                    FROM training_session_workers tsw
                    WHERE tsw.training_request_id = tbw.training_request_id
                      AND (
                          LOWER(COALESCE(tsw.attendance_status, 'pending')) NOT IN ('pending', '')
                          OR LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pending', '')
                      )
                ) AS training_started
         FROM training_batch_workers tbw
         LEFT JOIN training_requests tr ON tr.id = tbw.training_request_id
         WHERE tbw.batch_id = ?",
        'i',
        array((int)$batchId)
    );
}

function clms_safety_batch_row_locked($row) {
    if ((int)($row['ticked'] ?? 0) !== 1) return false;
    $requestStatus = strtolower((string)($row['request_status'] ?? ''));
    $batchStatus = strtolower((string)($row['batch_worker_status'] ?? ''));
    return in_array($batchStatus, array('scheduled', 'completed'), true)
        || in_array($requestStatus, array('contractor_confirmed', 'passed', 'failed', 'training_passed', 'training_failed'), true)
        || (int)($row['contractor_confirmed'] ?? 0) === 1
        || (int)($row['training_started'] ?? 0) === 1;
}
function clms_safety_active_rows($rows) {
    $out = array();
    $today = date('Y-m-d');
    foreach ($rows as $row) {
        $fromDate = trim((string)($row['from_date'] ?? ''));
        $toDate = trim((string)($row['to_date'] ?? ''));
        $dateActive = ($fromDate === '' || $fromDate <= $today) && ($toDate === '' || $toDate >= $today);
        if (strtolower((string)($row['status'] ?? '')) === 'active' && $dateActive) $out[] = $row;
    }
    return $out;
}

function clms_safety_set_batch_status($conn, $batchId, $status) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT id, training_date FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array((int)$batchId));
    if (!$batch) throw new RuntimeException('Batch not found.');
    $status = clms_safety_master_status($status);
    if ($status === 'active' && (string)$batch['training_date'] < date('Y-m-d')) {
        throw new RuntimeException('Previous date batch cannot be activated.');
    }
    if ($status !== 'active') {
        $assigned = db_single($conn, "SELECT COUNT(*) AS cnt FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', array((int)$batchId));
        if ($assigned && (int)$assigned['cnt'] > 0) {
            throw new RuntimeException('This batch cannot be inactivated because it is already scheduled for training');
        }
    }
    $storedStatus = $status === 'active' ? 'open' : 'inactive';
    db_execute($conn, "UPDATE training_class_batches SET status = ?, updated_at = NOW() WHERE id = ?", 'si', array($storedStatus, (int)$batchId));
}

function clms_safety_create_batch($conn, $data, $userId) {
    clms_safety_ensure_control_schema($conn);

    $trainingDate = trim((string)($data['training_date'] ?? ''));
    $venueId = (int)($data['venue_id'] ?? 0);
    $languageId = (int)($data['language_id'] ?? 0);
    $sessionName = strtoupper(trim((string)($data['session_name'] ?? 'FN')));
    $timeFrom = trim((string)($data['time_from'] ?? ''));
    $timeTo = trim((string)($data['time_to'] ?? ''));
    $typeId = (int)($data['training_type_id'] ?? 0);
    $instructorId = (int)($data['instructor_id'] ?? 0);
    $emergencySeats = max(0, (int)($data['emergency_seats'] ?? 5));
    $saveMode = (($data['save_mode'] ?? 'schedule') === 'draft') ? 'draft' : 'open';

    if (!$trainingDate || !$venueId || !$languageId || !$typeId) {
        throw new RuntimeException('Training date, location, language and type are required.');
    }
    if ($trainingDate < date('Y-m-d')) {
        throw new RuntimeException('Previous training date is not allowed. Select today or a future date.');
    }

    $today = date('Y-m-d');
    $venue = db_single($conn, "SELECT id, venue_name, COALESCE(seats, 35) seats FROM training_venue_masters WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($venueId, $today, $today));
    $language = db_single($conn, "SELECT id, language_name FROM training_language_masters WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($languageId, $today, $today));
    $type = db_single($conn, "SELECT id, type_name FROM master_training_types WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($typeId, $today, $today));
    $instructor = $instructorId ? db_single($conn, "SELECT id, instructor_name FROM safety_instructor_masters WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($instructorId, $today, $today)) : null;
    if (!$venue || !$language || !$type) throw new RuntimeException('Invalid master selection.');

    $regularSeats = max(1, (int)$venue['seats']);
    $capacity = $regularSeats + $emergencySeats;
    $token = clms_safety_generate_batch_token($conn);
    $batchNumber = clms_safety_generate_batch_number($conn, $trainingDate);

    $conn->begin_transaction();
    try {
        db_execute(
            $conn,
            "INSERT INTO training_class_batches (batch_token, batch_number, training_date, venue_id, venue_name, capacity, emergency_seats, language_id, language_name, session_name, time_from, time_to, training_type_id, training_type, instructor_id, instructor_name, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            'sssisiiissssisissi',
            array($token, $batchNumber, $trainingDate, $venueId, $venue['venue_name'], $capacity, $emergencySeats, $languageId, $language['language_name'], $sessionName, $timeFrom ?: null, $timeTo ?: null, $typeId, $type['type_name'], $instructorId ?: null, $instructor['instructor_name'] ?? '', $saveMode, $userId)
        );
        $batchId = (int)mysqli_insert_id($conn);
        $conn->commit();
        return array('batch_id' => $batchId, 'batch_number' => $batchNumber, 'selected' => 0);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_batch_capacity_summary($conn, array $batch) {
    $storedCapacity = max(1, (int)($batch['capacity'] ?? 0));
    $emergencySeats = max(0, (int)($batch['emergency_seats'] ?? 0));
    $venueSeats = 0;

    if (!empty($batch['venue_id'])) {
        $venue = db_single($conn, "SELECT COALESCE(seats, 0) AS seats FROM training_venue_masters WHERE id = ? LIMIT 1", 'i', array((int)$batch['venue_id']));
        $venueSeats = max(0, (int)($venue['seats'] ?? 0));
    }

    if ($venueSeats > 0 && $storedCapacity <= $venueSeats) {
        $regularSeats = $storedCapacity;
        $totalCapacity = $regularSeats + $emergencySeats;
    } else {
        $totalCapacity = $storedCapacity;
        $regularSeats = max(0, $totalCapacity - $emergencySeats);
    }

    return array(
        'total' => $totalCapacity,
        'regular' => $regularSeats,
        'emergency' => $emergencySeats,
    );
}

function clms_safety_reschedule_batch($conn, $batchId, array $data, $userId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array((int)$batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $lockedSessionCount = db_count(
        $conn,
        "SELECT COUNT(*)
         FROM training_schedule ts
         WHERE ts.batch_number = ?
           AND LOWER(COALESCE(ts.session_status, 'open')) IN ('completed', 'locked')",
        's',
        array((string)$batch['batch_number'])
    );
    if ($lockedSessionCount > 0) {
        throw new RuntimeException('This training session is already completed/locked. It cannot be rescheduled.');
    }
    $trainingDate = trim((string)($data['reschedule_date'] ?? $batch['training_date']));
    $venueId = (int)($data['reschedule_venue_id'] ?? ($batch['venue_id'] ?? 0));
    $sessionName = strtoupper(trim((string)($data['reschedule_session_name'] ?? $batch['session_name'] ?? 'FN')));
    $timeFrom = trim((string)($data['reschedule_time_from'] ?? ''));
    $timeTo = trim((string)($data['reschedule_time_to'] ?? ''));
    $instructorId = (int)($data['reschedule_instructor_id'] ?? ($batch['instructor_id'] ?? 0));

    if (!$trainingDate || !$venueId || !in_array($sessionName, array('FN', 'AN'), true)) {
        throw new RuntimeException('New date, location and session are required.');
    }
    if ($trainingDate < date('Y-m-d')) {
        throw new RuntimeException('Previous training date is not allowed. Select today or a future date.');
    }

    $today = date('Y-m-d');
    $venue = db_single($conn, "SELECT id, venue_name, COALESCE(seats, 35) seats FROM training_venue_masters WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($venueId, $today, $today));
    if (!$venue) throw new RuntimeException('Invalid training location.');
    $instructor = $instructorId ? db_single($conn, "SELECT id, instructor_name FROM safety_instructor_masters WHERE id = ? AND LOWER(status) = 'active' AND (from_date IS NULL OR from_date <= ?) AND (to_date IS NULL OR to_date >= ?) LIMIT 1", 'iss', array($instructorId, $today, $today)) : null;

    $emergencySeats = max(0, (int)($batch['emergency_seats'] ?? 0));
    $newCapacity = max(1, (int)$venue['seats']) + $emergencySeats;
    $assignedCount = db_count($conn, "SELECT COUNT(*) FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', array((int)$batchId));
    if ($assignedCount > $newCapacity) {
        throw new RuntimeException('Assigned workers exceed the new location capacity. Remove workers or select a larger location.');
    }

    $finalTime = $timeFrom !== '' ? $timeFrom : ($sessionName === 'AN' ? '14:00:00' : '09:00:00');
    if (strlen($finalTime) === 5) $finalTime .= ':00';
    $shift = $sessionName === 'AN' ? 'evening' : 'morning';
    $instructorName = $instructor['instructor_name'] ?? '';

    $conn->begin_transaction();
    try {
        db_execute(
            $conn,
            "INSERT INTO batch_reschedule_history 
             (batch_id, original_date, original_venue_id, original_venue_name, original_session,
              rescheduled_date, rescheduled_venue_id, rescheduled_venue_name, rescheduled_session, rescheduled_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'issssssssi',
            array((int)$batchId, $batch['training_date'], $batch['venue_id'], $batch['venue_name'], $batch['session_name'],
                  $trainingDate, $venueId, $venue['venue_name'], $sessionName, (int)$userId)
        );
        db_execute(
            $conn,
            "UPDATE training_class_batches
             SET training_date = ?, venue_id = ?, venue_name = ?, capacity = ?, session_name = ?,
                 time_from = ?, time_to = ?, instructor_id = ?, instructor_name = ?, status = 'scheduled', updated_at = NOW()
             WHERE id = ?",
            'sisisssisi',
            array($trainingDate, $venueId, $venue['venue_name'], $newCapacity, $sessionName, $finalTime, $timeTo ?: null, $instructorId ?: null, $instructorName, (int)$batchId)
        );

        $session = db_single($conn, "SELECT id FROM training_schedule WHERE batch_number = ? LIMIT 1", 's', array($batch['batch_number']));
        if ($session) {
            $sessionId = (int)$session['id'];
            db_execute(
                $conn,
                "UPDATE training_schedule
                 SET session_date = ?, session_time = ?, location = ?, capacity = ?, trainer_name = ?, training_type = ?, session_status = 'open'
                 WHERE id = ?",
                'sssissi',
                array($trainingDate, $finalTime, $venue['venue_name'], $newCapacity, $instructorName, $batch['training_type'], $sessionId)
            );
        } else {
            db_execute(
                $conn,
                "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())",
                'sssisss',
                array($trainingDate, $finalTime, $venue['venue_name'], $newCapacity, $instructorName, $batch['batch_number'], $batch['training_type'])
            );
            $sessionId = (int)mysqli_insert_id($conn);
        }

        db_execute(
            $conn,
            "UPDATE training_session_workers tsw
             JOIN training_batch_workers tbw ON tbw.training_request_id = tsw.training_request_id
             SET tsw.session_id = ?
             WHERE tbw.batch_id = ? AND tbw.ticked = 1",
            'ii',
            array($sessionId, (int)$batchId)
        );

        db_execute(
            $conn,
            "UPDATE training_session_workers tsw
             JOIN training_batch_workers tbw ON tbw.training_request_id = tsw.training_request_id
             SET tsw.attendance_status = 'pending',
                 tsw.result = 'pending',
                 tsw.theory_score = 0,
                 tsw.practical_score = 0,
                 tsw.total_score = 0,
                 tsw.valid_till = NULL,
                 tsw.remarks = NULL
             WHERE tbw.batch_id = ? AND tbw.ticked = 1",
            'i',
            array((int)$batchId)
        );

        db_execute(
            $conn,
            "UPDATE training_requests tr
             JOIN training_batch_workers tbw ON tbw.training_request_id = tr.id
             SET tr.training_type = ?, tr.scheduled_date = ?, tr.scheduled_shift = ?, tr.scheduled_venue = ?,
                 tr.scheduled_time = ?, tr.batch_number = ?, tr.instructor = ?, tr.scheduled_by = ?,
                 tr.scheduled_session_id = ?, tr.updated_at = NOW()
             WHERE tbw.batch_id = ? AND tbw.ticked = 1",
            'sssssssiii',
            array($batch['training_type'], $trainingDate, $shift, $venue['venue_name'], $finalTime, $batch['batch_number'], $instructorName, (int)$userId, $sessionId, (int)$batchId)
        );

        db_execute(
            $conn,
            "UPDATE training_batch_workers SET status = 'scheduled', scheduled_at = NOW() WHERE batch_id = ? AND ticked = 1",
            'i',
            array((int)$batchId)
        );

        $conn->commit();
        return array('batch_number' => $batch['batch_number'], 'training_date' => $trainingDate, 'session_name' => $sessionName);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_add_requests_to_batch($conn, $batchId, array $requestIds, $userId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array((int)$batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $requestIds = array_values(array_unique(array_filter(array_map('intval', $requestIds), function($id) { return $id > 0; })));
    if (!$requestIds) return array('added' => 0, 'batch_number' => $batch['batch_number']);

    $capacityInfo = clms_safety_batch_capacity_summary($conn, $batch);
    $capacity = (int)$capacityInfo['total'];
    $existingSelected = db_count($conn, "SELECT COUNT(*) FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', array((int)$batchId));

    $alreadyInBatchRows = db_fetch_all($conn, "SELECT training_request_id FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', array((int)$batchId));
    $alreadyInBatch = array();
    foreach ($alreadyInBatchRows as $row) $alreadyInBatch[(int)$row['training_request_id']] = true;

    $newCount = 0;
    foreach ($requestIds as $requestId) {
        if (!isset($alreadyInBatch[$requestId])) $newCount++;
    }
    if (($existingSelected + $newCount) > $capacity) {
        throw new RuntimeException('Maximum seat limit exceeded. Only ' . max(0, $capacity - $existingSelected) . ' seat(s) are available in this batch.');
    }

    $attempts30ForBatchExpr = clms_training_attempts_30_sql('tr.workman_id', '?');
    $conn->begin_transaction();
    try {
        $added = 0;
        foreach ($requestIds as $requestId) {
            $row = db_single(
                $conn,
                "SELECT tr.id AS request_id, tr.workman_id, w.name, w.safety_language,
                        COALESCE(tbw_same.ticked, 0) AS already_selected,
                        EXISTS (
                            SELECT 1
                            FROM training_batch_workers used
                            WHERE used.training_request_id = tr.id
                              AND used.batch_id <> ?
                              AND used.ticked = 1
                              AND LOWER(COALESCE(used.status, 'scheduled')) IN ('scheduled', 'completed')
                        ) AS used_elsewhere,
                        $attempts30ForBatchExpr + 1 AS attempt_no
                 FROM training_requests tr
                 JOIN workmen w ON w.id = tr.workman_id
                 LEFT JOIN training_batch_workers tbw_same ON tbw_same.batch_id = ? AND tbw_same.training_request_id = tr.id
                 WHERE tr.id = ?
                 LIMIT 1",
                'isii',
                array((int)$batchId, $batch['training_date'], (int)$batchId, $requestId)
            );
            if (!$row) continue;
            if ((int)$row['used_elsewhere'] === 1) {
                throw new RuntimeException(($row['name'] ?? 'Worker') . ' is already assigned to another active batch.');
            }
            if (strtolower(trim((string)($row['safety_language'] ?: $batch['language_name']))) !== strtolower(trim((string)$batch['language_name']))) {
                throw new RuntimeException(($row['name'] ?? 'Worker') . ' does not match this batch language.');
            }
            $attemptNo = max(1, (int)$row['attempt_no']);
            if ($attemptNo > 3) {
                throw new RuntimeException(($row['name'] ?? 'Worker') . ' has reached maximum 3 attempts. Please apply again after the allowed period.');
            }

            db_execute(
                $conn,
                "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, token_number, training_token, attempt_no, status, created_at)
                 VALUES (?, ?, ?, 1, NULL, NULL, ?, 'draft', NOW())
                 ON DUPLICATE KEY UPDATE training_request_id = VALUES(training_request_id), ticked = 1, token_number = NULL, training_token = NULL, attempt_no = VALUES(attempt_no), status = 'draft'",
                'iiii',
                array((int)$batchId, $requestId, (int)$row['workman_id'], $attemptNo)
            );

            if (empty($row['already_selected'])) {
                $added++;
            }
        }

        $conn->commit();
        return array('added' => $added, 'batch_number' => $batch['batch_number']);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_schedule_batch($conn, $batchId, $selectedRequestIds, $userId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $capacityInfo = clms_safety_batch_capacity_summary($conn, $batch);
    $capacity = (int)$capacityInfo['total'];
    $selectedRequestIds = array_values(array_unique(array_filter(array_map('intval', (array)$selectedRequestIds), function($id) { return $id > 0; })));

    $candidates = clms_safety_batch_candidates($conn, $batchId, (int)$forceRequestId);
    $candidateMap = array();
    foreach ($candidates as $candidate) {
        $candidateMap[(int)$candidate['training_request_id']] = $candidate;
    }

    $finalTime = $batch['time_from'] ?: ($batch['session_name'] === 'AN' ? '14:00:00' : '09:00:00');
    $shift = $batch['session_name'] === 'AN' ? 'evening' : 'morning';

    $scheduledEmailWorkers = array();

    $conn->begin_transaction();
    try {
        $selectedMap = array();
        foreach ($selectedRequestIds as $selectedId) {
            $selectedMap[(int)$selectedId] = true;
        }

        $lockedRequestIds = array();
        $existingRows = clms_safety_batch_existing_rows($conn, $batchId);
        foreach ($existingRows as $existing) {
            $existingRequestId = (int)$existing['training_request_id'];
            if (clms_safety_batch_row_locked($existing)) {
                $lockedRequestIds[$existingRequestId] = true;
            }
        }

        $newSelectedRequestIds = array();
        foreach ($selectedRequestIds as $requestId) {
            $requestId = (int)$requestId;
            if (isset($lockedRequestIds[$requestId])) {
                continue;
            }
            $newSelectedRequestIds[] = $requestId;
        }
        $newSelectedRequestIds = array_values(array_unique($newSelectedRequestIds));

        if (!$newSelectedRequestIds) {
            if (count($lockedRequestIds) > 0) {
                throw new RuntimeException('This batch is already finalized. Select newly added workers before finalizing again.');
            }
            throw new RuntimeException('Please select at least one worker to schedule.');
        }

        if ((count($lockedRequestIds) + count($newSelectedRequestIds)) > $capacity) {
            throw new RuntimeException('Maximum seat limit exceeded. Finalized workers are already part of this batch.');
        }

        $session = db_single($conn, "SELECT id FROM training_schedule WHERE batch_number = ? LIMIT 1", 's', array($batch['batch_number']));
        if (!$session) {
            db_execute(
                $conn,
                "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())",
                'sssisss',
                array($batch['training_date'], $finalTime, $batch['venue_name'], $capacity, $batch['instructor_name'] ?? '', $batch['batch_number'], $batch['training_type'])
            );
            $sessionId = (int)mysqli_insert_id($conn);
        } else {
            $sessionId = (int)$session['id'];
            db_execute(
                $conn,
                "UPDATE training_schedule
                 SET session_date = ?, session_time = ?, location = ?, capacity = ?, trainer_name = ?, training_type = ?, session_status = 'open'
                 WHERE id = ?",
                'sssissi',
                array($batch['training_date'], $finalTime, $batch['venue_name'], $capacity, $batch['instructor_name'] ?? '', $batch['training_type'], $sessionId)
            );
        }

        foreach ($existingRows as $existing) {
            $existingRequestId = (int)$existing['training_request_id'];
            if (isset($lockedRequestIds[$existingRequestId]) || isset($selectedMap[$existingRequestId])) {
                continue;
            }

            db_execute(
                $conn,
                "UPDATE training_batch_workers SET ticked = 0, token_number = NULL, training_token = NULL, status = 'waiting' WHERE batch_id = ? AND training_request_id = ?",
                'ii',
                array($batchId, $existingRequestId)
            );
            db_execute($conn, "DELETE FROM training_session_workers WHERE training_request_id = ?", 'i', array($existingRequestId));
            db_execute(
                $conn,
                "UPDATE training_requests
                 SET status = 'pending_safety',
                     contractor_confirmed = 0,
                     scheduled_session_id = NULL,
                     batch_number = NULL,
                     updated_at = NOW()
                 WHERE id = ? AND status IN ('scheduled', 'pending', 'welfare_pending', 'pending_safety', 'contractor_confirmed')",
                'i',
                array($existingRequestId)
            );
            db_execute(
                $conn,
                "UPDATE workmen SET training_status = 'pending', safety_training_status = 'PENDING_TRAINING' WHERE id = ?",
                'i',
                array((int)$existing['workman_id'])
            );
        }

        $counter = count($lockedRequestIds) + 1;
        foreach ($newSelectedRequestIds as $requestId) {
            if (!isset($candidateMap[$requestId])) {
                throw new RuntimeException('One selected worker is not eligible for this batch language.');
            }
            $candidate = $candidateMap[$requestId];
            $attemptNo = max(1, (int)$candidate['attempt_no']);
            if ($attemptNo > 3) {
                throw new RuntimeException(($candidate['name'] ?? 'Worker') . ' has reached maximum 3 attempts. Please apply for training again.');
            }

            $completedAttempt = db_single(
                $conn,
                "SELECT tr.id, tr.status,
                        EXISTS (
                            SELECT 1
                            FROM training_session_workers tsw_done
                            WHERE tsw_done.training_request_id = tr.id
                              AND LOWER(COALESCE(tsw_done.result, 'pending')) IN ('pass', 'passed', 'fail', 'failed')
                        ) AS has_session_result,
                        EXISTS (
                            SELECT 1
                            FROM training_results trr_done
                            WHERE trr_done.training_request_id = tr.id
                              AND LOWER(COALESCE(trr_done.result, '')) IN ('pass', 'passed', 'fail', 'failed')
                        ) AS has_result_row
                 FROM training_requests tr
                 WHERE tr.id = ?
                 LIMIT 1",
                'i',
                array((int)$requestId)
            );
            $requestAlreadyCompleted = $completedAttempt && (
                in_array(strtolower((string)($completedAttempt['status'] ?? '')), array('passed', 'pass', 'failed', 'fail', 'absent', 'training_passed', 'training_failed'), true)
                || (int)($completedAttempt['has_session_result'] ?? 0) === 1
                || (int)($completedAttempt['has_result_row'] ?? 0) === 1
            );
            if ($requestAlreadyCompleted) {
                $newRemarks = 'Re-training attempt ' . $attemptNo . ' of 3 scheduled after previous training result.';
                $created = db_execute(
                    $conn,
                    "INSERT INTO training_requests
                        (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
                     VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'safety_retest', ?, 'pending_safety', NOW(), NOW())",
                    'iissssi',
                    array((int)$candidate['workman_id'], (int)$candidate['contractor_id'], $batch['training_type'], $batch['training_date'], $shift, $newRemarks, (int)$userId)
                );
                if (!$created) {
                    throw new RuntimeException('Could not create a fresh training request for re-test. Please try again.');
                }
                $requestId = (int)mysqli_insert_id($conn);
            }

            $token = !empty($candidate['token_number']) ? (string)$candidate['token_number'] : clms_safety_generate_unique_token_number($conn);
            $trainingToken = !empty($candidate['training_token']) ? (string)$candidate['training_token'] : clms_safety_generate_training_token($batch['training_date'], $counter);
            db_execute(
                $conn,
                "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, token_number, training_token, attempt_no, status, scheduled_at, created_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, 'scheduled', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE training_request_id = VALUES(training_request_id), ticked = 1, token_number = VALUES(token_number), training_token = VALUES(training_token), attempt_no = VALUES(attempt_no), status = 'scheduled', scheduled_at = COALESCE(training_batch_workers.scheduled_at, NOW())",
                'iiissi',
                array($batchId, $requestId, (int)$candidate['workman_id'], $token, $trainingToken, $attemptNo)
            );
            db_execute(
                $conn,
                "UPDATE training_requests
                 SET training_type = ?, scheduled_date = ?, scheduled_shift = ?, scheduled_venue = ?, scheduled_time = ?,
                     batch_number = ?, instructor = ?, contractor_confirmed = 1, scheduled_by = ?, scheduled_session_id = ?,
                     status = 'contractor_confirmed', updated_at = NOW()
                 WHERE id = ?",
                'sssssssiii',
                array($batch['training_type'], $batch['training_date'], $shift, $batch['venue_name'], $finalTime, $batch['batch_number'], $batch['instructor_name'] ?? '', $userId, $sessionId, $requestId)
            );
            db_execute(
                $conn,
                "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                 VALUES (?, ?, ?, 'pending', 'pending', NOW())
                 ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
                'iii',
                array($sessionId, (int)$candidate['workman_id'], $requestId)
            );
            db_execute(
                $conn,
                "UPDATE workmen SET training_status = 'scheduled', safety_training_status = 'TRAINING_SCHEDULED' WHERE id = ?",
                'i',
                array((int)$candidate['workman_id'])
            );
            $candidate['training_request_id'] = $requestId;
            $candidate['token_number'] = $token;
            $candidate['training_token'] = $trainingToken;
            $candidate['attempt_no'] = $attemptNo;
            $scheduledEmailWorkers[] = $candidate;
            $counter++;
        }

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
            array($sessionId, $sessionId)
        );
        db_execute($conn, "UPDATE training_class_batches SET status = 'scheduled', updated_at = NOW() WHERE id = ?", 'i', array($batchId));
        $conn->commit();
        $emailSummary = clms_safety_send_batch_schedule_emails($conn, $batch, $scheduledEmailWorkers, $finalTime);
        return array('batch_number' => $batch['batch_number'], 'scheduled' => count($newSelectedRequestIds), 'session_id' => $sessionId, 'email' => $emailSummary);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_save_batch_selection($conn, $batchId, $selectedRequestIds, $userId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $capacityInfo = clms_safety_batch_capacity_summary($conn, $batch);
    $capacity = (int)$capacityInfo['total'];
    $selectedRequestIds = array_values(array_unique(array_map('intval', (array)$selectedRequestIds)));
    $selectedRequestIds = array_filter($selectedRequestIds, function($id) { return $id > 0; });
    if (count($selectedRequestIds) > $capacity) {
        throw new RuntimeException('Maximum seat limit exceeded.');
    }

    $candidates = clms_safety_batch_candidates($conn, $batchId, (int)$forceRequestId);
    $candidateMap = array();
    foreach ($candidates as $candidate) {
        $candidateMap[(int)$candidate['training_request_id']] = $candidate;
    }

    $conn->begin_transaction();
    try {
        $selectedMap = array();
        foreach ($selectedRequestIds as $selectedId) {
            $selectedMap[(int)$selectedId] = true;
        }

        $lockedRequestIds = array();
        $existingRows = clms_safety_batch_existing_rows($conn, $batchId);
        foreach ($existingRows as $existing) {
            $existingRequestId = (int)$existing['training_request_id'];
            $isLocked = clms_safety_batch_row_locked($existing);

            if ($isLocked) {
                $lockedRequestIds[$existingRequestId] = true;
                if (!isset($selectedMap[$existingRequestId])) {
                    $selectedMap[$existingRequestId] = true;
                    $selectedRequestIds[] = $existingRequestId;
                }
                continue;
            }

            if (!isset($selectedMap[$existingRequestId])) {
                db_execute(
                    $conn,
                    "UPDATE training_batch_workers SET ticked = 0, status = 'waiting', token_number = NULL, training_token = NULL WHERE batch_id = ? AND training_request_id = ?",
                    'ii',
                    array($batchId, $existingRequestId)
                );
            }
        }

        $selectedRequestIds = array_values(array_unique(array_filter(array_map('intval', $selectedRequestIds), function($id) { return $id > 0; })));
        if (count($selectedRequestIds) > $capacity) {
            throw new RuntimeException('Maximum seat limit exceeded. Finalized workers are already part of this batch.');
        }

        $counter = 1;
        foreach ($selectedRequestIds as $requestId) {
            $requestId = (int)$requestId;
            if (isset($lockedRequestIds[$requestId])) {
                $counter++;
                continue;
            }
            if (!isset($candidateMap[$requestId])) {
                throw new RuntimeException('One selected worker is not eligible for this batch language.');
            }
            $candidate = $candidateMap[$requestId];
            $attemptNo = max(1, (int)$candidate['attempt_no']);
            if ($attemptNo > 3) {
                throw new RuntimeException(($candidate['name'] ?? 'Worker') . ' has reached maximum 3 attempts. Please apply for training again.');
            }
            $token = !empty($candidate['token_number']) ? (string)$candidate['token_number'] : clms_safety_generate_unique_token_number($conn);
            $trainingToken = !empty($candidate['training_token']) ? (string)$candidate['training_token'] : clms_safety_generate_training_token($batch['training_date'], $counter);
            db_execute(
                $conn,
                "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, token_number, training_token, attempt_no, status, created_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, 'draft', NOW())
                 ON DUPLICATE KEY UPDATE training_request_id = VALUES(training_request_id), ticked = 1, token_number = VALUES(token_number), training_token = VALUES(training_token), attempt_no = VALUES(attempt_no), status = 'draft'",
                'iiissi',
                array($batchId, $requestId, (int)$candidate['workman_id'], $token, $trainingToken, $attemptNo)
            );
            $counter++;
        }

        $batchStatus = count($lockedRequestIds) > 0 ? 'scheduled' : 'draft';
        db_execute($conn, "UPDATE training_class_batches SET status = ?, updated_at = NOW() WHERE id = ?", 'si', array($batchStatus, $batchId));
        $conn->commit();
        return array('batch_number' => $batch['batch_number'], 'selected' => count($selectedRequestIds));
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
?>
