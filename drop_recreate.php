<?php
require 'include/config.php';
$conn->query('DROP TABLE IF EXISTS noc_requests');
require 'apply_blocking_schema.php';
