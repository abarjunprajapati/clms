<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

$applyFix = isset($_GET['fix']) && $_GET['fix'] === '1';
$database = clms_db_real_escape_string($conn, $Dbname ?? '');
$report = [];
$fixed = [];
$skipped = [];
$errors = [];

$sql = "
    SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND COLUMN_NAME = 'id'
      AND DATA_TYPE IN ('tinyint','smallint','mediumint','int','bigint')
    ORDER BY TABLE_NAME
";

$result = clms_db_query($conn, $sql);
if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to inspect schema: ' . clms_db_error($conn)
    ]);
    exit;
}

while ($row = clms_db_fetch_assoc($result)) {
    $table = $row['TABLE_NAME'];
    $columnType = $row['COLUMN_TYPE'];
    $isAuto = stripos($row['EXTRA'] ?? '', 'auto_increment') !== false;
    $isPrimary = ($row['COLUMN_KEY'] ?? '') === 'PRI';

    $item = [
        'table' => $table,
        'column_type' => $columnType,
        'column_key' => $row['COLUMN_KEY'],
        'extra' => $row['EXTRA'],
        'status' => $isAuto ? 'ok' : 'needs_fix'
    ];
    $report[] = $item;

    if ($isAuto) {
        if ($applyFix) {
            $maxRes = clms_db_query($conn, "SELECT COALESCE(MAX(`id`), 0) + 1 AS next_id FROM `$table`");
            $maxRow = $maxRes ? clms_db_fetch_assoc($maxRes) : ['next_id' => 1];
            $nextId = max(1, (int)($maxRow['next_id'] ?? 1));
            if (!clms_db_query($conn, "ALTER TABLE `$table` AUTO_INCREMENT = $nextId")) {
                $errors[] = [
                    'table' => $table,
                    'error' => clms_db_error($conn)
                ];
            }
        }
        continue;
    }

    if (!$isPrimary) {
        $skipped[] = [
            'table' => $table,
            'reason' => 'id is not a primary key; skipped to avoid unsafe schema change'
        ];
        continue;
    }

    if ($applyFix) {
        $alter = "ALTER TABLE `$table` MODIFY `id` $columnType NOT NULL AUTO_INCREMENT";
        if (!clms_db_query($conn, $alter)) {
            $errors[] = [
                'table' => $table,
                'error' => clms_db_error($conn)
            ];
            continue;
        }

        $maxRes = clms_db_query($conn, "SELECT COALESCE(MAX(`id`), 0) + 1 AS next_id FROM `$table`");
        $maxRow = $maxRes ? clms_db_fetch_assoc($maxRes) : ['next_id' => 1];
        $nextId = max(1, (int)($maxRow['next_id'] ?? 1));
        if (!clms_db_query($conn, "ALTER TABLE `$table` AUTO_INCREMENT = $nextId")) {
            $errors[] = [
                'table' => $table,
                'error' => clms_db_error($conn)
            ];
            continue;
        }

        $fixed[] = $table;
    }
}

echo json_encode([
    'success' => empty($errors),
    'mode' => $applyFix ? 'fix_applied' : 'dry_run',
    'message' => $applyFix
        ? 'Auto increment repair completed. Check fixed/skipped/errors.'
        : 'Dry run only. Open this URL with ?fix=1 as super_admin to apply safe fixes.',
    'fixed' => $fixed,
    'skipped' => $skipped,
    'errors' => $errors,
    'report' => $report
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
