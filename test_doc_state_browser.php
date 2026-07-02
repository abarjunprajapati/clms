<?php
require 'include/config.php';
require 'include/gate_pass_document_master.php';

$workmanId = 36; // Samuel Augustine
$docs = [];
$res = $conn->query("
    SELECT d.document_type, d.file_path, COALESCE(d.status, 'pending') AS status
    FROM documents d
    JOIN (
        SELECT document_type, MAX(id) AS latest_id
        FROM documents
        WHERE workman_id = $workmanId
        GROUP BY document_type
    ) latest_docs ON latest_docs.latest_id = d.id
");
while ($res && ($row = $res->fetch_assoc())) {
    $docs[] = $row;
}
echo "<pre>";
echo "DOCS:\n";
print_r($docs);
echo "\n\n";

echo "STATE:\n";
print_r(clms_gate_pass_mandatory_doc_state($conn, $docs));
echo "</pre>";
