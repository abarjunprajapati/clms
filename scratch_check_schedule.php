<?php
require 'config.live.php';
$res = mysqli_query($conn, 'DESCRIBE training_schedule');
while($r = mysqli_fetch_assoc($res)) { echo $r['Field'].', '; }
