<?php
include __DIR__ . '/include/config.php';
try {
    echo "COUNT(sap_vendors): " . db_fetch_all($conn, "SELECT COUNT(*) as c FROM sap_vendors")[0]['c'] . "\n";
    echo "DESCRIBE sap_vendors:\n";
    print_r(db_fetch_all($conn, "DESCRIBE sap_vendors"));
    echo "SELECT * FROM sap_vendors LIMIT 5:\n";
    print_r(db_fetch_all($conn, "SELECT * FROM sap_vendors LIMIT 5"));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
