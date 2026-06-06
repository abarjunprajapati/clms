<?php

function clms_payment_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_payment_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_payment_ensure_column($conn, $table, $column, $definition) {
    if (!clms_payment_table_exists($conn, $table) || clms_payment_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function clms_ensure_payment_flow($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_payment_requests (
        id INT NOT NULL AUTO_INCREMENT,
        payment_ref VARCHAR(60) NOT NULL UNIQUE,
        payment_token VARCHAR(80) NOT NULL UNIQUE,
        contractor_id INT NOT NULL,
        application_no VARCHAR(80) NULL,
        worker_count INT NOT NULL DEFAULT 0,
        fee_per_worker DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        gst_percent DECIMAL(5,2) NOT NULL DEFAULT 18.00,
        gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(10) NOT NULL DEFAULT 'INR',
        payment_link VARCHAR(500) NULL,
        link_expires_at DATETIME NULL,
        gateway_provider VARCHAR(50) NULL,
        gateway_order_id VARCHAR(120) NULL,
        gateway_payment_id VARCHAR(120) NULL,
        payer_reference VARCHAR(150) NULL,
        contractor_payment_note TEXT NULL,
        submitted_at DATETIME NULL,
        verified_by INT NULL,
        verified_at DATETIME NULL,
        verification_remarks TEXT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        paid_at DATETIME NULL,
        invoice_no VARCHAR(80) NULL,
        invoice_generated_at DATETIME NULL,
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_training_payment_contractor (contractor_id, status),
        INDEX idx_training_payment_token (payment_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'payment_ref' => 'VARCHAR(60) NOT NULL UNIQUE',
        'payment_token' => 'VARCHAR(80) NOT NULL UNIQUE',
        'contractor_id' => 'INT NOT NULL',
        'application_no' => 'VARCHAR(80) NULL',
        'worker_count' => 'INT NOT NULL DEFAULT 0',
        'fee_per_worker' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
        'subtotal_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
        'gst_percent' => 'DECIMAL(5,2) NOT NULL DEFAULT 18.00',
        'gst_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
        'total_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
        'currency' => "VARCHAR(10) NOT NULL DEFAULT 'INR'",
        'payment_link' => 'VARCHAR(500) NULL',
        'link_expires_at' => 'DATETIME NULL',
        'gateway_provider' => 'VARCHAR(50) NULL',
        'gateway_order_id' => 'VARCHAR(120) NULL',
        'gateway_payment_id' => 'VARCHAR(120) NULL',
        'payer_reference' => 'VARCHAR(150) NULL',
        'contractor_payment_note' => 'TEXT NULL',
        'submitted_at' => 'DATETIME NULL',
        'verified_by' => 'INT NULL',
        'verified_at' => 'DATETIME NULL',
        'verification_remarks' => 'TEXT NULL',
        'status' => "VARCHAR(30) NOT NULL DEFAULT 'pending'",
        'paid_at' => 'DATETIME NULL',
        'invoice_no' => 'VARCHAR(80) NULL',
        'invoice_generated_at' => 'DATETIME NULL',
        'created_by' => 'INT NULL',
        'created_at' => 'DATETIME NULL',
        'updated_at' => 'DATETIME NULL',
    ] as $column => $definition) {
        clms_payment_ensure_column($conn, 'training_payment_requests', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_payment_request_workers (
        id INT NOT NULL AUTO_INCREMENT,
        payment_request_id INT NOT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NULL,
        temp_id VARCHAR(80) NULL,
        created_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_payment_workman (payment_request_id, workman_id),
        INDEX idx_payment_worker (workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'payment_request_id' => 'INT NOT NULL',
        'workman_id' => 'INT NOT NULL',
        'training_request_id' => 'INT NULL',
        'temp_id' => 'VARCHAR(80) NULL',
        'created_at' => 'DATETIME NULL',
    ] as $column => $definition) {
        clms_payment_ensure_column($conn, 'training_payment_request_workers', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
        id INT NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'setting_key' => 'VARCHAR(100) NOT NULL UNIQUE',
        'setting_value' => 'TEXT NULL',
        'setting_group' => "VARCHAR(50) DEFAULT 'general'",
        'description' => 'TEXT NULL',
        'updated_by' => 'INT NULL',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        clms_payment_ensure_column($conn, 'system_settings', $column, $definition);
    }

    $defaults = [
        ['training_fee_per_worker', '500', 'payment', 'Safety induction fee per worker'],
        ['training_payment_gst_percent', '18', 'payment', 'GST percentage for safety induction fee'],
        ['training_payment_link_valid_hours', '72', 'payment', 'Payment link validity in hours'],
        ['payment_gateway_provider', 'demo_qr', 'payment', 'Gateway provider name. demo_qr enables QR demo flow.'],
        ['payment_gateway_key_id', '', 'payment', 'Gateway public/key id.'],
        ['payment_gateway_key_secret', '', 'payment', 'Gateway secret key. Keep server-side only.'],
        ['payment_demo_merchant_name', 'CLMS Safety Training', 'payment', 'Demo QR merchant name'],
        ['payment_demo_upi_id', 'clms-demo@upi', 'payment', 'Demo UPI ID shown with QR'],
        ['payment_demo_qr_path', '', 'payment', 'Uploaded demo QR image path'],
    ];
    foreach ($defaults as $setting) {
        $exists = db_count($conn, "SELECT COUNT(*) FROM system_settings WHERE setting_key = ?", 's', [$setting[0]]);
        if ($exists > 0) continue;
        db_execute(
            $conn,
            "INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_at)
             VALUES (?, ?, ?, ?, NOW())",
            'ssss',
            $setting
        );
    }
}

function clms_payment_setting($conn, $key, $default = '') {
    clms_ensure_payment_flow($conn);
    $row = db_single(
        $conn,
        "SELECT setting_value
         FROM system_settings
         WHERE setting_key = ?
         ORDER BY COALESCE(updated_at, '1970-01-01') DESC, id DESC
         LIMIT 1",
        's',
        [$key]
    );
    $value = $row['setting_value'] ?? $default;
    return $value === '' || $value === null ? $default : $value;
}

function clms_training_fee_per_worker($conn) {
    return max(0, (float)clms_payment_setting($conn, 'training_fee_per_worker', '500'));
}

function clms_training_payment_gst_percent($conn) {
    return max(0, (float)clms_payment_setting($conn, 'training_payment_gst_percent', '18'));
}

function clms_training_payment_link_hours($conn) {
    return max(1, (int)clms_payment_setting($conn, 'training_payment_link_valid_hours', '72'));
}

function clms_payment_gateway_configured($conn) {
    $provider = trim((string)clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr'));
    if ($provider === 'demo_qr') return true;
    return $provider !== ''
        && trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', '')) !== ''
        && trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', '')) !== '';
}

function clms_set_payment_setting($conn, $key, $value, $userId = 0) {
    clms_ensure_payment_flow($conn);
    $exists = db_count($conn, "SELECT COUNT(*) FROM system_settings WHERE setting_key = ?", 's', [$key]);
    if ($exists > 0) {
        db_execute(
            $conn,
            "UPDATE system_settings
             SET setting_value = ?, setting_group = 'payment', description = 'Payment setting', updated_by = ?, updated_at = NOW()
             WHERE setting_key = ?",
            'sis',
            [(string)$value, (int)$userId, $key]
        );
        return;
    }

    db_execute(
        $conn,
        "INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_by, updated_at)
         VALUES (?, ?, 'payment', 'Payment setting', ?, NOW())",
        'ssi',
        [$key, (string)$value, (int)$userId]
    );
}

function clms_demo_payment_details($conn, $request = null) {
    $qrPath = trim((string)clms_payment_setting($conn, 'payment_demo_qr_path', ''));
    $qrUrl = '';
    if ($qrPath !== '') {
        $qrUrl = preg_match('/^https?:\/\//i', $qrPath)
            ? $qrPath
            : clms_payment_base_url() . ltrim($qrPath, '/');
        if (strpos($qrUrl, '?') === false && !preg_match('/^https?:\/\//i', $qrPath)) {
            $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($qrPath, '/'));
            if (is_file($localPath)) {
                $qrUrl .= '?v=' . filemtime($localPath);
            }
        }
    }
    return [
        'merchant_name' => clms_payment_setting($conn, 'payment_demo_merchant_name', 'CLMS Safety Training'),
        'upi_id' => clms_payment_setting($conn, 'payment_demo_upi_id', 'clms-demo@upi'),
        'qr_url' => $qrUrl,
        'amount' => $request ? (float)$request['total_amount'] : 0,
    ];
}

function clms_payment_base_url() {
    if (defined('BASE_URL')) return rtrim(BASE_URL, '/') . '/';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/clms/';
}

function clms_payment_worker_ids_for_request($conn, $paymentRequestId) {
    $rows = db_fetch_all(
        $conn,
        "SELECT workman_id FROM training_payment_request_workers WHERE payment_request_id = ?",
        'i',
        [(int)$paymentRequestId]
    );
    return array_map(function($row) { return (int)$row['workman_id']; }, $rows);
}

function clms_find_pending_training_payment($conn, $contractorId, array $workerIds) {
    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$workerIds) return null;
    $placeholders = implode(',', array_fill(0, count($workerIds), '?'));
    $types = 'i' . str_repeat('i', count($workerIds));
    $params = array_merge([(int)$contractorId], $workerIds);

    $rows = db_fetch_all(
        $conn,
        "SELECT pr.*
         FROM training_payment_requests pr
         JOIN training_payment_request_workers pw ON pw.payment_request_id = pr.id
         WHERE pr.contractor_id = ?
           AND pr.status IN ('pending','link_sent','gateway_created','submitted')
           AND pw.workman_id IN ($placeholders)
         GROUP BY pr.id
         ORDER BY pr.id DESC",
        $types,
        $params
    );
    foreach ($rows as $row) {
        $existingIds = clms_payment_worker_ids_for_request($conn, (int)$row['id']);
        sort($existingIds);
        $targetIds = $workerIds;
        sort($targetIds);
        if ($existingIds === $targetIds) return $row;
    }
    return null;
}

function clms_filter_workers_without_training_payment($conn, array $workerIds) {
    clms_ensure_payment_flow($conn);
    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$workerIds) return [];

    $placeholders = implode(',', array_fill(0, count($workerIds), '?'));
    $types = str_repeat('i', count($workerIds));
    $rows = db_fetch_all(
        $conn,
        "SELECT DISTINCT pw.workman_id
         FROM training_payment_request_workers pw
         JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
         WHERE pw.workman_id IN ($placeholders)
           AND pr.status IN ('pending','link_sent','gateway_created','submitted','paid')",
        $types,
        $workerIds
    );
    $existing = array_fill_keys(array_map(function($row) {
        return (int)$row['workman_id'];
    }, $rows), true);

    return array_values(array_filter($workerIds, function($workerId) use ($existing) {
        return !isset($existing[(int)$workerId]);
    }));
}

function clms_get_contractor_user_for_payment($conn, $contractorId) {
    $contractor = db_single($conn, "SELECT id, user_id, email, contractor_name, vendor_name, application_no FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$contractorId]);
    if (!$contractor) return null;
    $userId = (int)($contractor['user_id'] ?? 0);
    if (!$userId) {
        $user = db_single($conn, "SELECT id FROM users WHERE role = 'contractor' AND contractor_id IN (?, ?) ORDER BY id DESC LIMIT 1", 'ss', [$contractor['id'], $contractor['application_no'] ?? '']);
        $userId = (int)($user['id'] ?? 0);
    }
    $contractor['resolved_user_id'] = $userId;
    return $contractor;
}

function clms_create_training_payment_request($conn, $contractorId, array $workerIds, $createdBy = 0, $source = 'enrolment') {
    clms_ensure_payment_flow($conn);
    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$contractorId || !$workerIds) return null;
    $workerIds = clms_filter_workers_without_training_payment($conn, $workerIds);
    if (!$workerIds) return null;

    $existing = clms_find_pending_training_payment($conn, $contractorId, $workerIds);
    if ($existing) return $existing;

    $workerCount = count($workerIds);
    $fee = clms_training_fee_per_worker($conn);
    if ($fee <= 0) return null;
    $gstPercent = clms_training_payment_gst_percent($conn);
    $subtotal = round($fee * $workerCount, 2);
    $gstAmount = round($subtotal * ($gstPercent / 100), 2);
    $total = round($subtotal + $gstAmount, 2);
    $token = bin2hex(random_bytes(24));
    $ref = 'PAY-' . date('Ymd') . '-' . random_int(1000, 9999);
    $invoiceNo = 'GST-' . date('Ymd') . '-' . random_int(1000, 9999);
    $linkHours = clms_training_payment_link_hours($conn);
    $paymentLink = clms_payment_base_url() . 'pages/payment.php?token=' . urlencode($token);

    $contractor = db_single($conn, "SELECT application_no FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$contractorId]);
    db_execute(
        $conn,
        "INSERT INTO training_payment_requests
            (payment_ref, payment_token, contractor_id, application_no, worker_count, fee_per_worker,
             subtotal_amount, gst_percent, gst_amount, total_amount, currency, payment_link, link_expires_at,
             gateway_provider, status, invoice_no, invoice_generated_at, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'INR', ?, DATE_ADD(NOW(), INTERVAL ? HOUR),
             ?, 'link_sent', ?, NOW(), ?, NOW(), NOW())",
        'ssisidddddsissi',
        [
            $ref,
            $token,
            (int)$contractorId,
            $contractor['application_no'] ?? '',
            $workerCount,
            $fee,
            $subtotal,
            $gstPercent,
            $gstAmount,
            $total,
            $paymentLink,
            $linkHours,
            clms_payment_setting($conn, 'payment_gateway_provider', ''),
            $invoiceNo,
            (int)$createdBy,
        ]
    );
    $paymentRequestId = (int)mysqli_insert_id($conn);

    foreach ($workerIds as $workerId) {
        $worker = db_single($conn, "SELECT temp_id FROM workmen WHERE id = ? LIMIT 1", 'i', [$workerId]);
        $training = db_single($conn, "SELECT id FROM training_requests WHERE workman_id = ? ORDER BY id DESC LIMIT 1", 'i', [$workerId]);
        db_execute(
            $conn,
            "INSERT IGNORE INTO training_payment_request_workers
                (payment_request_id, workman_id, training_request_id, temp_id, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            'iiis',
            [$paymentRequestId, $workerId, (int)($training['id'] ?? 0), $worker['temp_id'] ?? '']
        );
    }

    $request = clms_get_training_payment_request($conn, $paymentRequestId);
    clms_notify_training_payment_request($conn, $request, $source);
    return $request;
}

function clms_get_training_payment_request($conn, $idOrToken) {
    clms_ensure_payment_flow($conn);
    if (is_numeric($idOrToken)) {
        return db_single($conn, "SELECT * FROM training_payment_requests WHERE id = ? LIMIT 1", 'i', [(int)$idOrToken]);
    }
    return db_single($conn, "SELECT * FROM training_payment_requests WHERE payment_token = ? LIMIT 1", 's', [(string)$idOrToken]);
}

function clms_training_payment_workers($conn, $paymentRequestId) {
    return db_fetch_all(
        $conn,
        "SELECT w.id, w.name, w.temp_id, w.aadhaar, w.worker_type, pw.training_request_id
         FROM training_payment_request_workers pw
         JOIN workmen w ON w.id = pw.workman_id
         WHERE pw.payment_request_id = ?
         ORDER BY w.name",
        'i',
        [(int)$paymentRequestId]
    );
}

function clms_notify_training_payment_request($conn, $request, $source = 'enrolment') {
    if (!$request) return;
    require_once __DIR__ . '/NotificationEngine.php';
    $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);
    $userId = (int)($contractor['resolved_user_id'] ?? 0);
    if (!$userId) return;
    $message = "Safety induction fee payment link generated. Ref {$request['payment_ref']}, Amount Rs. "
        . number_format((float)$request['total_amount'], 2)
        . ". Link valid till " . date('d M Y h:i A', strtotime($request['link_expires_at']))
        . ". " . $request['payment_link'];
    NotificationEngine::trigger($conn, $userId, 'Safety Training Payment', $message, 'payment');
}

function clms_mark_training_payment_paid($conn, $paymentRequestId, $gatewayPaymentId = '', $gatewayOrderId = '') {
    clms_ensure_payment_flow($conn);
    db_execute(
        $conn,
        "UPDATE training_payment_requests
         SET status = 'paid',
             gateway_payment_id = ?,
             gateway_order_id = COALESCE(NULLIF(?, ''), gateway_order_id),
             paid_at = NOW(),
             updated_at = NOW()
         WHERE id = ?",
        'ssi',
        [$gatewayPaymentId, $gatewayOrderId, (int)$paymentRequestId]
    );
    db_execute(
        $conn,
        "UPDATE training_requests tr
         JOIN training_payment_request_workers pw ON pw.training_request_id = tr.id
         SET tr.remarks = CONCAT(COALESCE(tr.remarks, ''), '\nPayment verified for safety induction fee.'),
             tr.updated_at = NOW()
         WHERE pw.payment_request_id = ?",
        'i',
        [(int)$paymentRequestId]
    );
}

function clms_submit_demo_training_payment($conn, $paymentRequestId, $payerReference, $note = '') {
    clms_ensure_payment_flow($conn);
    $payerReference = trim((string)$payerReference);
    if ($payerReference === '') {
        throw new InvalidArgumentException('Payment reference / UTR is required.');
    }
    db_execute(
        $conn,
        "UPDATE training_payment_requests
         SET status = 'submitted',
             payer_reference = ?,
             contractor_payment_note = ?,
             submitted_at = NOW(),
             gateway_provider = 'demo_qr',
             updated_at = NOW()
         WHERE id = ?",
        'ssi',
        [$payerReference, trim((string)$note), (int)$paymentRequestId]
    );
}

function clms_verify_demo_training_payment($conn, $paymentRequestId, $approved, $remarks = '', $userId = 0) {
    clms_ensure_payment_flow($conn);
    if ($approved) {
        db_execute(
            $conn,
            "UPDATE training_payment_requests
             SET status = 'paid',
                 gateway_payment_id = COALESCE(NULLIF(payer_reference, ''), gateway_payment_id),
                 verified_by = ?,
                 verified_at = NOW(),
                 verification_remarks = ?,
                 paid_at = COALESCE(paid_at, NOW()),
                 updated_at = NOW()
             WHERE id = ?",
            'isi',
            [(int)$userId, trim((string)$remarks), (int)$paymentRequestId]
        );
        db_execute(
            $conn,
            "UPDATE training_requests tr
             JOIN training_payment_request_workers pw ON pw.training_request_id = tr.id
             SET tr.remarks = CONCAT(COALESCE(tr.remarks, ''), '\nPayment verified by Welfare.'),
                 tr.updated_at = NOW()
             WHERE pw.payment_request_id = ?",
            'i',
            [(int)$paymentRequestId]
        );
        return;
    }

    db_execute(
        $conn,
        "UPDATE training_payment_requests
         SET status = 'link_sent',
             verified_by = ?,
             verified_at = NOW(),
             verification_remarks = ?,
             updated_at = NOW()
         WHERE id = ?",
        'isi',
        [(int)$userId, trim((string)$remarks), (int)$paymentRequestId]
    );
}
