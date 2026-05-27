<?php
$c = mysqli_connect('localhost','root','','new_clms');
echo "=== CONTRACTORS ===\n";
$r = mysqli_query($c,'DESCRIBE contractors');
while($row=mysqli_fetch_assoc($r)) echo $row['Field'].',';
echo "\n\n=== WORKMEN ===\n";
$r2 = mysqli_query($c,'DESCRIBE workmen');
while($row=mysqli_fetch_assoc($r2)) echo $row['Field'].',';
echo "\n\n=== GATE_PASSES ===\n";
$r3 = mysqli_query($c,'DESCRIBE gate_passes');
if ($r3) { while($row=mysqli_fetch_assoc($r3)) echo $row['Field'].','; } else echo "Table not found";
echo "\n\n=== TRAINING_REQUESTS ===\n";
$r4 = mysqli_query($c,'DESCRIBE training_requests');
if ($r4) { while($row=mysqli_fetch_assoc($r4)) echo $row['Field'].','; } else echo "Table not found";

