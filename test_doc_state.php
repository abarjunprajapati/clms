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

echo "DOCS:\n";
echo json_encode($docs, JSON_PRETTY_PRINT);
echo "\n\n";

echo "STATE:\n";
echo json_encode(clms_gate_pass_mandatory_doc_state($conn, $docs), JSON_PRETTY_PRINT);
