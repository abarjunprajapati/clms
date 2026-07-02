<?php 
require 'include/config.php'; 
$res = mysqli_query($conn, 'SHOW COLUMNS FROM contractors'); 
while($row = mysqli_fetch_assoc($res)) { 
  echo $row['Field'] . "\n"; 
}
