<?php
include 'include/config.php';

echo "=== DEBUGGING GATE PASS REQUEST ISSUE ===\n\n";

// Check recent gate pass requests
$res = $conn->query("SELECT gprw.id, gprw.workman_id, gprw.status, gpr.request_no, w.name, w.training_status, w.pass_issuer_verified FROM gate_pass_request_workers gprw JOIN gate_pass_requests gpr ON gprw.request_id = gpr.id JOIN workmen w ON gprw.workman_id = w.id ORDER BY gprw.created_at DESC LIMIT 5");

echo "1. RECENT GATE PASS REQUEST WORKERS (Last 5):\n";
if($res && $res->num_rows > 0) {
  while($r = $res->fetch_assoc()) {
    echo "   - Worker: {$r['name']} (ID: {$r['workman_id']})\n";
    echo "     Status: {$r['status']}, Request: {$r['request_no']}\n";
    echo "     Training: {$r['training_status']}, Pass Issuer Verified: {$r['pass_issuer_verified']}\n\n";
  }
} else {
  echo "   NO RECORDS FOUND\n\n";
}

// Check for workers with pass_issuer_verified = 1
echo "2. WORKERS WITH pass_issuer_verified = 1:\n";
$res = $conn->query("SELECT id, name, training_status, pass_issuer_verified FROM workmen WHERE pass_issuer_verified = 1 ORDER BY updated_at DESC LIMIT 5");
if($res && $res->num_rows > 0) {
  while($r = $res->fetch_assoc()) {
    echo "   - {$r['name']} (ID: {$r['id']}), Training: {$r['training_status']}\n";
  }
} else {
  echo "   NO WORKERS FOUND\n";
}

echo "\n3. WORKMEN WITH APPROVED DOCUMENTS:\n";
$res = $conn->query("SELECT DISTINCT w.id, w.name, w.training_status, w.pass_issuer_verified, COUNT(d.id) as doc_count FROM workmen w LEFT JOIN documents d ON w.id = d.workman_id WHERE w.pass_issuer_verified = 1 GROUP BY w.id ORDER BY w.updated_at DESC LIMIT 5");
if($res && $res->num_rows > 0) {
  while($r = $res->fetch_assoc()) {
    echo "   - {$r['name']} (ID: {$r['id']}), Docs: {$r['doc_count']}, Training: {$r['training_status']}\n";
  }
} else {
  echo "   NO RECORDS FOUND\n";
}

// Check pending requests query from pending_requests.php
echo "\n4. PENDING_REQUESTS.PHP QUERY RESULT:\n";
$query = "SELECT w.*, c.contractor_name, gpr.request_no, gprw.status as request_status, gprw.created_at as request_date
          FROM gate_pass_request_workers gprw
          JOIN gate_pass_requests gpr ON gprw.request_id = gpr.id
          JOIN workmen w ON gprw.workman_id = w.id
          LEFT JOIN contractors c ON w.contractor_id = c.id
          WHERE gprw.status = 'approved'
          ORDER BY gprw.created_at ASC";
$res = $conn->query($query);
if($res && $res->num_rows > 0) {
  echo "   FOUND " . $res->num_rows . " records\n";
  while($r = $res->fetch_assoc()) {
    echo "   - {$r['name']} (ID: {$r['id']}), Request: {$r['request_no']}\n";
  }
} else {
  echo "   NO RECORDS FOUND (Empty result)\n";
}

?>
