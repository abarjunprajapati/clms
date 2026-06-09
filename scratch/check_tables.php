<?php
require 'include/config.php';
echo "execution_officers:\n";
print_r(db_fetch_all($conn, "DESCRIBE execution_officers"));
echo "\naudit_logs:\n";
print_r(db_fetch_all($conn, "DESCRIBE audit_logs"));
