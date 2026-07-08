<?php require 'c:/xampp/htdocs/CLMS1/include/config.php'; echo json_encode(db_fetch_all($conn, 'SELECT id, training_date, session_name, language_name, status FROM training_class_batches')); ?>
