<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer', 'super_admin']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/education_flow.php';
include '../../include/layout.php';
require_once '../../include/wage_settings.php';
require_once '../../include/nationality_location_masters.php';
require_once '../../include/age_range_mapping.php';
require_once '../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
$vendor_code = $_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? '';
$enrolmentTypeMap = [
    'contractor' => ['pass' => 'Contractor', 'label' => 'Contractor Self Enrollment', 'plural' => 'Contractor'],
    'representative' => ['pass' => 'Representative', 'label' => 'Representative Enrollment', 'plural' => 'Representatives'],
    'supervisor' => ['pass' => 'Supervisor', 'label' => 'Supervisor Enrollment', 'plural' => 'Supervisors'],
    'workmen' => ['pass' => 'Workman', 'label' => 'Workmen Enrollment', 'plural' => 'Workmen'],
    'workman' => ['pass' => 'Workman', 'label' => 'Workmen Enrollment', 'plural' => 'Workmen'],
];
$requestedType = strtolower(trim($_GET['type'] ?? 'workmen'));
if (!isset($enrolmentTypeMap[$requestedType])) {
    $requestedType = 'workmen';
}
$selectedType = $enrolmentTypeMap[$requestedType];
$prefillAadhaar = preg_replace('/\D+/', '', (string)($_GET['aadhaar'] ?? ''));
clms_get_portal_contractor($conn);
clms_safety_ensure_control_schema($conn);
$educationFlow = clms_get_education_flow($conn);
$minimumCertifiedWage = clms_get_minimum_certified_wage($conn);
$activeCertifiedWages = clms_get_active_certified_wage_map($conn);
$activeAgeRange = clms_get_active_age_range($conn);

function enrolment_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && mysqli_num_rows($result) > 0;
}

function enrolment_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function enrolment_expr($conn, $table, $column, $alias = null, $default = "''") {
    $alias = $alias ?: $column;
    $safeColumn = str_replace('`', '``', $column);
    $safeAlias = str_replace('`', '``', $alias);
    if (enrolment_column_exists($conn, $table, $column)) {
        return "`$safeColumn` AS `$safeAlias`";
    }
    return "$default AS `$safeAlias`";
}

function enrolment_col_ref($conn, $table, $alias, $column, $default = "''") {
    $safeColumn = str_replace('`', '``', $column);
    if (enrolment_column_exists($conn, $table, $column)) {
        return "$alias.`$safeColumn`";
    }
    return $default;
}

function enrolment_add_work_option(&$workOptions, &$seenWorkOrders, $workOrderNo, $projectName = '', $department = '', $projectNo = '', $source = '') {
    $workOrderNo = trim((string)$workOrderNo);
    if ($workOrderNo === '' || isset($seenWorkOrders[$workOrderNo])) {
        return;
    }

    $seenWorkOrders[$workOrderNo] = true;
    $workOptions[] = [
        'work_order_no' => $workOrderNo,
        'project_name' => trim((string)$projectName),
        'department' => trim((string)$department),
        'project_no' => trim((string)($projectNo !== '' ? $projectNo : $workOrderNo)),
        'source' => trim((string)$source),
    ];
}

function enrolment_fetch_one($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

function enrolment_fetch_all($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function enrolment_insert_contractor_from_a3($conn, $a3) {
    if (!enrolment_table_exists($conn, 'contractors')) {
        return 0;
    }

    $vendorCode = trim((string)($a3['vendor_code'] ?? ''));
    $workOrderNo = trim((string)($a3['work_order_no'] ?? ''));
    $customerCode = trim((string)($a3['customer_code'] ?? ($_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? '')));
    if ($vendorCode === '' && $workOrderNo === '' && $customerCode !== '') {
        $vendorCode = 'CUST-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $customerCode);
    }
    if ($vendorCode === '' && $workOrderNo === '') {
        return 0;
    }

    $whereParts = [];
    if ($vendorCode !== '' && enrolment_column_exists($conn, 'contractors', 'vendor_code')) {
        $whereParts[] = "vendor_code = '" . mysqli_real_escape_string($conn, $vendorCode) . "'";
    }
    if ($workOrderNo !== '' && enrolment_column_exists($conn, 'contractors', 'work_order_no')) {
        $whereParts[] = "work_order_no = '" . mysqli_real_escape_string($conn, $workOrderNo) . "'";
    }

    if ($whereParts) {
        $existing = enrolment_fetch_one($conn, "SELECT id FROM contractors WHERE " . implode(' OR ', $whereParts) . " ORDER BY id DESC LIMIT 1");
        if (!empty($existing['id'])) {
            return (int)$existing['id'];
        }
    }

    $displayName = $vendorCode ?: ($workOrderNo ?: 'Approved Contractor');
    if ($vendorCode !== '' && enrolment_table_exists($conn, 'sap_vendors')) {
        $safeVendor = mysqli_real_escape_string($conn, $vendorCode);
        $vendorNameExpr = enrolment_column_exists($conn, 'sap_vendors', 'vendor_name') ? 'vendor_name' : "'' AS vendor_name";
        $contractorNameExpr = enrolment_column_exists($conn, 'sap_vendors', 'contractor_name') ? 'contractor_name' : "'' AS contractor_name";
        $nameExpr = enrolment_column_exists($conn, 'sap_vendors', 'name') ? 'name' : "'' AS name";
        $vendor = enrolment_fetch_one($conn, "SELECT $vendorNameExpr, $contractorNameExpr, $nameExpr FROM sap_vendors WHERE vendor_code = '$safeVendor' LIMIT 1");
        $displayName = ($vendor['vendor_name'] ?? '') ?: (($vendor['contractor_name'] ?? '') ?: (($vendor['name'] ?? '') ?: $displayName));
    }

    $values = [
        'vendor_code' => $vendorCode,
        'sap_code' => $vendorCode,
        'contractor_id' => $vendorCode,
        'contractor_name' => $displayName,
        'vendor_name' => $displayName,
        'name' => $displayName,
        'work_order_no' => $workOrderNo,
        'work_awarding_department' => $a3['work_awarding_department'] ?? '',
        'application_no' => 'A3-' . ($a3['id'] ?? time()),
        'status' => 'approved',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $columns = [];
    $escapedValues = [];
    foreach ($values as $column => $value) {
        if (!enrolment_column_exists($conn, 'contractors', $column)) {
            continue;
        }
        $columns[] = '`' . str_replace('`', '``', $column) . '`';
        $escapedValues[] = "'" . mysqli_real_escape_string($conn, (string)$value) . "'";
    }

    if (!$columns) {
        return 0;
    }

    $ok = mysqli_query($conn, "INSERT INTO contractors (" . implode(',', $columns) . ") VALUES (" . implode(',', $escapedValues) . ")");
    return $ok ? (int)mysqli_insert_id($conn) : 0;
}

function enrolment_worker_type_condition($conn, $table, $type) {
    if (!enrolment_column_exists($conn, $table, 'worker_type')) {
        return '1=1';
    }

    if ($type === 'workmen' || $type === 'workman') {
        return "worker_type IN ('workman', 'workmen', 'Workman', 'Workmen', 'Workman Pass', 'Workmen Pass')";
    }

    $map = [
        'contractor' => ['contractor', 'Contractor', 'Contractor Pass'],
        'representative' => ['representative', 'Representative', 'Representative Pass'],
        'supervisor' => ['supervisor', 'Supervisor', 'Supervisor Pass'],
    ];
    $values = $map[$type] ?? [];
    if (!$values) {
        return '1=1';
    }
    $quoted = array_map(function($value) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $value) . "'";
    }, $values);

    return 'worker_type IN (' . implode(',', $quoted) . ')';
}

function enrolment_customer_contractor_ids($conn) {
    $customerCode = $_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? '';
    if ($customerCode === '') {
        return [];
    }

    $customerCode = mysqli_real_escape_string($conn, $customerCode);
    $ids = [];

    if (enrolment_table_exists($conn, 'contractor_annexure3a')) {
        $a3Rows = enrolment_fetch_all($conn, "
            SELECT *
            FROM contractor_annexure3a
            WHERE customer_code = '$customerCode'
              AND LOWER(COALESCE(status, '')) = 'approved'
            ORDER BY " . (enrolment_column_exists($conn, 'contractor_annexure3a', 'updated_at') ? 'updated_at DESC,' : '') . " id DESC
        ");

        foreach ($a3Rows as $a3) {
            $id = enrolment_insert_contractor_from_a3($conn, $a3);
            if ($id) {
                $ids[] = $id;
            }
        }
    }

    if (!$ids && enrolment_table_exists($conn, 'work_orders') && enrolment_table_exists($conn, 'contractors')) {
        $rows = enrolment_fetch_all($conn, "
            SELECT DISTINCT c.id
            FROM contractors c
            JOIN work_orders wo ON wo.vendor_code = c.vendor_code
            WHERE wo.customer_code = '$customerCode'
        ");
        $ids = array_map('intval', array_column($rows, 'id'));
    }

    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}

function enrolment_contractor_select_expr($conn) {
    return implode(', ', [
        enrolment_expr($conn, 'contractors', 'id', 'id', '0'),
        enrolment_expr($conn, 'contractors', 'contractor_name', 'contractor_name'),
        enrolment_expr($conn, 'contractors', 'vendor_code', 'vendor_code'),
        enrolment_expr($conn, 'contractors', 'vendor_name', 'vendor_name'),
        enrolment_expr($conn, 'contractors', 'status', 'status'),
        enrolment_expr($conn, 'contractors', 'work_order_no', 'work_order_no'),
        enrolment_expr($conn, 'contractors', 'work_awarding_department', 'work_awarding_department'),
    ]);
}

function enrolment_get_contractor_from_approved_a3($conn, $vendorCode) {
    $vendorCode = trim((string)$vendorCode);
    if ($vendorCode === '' || !enrolment_table_exists($conn, 'contractor_annexure3a')) {
        return null;
    }

    $safeVendor = mysqli_real_escape_string($conn, $vendorCode);
    $a3 = enrolment_fetch_one($conn, "
        SELECT *
        FROM contractor_annexure3a
        WHERE vendor_code = '$safeVendor'
          AND LOWER(COALESCE(status, '')) = 'approved'
        ORDER BY " . (enrolment_column_exists($conn, 'contractor_annexure3a', 'updated_at') ? 'updated_at DESC,' : '') . " id DESC
        LIMIT 1
    ");
    if (!$a3) {
        return null;
    }

    $id = enrolment_insert_contractor_from_a3($conn, $a3);
    if (!$id) {
        return null;
    }

    $contractorSelect = enrolment_contractor_select_expr($conn);
    return enrolment_fetch_one($conn, "SELECT $contractorSelect FROM contractors WHERE id = " . (int)$id . " LIMIT 1");
}

function enrolment_get_customer_portal_contractor($conn) {
    if (($_SESSION['role'] ?? '') !== 'customer') {
        return null;
    }

    $customerCode = $_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? '';
    if ($customerCode === '') {
        return null;
    }

    $isApproved = clms_onboarding_is_complete($conn, 'customer', $customerCode, $_SESSION['user_id'] ?? 0);
    if (!$isApproved) {
        return null;
    }

    $portal = clms_get_portal_contractor($conn);
    if (empty($portal['id'])) {
        return null;
    }

    if (enrolment_column_exists($conn, 'contractors', 'status') && strtolower((string)($portal['status'] ?? '')) !== 'approved') {
        mysqli_query($conn, "UPDATE contractors SET status = 'approved' WHERE id = " . (int)$portal['id'] . " LIMIT 1");
    }

    $contractorSelect = enrolment_contractor_select_expr($conn);
    return enrolment_fetch_one($conn, "SELECT $contractorSelect FROM contractors WHERE id = " . (int)$portal['id'] . " LIMIT 1");
}

function renderContent() {
    global $conn, $user_id, $vendor_code, $educationFlow, $role, $requestedType, $selectedType, $prefillAadhaar, $minimumCertifiedWage, $activeCertifiedWages, $activeAgeRange;
    $nationalityOptions = clms_get_nationality_options($conn);
    $religionOptions = clms_get_religion_options($conn);
    $stateDistrictMap = clms_get_state_district_map($conn);
    $minAge = max(0, (int)($activeAgeRange['min_age'] ?? 18));
    $maxAge = max(1, (int)($activeAgeRange['max_age'] ?? 60));
    $dobMax = date('Y-m-d', strtotime("-{$minAge} years"));
    $dobMin = date('Y-m-d', strtotime("-{$maxAge} years"));

    // Get contractor record
    $contractor = null;
    if ($role === 'customer') {
        $contractor = enrolment_get_customer_portal_contractor($conn);
        $customerContractorIds = !empty($contractor['id']) ? [(int)$contractor['id']] : [];
    } elseif (enrolment_table_exists($conn, 'contractors')) {
        $contractorSelect = enrolment_contractor_select_expr($conn);
        $where = enrolment_column_exists($conn, 'contractors', 'user_id')
            ? "user_id = " . (int)$user_id
            : "vendor_code = '" . mysqli_real_escape_string($conn, $vendor_code) . "'";
        $contractor = enrolment_fetch_one($conn, "SELECT $contractorSelect FROM contractors WHERE $where LIMIT 1");
        if (!$contractor) {
            $contractor = enrolment_get_contractor_from_approved_a3($conn, $vendor_code);
        }
    }
    $c_id = $contractor['id'] ?? null;
    $contractorWhere = $c_id ? "contractor_id = " . (int)$c_id : '1=0';
    if ($role === 'customer') {
        $customerContractorIds = $customerContractorIds ?? enrolment_customer_contractor_ids($conn);
        $contractorWhere = $customerContractorIds ? "contractor_id IN (" . implode(',', $customerContractorIds) . ")" : '1=0';
    }

    if ($contractorWhere === '1=0') {
        $target = $role === 'customer' ? '../customer/annexure-3a.php' : 'annexure-2a.php';
        $label = $role === 'customer' ? 'Open Contractor Info 3A' : 'Open Contractor Registration';
        echo '<div class="alert alert-warning" style="margin:20px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;"><div><i class="fas fa-exclamation-triangle"></i> Worker Management will open after Customer form is approved for this customer.</div><a class="btn btn-sm btn-primary" href="' . htmlspecialchars($target) . '">' . htmlspecialchars($label) . '</a></div>';
        return;
    }

    $project_name = '';
    $department_name = $contractor['work_awarding_department'] ?? '';
    $vendorCodeForSap = $contractor['vendor_code'] ?? ($_SESSION['contractor_id'] ?? '');
    $workOptions = [];
    $seenWorkOrders = [];
    $selectedContractorWhere = $role === 'customer'
        ? ($customerContractorIds ? "s.contractor_id IN (" . implode(',', $customerContractorIds) . ")" : '1=0')
        : ($c_id ? "s.contractor_id = " . (int)$c_id : '1=0');

    if ($selectedContractorWhere !== '1=0' && enrolment_table_exists($conn, 'contractor_po_selection')) {
        $joinPo = enrolment_table_exists($conn, 'sap_po_master') && enrolment_column_exists($conn, 'sap_po_master', 'po_number');
        $poText = $joinPo ? enrolment_col_ref($conn, 'sap_po_master', 'po', 'header_text', "''") : "''";
        $poDept = $joinPo ? enrolment_col_ref($conn, 'sap_po_master', 'po', 'purchasing_group', "''") : "''";
        $poJoinSql = $joinPo ? "LEFT JOIN sap_po_master po ON po.po_number = s.po_number" : "";
        $poRows = enrolment_fetch_all($conn, "
            SELECT s.po_number AS work_order_no,
                   COALESCE($poText, '') AS project_name,
                   COALESCE($poDept, '') AS department,
                   s.po_number AS project_no
            FROM contractor_po_selection s
            $poJoinSql
            WHERE $selectedContractorWhere
            ORDER BY s.po_number DESC
        ");
        foreach ($poRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'PO');
        }
    }

    if ($selectedContractorWhere !== '1=0' && enrolment_table_exists($conn, 'contractor_pwo_selection')) {
        $joinPwo = enrolment_table_exists($conn, 'sap_pwo_master') && enrolment_column_exists($conn, 'sap_pwo_master', 'pwo_number');
        $pwoProject = $joinPwo ? enrolment_col_ref($conn, 'sap_pwo_master', 'pwo', 'project', 'NULL') : 'NULL';
        $pwoVessel = $joinPwo ? enrolment_col_ref($conn, 'sap_pwo_master', 'pwo', 'vessel', 'NULL') : 'NULL';
        $pwoDesc = $joinPwo ? enrolment_col_ref($conn, 'sap_pwo_master', 'pwo', 'pwo_description', 'NULL') : 'NULL';
        $pwoJoinSql = $joinPwo ? "LEFT JOIN sap_pwo_master pwo ON pwo.pwo_number = s.pwo_number" : "";
        $pwoRows = enrolment_fetch_all($conn, "
            SELECT s.pwo_number AS work_order_no,
                   COALESCE($pwoProject, $pwoVessel, $pwoDesc, '') AS project_name,
                   '' AS department,
                   s.pwo_number AS project_no
            FROM contractor_pwo_selection s
            $pwoJoinSql
            WHERE $selectedContractorWhere
            ORDER BY s.pwo_number DESC
        ");
        foreach ($pwoRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'PWO');
        }
    }

    if ($selectedContractorWhere !== '1=0' && enrolment_table_exists($conn, 'contractor_so_selection')) {
        $joinSo = enrolment_table_exists($conn, 'sap_sale_order_master') && enrolment_column_exists($conn, 'sap_sale_order_master', 'sale_order_no');
        $soDesc = $joinSo ? enrolment_col_ref($conn, 'sap_sale_order_master', 'so', 'description', "''") : "''";
        $soDept = $joinSo ? enrolment_col_ref($conn, 'sap_sale_order_master', 'so', 'department', "''") : "''";
        $soJoinSql = $joinSo ? "LEFT JOIN sap_sale_order_master so ON so.sale_order_no = s.sale_order_no" : "";
        $soRows = enrolment_fetch_all($conn, "
            SELECT s.sale_order_no AS work_order_no,
                   COALESCE($soDesc, '') AS project_name,
                   COALESCE($soDept, '') AS department,
                   s.sale_order_no AS project_no
            FROM contractor_so_selection s
            $soJoinSql
            WHERE $selectedContractorWhere
            ORDER BY s.sale_order_no DESC
        ");
        foreach ($soRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'SO');
        }
    }

    if ($vendorCodeForSap !== '' && enrolment_table_exists($conn, 'sap_po_master') && enrolment_column_exists($conn, 'sap_po_master', 'po_number')) {
        $safeVendor = mysqli_real_escape_string($conn, $vendorCodeForSap);
        $vendorFilters = [];
        if (enrolment_column_exists($conn, 'sap_po_master', 'vendor_code')) {
            $vendorFilters[] = "vendor_code = '$safeVendor'";
        }
        if (enrolment_column_exists($conn, 'sap_po_master', 'customer_code')) {
            $vendorFilters[] = "customer_code = '$safeVendor'";
        }
        $orderExpr = enrolment_column_exists($conn, 'sap_po_master', 'document_date') ? 'document_date DESC' : 'po_number DESC';
        $poRows = $vendorFilters ? enrolment_fetch_all($conn, "
            SELECT
                po_number AS work_order_no,
                COALESCE(" . (enrolment_column_exists($conn, 'sap_po_master', 'header_text') ? 'header_text' : 'NULL') . ", po_number) AS project_name,
                COALESCE(" . (enrolment_column_exists($conn, 'sap_po_master', 'purchasing_group') ? 'purchasing_group' : 'NULL') . ", '') AS department,
                po_number AS project_no
            FROM sap_po_master
            WHERE " . implode(' OR ', $vendorFilters) . "
            ORDER BY $orderExpr
        ") : [];
        foreach ($poRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'PO');
        }
    }

    if ($vendorCodeForSap !== '' && enrolment_table_exists($conn, 'sap_pwo_master') && enrolment_column_exists($conn, 'sap_pwo_master', 'pwo_number')) {
        $safeVendor = mysqli_real_escape_string($conn, $vendorCodeForSap);
        $vendorFilters = [];
        if (enrolment_column_exists($conn, 'sap_pwo_master', 'vendor_code')) {
            $vendorFilters[] = "vendor_code = '$safeVendor'";
        }
        if (enrolment_column_exists($conn, 'sap_pwo_master', 'customer_code')) {
            $vendorFilters[] = "customer_code = '$safeVendor'";
        }
        $statusWhere = enrolment_column_exists($conn, 'sap_pwo_master', 'status') ? "AND COALESCE(status, 'active') = 'active'" : "";
        $orderExpr = enrolment_column_exists($conn, 'sap_pwo_master', 'id') ? 'id DESC' : 'pwo_number DESC';
        $pwoRows = $vendorFilters ? enrolment_fetch_all($conn, "
            SELECT
                pwo_number AS work_order_no,
                COALESCE(" . (enrolment_column_exists($conn, 'sap_pwo_master', 'project') ? 'project' : 'NULL') . ", " . (enrolment_column_exists($conn, 'sap_pwo_master', 'vessel') ? 'vessel' : 'NULL') . ", pwo_number) AS project_name,
                '' AS department,
                pwo_number AS project_no
            FROM sap_pwo_master
            WHERE (" . implode(' OR ', $vendorFilters) . ") $statusWhere
            ORDER BY $orderExpr
        ") : [];
        foreach ($pwoRows as $pwoRow) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $pwoRow['work_order_no'], $pwoRow['project_name'], $pwoRow['department'], $pwoRow['project_no'], 'PWO');
        }
    }
    if ($vendorCodeForSap !== '' && enrolment_table_exists($conn, 'sap_sale_order_master') && enrolment_column_exists($conn, 'sap_sale_order_master', 'sale_order_no')) {
        $safeVendor = mysqli_real_escape_string($conn, $vendorCodeForSap);
        $vendorFilters = [];
        if (enrolment_column_exists($conn, 'sap_sale_order_master', 'vendor_code')) {
            $vendorFilters[] = "vendor_code = '$safeVendor'";
        }
        if (enrolment_column_exists($conn, 'sap_sale_order_master', 'customer_code')) {
            $vendorFilters[] = "customer_code = '$safeVendor'";
        }
        $orderExpr = enrolment_column_exists($conn, 'sap_sale_order_master', 'doc_date') ? 'doc_date DESC' : 'sale_order_no DESC';
        $soRows = $vendorFilters ? enrolment_fetch_all($conn, "
            SELECT
                sale_order_no AS work_order_no,
                COALESCE(" . (enrolment_column_exists($conn, 'sap_sale_order_master', 'description') ? 'description' : 'NULL') . ", sale_order_no) AS project_name,
                COALESCE(" . (enrolment_column_exists($conn, 'sap_sale_order_master', 'department') ? 'department' : 'NULL') . ", '') AS department,
                sale_order_no AS project_no
            FROM sap_sale_order_master
            WHERE " . implode(' OR ', $vendorFilters) . "
            ORDER BY $orderExpr
        ") : [];
        foreach ($soRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'SO');
        }
    }
    if (empty($workOptions) && $vendorCodeForSap !== '' && enrolment_table_exists($conn, 'work_orders')) {
        $safeVendor = mysqli_real_escape_string($conn, $vendorCodeForSap);
        $workOrderRows = enrolment_fetch_all($conn, "
            SELECT work_order_no, project_name, department, work_order_no AS project_no
            FROM work_orders
            WHERE vendor_code = '$safeVendor' AND COALESCE(wo_status, 'ACTIVE') = 'ACTIVE'
            ORDER BY id DESC
        ");
        foreach ($workOrderRows as $row) {
            enrolment_add_work_option($workOptions, $seenWorkOrders, $row['work_order_no'], $row['project_name'], $row['department'], $row['project_no'], 'WO');
        }
    }
    if (empty($workOptions) && !empty($contractor['work_order_no'])) {
        enrolment_add_work_option($workOptions, $seenWorkOrders, $contractor['work_order_no'], $project_name ?: 'General Project', $department_name, $contractor['work_order_no'], 'WO');
    }
    if (!empty($contractor['work_order_no']) && enrolment_table_exists($conn, 'work_orders') && enrolment_column_exists($conn, 'work_orders', 'work_order_no')) {
        $woSelect = implode(', ', [
            enrolment_expr($conn, 'work_orders', 'project_name', 'project_name'),
            enrolment_expr($conn, 'work_orders', 'department', 'department'),
        ]);
        $woNo = mysqli_real_escape_string($conn, $contractor['work_order_no']);
        $wo_row = enrolment_fetch_one($conn, "SELECT $woSelect FROM work_orders WHERE work_order_no = '$woNo' LIMIT 1");
        $project_name = $wo_row['project_name'] ?? '';
        if ($department_name === '') {
            $department_name = $wo_row['department'] ?? '';
        }
    }

    // Fetch enrolled workers from workmen. This is the workflow source of truth.
    $workers = [];
    if ($c_id && enrolment_table_exists($conn, 'workmen')) {
        $workerTypeExpr = enrolment_column_exists($conn, 'workmen', 'worker_type') ? 'worker_type' : "''";
        $skillCategoryExpr = enrolment_column_exists($conn, 'workmen', 'skill_category') && enrolment_column_exists($conn, 'workmen', 'skill')
            ? "COALESCE(NULLIF(skill_category, ''), skill) AS skill_category"
            : (enrolment_column_exists($conn, 'workmen', 'skill_category')
                ? enrolment_expr($conn, 'workmen', 'skill_category', 'skill_category')
                : enrolment_expr($conn, 'workmen', 'skill', 'skill_category'));
        $roleTypeFallbacks = [];
        if (enrolment_column_exists($conn, 'workmen', 'skill_category')) $roleTypeFallbacks[] = "NULLIF(skill_category, '')";
        if (enrolment_column_exists($conn, 'workmen', 'skill')) $roleTypeFallbacks[] = "NULLIF(skill, '')";
        $roleTypeFallbacks[] = "''";
        if (enrolment_column_exists($conn, 'workmen', 'role_type')) {
            array_unshift($roleTypeFallbacks, "NULLIF(role_type, '')");
        }
        $roleTypeExpr = "COALESCE(" . implode(', ', $roleTypeFallbacks) . ") AS role_type";
        $orderExpr = enrolment_column_exists($conn, 'workmen', 'created_at') ? 'created_at DESC' : 'id DESC';
        $typeWhere = enrolment_worker_type_condition($conn, 'workmen', $requestedType);
        $nonDraftWhere = "";
        $workers = enrolment_fetch_all($conn, "
            SELECT
                " . enrolment_expr($conn, 'workmen', 'id', 'id', '0') . ",
                " . (enrolment_column_exists($conn, 'workmen', 'work_order_no') ? enrolment_expr($conn, 'workmen', 'work_order_no', 'work_order_no') : enrolment_expr($conn, 'workmen', 'application_no', 'work_order_no')) . ",
                " . enrolment_expr($conn, 'workmen', 'project_name', 'project_name') . ",
                CASE
                    WHEN $workerTypeExpr IN ('Supervisor Pass', 'supervisor') THEN 'Supervisor'
                    WHEN $workerTypeExpr IN ('Contractor Pass', 'contractor') THEN 'Contractor'
                    WHEN $workerTypeExpr IN ('Representative Pass', 'representative') THEN 'Representative'
                    ELSE 'Workman'
                END AS pass_type,
                " . (enrolment_column_exists($conn, 'workmen', 'created_at') ? 'DATE(created_at)' : "''") . " AS registration_date,
                " . enrolment_expr($conn, 'workmen', 'aadhaar', 'aadhaar') . ",
                " . enrolment_expr($conn, 'workmen', 'name', 'name') . ",
                " . enrolment_expr($conn, 'workmen', 'father_name', 'father_name') . ",
                " . enrolment_expr($conn, 'workmen', 'gender', 'gender') . ",
                " . enrolment_expr($conn, 'workmen', 'dob', 'dob') . ",
                " . enrolment_expr($conn, 'workmen', 'marital_status', 'marital_status') . ",
                " . enrolment_expr($conn, 'workmen', 'nationality', 'nationality', "'Indian'") . ",
                '' AS identification_mark,
                " . enrolment_expr($conn, 'workmen', 'present_address', 'present_address') . ",
                " . enrolment_expr($conn, 'workmen', 'permanent_address', 'permanent_address') . ",
                " . enrolment_expr($conn, 'workmen', 'state', 'state') . ",
                " . enrolment_expr($conn, 'workmen', 'district', 'district') . ",
                " . enrolment_expr($conn, 'workmen', 'pincode', 'pincode') . ",
                '' AS police_station,
                " . enrolment_expr($conn, 'workmen', 'mobile', 'mobile') . ",
                '' AS emergency_contact,
                " . enrolment_expr($conn, 'workmen', 'department', 'department') . ",
                " . enrolment_expr($conn, 'workmen', 'nature_of_work', 'nature_of_work') . ",
                " . $skillCategoryExpr . ",
                " . enrolment_expr($conn, 'workmen', 'blood_group', 'blood_group') . ",
                " . enrolment_expr($conn, 'workmen', 'experience', 'experience') . ",
                " . enrolment_expr($conn, 'workmen', 'region', 'region') . ",
                " . enrolment_expr($conn, 'workmen', 'pwd_status', 'pwd_status') . ",
                " . enrolment_expr($conn, 'workmen', 'passport_no', 'passport_no') . ",
                " . enrolment_expr($conn, 'workmen', 'driving_licence_no', 'driving_licence_no') . ",
                " . enrolment_expr($conn, 'workmen', 'email', 'email') . ",
                " . enrolment_expr($conn, 'workmen', 'contact_email', 'contact_email') . ",
                " . enrolment_expr($conn, 'workmen', 'dcate', 'dcate') . ",
                " . enrolment_expr($conn, 'workmen', 'certified_wage_rate', 'certified_wage_rate') . ",
                " . enrolment_expr($conn, 'workmen', 'safety_language', 'safety_language') . ",
                " . enrolment_expr($conn, 'workmen', 'work_order_source', 'work_order_source') . ",
                " . enrolment_expr($conn, 'workmen', 'safety_fee_payment_option', 'safety_fee_payment_option') . ",
                " . enrolment_expr($conn, 'workmen', 'executing_officer_code', 'executing_officer_code') . ",
                " . enrolment_expr($conn, 'workmen', 'executing_officer_name', 'executing_officer_name') . ",
                " . enrolment_expr($conn, 'workmen', 'execution_training_status', 'execution_training_status') . ",
                " . enrolment_expr($conn, 'workmen', 'execution_training_reviewed_by', 'execution_training_reviewed_by', '0') . ",
                " . enrolment_expr($conn, 'workmen', 'uan_number', 'pf_no') . ",
                " . enrolment_expr($conn, 'workmen', 'esic_number', 'esi_no') . ",
                '' AS bank_account,
                '' AS ifsc,
                " . enrolment_expr($conn, 'workmen', 'photo', 'photo') . ",
                '' AS signature,
                '' AS aadhaar_doc,
                '' AS medical_doc,
                '' AS police_doc,
                '' AS insurance_doc,
                " . enrolment_expr($conn, 'workmen', 'education', 'education') . ",
                " . $roleTypeExpr . ",
                " . enrolment_expr($conn, 'workmen', 'training_status', 'safety_status') . ",
                (
                    SELECT tr.status
                    FROM training_requests tr
                    WHERE tr.workman_id = workmen.id
                    ORDER BY tr.id DESC
                    LIMIT 1
                ) AS latest_training_request_status,
                (
                    SELECT tr.batch_number
                    FROM training_requests tr
                    WHERE tr.workman_id = workmen.id
                    ORDER BY tr.id DESC
                    LIMIT 1
                ) AS latest_training_batch,
                (
                    SELECT tr.scheduled_date
                    FROM training_requests tr
                    WHERE tr.workman_id = workmen.id
                    ORDER BY tr.id DESC
                    LIMIT 1
                ) AS latest_training_date,
                (
                    SELECT COUNT(*)
                    FROM training_requests tr
                    WHERE tr.workman_id = workmen.id
                      AND LOWER(COALESCE(tr.status, 'pending')) IN ('failed','fail','absent','passed')
                      AND tr.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ) AS training_attempts_30,
                " . enrolment_expr($conn, 'workmen', 'status', 'gate_pass_status') . ",
                " . enrolment_expr($conn, 'workmen', 'temp_id', 'temp_id') . ",
                " . enrolment_expr($conn, 'workmen', 'created_at', 'created_at') . "
            FROM workmen
            WHERE $contractorWhere AND $typeWhere $nonDraftWhere
            ORDER BY $orderExpr
        ");
    }
    ?>
    <style>
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.65);
      backdrop-filter: blur(4px);
      z-index: 10550;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    .modal-overlay.show { opacity: 1; pointer-events: all; }
    .modal-overlay.hidden { opacity: 0; pointer-events: none; }
    .modal-box {
      position: relative;
      background: white;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      width: min(100%, 850px);
      max-height: 90vh;
      overflow-y: auto;
      animation: modalIn .25s ease;
    }
    @keyframes modalIn { from { transform: scale(.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-header { display:flex;align-items:center;justify-content:space-between;padding:20px;border-bottom:1px solid var(--border-color); }
    .modal-title  { font-size:16px;font-weight:700;margin:0; }
    .modal-close  { background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);line-height:1; }
    .modal-tabs   { display:flex;gap:0;border-bottom:1px solid var(--border-color);padding:0 20px; background: #f8fafc; }
    .modal-tab    { background:none;border:none;padding:12px 16px;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent; }
    .modal-tab.active { color:#6366f1;border-bottom-color:#6366f1; background: white; }
    .modal-tab-content { display:block; padding: 20px; }
    .modal-tab-content.hidden { display:none; }

    /* New Inline Form Square Tabs */
    .square-tabs { display: flex; gap: 10px; padding: 20px 20px 0 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; overflow-x: auto; }
    .square-tab { background: #f1f5f9; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .square-tab.active { background: #6366f1; color: white; border-color: #6366f1; }
    .square-tab:hover:not(.active) { background: #e2e8f0; }
    
    .form-grid-3 { display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; }
    .form-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:15px; }
    .form-grid-4 { display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; }
    .form-group { margin-bottom: 15px; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);font-size:13px;box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
    .enroll-actions { padding:15px 20px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; gap:10px; align-items:center; background:#fff; position:sticky; bottom:0; z-index:5; }
    .enroll-actions-left, .enroll-actions-right { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .enroll-actions .btn { min-width:118px; min-height:38px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
    .btn-primary-soft { background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; }
    .btn-primary-soft:hover { background:#dbeafe; }
    .conditional-field.hidden { display:none; }
    .doc-card { border:1px solid #e2e8f0; background:#f8fafc; border-radius:10px; padding:14px; }
    .doc-card .form-label { margin-bottom:8px; }
    @media (max-width: 900px) { .form-grid-3, .form-grid-4 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 640px) { .form-grid-2, .form-grid-3, .form-grid-4, .preview-grid, .preview-section-grid { grid-template-columns: 1fr; } .enroll-actions { flex-direction:column; align-items:stretch; } .enroll-actions-left, .enroll-actions-right { width:100%; } .enroll-actions .btn { flex:1; } }
    .work-flow-panel { grid-column:1 / -1; border:1px solid var(--border-color); border-radius:8px; background:#f8fafc; padding:16px; margin-bottom:5px; }
    .work-flow-title { display:flex; align-items:center; gap:8px; font-size:14px; font-weight:700; color:#0f172a; margin-bottom:14px; }
    .work-flow-title i { color:#6366f1; }
    .work-flow-steps { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:18px; align-items:start; position:relative; }
    .work-flow-step { position:relative; min-width:0; }
    .work-flow-step:not(:last-child)::after { content:'\f061'; font-family:'Font Awesome 5 Free'; font-weight:900; position:absolute; top:42px; right:-14px; color:#94a3b8; font-size:13px; }
    .flow-step-head { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin-bottom:9px; }
    .flow-step-number { width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:#e0e7ff; color:#4338ca; font-size:11px; }
    .flow-control { width:100%; border:1.5px solid #cbd5e1 !important; border-radius:8px; background:#fff !important; color:#0f172a !important; padding:10px 12px; min-height:44px; font-size:13px; font-weight:700; }
    .flow-control:disabled { background:#f1f5f9 !important; color:#94a3b8 !important; cursor:not-allowed; }
    .flow-control:focus { border-color:#6366f1 !important; box-shadow:0 0 0 3px rgba(99,102,241,.14); outline:none; }
    @media (max-width: 900px) {
      .work-flow-steps { grid-template-columns:1fr; }
      .work-flow-step:not(:last-child)::after { content:'\f063'; top:auto; bottom:-16px; right:50%; transform:translateX(50%); }
    }
    
    .doc-card { background:#f8fafc; border:1px solid var(--border-color); border-radius:10px; padding:12px; }
    .badge-status { font-size:10px; padding:3px 8px; border-radius:10px; font-weight:600; text-transform:uppercase; }
    .booking-cell { min-width:180px; }
    .booking-status { display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800;text-transform:uppercase; }
    .booking-status.pending { background:#fef3c7;color:#92400e; }
    .booking-status.scheduled { background:#dbeafe;color:#1d4ed8; }
    .booking-status.pass { background:#dcfce7;color:#166534; }
    .booking-status.fail { background:#fee2e2;color:#991b1b; }
    .booking-meta { display:block;font-size:11px;color:#64748b;margin-top:5px;line-height:1.35; }
    .booking-actions { display:flex;gap:6px;flex-wrap:wrap;margin-top:7px; }
    .training-booking-box { display:grid; gap:12px; max-width:720px; }
    .choice-row { display:flex; align-items:center; gap:10px; border:1px solid #cbd5e1; border-radius:8px; padding:12px; font-weight:700; cursor:pointer; }
    .choice-row.active { border-color:#2563eb; background:#eff6ff; color:#1d4ed8; }
    .choice-row input { width:18px; height:18px; }
    .training-booking-form { margin-top:16px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:10px; padding:16px; }
    .training-booking-form.hidden { display:none; }
    .preview-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .preview-section { border:1px solid #dbe4ef; border-radius:8px; background:#fff; overflow:hidden; }
    .preview-section.full { grid-column:1 / -1; }
    .preview-section-title { padding:10px 12px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:900; color:#334155; text-transform:uppercase; }
    .preview-section-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0; }
    .preview-item { border:1px solid #e2e8f0; border-radius:8px; padding:10px; background:#fff; }
    .preview-item span { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; margin-bottom:4px; }
    .preview-section .preview-item { border:0; border-radius:0; border-right:1px solid #eef2f7; border-bottom:1px solid #eef2f7; min-width:0; }
    .preview-section .preview-item strong { display:block; overflow-wrap:anywhere; line-height:1.35; }
    .preview-question { margin:16px 0 0; padding:10px 12px; border:1px solid #bfdbfe; border-radius:8px; background:#eff6ff; color:#1e3a8a; font-weight:800; }
    #enrollForm .form-control {
      min-height: 42px;
      border: 1.5px solid #cbd5e1 !important;
      border-radius: 8px;
      background-color: #ffffff !important;
      color: #0f172a !important;
      padding: 10px 12px;
      box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    #enrollForm select.form-control {
      appearance: auto;
    }
    #enrollForm textarea.form-control {
      min-height: 82px;
      resize: vertical;
    }
    #enrollForm .form-control:focus {
      border-color: #2563eb !important;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
      outline: none;
    }
    #enrollForm .form-control[readonly] {
      background-color: #f8fafc !important;
      color: #334155 !important;
    }
    </style>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-users" style="color:#6366f1;margin-right:10px;"></i> <?= htmlspecialchars($selectedType['label']) ?></h2>
        <!-- <p class="page-subtitle">Add workers with full Annexure 4A details and document uploads.</p> -->
      </div>
      <button class="btn btn-primary" id="btnOpenModal"><i class="fas fa-plus"></i> New <?= htmlspecialchars($selectedType['pass']) ?></button>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(99,102,241,0.1);color:#6366f1"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= count($workers) ?></div>
        <div class="stat-label"><?= htmlspecialchars($selectedType['plural']) ?> Enrolled</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="fas fa-check"></i></div>
        <div class="stat-value"><?= count(array_filter($workers, function($w) { return ($w['safety_status']??'')==='pass'; })) ?></div>
        <div class="stat-label">Safety Passed</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="fas fa-id-badge"></i></div>
        <div class="stat-value"><?= count(array_filter($workers, function($w) { return !empty($w['temp_id']); })) ?></div>
        <div class="stat-label">Temp IDs Issued</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:#ef4444"><i class="fas fa-door-open"></i></div>
        <div class="stat-value"><?= count(array_filter($workers, function($w) { return ($w['gate_pass_status']??'')==='active'; })) ?></div>
        <div class="stat-label">Active Passes</div>
      </div>
    </div>

    <!-- Annexure 5/A: Pass Limits Widget -->
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-shield-alt" style="color:#6366f1;"></i>   Pass Limits</div>
      </div>
      <div class="card-body" id="passLimitsWidget">
        <p style="color:var(--text-muted);font-size:13px;">Loading pass limits...</p>
      </div>
    </div>
    <script src="../../js/passLimitValidator.js"></script>
    <script>
    (async () => {
      const cid = <?= $c_id ? (int)$c_id : 0 ?>;
      if (cid) {
        await PassLimitValidator.fetchLimits(cid);
        PassLimitValidator.renderSummary(document.getElementById('passLimitsWidget'));
      } else {
        document.getElementById('passLimitsWidget').innerHTML = '<p style="color:var(--text-muted);">No contractor record found.</p>';
      }
    })();
    </script>

    <div id="listSection">
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center gap-3">
          <div class="card-title"><?= htmlspecialchars($selectedType['plural']) ?> List</div>
          <button class="btn btn-sm btn-outline-primary" onclick="downloadWorkerList()">
            <i class="fas fa-file-pdf"></i> Download PDF
          </button>
        </div>
        <input type="text" id="searchWorker" class="form-control" style="width:250px;" placeholder="Search name or Aadhaar...">
      </div>
      <div class="card-body p-0">
        <table class="data-table" id="workerTable">
          <thead>
            <tr>
              <th>Person Details</th>
              <th>Aadhaar</th>
              <th>Pass / Role</th>
              <th>Department / Work</th>
              <th>Temp ID</th>
              <th>Executing Officer</th>
              <th>Safety Booking</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($workers as $w):
              $bookingStatus = strtolower((string)($w['latest_training_request_status'] ?: ($w['safety_status'] ?? 'pending')));
              $bookingClass = in_array($bookingStatus, ['pass','passed','completed','training_passed','qualified'], true)
                  ? 'pass'
                  : (in_array($bookingStatus, ['fail','failed','absent','training_failed'], true) ? 'fail' : (in_array($bookingStatus, ['scheduled','contractor_confirmed'], true) ? 'scheduled' : 'pending'));
              $bookingLabel = strtoupper(str_replace('_', ' ', $bookingStatus ?: 'pending'));
              $attemptsLeft = max(0, 3 - (int)($w['training_attempts_30'] ?? 0));
              $bookUrl = 'book_safety_training.php?worker_id=' . (int)$w['id'];
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-sm">
                    <?php if ($w['photo']): ?>
                      <img src="../../uploads/workers/<?= $w['photo'] ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                      <i class="fas fa-user-circle fa-2x text-muted"></i>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($w['name']) ?></div>
                    <small class="text-muted"><?= $w['gender'] ?> | <?= $w['dob'] ?></small>
                  </div>
                </div>
              </td>
              <td><code><?= $w['aadhaar'] ?></code></td>
              <td>
                <span class="badge badge-gray"><?= $w['pass_type'] ?></span><br>
                <small><?= $w['role_type'] ?></small>
              </td>
              <td>
                <div><?= $w['department'] ?></div>
                <small><?= $w['nature_of_work'] ?></small>
              </td>
              <td><code class="text-primary"><?= $w['temp_id'] ?? 'PENDING' ?></code></td>
              <td>
                <?php if (!empty($w['executing_officer_code'])): ?>
                  <code style="background:rgba(14,165,233,0.1);color:#0369a1;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;"><?= htmlspecialchars($w['executing_officer_code']) ?></code>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($w['executing_officer_name'] ?: 'Name not found') ?></div>
                  <?php
                    $eoStatus = strtolower((string)($w['execution_training_status'] ?? 'pending'));
                    $eoReviewed = (int)($w['execution_training_reviewed_by'] ?? 0) > 0;
                    $eoIsApproved = $eoStatus === 'approved' && $eoReviewed;
                    $eoBadge = $eoIsApproved ? 'bg-success text-white' : ($eoStatus === 'rejected' ? 'bg-danger text-white' : 'bg-warning');
                    $eoLabel = $eoIsApproved ? 'EO Approved' : ($eoStatus === 'rejected' ? 'EO Rejected' : 'EO Pending');
                  ?>
                  <span class="badge-status <?= $eoBadge ?>" style="margin-top:4px;display:inline-block;"><?= $eoLabel ?></span>
                <?php else: ?>
                  <span style="opacity:0.4;">-</span>
                <?php endif; ?>
              </td>
              <td class="booking-cell">
                <span class="booking-status <?= $bookingClass ?>"><?= htmlspecialchars($bookingLabel) ?></span>
                <span class="booking-meta">
                  <?= !empty($w['latest_training_batch']) ? 'Batch: ' . htmlspecialchars($w['latest_training_batch']) : 'Batch: Not booked' ?>
                  <?= !empty($w['latest_training_date']) ? '<br>Date: ' . htmlspecialchars(date('d-m-Y', strtotime($w['latest_training_date']))) : '' ?>
                  <br>Attempts left: <?= (int)$attemptsLeft ?>
                </span>
                <?php if (!in_array($bookingClass, ['pass'], true)): ?>
                  <div class="booking-actions">
                    <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($bookUrl) ?>"><i class="fas fa-calendar-check"></i> <?= $bookingClass === 'fail' ? 'Book Retest' : 'Book Safety' ?></a>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge-status <?= $w['safety_status']==='pass'?'bg-success text-white':'bg-warning' ?>">Safety: <?= $w['safety_status'] ?></span>
              </td>
              <td>
                <div style="display:flex;gap:5px;">
                  <button class="btn btn-sm btn-outline" title="View Profile" onclick="viewWorker(<?= htmlspecialchars(json_encode($w)) ?>)"><i class="fas fa-eye"></i></button>
                  <button class="btn btn-sm btn-outline" title="Edit Worker" onclick="editWorker(<?= htmlspecialchars(json_encode($w)) ?>)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" title="Delete Worker" onclick="deleteWorker(<?= (int)$w['id'] ?>, '<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>')">
                    <i class="fas fa-trash"></i>
                  </button>
                  <?php if(!empty($w['temp_id'])): ?>
                  <button class="btn btn-sm btn-primary" title="Download Temp ID Card" onclick="downloadTempCard(<?= htmlspecialchars(json_encode($w)) ?>)">
                    <i class="fas fa-download"></i> PDF
                  </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    </div> <!-- end listSection -->

    <!-- Inline Form Section -->
    <div id="formSection" class="hidden">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <h3 class="card-title" id="enrollFormTitle">Workmen Enrollment</h3>
          <button class="btn btn-outline" onclick="closeForm()">Back to List</button>
        </div>
        <form id="enrollForm" enctype="multipart/form-data">
          <input type="hidden" name="worker_id" id="workerEditId" value="">
          <input type="hidden" name="source" id="workerSource" value="MANUAL">
          <input type="hidden" name="work_order_source" id="workOrderSource" value="">
          <input type="hidden" name="contractor_id" value="<?= (int)$c_id ?>">
          <div class="square-tabs">
            <button type="button" class="square-tab active" data-tab="basic">1. Basic Info</button>
            <button type="button" class="square-tab" data-tab="personal">2. Personal / Medical</button>
            <button type="button" class="square-tab" data-tab="address">3. Address / Contact</button>
            <button type="button" class="square-tab" data-tab="work">4. Work / Compliance</button>
            <button type="button" class="square-tab" data-tab="docs">5. Documents</button>
            <button type="button" class="square-tab" data-tab="payment">6. Safety Fee Payment</button>
            <button type="button" class="square-tab" data-tab="training">7. Book Appointment for Safety Training</button>
          </div>

          <!-- Tab 1: Basic -->
          <div class="modal-tab-content" id="tab-basic">
            <div class="form-grid-3">
              <div class="form-group">
                <label class="form-label required">Pass Type</label>
                <select class="form-control" name="pass_type" id="passTypeSelect" required>
                  <option value="Contractor" <?= $selectedType['pass'] === 'Contractor' ? 'selected' : '' ?>>Contractor</option>
                  <option value="Supervisor" <?= $selectedType['pass'] === 'Supervisor' ? 'selected' : '' ?>>Supervisor</option>
                  <option value="Representative" <?= $selectedType['pass'] === 'Representative' ? 'selected' : '' ?>>Representative</option>
                  <option value="Workman" <?= $selectedType['pass'] === 'Workman' ? 'selected' : '' ?>>Workman</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Work Order No</label>
                <select class="form-control" name="work_order_no" id="workOrderSelect" required>
                  <option value="">Select work order</option>
                  <?php foreach ($workOptions as $wo): ?>
                    <option value="<?= htmlspecialchars($wo['work_order_no']) ?>"
                            data-project="<?= htmlspecialchars($wo['project_name'] ?? '') ?>"
                            data-project-no="<?= htmlspecialchars($wo['project_no'] ?? $wo['work_order_no']) ?>"
                            data-department="<?= htmlspecialchars($wo['department'] ?? '') ?>"
                            data-source="<?= htmlspecialchars($wo['source'] ?? '') ?>">
                      <?= !empty($wo['source']) ? '[' . htmlspecialchars($wo['source']) . '] ' : '' ?><?= htmlspecialchars($wo['work_order_no']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Project No / WBS No</label>
                <select class="form-control" name="project_name" id="projectWbsSelect" required>
                  <option value="">Select project / WBS</option>
                  <?php foreach ($workOptions as $wo): ?>
                    <option value="<?= htmlspecialchars($wo['project_no'] ?? $wo['work_order_no']) ?>" data-work-order="<?= htmlspecialchars($wo['work_order_no']) ?>">
                      <?= !empty($wo['source']) ? '[' . htmlspecialchars($wo['source']) . '] ' : '' ?><?= htmlspecialchars($wo['project_no'] ?? $wo['work_order_no']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Date of Joining</label>
                <input type="date" class="form-control" name="registration_date" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label required">Aadhaar Number <span id="aadhaarStatus" class="badge-status" style="display:none; margin-left:10px;"></span></label>
                <input type="text" class="form-control" name="aadhaar" id="aadhaarInput" maxlength="12" inputmode="numeric" autocomplete="off" required>
              </div>
              <div class="form-group">
                <label class="form-label required">Full Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="form-group">
                <label class="form-label required">Father Name</label>
                <input type="text" class="form-control" name="father_name" required>
              </div>
            </div>
          </div>

          <!-- Tab 2: Personal -->
          <div class="modal-tab-content hidden" id="tab-personal">
            <div class="form-grid-3">
              <div class="form-group">
                <label class="form-label required">Gender</label>
                <select class="form-control" name="gender" required>
                  <option>Male</option><option>Female</option><option>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Date of Birth</label>
                <input type="date" class="form-control" name="dob" id="dobInput" min="<?= $dobMin ?>" max="<?= $dobMax ?>" required>
                <small class="form-hint" id="dobHint">Age must be between <?= (int)$minAge ?> and <?= (int)$maxAge ?> years.</small>
              </div>
              <div class="form-group">
                <label class="form-label required">Marital Status</label>
                <select class="form-control" name="marital_status" required>
                  <option>Single</option><option>Married</option><option>Widowed</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Nationality</label>
                <input type="text" class="form-control" name="nationality" list="nationalityList" value="Indian" required>
                <datalist id="nationalityList">
                  <?php foreach ($nationalityOptions as $nationality): ?>
                    <option value="<?= htmlspecialchars($nationality) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="form-group">
                <label class="form-label">Identification Mark</label>
                <input type="text" class="form-control" name="identification_mark">
              </div>
              <div class="form-group">
                <label class="form-label">Blood Group</label>
                <select class="form-control" name="blood_group">
                  <option value="">Select</option>
                  <option>A+</option><option>A-</option>
                  <option>B+</option><option>B-</option>
                  <option>AB+</option><option>AB-</option>
                  <option>O+</option><option>O-</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Religion</label>
                <input type="text" class="form-control" name="region" list="religionList">
                <datalist id="religionList">
                  <?php foreach ($religionOptions as $religion): ?>
                    <option value="<?= htmlspecialchars($religion) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="form-group">
                <label class="form-label required">Person with Disability</label>
                <select class="form-control" name="pwd_status" required>
                  <option value="">Select</option>
                  <option value="YES">Yes</option>
                  <option value="NO">No</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Passport No</label>
                <input type="text" class="form-control" name="passport_no">
              </div>
              <div class="form-group">
                <label class="form-label">Driving Licence No</label>
                <input type="text" class="form-control" name="driving_licence_no">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
              </div>
              <div class="form-group">
                <label class="form-label">UAN No</label>
                <input type="text" class="form-control" name="uan_number">
              </div>
            </div>
          </div>

          <!-- Tab 3: Address -->
          <div class="modal-tab-content hidden" id="tab-address">
            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label required">Permanent Address</label>
                <textarea class="form-control" name="permanent_address" rows="2" required></textarea>
              </div>
              <div class="form-group">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:5px;">
                  <label class="form-label required" style="margin-bottom:0;">Present Address</label>
                  <button type="button" class="btn btn-sm btn-primary-soft" id="btnCopyPermanentAddress" style="min-height:30px;min-width:auto;padding:5px 10px;">Same as Permanent</button>
                </div>
                <textarea class="form-control" name="present_address" rows="2" required></textarea>
              </div>
            </div>
            <div class="form-grid-3">
              <div class="form-group">
                <label class="form-label required">State</label>
                <select class="form-control" name="state" id="stateSelect" required></select>
                <input type="text" class="form-control" name="state" id="stateInput" required disabled style="display:none;margin-top:0;">
              </div>
              <div class="form-group">
                <label class="form-label required">District</label>
                <select class="form-control" name="district" id="districtSelect" required></select>
                <input type="text" class="form-control" name="district" id="districtInput" required disabled style="display:none;margin-top:0;">
              </div>
              <div class="form-group">
                <label class="form-label required">Pin Code</label>
                <input type="text" class="form-control" name="pincode" maxlength="6" required>
              </div>
            </div>
            <div class="form-grid-3">
              <div class="form-group">
                <label class="form-label required">Mobile Number</label>
                <input type="tel" class="form-control" name="mobile" maxlength="10" required>
              </div>
            </div>
          </div>

          <!-- Tab 4: Work -->
          <div class="modal-tab-content hidden" id="tab-work">
            <div class="form-grid-3">
              <div class="form-group">
                <label class="form-label required">Department</label>
                <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($department_name) ?>" <?= $department_name !== '' ? 'readonly style="background-color: #f1f5f9;"' : '' ?> required>
              </div>
              <div class="form-group">
                <label class="form-label">Years of Experience</label>
                <input type="number" min="0" step="0.5" class="form-control" name="experience">
              </div>
              <div class="work-flow-panel" id="annexure4aWorkFlow">
                <div class="work-flow-title"><i class="fas fa-project-diagram"></i> Education to Job Profile Flow</div>
                <div class="work-flow-steps">
                  <div class="work-flow-step">
                    <div class="flow-step-head"><span class="flow-step-number">1</span> Category</div>
                    <select class="flow-control" id="categoryOptions" aria-label="Category selection" required></select>
                  </div>
                  <div class="work-flow-step">
                    <div class="flow-step-head"><span class="flow-step-number">2</span> Qualification</div>
                    <select class="flow-control" id="qualificationOptions" aria-label="Qualification selection" required disabled></select>
                  </div>
                  <div class="work-flow-step">
                    <div class="flow-step-head"><span class="flow-step-number">3</span> Job Profile</div>
                    <select class="flow-control" id="jobProfileOptions" aria-label="Job profile selection" required disabled></select>
                  </div>
                </div>
                <input type="hidden" name="nature_of_work" required>
                <input type="hidden" name="skill_category" required>
                <input type="hidden" name="education" id="educationQualification" required>
                <input type="hidden" name="role_type" required>
              </div>
              <div class="form-group">
                <label class="form-label required">EPF Registered</label>
                <select class="form-control" name="epf_registered_worker" id="epfRegisteredWorker" required>
                  <option value="">Select</option>
                  <option value="YES">Yes</option>
                  <option value="NO">No</option>
                </select>
              </div>
              <div class="form-group conditional-field hidden" id="epfNumberWrap">
                <label class="form-label required">UAN Number</label>
                <input type="text" class="form-control" name="pf_no" id="epfNumberInput">
              </div>
              <div class="form-group">
                <label class="form-label required">ESI Registered</label>
                <select class="form-control" name="esi_registered_worker" id="esiRegisteredWorker" required>
                  <option value="">Select</option>
                  <option value="YES">Yes</option>
                  <option value="NO">No</option>
                </select>
              </div>
              <div class="form-group conditional-field hidden" id="esiNumberWrap">
                <label class="form-label required">ESI Number</label>
                <input type="text" class="form-control" name="esi_no" id="esiNumberInput">
              </div>
              <div class="form-group">
                <label class="form-label required">Certified Wage Rate</label>
                <input type="number" class="form-control" name="certified_wage_rate" id="certifiedWageRate" min="<?= htmlspecialchars((string)$minimumCertifiedWage) ?>" step="0.01" required>
                <small class="form-hint" id="certifiedWageHint">Select category to apply approved wage rate. Minimum allowed: <?= number_format((float)$minimumCertifiedWage, 2) ?></small>
              </div>
              <div class="form-group">
                <label class="form-label required">Language Preferred for Safety Induction</label>
                <select class="form-control" name="safety_language" required>
                  <option value="">Select</option>
                  <option value="Hindi">Hindi</option>
                  <option value="Malayalam">Malayalam</option>
                  <option value="Tamil">Tamil</option>
                  <option value="English">English</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required">Executing Officer E-Code <span id="eoCodeStatus" class="badge-status" style="display:none;margin-left:10px;"></span></label>
                <input type="text" class="form-control" name="executing_officer_code" id="executingOfficerCode" maxlength="50" required style="text-transform:uppercase;">
                <small class="form-hint">E-Code will be verified from User Master/SAP/SQL Server.</small>
              </div>
              <div class="form-group">
                <label class="form-label">Executing Officer Name</label>
                <input type="text" class="form-control" name="executing_officer_name" id="executingOfficerName" readonly>
              </div>
            </div>
          </div>

          <!-- Tab 5: Documents -->
          <div class="modal-tab-content hidden" id="tab-docs">
            <div class="form-grid-3">
                <div class="doc-card">
                <label class="form-label required">Photo</label>
                <input type="file" class="form-control" name="photo" accept=".jpg,.jpeg,image/jpeg" data-max-size="2097152" required>
                <small class="form-hint">JPG/JPEG only, max 2 MB.</small>
              </div>
              <div class="doc-card">
                <label class="form-label required">Aadhaar Copy</label>
                <input type="file" class="form-control" name="aadhaar_doc" accept=".pdf,application/pdf" data-max-size="5242880" required>
                <small class="form-hint">PDF only, max 5 MB.</small>
              </div>
              <div class="doc-card">
                <label class="form-label">Training Attendance Approval by Executing Officer / Mentor</label>
                <input type="file" class="form-control" name="training_approval_doc" accept=".pdf,application/pdf" data-max-size="5242880">
                <small class="form-hint">PDF only, max 5 MB.</small>
              </div>
            </div>
          </div>

          <!-- Tab 6: Safety Fee Payment -->
          <div class="modal-tab-content hidden" id="tab-payment">
            <div class="training-booking-box" id="pwoPaymentOptionBox" style="margin-bottom:12px;display:none;">
              <label class="choice-row active">
                <input type="radio" name="safety_fee_payment_option" value="pay_now" checked>
                <span>Pay Safety Fee Now</span>
              </label>
              <label class="choice-row">
                <input type="radio" name="safety_fee_payment_option" value="pay_later">
                <span>Pay Safety Fee Later</span>
              </label>
            </div>
            <div class="alert alert-info" id="pwoPaymentRequiredNote" style="margin:0 0 12px;display:none;">
              Pay Safety Fee first. Safety Training & Seat Booking will open after payment.
            </div>
            <div class="alert alert-info" id="nonPwoPaymentNote" style="margin:0 0 12px;">
              Safety Fee Payment is applicable only for PWO. For non-PWO, continue to Safety Seat Booking.
            </div>
          </div>

          <!-- Tab 7: Book Appointment for Safety Training -->
          <div class="modal-tab-content hidden" id="tab-training">
            <div class="training-booking-box" id="trainingBookingChoiceBox">
              <label class="choice-row">
                <input type="radio" name="training_booking_choice" value="book_now" checked>
                <span>I need to book an appointment for Safety Training</span>
              </label>
              <label class="choice-row" id="trainingLaterChoiceRow">
                <input type="radio" name="training_booking_choice" value="not_now">
                <span id="trainingLaterChoiceText">Save as draft and book Safety Training later</span>
              </label>
            </div>
            <div id="trainingBookingForm" class="training-booking-form hidden">
              <div class="form-grid-3">
                <div class="form-group">
                  <label class="form-label">Aadhaar No</label>
                  <input type="text" class="form-control" id="trainingAadhaarDisplay" readonly>
                  <small class="form-hint">Auto fetched from Basic Info.</small>
                </div>
                <div class="form-group">
                  <label class="form-label">Name</label>
                  <input type="text" class="form-control" id="trainingNameDisplay" readonly>
                  <small class="form-hint">Auto fetched from Basic Info.</small>
                </div>
                <div class="form-group">
                  <label class="form-label required">Language of Training</label>
                  <input type="text" class="form-control" id="trainingBookingLanguageDisplay" readonly>
                  <input type="hidden" name="training_booking_language" id="trainingBookingLanguage" value="Malayalam">
                  <small class="form-hint">Auto fetched from Safety Language.</small>
                </div>
                <div class="form-group">
                  <label class="form-label required">Select Training Date</label>
                  <select class="form-control" name="training_booking_date" id="trainingBookingDate">
                    <option value="">Select scheduled date</option>
                  </select>
                  <small class="form-hint" id="trainingDateHint">Scheduled batches will appear after selecting language.</small>
                </div>
                <div class="form-group">
                  <label class="form-label required">Session</label>
                  <select class="form-control" name="training_booking_session" id="trainingBookingSession">
                    <option value="">Select session</option>
                    <option value="FN">FN</option>
                    <option value="AN">AN</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="alert alert-info" style="margin-top:12px;">
              Save Draft will not submit this entitlement for processing. Training can be booked later from the Book Safety Training menu.
            </div>
          </div>

          <div class="enroll-actions">
            <div class="enroll-actions-left">
            </div>
            <div class="enroll-actions-right">
              <button type="button" class="btn btn-outline" id="btnPrevTab">Previous</button>
              <button type="button" class="btn btn-primary-soft" id="btnNextTab">Next</button>
              <button type="button" class="btn btn-primary-soft" id="btnSaveDraft" style="display:none;">Save Draft</button>
              <button type="button" class="btn btn-primary" id="btnSubmit" style="display:none;">Submit Entitlement</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- View Modal (Original Style) -->
    <div id="viewModal" class="modal-overlay hidden">
      <div class="modal-box" style="max-width:600px;">
        <div class="modal-header">
          <h3 class="modal-title">Worker Profile</h3>
          <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div id="viewContent" style="padding:20px;"></div>
      </div>
    </div>

    <div id="submitPreviewModal" class="modal-overlay hidden">
      <div class="modal-box" style="max-width:980px;">
        <div class="modal-header">
          <h3 class="modal-title">Preview Entitlement Details</h3>
          <button class="modal-close" type="button" onclick="closeSubmitPreview()">&times;</button>
        </div>
        <div style="padding:20px;">
          <div id="submitPreviewContent" class="preview-grid"></div>
          <div class="preview-question">Are you sure to submit?</div>
          <label class="choice-row" style="margin-top:16px;">
            <input type="checkbox" id="submitVerifiedCheckbox">
            <span>I verified the information.</span>
          </label>
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <button type="button" class="btn btn-outline" onclick="closeSubmitPreview()">No, Back</button>
            <button type="button" class="btn btn-primary" id="btnProceedSubmit">Yes, Proceed to Submit</button>
          </div>
        </div>
      </div>
    </div>

    <!-- PDF Generation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <!-- Hidden Temporary ID Card Template (Standard CR80 Size) -->
    <div id="tempCardTemplate" style="position: absolute; top: 0; left: 0; width: 0; height: 0; overflow: hidden; opacity: 0; pointer-events: none; z-index: -1;">
      <div id="id-card-content" style="width: 3.375in; height: 2.125in; border: 1px solid #1e3a8a; border-radius: 8px; font-family: 'Arial', sans-serif; background:#fff; color:#000; overflow:hidden; position:relative; box-sizing: border-box;">
        
        <!-- Blue Header Strip -->
        <div style="background: #1e3a8a; color: #fff; padding: 5px 10px; display: flex; align-items: center; justify-content: space-between;">
          <div style="font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Contractor Labour Management</div>
          <div style="font-size: 7px; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;">TEMP PASS</div>
        </div>

        <div style="display: flex; padding: 10px; gap: 12px; height: calc(100% - 45px);">
          <!-- Left: Photo -->
          <div style="width: 0.8in; height: 1in; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; background: #f8fafc; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
             <img id="pdf-photo" src="" crossorigin="anonymous" style="width: 100%; height: 100%; object-fit: cover; display: none;">
             <div id="pdf-photo-placeholder" style="font-size: 8px; color: #94a3b8; font-weight: bold;">PHOTO</div>
          </div>
          
          <!-- Right: Details -->
          <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 4px;">
            <div style="line-height: 1.1;">
               <span style="font-size: 6px; color: #64748b; text-transform: uppercase; font-weight: bold;">Name</span>
               <div id="pdf-name" style="font-size: 11px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
            </div>
            <div style="line-height: 1.1;">
               <span style="font-size: 6px; color: #64748b; text-transform: uppercase; font-weight: bold;">Temporary ID</span>
               <div id="pdf-tempid" style="font-size: 12px; font-weight: 900; color: #2563eb;"></div>
            </div>
            <div style="line-height: 1.1;">
               <span style="font-size: 6px; color: #64748b; text-transform: uppercase; font-weight: bold;">Contractor</span>
               <div id="pdf-contractor" style="font-size: 9px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 2px;">
              <div style="line-height: 1.1;">
                 <span style="font-size: 6px; color: #64748b; text-transform: uppercase; font-weight: bold;">Trade</span>
                 <div id="pdf-trade" style="font-size: 8px; font-weight: 600;"></div>
              </div>
              <div style="line-height: 1.1;">
                 <span style="font-size: 6px; color: #64748b; text-transform: uppercase; font-weight: bold;">Aadhaar</span>
                 <div id="pdf-aadhaar" style="font-size: 8px; font-weight: 600;"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Footer Info -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: #f1f5f9; padding: 4px 10px; font-size: 6px; color: #475569; text-align: center; border-top: 1px solid #e2e8f0;">
          VALID FOR 7 DAYS • SYSTEM GENERATED • MUST BE CARRIED ON DUTY
        </div>
      </div>
    </div>

    <script>
    async function downloadTempCard(w) {
        // Populate template
        document.getElementById('pdf-name').innerText = w.name;
        document.getElementById('pdf-tempid').innerText = w.temp_id;
        document.getElementById('pdf-contractor').innerText = "<?= htmlspecialchars($contractor['contractor_name'] ?? 'N/A') ?>";
        document.getElementById('pdf-trade').innerText = w.nature_of_work;
        document.getElementById('pdf-aadhaar').innerText = w.aadhaar;
        
        const photoImg = document.getElementById('pdf-photo');
        const placeholder = document.getElementById('pdf-photo-placeholder');
        
        if (w.photo) {
            photoImg.src = "../../uploads/workers/" + w.photo;
            // Wait for image to load to ensure it appears in PDF
            await new Promise((resolve) => {
                if (photoImg.complete) resolve();
                else {
                    photoImg.onload = resolve;
                    photoImg.onerror = resolve;
                }
            });
            photoImg.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            photoImg.style.display = 'none';
            placeholder.style.display = 'flex';
        }

        // Delay to ensure rendering is complete
        setTimeout(async () => {
            const element = document.getElementById('id-card-content');
            const options = {
                margin:       0,
                filename:     'TempID_' + w.temp_id + '.pdf',
                image:        { type: 'jpeg', quality: 1.0 },
                html2canvas:  { scale: 2, useCORS: true, allowTaint: true, scrollY: 0 },
                jsPDF:        { unit: 'in', format: [3.375, 2.125], orientation: 'landscape', compress: true }
            };

            try {
                await html2pdf().set(options).from(element).save();
            } catch (err) {
                console.error('PDF generation failed', err);
                alert('Failed to generate PDF. Please try again.');
            }
        }, 600);
    }

    async function downloadWorkerList() {
        const table = document.getElementById('workerTable');
        
        // Clone table to remove interactive elements and styling issues
        const clone = table.cloneNode(true);
        // Remove the "Actions" column from clone
        clone.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());
        
        const wrapper = document.createElement('div');
        wrapper.style.padding = '20px';
        wrapper.style.background = 'white';
        wrapper.innerHTML = `
            <h2 style="text-align:center; color:#1e3a8a;">Worker Enrollment List</h2>
            <p style="text-align:center; font-size:12px; color:#64748b;">Generated on: <?= date('d-M-Y H:i') ?></p>
        `;
        wrapper.appendChild(clone);
        
        const options = {
            margin:       [0.5, 0.3],
            filename:     'Worker_List_<?= date('Y-m-d') ?>.pdf',
            image:        { type: 'jpeg', quality: 0.95 },
            html2canvas:  { scale: 1.5, useCORS: true, allowTaint: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        try {
            await html2pdf().set(options).from(wrapper).save();
        } catch (err) {
            console.error('PDF generation failed', err);
            alert('Failed to generate PDF. Try smaller list or different browser.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    const listSection = document.getElementById('listSection');
        const formSection = document.getElementById('formSection');
        const viewModal = document.getElementById('viewModal');
        const form = document.getElementById('enrollForm');
        const tabOrder = ['basic', 'personal', 'address', 'work', 'docs', 'payment', 'training'];
        const scheduledTrainingSessions = <?= json_encode(db_fetch_all($conn, "SELECT id, batch_number, training_date, session_name, language_name, capacity FROM training_class_batches WHERE training_date >= CURDATE() AND LOWER(COALESCE(status, 'open')) IN ('open','draft','scheduled') ORDER BY training_date ASC, session_name ASC LIMIT 100"), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        
        const defaultDepartment = <?= json_encode($department_name) ?>;
        const workOptions = <?= json_encode($workOptions, JSON_UNESCAPED_SLASHES) ?>;
        const requestedPassType = <?= json_encode($selectedType['pass']) ?>;
        const requestedPassLabel = <?= json_encode($selectedType['label']) ?>;
        const prefillAadhaar = <?= json_encode($prefillAadhaar) ?>;
        const currentContractorId = <?= $c_id ? (int)$c_id : 0 ?>;
        const dobInput = document.getElementById('dobInput');
        const minAllowedAge = <?= json_encode((int)$minAge) ?>;
        const maxAllowedAge = <?= json_encode((int)$maxAge) ?>;
        const minDob = dobInput ? new Date(dobInput.min + 'T00:00:00') : null;
        const maxDob = dobInput ? new Date(dobInput.max + 'T00:00:00') : null;
        const photoMaxSize = 2 * 1024 * 1024;
        const pdfMaxSize = 5 * 1024 * 1024;
        const minimumCertifiedWage = <?= json_encode((float)$minimumCertifiedWage) ?>;
        const activeCertifiedWages = <?= json_encode($activeCertifiedWages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const passTypeSelect = document.getElementById('passTypeSelect');
        const certifiedWageInput = document.getElementById('certifiedWageRate');
        const certifiedWageHint = document.getElementById('certifiedWageHint');
        const aadhaarInput = document.getElementById('aadhaarInput');
        const executingOfficerCodeInput = document.getElementById('executingOfficerCode');
        const executingOfficerNameInput = document.getElementById('executingOfficerName');
        const executingOfficerStatus = document.getElementById('eoCodeStatus');
        let executingOfficerVerifyTimer = null;
        let executingOfficerVerifyRequest = 0;
        const indianStateDistricts = {
          'Andhra Pradesh': ['Anantapur', 'Chittoor', 'East Godavari', 'Guntur', 'Krishna', 'Kurnool', 'Prakasam', 'SPSR Nellore', 'Srikakulam', 'Visakhapatnam', 'Vizianagaram', 'West Godavari', 'YSR Kadapa'],
          'Arunachal Pradesh': ['Anjaw', 'Changlang', 'East Kameng', 'East Siang', 'Itanagar Capital Complex', 'Lohit', 'Lower Dibang Valley', 'Lower Subansiri', 'Namsai', 'Papum Pare', 'Tawang', 'Tirap', 'Upper Siang', 'Upper Subansiri', 'West Kameng', 'West Siang'],
          'Assam': ['Baksa', 'Barpeta', 'Bongaigaon', 'Cachar', 'Darrang', 'Dhemaji', 'Dhubri', 'Dibrugarh', 'Goalpara', 'Golaghat', 'Hailakandi', 'Jorhat', 'Kamrup', 'Kamrup Metropolitan', 'Karimganj', 'Lakhimpur', 'Nagaon', 'Nalbari', 'Sivasagar', 'Sonitpur', 'Tinsukia'],
          'Bihar': ['Araria', 'Aurangabad', 'Begusarai', 'Bhagalpur', 'Bhojpur', 'Darbhanga', 'Gaya', 'Katihar', 'Madhubani', 'Muzaffarpur', 'Nalanda', 'Patna', 'Purnia', 'Samastipur', 'Saran', 'Siwan'],
          'Chhattisgarh': ['Balod', 'Bastar', 'Bilaspur', 'Durg', 'Janjgir-Champa', 'Korba', 'Raipur', 'Rajnandgaon', 'Surguja'],
          'Goa': ['North Goa', 'South Goa'],
          'Gujarat': ['Ahmedabad', 'Amreli', 'Anand', 'Banaskantha', 'Bharuch', 'Bhavnagar', 'Gandhinagar', 'Jamnagar', 'Junagadh', 'Kutch', 'Mehsana', 'Rajkot', 'Surat', 'Vadodara', 'Valsad'],
          'Haryana': ['Ambala', 'Bhiwani', 'Faridabad', 'Gurugram', 'Hisar', 'Karnal', 'Kurukshetra', 'Panipat', 'Rewari', 'Rohtak', 'Sonipat', 'Yamunanagar'],
          'Himachal Pradesh': ['Bilaspur', 'Chamba', 'Hamirpur', 'Kangra', 'Kinnaur', 'Kullu', 'Mandi', 'Shimla', 'Sirmaur', 'Solan', 'Una'],
          'Jharkhand': ['Bokaro', 'Dhanbad', 'East Singhbhum', 'Giridih', 'Hazaribagh', 'Ranchi', 'West Singhbhum'],
          'Karnataka': ['Bagalkot', 'Ballari', 'Belagavi', 'Bengaluru Rural', 'Bengaluru Urban', 'Bidar', 'Chikkamagaluru', 'Dakshina Kannada', 'Davanagere', 'Dharwad', 'Hassan', 'Kalaburagi', 'Mysuru', 'Raichur', 'Shivamogga', 'Tumakuru', 'Udupi', 'Vijayapura'],
          'Kerala': ['Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad'],
          'Madhya Pradesh': ['Bhopal', 'Chhindwara', 'Gwalior', 'Indore', 'Jabalpur', 'Rewa', 'Sagar', 'Satna', 'Ujjain'],
          'Maharashtra': ['Ahmednagar', 'Akola', 'Amravati', 'Aurangabad', 'Jalgaon', 'Kolhapur', 'Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Nashik', 'Pune', 'Raigad', 'Satara', 'Solapur', 'Thane'],
          'Manipur': ['Bishnupur', 'Churachandpur', 'Imphal East', 'Imphal West', 'Thoubal', 'Ukhrul'],
          'Meghalaya': ['East Khasi Hills', 'East Garo Hills', 'Jaintia Hills', 'Ri Bhoi', 'West Garo Hills', 'West Khasi Hills'],
          'Mizoram': ['Aizawl', 'Champhai', 'Kolasib', 'Lunglei', 'Mamit', 'Serchhip'],
          'Nagaland': ['Dimapur', 'Kohima', 'Mokokchung', 'Mon', 'Phek', 'Tuensang', 'Wokha', 'Zunheboto'],
          'Odisha': ['Angul', 'Balasore', 'Bargarh', 'Bhadrak', 'Cuttack', 'Ganjam', 'Jagatsinghpur', 'Kendrapara', 'Khordha', 'Puri', 'Sambalpur', 'Sundargarh'],
          'Punjab': ['Amritsar', 'Bathinda', 'Firozpur', 'Gurdaspur', 'Hoshiarpur', 'Jalandhar', 'Ludhiana', 'Patiala', 'SAS Nagar', 'Sangrur'],
          'Rajasthan': ['Ajmer', 'Alwar', 'Bharatpur', 'Bikaner', 'Jaipur', 'Jodhpur', 'Kota', 'Udaipur'],
          'Sikkim': ['East Sikkim', 'North Sikkim', 'South Sikkim', 'West Sikkim'],
          'Tamil Nadu': ['Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore', 'Erode', 'Kanchipuram', 'Kanyakumari', 'Madurai', 'Nilgiris', 'Salem', 'Thanjavur', 'Thiruvallur', 'Tiruchirappalli', 'Tirunelveli', 'Vellore'],
          'Telangana': ['Adilabad', 'Hyderabad', 'Karimnagar', 'Khammam', 'Mahabubnagar', 'Medak', 'Nalgonda', 'Nizamabad', 'Rangareddy', 'Warangal'],
          'Tripura': ['Dhalai', 'Gomati', 'North Tripura', 'South Tripura', 'West Tripura'],
          'Uttar Pradesh': ['Agra', 'Aligarh', 'Bareilly', 'Ghaziabad', 'Gorakhpur', 'Kanpur Nagar', 'Lucknow', 'Meerut', 'Prayagraj', 'Varanasi'],
          'Uttarakhand': ['Almora', 'Chamoli', 'Dehradun', 'Haridwar', 'Nainital', 'Pauri Garhwal', 'Tehri Garhwal', 'Udham Singh Nagar'],
          'West Bengal': ['Bankura', 'Darjeeling', 'Hooghly', 'Howrah', 'Jalpaiguri', 'Kolkata', 'Malda', 'Murshidabad', 'Nadia', 'North 24 Parganas', 'Paschim Bardhaman', 'Purba Medinipur', 'South 24 Parganas'],
          'Andaman and Nicobar Islands': ['Nicobar', 'North and Middle Andaman', 'South Andaman'],
          'Chandigarh': ['Chandigarh'],
          'Dadra and Nagar Haveli and Daman and Diu': ['Dadra and Nagar Haveli', 'Daman', 'Diu'],
          'Delhi': ['Central Delhi', 'East Delhi', 'New Delhi', 'North Delhi', 'South Delhi', 'West Delhi'],
          'Jammu and Kashmir': ['Anantnag', 'Baramulla', 'Jammu', 'Kathua', 'Srinagar', 'Udhampur'],
          'Ladakh': ['Kargil', 'Leh'],
          'Lakshadweep': ['Lakshadweep'],
          'Puducherry': ['Karaikal', 'Mahe', 'Puducherry', 'Yanam']
        };
        const masterStateDistricts = <?= json_encode($stateDistrictMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        if (Object.keys(masterStateDistricts).length) {
          Object.entries(masterStateDistricts).forEach(([state, districts]) => {
            indianStateDistricts[state] = Array.from(new Set([...(indianStateDistricts[state] || []), ...districts]));
          });
        }

        function validateDobAge(showMessage = true) {
          if (!dobInput || !dobInput.value) return true;
          const year = String(dobInput.value).split('-')[0] || '';
          if (year.length !== 4) {
            dobInput.setCustomValidity('Date of Birth year must be 4 digits.');
          } else {
            const dob = new Date(dobInput.value + 'T00:00:00');
            if (maxDob && dob > maxDob) {
              dobInput.setCustomValidity(`Worker age is below ${minAllowedAge} years. Registration is not allowed.`);
            } else if (minDob && dob < minDob) {
              dobInput.setCustomValidity(`Worker age is above ${maxAllowedAge} years. Registration is not allowed.`);
            } else {
              dobInput.setCustomValidity('');
            }
          }
          if (showMessage && !dobInput.checkValidity()) {
            dobInput.reportValidity();
          }
          return dobInput.checkValidity();
        }

        function validateAadhaarNumber(showMessage = true) {
          if (!aadhaarInput) return true;
          aadhaarInput.value = aadhaarInput.value.replace(/\D/g, '').slice(0, 12);
          if (!aadhaarInput.value) {
            aadhaarInput.setCustomValidity('');
            return true;
          }
          const isValid = /^\d{12}$/.test(aadhaarInput.value);
          aadhaarInput.setCustomValidity(isValid ? '' : 'Please enter correct Aadhar number');
          if (!isValid && showMessage) {
            activateTab('basic');
            notify('Invalid Aadhar Number', 'Please enter correct Aadhar number', 'warning');
            setTimeout(() => aadhaarInput.focus(), 120);
          }
          return isValid;
        }

        function normalizePassLimitType(passType) {
          const raw = String(passType || '').toLowerCase();
          if (raw.includes('supervisor')) return 'Supervisor';
          if (raw.includes('representative')) return 'Representative';
          if (raw.includes('contractor')) return 'Contractor';
          return 'Workman';
        }

        function validateSelectedPassLimit(count = 1) {
          if (typeof PassLimitValidator === 'undefined' || !currentContractorId) return true;
          const limitType = normalizePassLimitType(passTypeSelect?.value || requestedPassType);
          return PassLimitValidator.validate(limitType, count);
        }

        function validateWorkerFile(input, showMessage = true) {
          if (!input || !input.files || !input.files[0]) return true;
          const file = input.files[0];
          const ext = file.name.split('.').pop().toLowerCase();
          let message = '';
          if (input.name === 'photo') {
            if (!['jpg', 'jpeg'].includes(ext) || (file.type && file.type !== 'image/jpeg')) {
              message = 'Photo must be JPG/JPEG only.';
            } else if (file.size > photoMaxSize) {
              message = 'Photo size must be 2 MB or less.';
            }
          } else {
            if (ext !== 'pdf' || (file.type && file.type !== 'application/pdf')) {
              message = 'Document must be PDF only.';
            } else if (file.size > pdfMaxSize) {
              message = 'Document size must be 5 MB or less.';
            }
          }
          input.setCustomValidity(message);
          if (message && showMessage) input.reportValidity();
          return message === '';
        }

        function setExecutingOfficerStatus(message = '', type = '') {
          if (!executingOfficerStatus) return;
          executingOfficerStatus.textContent = message;
          executingOfficerStatus.style.display = message ? 'inline-block' : 'none';
          executingOfficerStatus.className = 'badge-status ' + (type || '');
        }

        async function verifyExecutingOfficerCode(showMessage = true) {
          const code = (executingOfficerCodeInput?.value || '').trim().toUpperCase();
          if (executingOfficerCodeInput) executingOfficerCodeInput.value = code;
          if (!code) {
            if (executingOfficerNameInput) executingOfficerNameInput.value = '';
            setExecutingOfficerStatus('', '');
            return false;
          }

          const requestId = ++executingOfficerVerifyRequest;
          setExecutingOfficerStatus('Checking...', 'badge-warning');
          try {
            const res = await fetch('../../api/verify_execution_officer.php?code=' + encodeURIComponent(code));
            const data = await res.json();
            if (requestId !== executingOfficerVerifyRequest) return false;
            if (data.success && data.data) {
              if (executingOfficerNameInput) executingOfficerNameInput.value = data.data.name || '';
              setExecutingOfficerStatus('Verified', 'badge-success');
              return true;
            }
            if (executingOfficerNameInput) executingOfficerNameInput.value = '';
            setExecutingOfficerStatus('Invalid', 'badge-danger');
            if (showMessage) {
              activateTab('work');
              notify('Invalid E-Code', data.message || 'Executing Officer E-Code User Master/SAP/SQL Server mein nahi mila.', 'error');
            }
            return false;
          } catch (err) {
            if (requestId !== executingOfficerVerifyRequest) return false;
            if (executingOfficerNameInput) executingOfficerNameInput.value = '';
            setExecutingOfficerStatus('Error', 'badge-danger');
            if (showMessage) notify('E-Code Check Failed', err.message || 'Unable to verify E-Code.', 'error');
            return false;
          }
        }

        function getFieldLabel(field) {
          if (!field) return 'Required field';
          const group = field.closest('.form-group, .doc-card, .work-flow-step');
          const label = group?.querySelector('.form-label, .flow-step-head');
          if (label) {
            return label.textContent.replace('*', '').replace(/\s+/g, ' ').trim();
          }
          return field.getAttribute('aria-label') || field.name || 'Required field';
        }

        function showInvalidFieldMessage(field) {
          if (!field) return;
          const label = getFieldLabel(field);
          const message = field.validity.valueMissing
            ? `${label} is required. Please fill this field before submitting.`
            : (field.validationMessage || `${label} is invalid.`);
          notify('Required Field Missing', message, 'warning');
        }

        function focusInvalidField(field) {
          if (!field) return;
          const tabPane = field.closest('.modal-tab-content');
          if (tabPane) {
            activateTab(tabPane.id.replace('tab-', ''));
          }
          setTimeout(() => {
            if (field.type !== 'hidden' && !field.disabled) {
              field.focus();
              field.reportValidity();
            }
          }, 120);
        }

        dobInput?.addEventListener('change', () => validateDobAge(false));
        aadhaarInput?.addEventListener('input', () => validateAadhaarNumber(false));
        aadhaarInput?.addEventListener('blur', () => {
          if (aadhaarInput.value) validateAadhaarNumber(false);
        });
        passTypeSelect?.addEventListener('change', () => validateSelectedPassLimit(1));
        document.querySelectorAll('#tab-docs input[type="file"]').forEach(input => {
          input.addEventListener('change', () => validateWorkerFile(input, true));
        });

        document.getElementById('btnOpenModal').onclick = () => {
          form.reset();
          document.getElementById('workerEditId').value = '';
          document.getElementById('enrollFormTitle').textContent = ' New ' + requestedPassLabel;
          setFieldValue('pass_type', requestedPassType);
          if (prefillAadhaar) {
            setFieldValue('aadhaar', prefillAadhaar);
          }
          const deptField = form.querySelector('[name="department"]');
          if (deptField && defaultDepartment) {
            deptField.value = defaultDepartment;
            deptField.setAttribute('readonly', true);
            deptField.style.backgroundColor = '#f1f5f9';
          }
          const woSelect = form.querySelector('[name="work_order_no"]');
          if (woSelect && workOptions.length === 1) {
            woSelect.value = workOptions[0].work_order_no;
            syncWorkOrderFields(woSelect.value);
          } else {
            refreshWorkflowPaymentState(false);
          }
          const photoInput = form.querySelector('[name="photo"]');
          const requiredDocInputs = form.querySelectorAll('#tab-docs input[type="file"]');
          requiredDocInputs.forEach(input => {
            if (['photo','aadhaar_doc'].includes(input.name)) {
              input.setAttribute('required', 'true');
            }
          });
          listSection.style.display = 'none';
          formSection.style.display = 'block';
          formSection.classList.remove('hidden');
          activateTab('basic');
          updateNationalityLocationMode();
          toggleConditionalRegistration('epf_registered_worker', 'epfNumberWrap', 'epfNumberInput');
          toggleConditionalRegistration('esi_registered_worker', 'esiNumberWrap', 'esiNumberInput');
          if (prefillAadhaar) {
            document.getElementById('aadhaarInput')?.dispatchEvent(new Event('blur'));
          }
        };
        
        function closeForm() {
          formSection.style.display = 'none';
          listSection.style.display = 'block';
          form.reset();
          document.getElementById('workerEditId').value = '';
          document.getElementById('enrollFormTitle').textContent = ' New ' + requestedPassLabel;
          resetWorkFlow();
          updateNationalityLocationMode();
          refreshWorkflowPaymentState(false);
        }
        
        function activateTab(tabId) {
          document.querySelectorAll('.square-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tabId));
          document.querySelectorAll('.modal-tab-content').forEach(c => c.classList.toggle('hidden', c.id !== 'tab-' + tabId));
          const index = tabOrder.indexOf(tabId);
          document.getElementById('btnPrevTab').style.visibility = index <= 0 ? 'hidden' : 'visible';
          document.getElementById('btnNextTab').style.display = index === tabOrder.length - 1 ? 'none' : 'inline-flex';
          const draftBtn = document.getElementById('btnSaveDraft');
          if (draftBtn) draftBtn.style.display = index === tabOrder.length - 1 ? 'inline-flex' : 'none';
          document.getElementById('btnSubmit').style.display = index === tabOrder.length - 1 ? 'inline-flex' : 'none';
          if (tabId === 'payment') refreshWorkflowPaymentState(false);
          if (tabId === 'training') refreshTrainingBookingFields();
        }
        
        document.querySelectorAll('.square-tab').forEach(btn => {
          btn.onclick = () => activateTab(btn.dataset.tab);
        });

        document.getElementById('btnPrevTab').onclick = () => {
          const current = document.querySelector('.square-tab.active')?.dataset.tab || 'basic';
          const index = Math.max(0, tabOrder.indexOf(current) - 1);
          activateTab(tabOrder[index]);
        };
        document.getElementById('btnNextTab').onclick = () => {
          const current = document.querySelector('.square-tab.active')?.dataset.tab || 'basic';
          const index = Math.min(tabOrder.length - 1, tabOrder.indexOf(current) + 1);
          activateTab(tabOrder[index]);
        };

        function syncWorkOrderFields(workOrderNo) {
          const option = workOptions.find(item => String(item.work_order_no) === String(workOrderNo));
          const projectSelect = form.querySelector('[name="project_name"]');
          const deptField = form.querySelector('[name="department"]');
          const sourceInput = document.getElementById('workOrderSource');
          if (projectSelect && option) projectSelect.value = option.project_no || option.work_order_no || '';
          if (sourceInput) {
            const inferred = String(option?.source || '').toUpperCase()
              || (String(workOrderNo || '').toUpperCase().indexOf('PWO') === 0 ? 'PWO' : '');
            sourceInput.value = inferred;
          }
          if (deptField && option && option.department) {
            deptField.value = option.department;
            deptField.setAttribute('readonly', true);
            deptField.style.backgroundColor = '#f1f5f9';
          }
          refreshWorkflowPaymentState();
        }

        form.querySelector('[name="work_order_no"]')?.addEventListener('change', (e) => syncWorkOrderFields(e.target.value));
        form.querySelector('[name="project_name"]')?.addEventListener('change', (e) => {
          const selected = e.target.selectedOptions[0];
          const workOrderNo = selected?.dataset.workOrder || '';
          if (workOrderNo) {
            form.querySelector('[name="work_order_no"]').value = workOrderNo;
            syncWorkOrderFields(workOrderNo);
          }
        });

        document.getElementById('btnCopyPermanentAddress')?.addEventListener('click', () => {
          const permanent = form.querySelector('[name="permanent_address"]');
          const present = form.querySelector('[name="present_address"]');
          if (permanent && present) {
            present.value = permanent.value;
            present.dispatchEvent(new Event('input', { bubbles: true }));
          }
        });

        function isIndianNationality() {
          return String(form.querySelector('[name="nationality"]')?.value || '').trim().toLowerCase() === 'indian';
        }

        function populateIndianStateOptions(selectedState = '') {
          const stateSelect = document.getElementById('stateSelect');
          if (!stateSelect) return;
          const states = Object.keys(indianStateDistricts);
          stateSelect.innerHTML = '<option value="">Select State</option>' + states.map(state => `<option value="${state}">${state}</option>`).join('');
          if (selectedState && states.includes(selectedState)) {
            stateSelect.value = selectedState;
          }
        }

        function populateIndianDistrictOptions(state, selectedDistrict = '') {
          const districtSelect = document.getElementById('districtSelect');
          if (!districtSelect) return;
          const districts = indianStateDistricts[state] || [];
          districtSelect.innerHTML = '<option value="">Select District</option>' + districts.map(district => `<option value="${district}">${district}</option>`).join('');
          if (selectedDistrict && districts.includes(selectedDistrict)) {
            districtSelect.value = selectedDistrict;
          }
        }

        function setStateDistrictValues(state = '', district = '') {
          const stateSelect = document.getElementById('stateSelect');
          const districtSelect = document.getElementById('districtSelect');
          const stateInput = document.getElementById('stateInput');
          const districtInput = document.getElementById('districtInput');
          if (isIndianNationality()) {
            populateIndianStateOptions(state);
            populateIndianDistrictOptions(stateSelect?.value || state, district);
          } else {
            if (stateInput) stateInput.value = state || '';
            if (districtInput) districtInput.value = district || '';
          }
        }

        function updateNationalityLocationMode() {
          const stateSelect = document.getElementById('stateSelect');
          const districtSelect = document.getElementById('districtSelect');
          const stateInput = document.getElementById('stateInput');
          const districtInput = document.getElementById('districtInput');
          const currentState = stateSelect && !stateSelect.disabled ? stateSelect.value : (stateInput?.value || '');
          const currentDistrict = districtSelect && !districtSelect.disabled ? districtSelect.value : (districtInput?.value || '');
          const indian = isIndianNationality();

          if (stateSelect && stateInput && districtSelect && districtInput) {
            stateSelect.disabled = !indian;
            districtSelect.disabled = !indian;
            stateInput.disabled = indian;
            districtInput.disabled = indian;
            stateSelect.style.display = indian ? '' : 'none';
            districtSelect.style.display = indian ? '' : 'none';
            stateInput.style.display = indian ? 'none' : '';
            districtInput.style.display = indian ? 'none' : '';
          }

          if (indian) {
            populateIndianStateOptions(currentState);
            populateIndianDistrictOptions(stateSelect?.value || currentState, currentDistrict);
          } else {
            if (stateInput) stateInput.value = currentState;
            if (districtInput) districtInput.value = currentDistrict;
          }
        }

        form.querySelector('[name="nationality"]')?.addEventListener('input', updateNationalityLocationMode);
        document.getElementById('stateSelect')?.addEventListener('change', (e) => populateIndianDistrictOptions(e.target.value, ''));
        populateIndianStateOptions();
        populateIndianDistrictOptions('', '');
        updateNationalityLocationMode();

        function toggleConditionalRegistration(selectName, wrapId, inputId) {
          const value = form.querySelector(`[name="${selectName}"]`)?.value || '';
          const wrap = document.getElementById(wrapId);
          const input = document.getElementById(inputId);
          const show = value === 'YES';
          if (wrap) wrap.classList.toggle('hidden', !show);
          if (input) {
            input.required = show;
            if (!show) input.value = '';
          }
        }
        form.querySelector('[name="epf_registered_worker"]')?.addEventListener('change', () => toggleConditionalRegistration('epf_registered_worker', 'epfNumberWrap', 'epfNumberInput'));
        form.querySelector('[name="esi_registered_worker"]')?.addEventListener('change', () => toggleConditionalRegistration('esi_registered_worker', 'esiNumberWrap', 'esiNumberInput'));

        // Aadhaar Auto-Fill Logic
        document.getElementById('aadhaarInput').addEventListener('blur', async function() {
            const aadhaar = this.value.trim();
            const statusBadge = document.getElementById('aadhaarStatus');
            const sourceInput = document.getElementById('workerSource');
            const formInputs = form.querySelectorAll('input:not([name="aadhaar"]):not([name="work_order_no"]):not([name="project_name"]):not([name="pass_type"]):not([name="registration_date"]), select, textarea');

            if (aadhaar.length === 12) {
                statusBadge.style.display = 'inline-block';
                statusBadge.className = 'badge-status bg-warning text-dark';
                statusBadge.innerText = 'Searching...';

                try {
                    const response = await fetch(`../../api/contractor/fetch_worker_aadhaar.php?aadhaar=${aadhaar}`);
                    const result = await response.json();

                    if (result.success && result.data) {
                        const data = result.data;
                        statusBadge.className = 'badge-status bg-success text-white';
                        statusBadge.innerText = `Found in ${data.source}`;
                        sourceInput.value = data.source;

                        // Auto-fill fields and make them readonly
                        const fieldsToFill = {
                            'name': data.name,
                            'father_name': data.father_name,
                            'gender': data.gender,
                            'dob': data.dob,
                            'marital_status': data.marital_status,
                            'nationality': data.nationality || 'Indian',
                            'mobile': data.mobile,
                            'present_address': data.present_address,
                            'permanent_address': data.permanent_address,
                            'state': data.state,
                            'district': data.district,
                            'department': data.department,
                            'nature_of_work': data.nature_of_work,
                            'skill_category': data.skill_category,
                            'education': data.education,
                            'role_type': data.role_type || data.skill_category,
                            'blood_group': data.blood_group,
                            'pf_no': data.pf_no,
                            'esi_no': data.esi_no,
                            'uan_number': data.uan_number,
                            'email': data.email
                        };

                        for (const [key, value] of Object.entries(fieldsToFill)) {
                            const field = form.querySelector(`[name="${key}"]`);
                            if (field && value) {
                                field.value = value;
                                // user asked: "auto only ready honga edit nhi" - make readonly
                                field.setAttribute('readonly', true);
                                if(field.tagName === 'SELECT') {
                                    field.style.pointerEvents = 'none'; // prevent dropdown interaction
                                    field.style.backgroundColor = '#f1f5f9';
                                }
                            }
                        }
                        updateNationalityLocationMode();
                        setStateDistrictValues(data.state || '', data.district || '');
                        syncWorkFlowFromFields();
                        
                        // Handle photo preview if we have one
                        if (data.photo) {
                           // Depending on implementation, you might want to show the photo or skip mandatory photo upload
                           // For now we just remove the required attribute from photo since it already exists
                           const photoInput = form.querySelector('[name="photo"]');
                           if(photoInput) photoInput.removeAttribute('required');
                        }

                        notify('Worker Found', `Details auto-filled from ${data.source}.`, 'success');
                    } else {
                        // Not found, reset form fields to allow manual entry
                        const preserveManualWorkFlow = sourceInput.value === 'MANUAL' && Boolean(flowState.category || flowState.qualification || flowState.jobProfile);
                        statusBadge.className = 'badge-status bg-gray text-dark';
                        statusBadge.innerText = 'New Worker';
                        sourceInput.value = 'MANUAL';
                        resetAutoFilledFields({ preserveWorkFlow: preserveManualWorkFlow });
                    }
                } catch (err) {
                    statusBadge.style.display = 'none';
                    console.error('Failed to fetch worker details', err);
                }
            } else {
                const preserveManualWorkFlow = sourceInput.value === 'MANUAL' && Boolean(flowState.category || flowState.qualification || flowState.jobProfile);
                statusBadge.style.display = 'none';
                sourceInput.value = 'MANUAL';
                resetAutoFilledFields({ preserveWorkFlow: preserveManualWorkFlow });
            }
        });

        const ANNEXURE4A_FLOW = <?= json_encode($educationFlow, JSON_UNESCAPED_SLASHES) ?>;

        const flowState = { category: '', qualification: '', jobProfile: '' };
        const flowEls = {
            category: document.getElementById('categoryOptions'),
            qualification: document.getElementById('qualificationOptions'),
            jobProfile: document.getElementById('jobProfileOptions'),
            natureSummary: document.getElementById('flowNatureSummary'),
            skillSummary: document.getElementById('flowSkillSummary'),
            roleSummary: document.getElementById('flowRoleSummary'),
            natureInput: form.querySelector('[name="nature_of_work"]'),
            skillInput: form.querySelector('[name="skill_category"]'),
            educationInput: form.querySelector('[name="education"]'),
            roleInput: form.querySelector('[name="role_type"]')
        };

        function setFlowSelectOptions(select, placeholder, values, selectedValue = '') {
            select.innerHTML = '';
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);

            values.forEach(value => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                select.appendChild(option);
            });

            select.value = values.includes(selectedValue) ? selectedValue : '';
            select.disabled = values.length === 0;
        }

        function setHiddenWorkFields() {
            flowEls.natureInput.value = flowState.jobProfile;
            flowEls.skillInput.value = flowState.category;
            flowEls.educationInput.value = flowState.qualification;
            flowEls.roleInput.value = flowState.category;
            if (flowEls.natureSummary) flowEls.natureSummary.textContent = flowState.jobProfile || 'Not selected';
            if (flowEls.skillSummary) flowEls.skillSummary.textContent = flowState.category || 'Not selected';
            if (flowEls.roleSummary) flowEls.roleSummary.textContent = flowState.category || 'Not selected';
            applyCertifiedWageForCategory(flowState.category, true);
        }

        function getCategoryWageRule(category) {
            const normalized = normalizeFlowCategory(category);
            return normalized ? (activeCertifiedWages[normalized] || null) : null;
        }

        function currentMinimumCertifiedWage() {
            const rule = getCategoryWageRule(flowState.category);
            const categoryWage = rule ? parseWageValue(rule.wage_rate) : NaN;
            if (Number.isFinite(categoryWage) && categoryWage > 0) return categoryWage;
            return Number(minimumCertifiedWage) || 0;
        }

        function applyCertifiedWageForCategory(category, force = false) {
            if (!certifiedWageInput) return;
            const rule = getCategoryWageRule(category);
            if (rule) {
                const rate = parseWageValue(rule.wage_rate);
                if (Number.isFinite(rate) && rate > 0) {
                    certifiedWageInput.min = rate.toFixed(2);
                    if (force || !certifiedWageInput.value) {
                        certifiedWageInput.value = rate.toFixed(2);
                    }
                    if (certifiedWageHint) {
                        const fromDate = rule.wage_from_date || '';
                        const toDate = rule.wage_to_date || '9999-12-31';
                        certifiedWageHint.textContent = `${normalizeFlowCategory(category)} approved wage: ${rate.toFixed(2)} (${fromDate} to ${toDate}).`;
                    }
                    return;
                }
            }

            certifiedWageInput.min = String(minimumCertifiedWage || 0);
            if (certifiedWageHint) {
                certifiedWageHint.textContent = `No active category wage found. Minimum allowed: ${(Number(minimumCertifiedWage) || 0).toFixed(2)}.`;
            }
        }

        function renderCategoryOptions() {
            setFlowSelectOptions(flowEls.category, 'Select category', Object.keys(ANNEXURE4A_FLOW), flowState.category);
            flowEls.category.disabled = false;
        }

        function renderQualificationOptions() {
            if (!flowState.category) {
                setFlowSelectOptions(flowEls.qualification, 'Select category first', [], '');
                return;
            }

            const qualifications = Object.keys(ANNEXURE4A_FLOW[flowState.category].qualifications);
            setFlowSelectOptions(flowEls.qualification, 'Select qualification', qualifications, flowState.qualification);
        }

        function renderJobProfileOptions() {
            if (!flowState.qualification) {
                setFlowSelectOptions(flowEls.jobProfile, 'Select qualification first', [], '');
                return;
            }

            const jobProfiles = ANNEXURE4A_FLOW[flowState.category].qualifications[flowState.qualification] || [];
            if (jobProfiles.length === 1 && flowState.jobProfile !== jobProfiles[0]) {
                flowState.jobProfile = jobProfiles[0];
            }
            setFlowSelectOptions(flowEls.jobProfile, 'Select job profile', jobProfiles, flowState.jobProfile);
        }

        function renderWorkFlow() {
            renderCategoryOptions();
            renderQualificationOptions();
            renderJobProfileOptions();
            setHiddenWorkFields();
        }

        function findCategoryForQualification(qualification) {
            return Object.keys(ANNEXURE4A_FLOW).find(category => {
                return Object.prototype.hasOwnProperty.call(ANNEXURE4A_FLOW[category].qualifications, qualification);
            }) || '';
        }

        function findFlowFromJobProfile(jobProfile, fallbackCategory = '') {
            for (const [category, categoryData] of Object.entries(ANNEXURE4A_FLOW)) {
                for (const [qualification, jobs] of Object.entries(categoryData.qualifications)) {
                    if (jobs.includes(jobProfile) && (!fallbackCategory || fallbackCategory === category)) {
                        return { category, qualification, jobProfile };
                    }
                }
            }
            return { category: fallbackCategory, qualification: '', jobProfile: '' };
        }

        function normalizeFlowCategory(category) {
            const value = (category || '').trim().toLowerCase();
            if (value === 'skilled') return 'Skilled';
            if (value === 'semi-skilled' || value === 'semi skilled' || value === 'semiskilled') return 'Semi-Skilled';
            if (value === 'unskilled') return 'Unskilled';
            return '';
        }

        function syncWorkFlowFromFields() {
            const category = normalizeFlowCategory(flowEls.skillInput.value || flowEls.roleInput.value);
            const qualification = (flowEls.educationInput.value || '').trim();
            const jobProfile = (flowEls.natureInput.value || '').trim();
            const normalizedQualification = qualification === 'B Tech' ? 'B.Tech' : qualification;
            const derivedCategory = category || findCategoryForQualification(normalizedQualification);
            const derivedFlow = jobProfile ? findFlowFromJobProfile(jobProfile, derivedCategory) : {};

            flowState.category = derivedFlow.category || derivedCategory || '';
            flowState.qualification = derivedFlow.qualification || normalizedQualification || '';
            flowState.jobProfile = derivedFlow.jobProfile || jobProfile || '';
            renderWorkFlow();
        }

        function resetWorkFlow() {
            flowState.category = '';
            flowState.qualification = '';
            flowState.jobProfile = '';
            renderWorkFlow();
        }

        renderWorkFlow();
        flowEls.category?.addEventListener('change', () => {
            flowState.category = flowEls.category.value;
            flowState.qualification = '';
            flowState.jobProfile = '';
            renderWorkFlow();
            applyCertifiedWageForCategory(flowState.category, true);
        });
        flowEls.qualification?.addEventListener('change', () => {
            flowState.qualification = flowEls.qualification.value;
            flowState.jobProfile = '';
            renderWorkFlow();
        });
        flowEls.jobProfile?.addEventListener('change', () => {
            flowState.jobProfile = flowEls.jobProfile.value;
            renderWorkFlow();
        });

        function resetAutoFilledFields(options = {}) {
            const preserveWorkFlow = Boolean(options.preserveWorkFlow);
            const fieldsToReset = [
                'name', 'gender', 'dob', 'marital_status', 'nationality', 'mobile', 'present_address', 
                'permanent_address', 'state', 'district', 'nature_of_work', 
                'skill_category', 'education', 'role_type', 'blood_group', 'pf_no', 'esi_no', 'uan_number', 'email', 'father_name',
                'executing_officer_code', 'executing_officer_name'
            ];
            
            fieldsToReset.forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.hasAttribute('readonly')) {
                        field.value = ''; // Only clear if it was auto-filled
                    }
                    field.removeAttribute('readonly');
                    if (field.tagName === 'SELECT') {
                        field.style.pointerEvents = 'auto';
                        field.style.backgroundColor = '';
                    }
                }
            });
            if (preserveWorkFlow) {
                renderWorkFlow();
            } else {
                resetWorkFlow();
            }
            updateNationalityLocationMode();
            const photoInput = form.querySelector('[name="photo"]');
            if(photoInput) photoInput.setAttribute('required', 'true');
        }

        function setFieldValue(name, value) {
          if (name === 'state' || name === 'district') {
            const stateValue = name === 'state' ? value : (form.querySelector('[name="state"]:not(:disabled)')?.value || '');
            const districtValue = name === 'district' ? value : (form.querySelector('[name="district"]:not(:disabled)')?.value || '');
            setStateDistrictValues(stateValue || '', districtValue || '');
            return;
          }
          const field = form.querySelector(`[name="${name}"]`);
          if (!field) return;
          if (field.type === 'radio') {
            form.querySelectorAll(`[name="${name}"]`).forEach(input => {
              input.checked = String(input.value) === String(value ?? '');
              input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            return;
          }
          field.value = value ?? '';
          field.dispatchEvent(new Event('input', { bubbles: true }));
          field.dispatchEvent(new Event('change', { bubbles: true }));
          if (name === 'nationality') updateNationalityLocationMode();
        }

        function passTypeFromWorker(worker) {
          const raw = String(worker.pass_type || worker.worker_type || worker.role_type || '').toLowerCase();
          if (raw.includes('supervisor')) return 'Supervisor';
          if (raw.includes('representative')) return 'Representative';
          if (raw.includes('contractor')) return 'Contractor';
          return 'Workman';
        }

        function editWorker(worker) {
          form.reset();
          document.getElementById('workerEditId').value = worker.id || '';
          document.getElementById('enrollFormTitle').textContent = ' Edit ' + requestedPassLabel;

          const values = {
            work_order_no: worker.work_order_no,
            project_name: worker.project_name || <?= json_encode($project_name ?: 'General Project') ?>,
            pass_type: passTypeFromWorker(worker),
            registration_date: worker.registration_date || new Date().toISOString().slice(0, 10),
            aadhaar: worker.aadhaar,
            name: worker.name,
            father_name: worker.father_name,
            gender: worker.gender,
            dob: worker.dob,
            marital_status: worker.marital_status,
            nationality: worker.nationality || 'Indian',
            identification_mark: worker.identification_mark,
            present_address: worker.present_address || worker.permanent_address,
            permanent_address: worker.permanent_address,
            state: worker.state,
            district: worker.district,
            pincode: worker.pincode,
            mobile: worker.mobile,
            department: worker.department || defaultDepartment,
            experience: worker.experience,
            pf_no: worker.pf_no,
            esi_no: worker.esi_no,
            epf_registered_worker: worker.pf_no ? 'YES' : 'NO',
            esi_registered_worker: worker.esi_no ? 'YES' : 'NO',
            certified_wage_rate: worker.certified_wage_rate,
            safety_language: worker.safety_language,
            work_order_source: worker.work_order_source,
            safety_fee_payment_option: worker.safety_fee_payment_option || 'pay_now',
            blood_group: worker.blood_group,
            region: worker.region,
            pwd_status: worker.pwd_status,
            passport_no: worker.passport_no,
            driving_licence_no: worker.driving_licence_no,
            email: worker.email,
            uan_number: worker.uan_number,
            nature_of_work: worker.nature_of_work,
            skill_category: worker.skill_category,
            education: worker.education,
            role_type: worker.role_type || worker.skill_category
            ,executing_officer_code: worker.executing_officer_code
            ,executing_officer_name: worker.executing_officer_name
            ,execution_training_reviewed_by: worker.execution_training_reviewed_by
          };

          Object.entries(values).forEach(([name, value]) => setFieldValue(name, value));
          setStateDistrictValues(values.state || '', values.district || '');
          toggleConditionalRegistration('epf_registered_worker', 'epfNumberWrap', 'epfNumberInput');
          toggleConditionalRegistration('esi_registered_worker', 'esiNumberWrap', 'esiNumberInput');
          const deptField = form.querySelector('[name="department"]');
          if (deptField && defaultDepartment) {
            deptField.setAttribute('readonly', true);
            deptField.style.backgroundColor = '#f1f5f9';
          }
          document.querySelectorAll('#tab-docs input[type="file"]').forEach(input => input.removeAttribute('required'));
          const trainingApprovalInput = form.querySelector('[name="training_approval_doc"]');
          const rejectedByEO = String(worker.execution_training_status || '').toLowerCase() === 'rejected';
          if (trainingApprovalInput && rejectedByEO) {
            trainingApprovalInput.setAttribute('required', 'true');
            notify('Document Required', 'Executing Officer ne request reject ki hai. Corrected Training Approval document dobara upload karein.', 'warning');
          }
          syncWorkFlowFromFields();
          syncWorkOrderFields(values.work_order_no || '');
          if (worker.safety_fee_payment_option) {
            setFieldValue('safety_fee_payment_option', worker.safety_fee_payment_option);
          }
          refreshWorkflowPaymentState(false);
          listSection.style.display = 'none';
          formSection.style.display = 'block';
          formSection.classList.remove('hidden');
          activateTab(rejectedByEO ? 'docs' : 'basic');
        }

        async function deleteWorker(workerId, workerName) {
          const ok = confirm(`Delete worker "${workerName}"?`);
          if (!ok) return;

          try {
            const body = new FormData();
            body.append('worker_id', workerId);
            const res = await fetch('../../api/delete_workman_4a.php', { method: 'POST', body });
            const text = await res.text();
            let result = {};
            try { result = text ? JSON.parse(text) : {}; } catch (e) { result = { success: false, message: text }; }
            if (result.success) {
              notify('Deleted', result.message || 'Worker deleted successfully.', 'success').then(() => location.reload());
            } else {
              notify('Error', result.message || `Delete failed. HTTP ${res.status}`, 'error');
            }
          } catch (err) {
            notify('Error', err.message || 'Delete failed.', 'error');
          }
        }

        function notify(title, message, type = 'info') {
          if (typeof Swal !== 'undefined' && Swal.fire) {
            return Swal.fire(title, message, type);
          }

          const text = message ? `${title}: ${message}` : title;
          alert(text);
          return Promise.resolve();
        }

        function parseWageValue(value) {
          const normalized = String(value || '').replace(/[^0-9.]/g, '');
          if (!normalized) return NaN;
          return Number(normalized);
        }

        function validateCertifiedWage(showMessage = true) {
          if (!certifiedWageInput) return true;
          const requiredWage = currentMinimumCertifiedWage();
          if (requiredWage <= 0) return true;
          const wage = parseWageValue(certifiedWageInput.value);
          if (!Number.isFinite(wage) || wage >= requiredWage) return true;

          if (showMessage) {
            activateTab('work');
            notify(
              'Certified Wage Rate Too Low',
              `Certified Wage Rate cannot be less than ${requiredWage.toFixed(2)} for ${flowState.category || 'selected category'}. Please enter an approved wage rate.`,
              'warning'
            );
            certifiedWageInput.focus();
          }
          return false;
        }

        certifiedWageInput?.addEventListener('blur', () => validateCertifiedWage(true));

        function refreshTrainingBookingFields() {
          refreshWorkflowPaymentState(false);
          const choice = form.querySelector('[name="training_booking_choice"]:checked')?.value || 'not_now';
          const bookingBox = document.getElementById('trainingBookingForm');
          const isBookingNow = choice === 'book_now';
          bookingBox?.classList.toggle('hidden', !isBookingNow);
          document.querySelectorAll('.choice-row').forEach(row => {
            const input = row.querySelector('[name="training_booking_choice"]');
            if (!input) return;
            row.classList.toggle('active', Boolean(input?.checked));
          });
          const aadhaarValue = form.querySelector('[name="aadhaar"]')?.value || '';
          const nameValue = form.querySelector('[name="name"]')?.value || '';
          const safetyLanguage = form.querySelector('[name="safety_language"]')?.value || 'Malayalam';
          const aadhaarDisplay = document.getElementById('trainingAadhaarDisplay');
          const nameDisplay = document.getElementById('trainingNameDisplay');
          const languageSelect = document.getElementById('trainingBookingLanguage');
          const languageDisplay = document.getElementById('trainingBookingLanguageDisplay');
          const dateSelect = document.getElementById('trainingBookingDate');
          const sessionSelect = document.getElementById('trainingBookingSession');
          const submitBtn = document.getElementById('btnSubmit');
          if (aadhaarDisplay) aadhaarDisplay.value = aadhaarValue;
          if (nameDisplay) nameDisplay.value = nameValue;
          if (languageSelect) languageSelect.value = safetyLanguage || 'Malayalam';
          if (languageDisplay) languageDisplay.value = safetyLanguage || 'Malayalam';
          [dateSelect, sessionSelect].forEach(field => {
            if (field) field.disabled = !isBookingNow;
          });
          if (languageSelect) languageSelect.disabled = !isBookingNow;
          if (!isBookingNow) {
            if (dateSelect) dateSelect.value = '';
            if (sessionSelect) sessionSelect.value = '';
          } else {
            populateTrainingDateOptions();
          }
          if (submitBtn) {
            const canSubmitWithoutBooking = isPwoPayLater();
            submitBtn.disabled = !isBookingNow && !canSubmitWithoutBooking;
            submitBtn.title = isBookingNow || canSubmitWithoutBooking ? '' : 'Save Draft is available. Submit requires Safety Training booking.';
          }
        }

        function selectedWorkOrderSource() {
          const hidden = document.getElementById('workOrderSource')?.value || '';
          if (hidden) return hidden.toUpperCase();
          const select = form.querySelector('[name="work_order_no"]');
          const selected = select?.selectedOptions?.[0];
          const source = String(selected?.dataset?.source || '').trim().toUpperCase();
          if (source) return source;
          const combinedText = `${select?.value || ''} ${selected?.textContent || ''}`.toUpperCase();
          if (/\bPWO\b/.test(combinedText) || combinedText.indexOf('PWO') === 0 || combinedText.indexOf('-PWO') >= 0) return 'PWO';
          if (/\bSO\b/.test(combinedText)) return 'SO';
          if (/\bPO\b/.test(combinedText)) return 'PO';
          return '';
        }

        function isPwoWorkOrder() {
          return selectedWorkOrderSource() === 'PWO';
        }

        function selectedSafetyFeeOption() {
          return form.querySelector('[name="safety_fee_payment_option"]:checked')?.value || 'pay_now';
        }

        function isPwoPayLater() {
          return isPwoWorkOrder() && selectedSafetyFeeOption() === 'pay_later';
        }

        function refreshWorkflowPaymentState(syncBooking = true) {
          const isPwo = isPwoWorkOrder();
          const paymentBox = document.getElementById('pwoPaymentOptionBox');
          const paymentRequiredNote = document.getElementById('pwoPaymentRequiredNote');
          const nonPwoPaymentNote = document.getElementById('nonPwoPaymentNote');
          const laterText = document.getElementById('trainingLaterChoiceText');
          const laterInput = form.querySelector('[name="training_booking_choice"][value="not_now"]');
          const bookNowInput = form.querySelector('[name="training_booking_choice"][value="book_now"]');
          if (paymentBox) paymentBox.style.display = isPwo ? 'grid' : 'none';
          if (nonPwoPaymentNote) nonPwoPaymentNote.style.display = isPwo ? 'none' : 'block';
          if (paymentRequiredNote) {
            paymentRequiredNote.style.display = isPwo ? 'block' : 'none';
            paymentRequiredNote.textContent = isPwoPayLater()
              ? 'Enrollment Completed. Please do Safety Payment for proceeding further.'
              : 'Pay Safety Fee first. Safety Training & Seat Booking will open after payment.';
          }
          document.querySelectorAll('[name="safety_fee_payment_option"]').forEach(input => {
            const row = input.closest('.choice-row');
            if (row) row.classList.toggle('active', Boolean(input.checked));
          });
          if (laterText) {
            laterText.textContent = isPwoWorkOrder() && selectedSafetyFeeOption() === 'pay_now'
              ? 'Pay Safety Fee first. Safety Training & Seat Booking will open after payment.'
              : isPwoPayLater()
              ? 'Enrollment Completed. Please do Safety Payment for proceeding further.'
              : 'Save as draft and book Safety Training later';
          }
          if (!isPwo && laterInput?.checked && bookNowInput) {
            bookNowInput.checked = true;
          }
          if (syncBooking) refreshTrainingBookingFields();
        }

        function fallbackTrainingDates() {
          const dates = [];
          const cursor = new Date();
          for (let i = 1; i <= 30; i++) {
            const d = new Date(cursor);
            d.setDate(cursor.getDate() + i);
            const day = d.getDay();
            if (day === 0) continue;
            const value = d.toISOString().slice(0, 10);
            dates.push({ training_date: value, session_name: '', batch_number: 'Preferred date', manual: true });
            if (dates.length >= 12) break;
          }
          return dates;
        }

        function populateTrainingDateOptions() {
          const language = document.getElementById('trainingBookingLanguage')?.value || '';
          const dateSelect = document.getElementById('trainingBookingDate');
          const hint = document.getElementById('trainingDateHint');
          if (!dateSelect) return;
          const current = dateSelect.value;
          let rows = scheduledTrainingSessions.filter(row => {
            const rowLanguage = String(row.language_name || '').trim().toLowerCase();
            const selectedLanguage = String(language || '').trim().toLowerCase();
            return !selectedLanguage || rowLanguage === selectedLanguage || selectedLanguage === 'others';
          });
          const hasScheduledRows = rows.length > 0;
          if (!hasScheduledRows) {
            rows = fallbackTrainingDates();
          }
          dateSelect.innerHTML = '<option value="">Select scheduled date</option>' + rows.map(row => {
            const session = row.session_name || '';
            const label = row.manual
              ? `${row.training_date} - preferred booking`
              : `${row.training_date} - ${session} (${row.batch_number || 'Batch'})`;
            return `<option value="${row.training_date}" data-session="${session}">${label}</option>`;
          }).join('');
          if (current && Array.from(dateSelect.options).some(option => option.value === current)) {
            dateSelect.value = current;
          } else if (rows.length > 0) {
            dateSelect.selectedIndex = 1;
          }
          const sessionSelect = document.getElementById('trainingBookingSession');
          const selectedSession = dateSelect.selectedOptions[0]?.dataset.session || '';
          if (sessionSelect) sessionSelect.value = selectedSession || sessionSelect.value || 'FN';
          if (hint) {
            hint.textContent = hasScheduledRows
              ? 'Scheduled safety batches are available for the selected language.'
              : 'No scheduled batch found for this language. Select a preferred date; Safety will schedule it.';
            hint.style.color = hasScheduledRows ? '#64748b' : '#92400e';
          }
        }

        document.querySelectorAll('[name="training_booking_choice"]').forEach(input => {
          input.addEventListener('change', refreshTrainingBookingFields);
        });
        document.querySelectorAll('[name="safety_fee_payment_option"]').forEach(input => {
          input.addEventListener('change', () => refreshWorkflowPaymentState(true));
        });
        document.getElementById('trainingBookingDate')?.addEventListener('change', e => {
          const session = e.target.selectedOptions[0]?.dataset.session || '';
          const sessionSelect = document.getElementById('trainingBookingSession');
          if (sessionSelect) sessionSelect.value = session || sessionSelect.value || 'FN';
        });
        ['aadhaar', 'name', 'safety_language'].forEach(name => {
          form.querySelector(`[name="${name}"]`)?.addEventListener('input', refreshTrainingBookingFields);
          form.querySelector(`[name="${name}"]`)?.addEventListener('change', refreshTrainingBookingFields);
        });
        refreshTrainingBookingFields();

        function previewValue(name) {
          const field = form.querySelector(`[name="${name}"]`);
          if (!field) return '';
          if (field.type === 'radio') {
            return form.querySelector(`[name="${name}"]:checked`)?.value || '';
          }
          if (field.tagName === 'SELECT') return field.selectedOptions[0]?.textContent?.trim() || field.value;
          return field.value || '';
        }

        function previewRawValue(name) {
          const field = form.querySelector(`[name="${name}"]`);
          if (!field) return '';
          if (field.type === 'radio') return form.querySelector(`[name="${name}"]:checked`)?.value || '';
          return field.value || '';
        }

        function escapePreviewHtml(value) {
          return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        function previewLabel(value) {
          const normalized = String(value || '').replace(/_/g, ' ').trim();
          return normalized ? normalized.replace(/\b\w/g, char => char.toUpperCase()) : '';
        }

        function previewFiles() {
          const fileLabels = [
            ['photo', 'Photo'],
            ['aadhaar_doc', 'Aadhaar Copy'],
            ['education_doc', 'Education Certificate'],
            ['bank_doc', 'Bank Document'],
            ['gatepass_doc', 'Gate Pass Document'],
            ['skill_cert_doc', 'Skill Certificate'],
            ['medical_doc', 'Medical Document'],
            ['police_doc', 'Police Verification'],
            ['insurance_doc', 'Insurance Document'],
            ['training_approval_doc', 'Training Attendance Approval']
          ];
          const isEdit = Boolean(document.getElementById('workerEditId')?.value);
          return fileLabels.map(([name, label]) => {
            const input = form.querySelector(`[name="${name}"]`);
            const fileName = input?.files?.[0]?.name || (isEdit ? 'Already uploaded / no new file selected' : '');
            return [label, fileName || '-'];
          });
        }

        function renderPreviewSection(title, items, full = false) {
          const filtered = items.filter(item => item && item.length >= 2);
          const body = filtered.map(([label, value]) => `
            <div class="preview-item">
              <span>${escapePreviewHtml(label)}</span>
              <strong>${escapePreviewHtml(value || '-')}</strong>
            </div>
          `).join('');
          return `
            <section class="preview-section ${full ? 'full' : ''}">
              <div class="preview-section-title">${escapePreviewHtml(title)}</div>
              <div class="preview-section-grid">${body}</div>
            </section>
          `;
        }

        function showSubmitPreview() {
          const bookingChoice = form.querySelector('[name="training_booking_choice"]:checked')?.value || 'not_now';
          const safetyPaymentOption = isPwoWorkOrder()
            ? previewLabel(selectedSafetyFeeOption())
            : 'Not applicable';
          const sections = [
            renderPreviewSection('Basic Info', [
              ['Pass Type', previewValue('pass_type')],
              ['Work Order No', previewValue('work_order_no')],
              ['Work Order Type', selectedWorkOrderSource() || '-'],
              ['Project No / WBS No', previewValue('project_name')],
              ['Date of Joining', previewValue('registration_date')],
              ['Aadhaar Number', previewValue('aadhaar')],
              ['Full Name', previewValue('name')],
              ['Father Name', previewValue('father_name')]
            ]),
            renderPreviewSection('Personal / Medical', [
              ['Gender', previewValue('gender')],
              ['Date of Birth', previewValue('dob')],
              ['Marital Status', previewValue('marital_status')],
              ['Nationality', previewValue('nationality')],
              ['Identification Mark', previewValue('identification_mark')],
              ['Blood Group', previewValue('blood_group')],
              ['Religion', previewValue('region')],
              ['Person with Disability', previewValue('pwd_status')],
              ['Passport No', previewValue('passport_no')],
              ['Driving Licence No', previewValue('driving_licence_no')]
            ]),
            renderPreviewSection('Address / Contact', [
              ['Permanent Address', previewValue('permanent_address')],
              ['Present Address', previewValue('present_address')],
              ['State', previewValue('state')],
              ['District', previewValue('district')],
              ['Pin Code', previewValue('pincode')],
              ['Mobile Number', previewValue('mobile')],
              ['Email', previewValue('email')],
              ['Emergency Contact', previewValue('emergency_contact')]
            ], true),
            renderPreviewSection('Work / Compliance', [
              ['Department', previewValue('department')],
              ['Years of Experience', previewValue('experience')],
              ['Category', flowState.category],
              ['Qualification', flowState.qualification],
              ['Job Profile / Nature of Work', previewValue('nature_of_work')],
              ['Skill Category', previewValue('skill_category')],
              ['Role Type', previewValue('role_type')],
              ['Education', previewValue('education')],
              ['EPF Registered', previewValue('epf_registered_worker')],
              ['UAN Number', previewValue('pf_no') || previewValue('uan_number')],
              ['ESI Registered', previewValue('esi_registered_worker')],
              ['ESI Number', previewValue('esi_no')],
              ['Certified Wage Rate', previewValue('certified_wage_rate')],
              ['Safety Induction Language', previewValue('safety_language')]
            ], true),
            renderPreviewSection('Executing Officer', [
              ['E-Code', previewValue('executing_officer_code')],
              ['Officer Name', previewValue('executing_officer_name')]
            ]),
            renderPreviewSection('Safety Training / Payment', [
              ['PO Type PWO?', isPwoWorkOrder() ? 'Yes' : 'No'],
              ['Safety Fee Option', safetyPaymentOption],
              ['Booking Choice', bookingChoice === 'book_now' ? 'Safety seat booking selected' : 'Book/pay later'],
              ['Training Date', bookingChoice === 'book_now' ? previewRawValue('training_booking_date') : '-'],
              ['Training Session', bookingChoice === 'book_now' ? previewRawValue('training_booking_session') : '-'],
              ['Training Language', previewRawValue('training_booking_language') || previewValue('safety_language')]
            ]),
            renderPreviewSection('Documents', previewFiles(), true),
            renderPreviewSection('Declaration', [
              ['Final Confirmation', 'Please verify all details before final submission.']
            ], true)
          ];
          document.getElementById('submitPreviewContent').innerHTML = sections.join('');
          document.getElementById('submitVerifiedCheckbox').checked = false;
          document.getElementById('submitPreviewModal').classList.remove('hidden');
          document.getElementById('submitPreviewModal').classList.add('show');
        }

        function closeSubmitPreview() {
          document.getElementById('submitPreviewModal').classList.add('hidden');
          document.getElementById('submitPreviewModal').classList.remove('show');
        }
        window.closeSubmitPreview = closeSubmitPreview;

        async function submitEnrollment(action = 'submit', confirmedPreview = false) {
          setHiddenWorkFields();

          if (action === 'draft') {
            const draftBtn = document.getElementById('btnSaveDraft');
            draftBtn.disabled = true;
            draftBtn.innerText = 'Saving...';
            const formData = new FormData(form);
            formData.append('action', 'draft');
            try {
              const res = await fetch('../../api/save_worker_4a.php', { method: 'POST', body: formData });
              const responseText = await res.text();
              let result = {};
              try { result = responseText ? JSON.parse(responseText) : {}; } catch (parseErr) { result = { success: false, message: responseText || 'Invalid server response.' }; }
              if (result.success) {
                const draftId = result.worker_id || result.workman_id || '';
                if (draftId) document.getElementById('workerEditId').value = draftId;
                const choice = form.querySelector('[name="training_booking_choice"]:checked')?.value || 'not_now';
                const msg = choice === 'book_now'
                  ? 'Your information has been saved as draft only. This is not submitted for processing.'
                  : 'The information has been saved as draft only. Please complete safety training booking before submitting.';
                notify('Draft Saved', msg, 'success');
              } else {
                const detail = result.message || responseText || `Draft save failed. HTTP ${res.status}`;
                notify('Error', detail.length > 500 ? detail.slice(0, 500) : detail, 'error');
              }
            } catch (err) {
              notify('Error', err.message || 'Server error.', 'error');
            } finally {
              draftBtn.disabled = false;
              draftBtn.innerText = 'Save Draft';
            }
            return;
          }

          if (!validateAadhaarNumber(true)) {
            return;
          }

          if (!flowState.category || !flowState.qualification || !flowState.jobProfile) {
            activateTab('work');
            notify('Selection Required', 'Please complete Category, Qualification, and Job Profile in the Work / Compliance flow.', 'warning');
            return;
          }

          if (!validateDobAge(false)) {
            focusInvalidField(dobInput);
            showInvalidFieldMessage(dobInput);
            return;
          }

          if (!validateCertifiedWage(true)) {
            return;
          }

          if (!(await verifyExecutingOfficerCode(true))) {
            focusInvalidField(executingOfficerCodeInput);
            return;
          }

          const bookingChoice = form.querySelector('[name="training_booking_choice"]:checked')?.value || 'not_now';
          if (bookingChoice !== 'book_now' && !isPwoPayLater()) {
            activateTab('training');
            notify('Safety Training Booking Required', 'The information has been saved as draft only. Please complete safety training booking before submitting.', 'warning');
            return;
          }
          if (bookingChoice === 'book_now' && (!previewValue('training_booking_date') || !previewValue('training_booking_session'))) {
            activateTab('training');
            notify('Training Booking Required', 'Please select safety training date and session.', 'warning');
            return;
          }

          const invalidFile = Array.from(document.querySelectorAll('#tab-docs input[type="file"]')).find(input => !validateWorkerFile(input, false));
          if (invalidFile) {
            focusInvalidField(invalidFile);
            showInvalidFieldMessage(invalidFile);
            return;
          }
          
          // Custom Validation: Find first invalid field and switch to its tab
          if (!form.checkValidity()) {
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
              focusInvalidField(firstInvalid);
              showInvalidFieldMessage(firstInvalid);
            }
            form.reportValidity();
            return;
          }

          // ========== ANNEXURE 5/A: CLIENT-SIDE LIMIT CHECK ==========
          const passType = form.querySelector('[name="pass_type"]')?.value || 'Workman';
          let limitType = 'Workman';
          if (passType.toLowerCase().includes('supervisor')) limitType = 'Supervisor';
          else if (passType.toLowerCase().includes('representative')) limitType = 'Representative';
          else if (passType.toLowerCase().includes('contractor')) limitType = 'Contractor';
          
          if (typeof PassLimitValidator !== 'undefined') {
            const isEdit = Boolean(document.getElementById('workerEditId')?.value);
            if (!isEdit && currentContractorId) {
              await PassLimitValidator.fetchLimits(currentContractorId);
              PassLimitValidator.renderSummary(document.getElementById('passLimitsWidget'));
            }
            if (!isEdit && !PassLimitValidator.validate(limitType, 1)) return;
          }
          // ========== END ANNEXURE 5/A CHECK ==========

          if (!confirmedPreview) {
            showSubmitPreview();
            return;
          }

          const btn = document.getElementById('btnSubmit');
          btn.disabled = true; btn.innerText = 'Submitting...';
          
          const formData = new FormData(form);
          formData.append('action', 'submit');
          try {
            const res = await fetch('../../api/save_worker_4a.php', { method: 'POST', body: formData });
            const responseText = await res.text();
            let result = {};
            try {
              result = responseText ? JSON.parse(responseText) : {};
            } catch (parseErr) {
              result = { success: false, message: responseText || 'Invalid server response.' };
            }
            if (result.success) {
              let successMessage = result.message + '\nTemp ID: ' + result.temp_id;
              if (result.payment && result.payment.payment_link) {
                successMessage += '\nPayment Ref: ' + result.payment.payment_ref + '\nAmount: Rs. ' + result.payment.amount;
              }
              notify('Success', successMessage, 'success').then(() => {
                if (result.payment && result.payment.payment_link && selectedSafetyFeeOption() === 'pay_now') {
                  const workerId = result.worker_id || result.workman_id || '';
                  window.location.href = workerId ? `../payment.php?selected_worker_id=${encodeURIComponent(workerId)}` : '../payment.php';
                } else {
                  location.reload();
                }
              });
            } else {
              notify('Error', result.message || `Enrollment failed. HTTP ${res.status}`, 'error');
            }
          } catch (err) { notify('Error', err.message || 'Server error.', 'error'); }
          finally { btn.disabled = false; btn.innerText = 'Submit Entitlement'; }
        }

        const saveDraftButton = document.getElementById('btnSaveDraft');
        if (saveDraftButton) {
          saveDraftButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            submitEnrollment('draft');
          });
        }

        const submitButton = document.getElementById('btnSubmit');
        if (submitButton) {
          submitButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            submitEnrollment('submit');
          });
        }

        document.getElementById('btnProceedSubmit')?.addEventListener('click', (e) => {
          e.preventDefault();
          const verified = document.getElementById('submitVerifiedCheckbox');
          if (!verified?.checked) {
            notify('Verification Required', 'Please tick I verified the information before submitting.', 'warning');
            return;
          }
          closeSubmitPreview();
          submitEnrollment('submit', true);
        });

        executingOfficerCodeInput?.addEventListener('input', () => {
          executingOfficerCodeInput.value = executingOfficerCodeInput.value.toUpperCase();
          if (executingOfficerNameInput) executingOfficerNameInput.value = '';
          executingOfficerVerifyRequest++;
          clearTimeout(executingOfficerVerifyTimer);
          const code = executingOfficerCodeInput.value.trim();
          if (code.length < 3) {
            setExecutingOfficerStatus('', '');
            return;
          }
          setExecutingOfficerStatus('Typing...', 'badge-warning');
          executingOfficerVerifyTimer = setTimeout(() => verifyExecutingOfficerCode(false), 500);
        });
        executingOfficerCodeInput?.addEventListener('blur', () => {
          clearTimeout(executingOfficerVerifyTimer);
          verifyExecutingOfficerCode(false);
        });

        if (prefillAadhaar) {
          document.getElementById('btnOpenModal')?.click();
        }

        form.onsubmit = async (e) => {
          e.preventDefault();
          submitEnrollment('submit');
        };

    function viewWorker(w) {
      document.getElementById('viewContent').innerHTML = `
        <div style="display:flex; gap:20px;">
          <img src="../../uploads/workers/${w.photo}" style="width:120px;height:140px;object-fit:cover;border:1px solid #ddd;">
          <div>
            <h4 style="margin:0">${w.name}</h4>
            <div style="color:var(--text-muted);margin-bottom:10px;">${w.gender} | DOB: ${w.dob}</div>
            <div><strong>Temp ID:</strong> <span class="text-primary">${w.temp_id}</span></div>
            <div><strong>Aadhaar:</strong> ${w.aadhaar}</div>
            <div><strong>Work:</strong> ${w.nature_of_work} (${w.department})</div>
            <div><strong>Executing Officer:</strong> ${(w.executing_officer_code || '-')} ${(w.executing_officer_name ? ' - ' + w.executing_officer_name : '')}</div>
            <div><strong>EO Approval:</strong> ${((w.execution_training_status === 'approved' && Number(w.execution_training_reviewed_by || 0) > 0) ? 'APPROVED' : (w.execution_training_status === 'rejected' ? 'REJECTED' : 'PENDING'))}</div>
          </div>
        </div>
        <hr>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px;">
          <div><strong>State:</strong> ${w.state}</div>
          <div><strong>District:</strong> ${w.district}</div>
          <div><strong>Mobile:</strong> ${w.mobile}</div>
          <div><strong>Emergency:</strong> ${w.emergency_contact}</div>
          <div><strong>Blood Group:</strong> ${w.blood_group || 'N/A'}</div>
          <div><strong>Skill:</strong> ${w.skill_category}</div>
        </div>
      `;
      viewModal.classList.remove('hidden');
      viewModal.classList.add('show');
    }
    
    function closeViewModal() {
      viewModal.classList.remove('show');
      setTimeout(() => viewModal.classList.add('hidden'), 250);
    }

    document.getElementById('searchWorker').onkeyup = function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#workerTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    };
    </script>
    <?php
}

renderLayout("Worker Enrollment (4A)", 'renderContent', $role, $name);
