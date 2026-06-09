<?php
// =====================================
// LIVE SERVER DATABASE OVERRIDE
// =====================================
// Keep all connection logic in include/config.php.
// On the live server, change only these values according to the hosting/server DB.

$DbDriver    = 'sqlsrv';          // mysql or sqlsrv
$Servername  = '10.210.11.50';    // live DB host or IP
$Username    = 'CLMSUSER';        // live DB username
$Password    = 'COtra@C#2627';    // live DB password
$Dbname      = 'csl_clms';        // live DB name
