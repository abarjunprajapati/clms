<?php
include 'c:/xampp/htdocs/clms/include/config.php';
echo "Table: documents\n";
$res=$conn->query('DESCRIBE documents');
while($row=$res->fetch_assoc()) echo $row['Field'].' | ';
echo "\n\nTable: contractor_documents\n";
$res=$conn->query('DESCRIBE contractor_documents');
while($row=$res->fetch_assoc()) echo $row['Field'].' | ';
echo "\n\nTable: workman_documents\n";
$res=$conn->query('DESCRIBE workman_documents');
while($row=$res->fetch_assoc()) echo $row['Field'].' | ';
?>
