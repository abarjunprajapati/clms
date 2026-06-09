<?php

function clms_default_gate_pass_documents() {
    return [
        ['medical_certificate', 'medical', 'Medical Fitness Certificate', 'Issued by Authorised Medical Attendant (AMA)', 1, 'fa-file-medical', '#ef4444', 10],
        ['police_clearance_certificate', 'pcc', 'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)', 'Issued by Local Police Station / Executing Officer', 1, 'fa-shield-alt', '#f59e0b', 20],
        ['pcc_forwarded_police', 'pcc', 'Proof of forwarding PCC to Thane Police Station', 'Copy of mail / letter sent', 0, 'fa-envelope-open-text', '#6366f1', 30],
        ['pcc_forwarded_cisf', 'pcc', 'Proof of forwarding PCC to CISF', 'Sealed accepted copy from CISF', 0, 'fa-envelope-circle-check', '#14b8a6', 40],
        ['pcc_police_station_name', 'pcc', 'Name of Police Station from where PCC has been obtained', 'Upload supporting document if available', 0, 'fa-building-shield', '#8b5cf6', 50],
        ['employee_compensation_policy', 'coverage', 'Employee Compensation Policy if not covered under ESI', 'Issued by licensed insurance companies', 1, 'fa-umbrella', '#3b82f6', 60],
        ['esi_epf_undertaking', 'coverage', 'ESI / EPF Undertaking if not covered under ESI / EPF', 'Issued by contractor', 0, 'fa-file-signature', '#10b981', 70],
    ];
}

function clms_gate_pass_doc_column_exists($conn, $column) {
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `gate_pass_document_masters` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_ensure_gate_pass_document_masters($conn) {
    $created = clms_db_query($conn, "CREATE TABLE IF NOT EXISTS gate_pass_document_masters (
        id INT NOT NULL AUTO_INCREMENT,
        upload_key VARCHAR(80) NOT NULL UNIQUE,
        category VARCHAR(40) NOT NULL,
        document_type VARCHAR(255) NOT NULL,
        hint VARCHAR(255) NULL,
        is_mandatory TINYINT(1) NOT NULL DEFAULT 0,
        icon VARCHAR(80) NULL,
        color VARCHAR(20) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_gate_doc_active (status, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;

    $columns = [
        'upload_key' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `upload_key` VARCHAR(80) NOT NULL UNIQUE AFTER `id`",
        'category' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `category` VARCHAR(40) NOT NULL AFTER `upload_key`",
        'document_type' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `document_type` VARCHAR(255) NOT NULL AFTER `category`",
        'hint' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `hint` VARCHAR(255) NULL AFTER `document_type`",
        'is_mandatory' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `is_mandatory` TINYINT(1) NOT NULL DEFAULT 0 AFTER `hint`",
        'icon' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `icon` VARCHAR(80) NULL AFTER `is_mandatory`",
        'color' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `color` VARCHAR(20) NULL AFTER `icon`",
        'sort_order' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `color`",
        'status' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `sort_order`",
        'created_at' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `created_at` DATETIME NULL AFTER `status`",
        'updated_at' => "ALTER TABLE `gate_pass_document_masters` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columns as $column => $sql) {
        if (!clms_gate_pass_doc_column_exists($conn, $column)) {
            clms_db_query($conn, $sql);
        }
    }

    foreach (clms_default_gate_pass_documents() as $doc) {
        db_execute(
            $conn,
            "INSERT INTO gate_pass_document_masters
                (upload_key, category, document_type, hint, is_mandatory, icon, color, sort_order, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                category = VALUES(category),
                document_type = VALUES(document_type),
                hint = VALUES(hint),
                icon = VALUES(icon),
                color = VALUES(color),
                sort_order = VALUES(sort_order),
                updated_at = NOW()",
            'ssssissi',
            [$doc[0], $doc[1], $doc[2], $doc[3], $doc[4], $doc[5], $doc[6], $doc[7]]
        );
    }

    return true;
}

function clms_get_gate_pass_document_master_rows($conn, $activeOnly = true) {
    if (!clms_ensure_gate_pass_document_masters($conn)) return [];
    $where = $activeOnly ? "WHERE LOWER(status) = 'active'" : '';
    return db_fetch_all(
        $conn,
        "SELECT id, upload_key, category, document_type, hint, is_mandatory, icon, color, sort_order, status
         FROM gate_pass_document_masters
         $where
         ORDER BY sort_order ASC, id ASC"
    );
}

function clms_get_gate_pass_documents_for_form($conn) {
    $rows = clms_get_gate_pass_document_master_rows($conn, true);
    return array_map(function($row) {
        return [
            'key' => $row['upload_key'],
            'id' => preg_replace('/[^a-z0-9-]+/', '-', strtolower(str_replace('_', '-', $row['upload_key']))),
            'icon' => $row['icon'] ?: 'fa-file',
            'color' => $row['color'] ?: '#64748b',
            'label' => $row['document_type'],
            'hint' => $row['hint'] ?: '',
            'required' => (int)$row['is_mandatory'] === 1,
            'category' => $row['category'],
        ];
    }, $rows);
}

function clms_get_gate_pass_document_type_map($conn, $mandatoryOnly = false) {
    $map = [];
    foreach (clms_get_gate_pass_document_master_rows($conn, true) as $row) {
        if ($mandatoryOnly && (int)$row['is_mandatory'] !== 1) continue;
        $map[$row['upload_key']] = $row['document_type'];
    }
    return $map;
}

function clms_gate_pass_doc_types_sql($conn) {
    $types = array_map(function($row) {
        return $row['document_type'];
    }, clms_get_gate_pass_document_master_rows($conn, true));
    if (!$types) {
        $types = array_map(function($doc) { return $doc[2]; }, clms_default_gate_pass_documents());
    }
    return "'" . implode("','", array_map([$conn, 'real_escape_string'], $types)) . "'";
}

function clms_gate_pass_doc_match_sql($conn) {
    $typesSql = clms_gate_pass_doc_types_sql($conn);
    return "(
        document_type IN ($typesSql)
        OR document_type LIKE '%Medical Fitness%'
        OR document_type LIKE '%Police Clearance%'
        OR document_type LIKE '%PCC%'
        OR document_type LIKE '%Employee Compensation%'
        OR document_type LIKE '%ESI%'
        OR document_type LIKE '%EPF%'
        OR file_path LIKE '%medical_certificate%'
        OR file_path LIKE '%police_clearance_certificate%'
        OR file_path LIKE '%employee_compensation_policy%'
        OR file_path LIKE '%pcc_forwarded%'
        OR file_path LIKE '%pcc_police_station%'
        OR file_path LIKE '%esi_epf_undertaking%'
    )";
}

function clms_gate_pass_mandatory_doc_state($conn, array $docs) {
    $mandatory = [];
    foreach (clms_get_gate_pass_document_master_rows($conn, true) as $row) {
        if ((int)$row['is_mandatory'] !== 1) continue;
        $category = trim((string)$row['category']);
        if ($category === '') continue;
        if (!isset($mandatory[$category])) {
            $mandatory[$category] = ['approved' => false, 'label' => $row['document_type']];
        }
    }

    if (!$mandatory) {
        $mandatory = [
            'medical' => ['approved' => false, 'label' => 'Medical Fitness Certificate'],
            'pcc' => ['approved' => false, 'label' => 'Police Clearance Certificate / PCC'],
            'coverage' => ['approved' => false, 'label' => 'Employee Compensation Policy / ESI-EPF Undertaking'],
        ];
    }

    foreach ($docs as $doc) {
        $type = strtolower((string)($doc['document_type'] ?? ''));
        $filePath = strtolower((string)($doc['file_path'] ?? ''));
        $status = strtolower((string)($doc['status'] ?? 'pending'));
        $category = null;

        if (strpos($type, 'medical fitness') !== false || strpos($filePath, 'medical_certificate') !== false) {
            $category = 'medical';
        } elseif (
            strpos($type, 'pcc') !== false
            || strpos($type, 'police clearance') !== false
            || strpos($type, 'police station') !== false
            || strpos($filePath, 'police_clearance_certificate') !== false
            || strpos($filePath, 'pcc_forwarded') !== false
            || strpos($filePath, 'pcc_police_station') !== false
        ) {
            $category = 'pcc';
        } elseif (
            strpos($type, 'employee compensation') !== false
            || strpos($type, 'esi') !== false
            || strpos($type, 'epf') !== false
            || strpos($filePath, 'employee_compensation_policy') !== false
            || strpos($filePath, 'esi_epf_undertaking') !== false
        ) {
            $category = 'coverage';
        }

        if ($category === null || !isset($mandatory[$category])) continue;
        if ($status === 'rejected' || $status === 'reupload_required') {
            return ['approved' => false, 'missing' => [$mandatory[$category]['label']]];
        }
        if ($status === 'approved') {
            $mandatory[$category]['approved'] = true;
        }
    }

    $missing = [];
    foreach ($mandatory as $rule) {
        if (!$rule['approved']) $missing[] = $rule['label'];
    }

    return ['approved' => empty($missing), 'missing' => $missing];
}

function clms_upsert_gate_pass_document_master($conn, array $data) {
    if (!clms_ensure_gate_pass_document_masters($conn)) {
        throw new RuntimeException('Gate pass document master table could not be created.');
    }

    $id = (int)($data['id'] ?? 0);
    $uploadKey = strtolower(trim((string)($data['upload_key'] ?? '')));
    $uploadKey = preg_replace('/[^a-z0-9_]+/', '_', $uploadKey);
    $category = trim((string)($data['category'] ?? ''));
    $documentType = trim((string)($data['document_type'] ?? ''));
    $hint = trim((string)($data['hint'] ?? ''));
    $mandatory = ((string)($data['is_mandatory'] ?? '0') === '1') ? 1 : 0;
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $status = strtolower(trim((string)($data['status'] ?? 'active'))) === 'inactive' ? 'inactive' : 'active';

    if ($uploadKey === '' || $documentType === '' || $category === '') {
        throw new InvalidArgumentException('Upload key, category and document type are required.');
    }

    if ($id > 0) {
        db_execute(
            $conn,
            "UPDATE gate_pass_document_masters
             SET upload_key = ?, category = ?, document_type = ?, hint = ?, is_mandatory = ?, sort_order = ?, status = ?, updated_at = NOW()
             WHERE id = ?",
            'ssssiisi',
            [$uploadKey, $category, $documentType, $hint, $mandatory, $sortOrder, $status, $id]
        );
        return $id;
    }

    db_execute(
        $conn,
        "INSERT INTO gate_pass_document_masters
            (upload_key, category, document_type, hint, is_mandatory, icon, color, sort_order, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 'fa-file', '#64748b', ?, ?, NOW(), NOW())",
        'ssssiis',
        [$uploadKey, $category, $documentType, $hint, $mandatory, $sortOrder, $status]
    );
    return (int)$conn->insert_id;
}
