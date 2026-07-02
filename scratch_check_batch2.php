<?php
include 'config.live.php';
$res = mysqli_query($conn, 'SELECT * FROM training_requests WHERE workman_id = 23 ORDER BY id DESC LIMIT 1');
if ($res) {
    print_r(mysqli_fetch_assoc($res));
}

$res2 = mysqli_query($conn, "SELECT id, batch_number, language, scheduled_date, session_type, status FROM training_schedules WHERE scheduled_date = '2026-06-25' AND session_type = 'AN'");
if ($res2) {
    while($r = mysqli_fetch_assoc($res2)) {
        print_r($r);
    }
}
