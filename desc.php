<?php
require 'include/config.php';
$q = $conn->query('DESC contractor_annexure3a');
while($r = $q->fetch_assoc()) echo $r['Field'].' - '.$r['Type']."\n";
