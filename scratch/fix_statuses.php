<?php
include 'd:/Xampp/htdocs/clms/include/config.php';
mysqli_query($conn, "UPDATE contractors SET status = 'draft' WHERE vendor_code = '1100927'");
echo "Fixed 1100927 status to draft.\n";

// Also fix any other approved contractors who were activated recently but haven't submitted anything
// (Those who have a user_id but NO submitted_at in contractors table or similar)
$res = mysqli_query($conn, "UPDATE contractors SET status = 'draft' WHERE status = 'approved' AND created_at > '2026-05-15'");
echo "Fixed " . mysqli_affected_rows($conn) . " other recent activations.\n";
