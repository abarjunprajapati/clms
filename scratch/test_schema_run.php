<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/compliance_schema.php';

try {
    ensureComplianceSchema($conn);
    echo "SUCCESS: ensureComplianceSchema executed without any errors!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
