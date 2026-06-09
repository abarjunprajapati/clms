<?php
/**
 * API Diagnostic - Tests if PHP can output JSON correctly
 * Visit: https://cslweb.teleconsystems.com/clms/api/test_api.php
 * Delete this file after debugging!
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);

$tests = array();

// Test 1: Basic output
$tests['basic_output'] = 'OK';

// Test 2: PHP version
$tests['php_version'] = PHP_VERSION;

// Test 3: DB connection
require_once dirname(__FILE__) . '/../include/config.php';
$tests['db_connected'] = isset($conn) && $conn !== false ? 'OK' : 'FAILED';

// Test 4: Tables exist?
if (isset($conn) && $conn) {
    $tables = array();
    $res = $conn->query("SHOW TABLES");
    if ($res) {
        while ($row = $res->fetch_array()) $tables[] = $row[0];
    }
    $tests['tables'] = $tables;
    $tests['annexure3a_exists'] = in_array('annexure3a', $tables) ? 'YES' : 'NO';
    $tests['workmen_exists'] = in_array('workmen', $tables) ? 'YES' : 'NO';
    $tests['annexure2a_exists'] = in_array('annexure2a', $tables) ? 'YES' : 'NO';
    $tests['application_workflow_exists'] = in_array('application_workflow', $tables) ? 'YES' : 'NO';

    // Test 5: Sample supervisor data
    $res2 = $conn->query("SELECT * FROM annexure3a LIMIT 3");
    if ($res2) {
        $ann3a_rows = array();
        while ($row = $res2->fetch_assoc()) $ann3a_rows[] = $row;
        $tests['annexure3a_sample'] = $ann3a_rows;
    } else {
        $tests['annexure3a_sample'] = 'query failed: ' . $conn->error;
    }
}

// Test 6: workflow_helpers.php loads OK?
try {
    require_once dirname(__FILE__) . '/workflow_helpers.php';
    $tests['workflow_helpers_loaded'] = 'OK';
} catch (Exception $e) {
    $tests['workflow_helpers_loaded'] = 'FAILED: ' . $e->getMessage();
} catch (Error $e) {
    $tests['workflow_helpers_loaded'] = 'PHP ERROR: ' . $e->getMessage();
}

echo json_encode($tests, JSON_PRETTY_PRINT);
?>

