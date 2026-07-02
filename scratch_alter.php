<?php
require 'include/config.php';
mysqli_query($conn, "ALTER TABLE contractors ADD COLUMN epf_exemption_reason VARCHAR(255) DEFAULT NULL, ADD COLUMN esi_exemption_reason VARCHAR(255) DEFAULT NULL");
echo "Done";
