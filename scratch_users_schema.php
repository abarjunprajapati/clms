<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
$res = mysqli_query($conn, "DESCRIBE users");
while($row = mysqli_fetch_assoc($res)) echo "{$row['Field']} - {$row['Type']}\n";
?>
