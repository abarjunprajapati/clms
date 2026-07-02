<?php
require 'include/config.php';
$conn->query("DELETE FROM noc_requests");
echo "Deleted all dummy requests";
