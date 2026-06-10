<?php

if (!defined('CLMS_DB_COMPAT_LOADED')) {
    define('CLMS_DB_COMPAT_LOADED', true);
}

class ClmsDbResult {
    public $driver;
    public $result;
    public $num_rows = 0;
    public $rows = null;
    public $cursor = 0;

    public function __construct($driver, $result) {
        $this->driver = $driver;
        $this->result = $result;
        if ($driver === 'mysql' && $result instanceof mysqli_result) {
            $this->num_rows = (int)$result->num_rows;
        } elseif ($driver === 'sqlsrv' && $result) {
            $rows = @sqlsrv_num_rows($result);
            $this->num_rows = $rows === false ? 0 : (int)$rows;
        }
    }

    public function fetch_assoc() {
        return clms_db_fetch_assoc($this);
    }

    public function fetch_array($mode = null) {
        $row = $this->fetch_assoc();
        if (!$row) return null;
        return $row;
    }

    public function fetch_all($mode = null) {
        $rows = [];
        while ($row = $this->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

class ClmsDbStatement {
    public $driver;
    public $conn;
    public $sql;
    public $stmt;
    public $params = [];
    public $boundResultRefs = [];
    public $resultRows = null;
    public $resultCursor = 0;
    public $error = '';
    public $insert_id = 0;
    public $affected_rows = 0;

    public function __construct($conn, $sql) {
        $this->conn = $conn;
        $this->driver = $conn->driver;
        $this->sql = clms_db_translate_sql($sql, $this->driver);
        if ($this->driver === 'mysql') {
            $this->stmt = clms_db_prepare($conn->link, $this->sql);
            if (!$this->stmt) $this->error = clms_db_error($conn->link);
        }
    }

    public function bind_param($types, &...$params) {
        $this->params = $params;
        if ($this->driver === 'mysql') {
            return clms_db_stmt_bind_param($this->stmt, $types, ...$params);
        }
        return true;
    }

    public function execute() {
        if ($this->driver === 'mysql') {
            $ok = clms_db_stmt_execute($this->stmt);
            if (!$ok) $this->error = clms_db_stmt_error($this->stmt);
            $this->insert_id = (int)clms_db_insert_id($this->conn->link);
            $this->conn->insert_id = $this->insert_id;
            $this->affected_rows = clms_db_stmt_affected_rows($this->stmt);
            return $ok;
        }
        $sql = clms_db_strip_mysql_upsert($this->sql);
        $stmt = sqlsrv_prepare($this->conn->link, $sql, $this->params, ['Scrollable' => SQLSRV_CURSOR_STATIC]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            $this->error = clms_db_sqlsrv_errors();
            $this->conn->error = $this->error;
            return false;
        }
        $this->stmt = $stmt;
        $this->insert_id = $this->conn->refreshInsertId();
        $this->affected_rows = (int)@sqlsrv_rows_affected($stmt);
        return true;
    }

    public function get_result() {
        if ($this->driver === 'mysql') {
            return new ClmsDbResult('mysql', clms_db_stmt_get_result($this->stmt));
        }
        return new ClmsDbResult('sqlsrv', $this->stmt);
    }

    public function close() {
        if ($this->driver === 'mysql' && $this->stmt) return clms_db_stmt_close($this->stmt);
        if ($this->driver === 'sqlsrv' && $this->stmt) return sqlsrv_free_stmt($this->stmt);
        return true;
    }

    public function bind_result(&...$vars) {
        $this->boundResultRefs = &$vars;
        return true;
    }

    public function fetch() {
        if ($this->driver === 'mysql') return $this->stmt ? $this->stmt->fetch() : false;
        if ($this->resultRows === null) {
            $this->resultRows = [];
            $result = $this->get_result();
            while ($row = $result ? $result->fetch_assoc() : null) $this->resultRows[] = array_values($row);
        }
        if (!isset($this->resultRows[$this->resultCursor])) return false;
        $row = $this->resultRows[$this->resultCursor++];
        foreach ($this->boundResultRefs as $i => &$ref) {
            $ref = $row[$i] ?? null;
        }
        return true;
    }
}

class ClmsDbConnection {
    public $driver;
    public $link;
    public $error = '';
    public $connect_error = '';
    public $insert_id = 0;
    public $affected_rows = 0;

    public function __construct($driver, $link) {
        $this->driver = $driver;
        $this->link = $link;
    }

    public function query($sql) {
        return clms_db_query($this, $sql);
    }

    public function prepare($sql) {
        return new ClmsDbStatement($this, $sql);
    }

    public function begin_transaction() {
        return clms_db_begin_transaction($this);
    }

    public function commit() {
        return clms_db_commit($this);
    }

    public function rollback() {
        return clms_db_rollback($this);
    }

    public function close() {
        if ($this->driver === 'mysql') return clms_db_close($this->link);
        if ($this->driver === 'sqlsrv') return sqlsrv_close($this->link);
        return true;
    }

    public function refreshInsertId() {
        if ($this->driver === 'mysql') {
            $this->insert_id = (int)clms_db_insert_id($this->link);
            return $this->insert_id;
        }
        $res = @sqlsrv_query($this->link, 'SELECT CONVERT(INT, SCOPE_IDENTITY()) AS id');
        $row = $res ? sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC) : null;
        $this->insert_id = (int)($row['id'] ?? 0);
        return $this->insert_id;
    }
}

function clms_db_sqlsrv_errors() {
    if (!function_exists('sqlsrv_errors')) return 'SQL Server driver not installed.';
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    if (!$errors) return '';
    return implode(' | ', array_map(function($e) {
        return trim(($e['SQLSTATE'] ?? '') . ' ' . ($e['code'] ?? '') . ' ' . ($e['message'] ?? ''));
    }, $errors));
}

function clms_db_connect($driver, $server, $username, $password, $database, $options = []) {
    $driver = strtolower((string)$driver);
    if ($driver === 'sqlsrv') {
        if (!function_exists('sqlsrv_connect')) {
            $conn = new ClmsDbConnection('sqlsrv', null);
            $conn->connect_error = 'PHP SQL Server extension missing. Install sqlsrv and pdo_sqlsrv for this PHP version.';
            $conn->error = $conn->connect_error;
            return $conn;
        }
        $info = [
            'Database' => $database,
            'UID' => $username,
            'PWD' => $password,
            'CharacterSet' => 'UTF-8',
            'TrustServerCertificate' => true,
        ];
        if (!empty($options['encrypt'])) $info['Encrypt'] = true;
        $link = sqlsrv_connect($server, $info);
        $conn = new ClmsDbConnection('sqlsrv', $link);
        if (!$link) {
            $conn->connect_error = clms_db_sqlsrv_errors();
            $conn->error = $conn->connect_error;
        }
        return $conn;
    }

    $link = mysqli_connect($server, $username, $password, $database);
    $conn = new ClmsDbConnection('mysql', $link);
    if (!$link) {
        $conn->connect_error = clms_db_connect_error();
        $conn->error = $conn->connect_error;
    }
    return $conn;
}

function clms_db_translate_sql($sql, $driver = 'mysql') {
    if ($driver !== 'sqlsrv') return $sql;
    $sql = preg_replace_callback('/`([^`]+)`/', function($m) {
        return '[' . str_replace(']', ']]', $m[1]) . ']';
    }, $sql);
    $sql = preg_replace('/\bNOW\(\)/i', 'GETDATE()', $sql);
    $sql = preg_replace('/\bCURDATE\(\)/i', 'CAST(GETDATE() AS date)', $sql);
    $sql = preg_replace('/\bCURRENT_TIMESTAMP\b/i', 'GETDATE()', $sql);
    $sql = preg_replace('/DATE_ADD\(([^,]+),\s*INTERVAL\s+(\d+)\s+DAY\)/i', 'DATEADD(day, $2, $1)', $sql);
    $sql = preg_replace('/DATE_SUB\(([^,]+),\s*INTERVAL\s+(\d+)\s+DAY\)/i', 'DATEADD(day, -$2, $1)', $sql);
    $sql = preg_replace('/DATE_ADD\(([^,]+),\s*INTERVAL\s+(\d+)\s+HOUR\)/i', 'DATEADD(hour, $2, $1)', $sql);
    $sql = preg_replace('/DATE_SUB\(([^,]+),\s*INTERVAL\s+(\d+)\s+HOUR\)/i', 'DATEADD(hour, -$2, $1)', $sql);
    $sql = preg_replace('/DATE_ADD\(([^,]+),\s*INTERVAL\s+(\d+)\s+YEAR\)/i', 'DATEADD(year, $2, $1)', $sql);
    $sql = preg_replace('/DATE_SUB\(([^,]+),\s*INTERVAL\s+(\d+)\s+YEAR\)/i', 'DATEADD(year, -$2, $1)', $sql);
    $sql = preg_replace('/TIMESTAMPDIFF\(\s*DAY\s*,\s*([^,]+),\s*([^)]+)\)/i', 'DATEDIFF(day, $1, $2)', $sql);
    $sql = preg_replace('/TIMESTAMPDIFF\(\s*HOUR\s*,\s*([^,]+),\s*([^)]+)\)/i', 'DATEDIFF(hour, $1, $2)', $sql);
    $sql = preg_replace('/TIMESTAMPDIFF\(\s*MINUTE\s*,\s*([^,]+),\s*([^)]+)\)/i', 'DATEDIFF(minute, $1, $2)', $sql);
    $sql = preg_replace("/DATE_FORMAT\(([^,]+),\s*'%Y-%m'\)/i", "FORMAT($1, 'yyyy-MM')", $sql);
    $sql = preg_replace('/\bDATE\(([^)]+)\)/i', 'CAST($1 AS date)', $sql);
    $sql = preg_replace('/SHOW\s+TABLES\s+LIKE\s+\'([^\']+)\'/i', "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$1'", $sql);
    $sql = preg_replace('/SHOW\s+COLUMNS\s+FROM\s+\[?([A-Za-z0-9_]+)\]?\s+LIKE\s+\'([^\']+)\'/i', "SELECT COLUMN_NAME AS Field, DATA_TYPE AS Type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$1' AND COLUMN_NAME = '$2'", $sql);
    $sql = preg_replace('/SHOW\s+COLUMNS\s+FROM\s+\[?([A-Za-z0-9_]+)\]?/i', "SELECT COLUMN_NAME AS Field, DATA_TYPE AS Type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$1'", $sql);
    $sql = preg_replace('/\bINSERT\s+IGNORE\s+INTO\b/i', 'INSERT INTO', $sql);
    $sql = preg_replace('/\s+LIMIT\s+(\?|\d+)\s*$/i', ' OFFSET 0 ROWS FETCH NEXT $1 ROWS ONLY', trim($sql));
    return $sql;
}

function clms_db_query($conn, $sql) {
    $sql = clms_db_translate_sql($sql, $conn->driver ?? 'mysql');
    if (($conn->driver ?? 'mysql') === 'sqlsrv') {
        if (clms_db_sqlsrv_should_skip_schema_sql($conn, $sql)) return true;
        $res = @sqlsrv_query($conn->link, clms_db_strip_mysql_upsert($sql), [], ['Scrollable' => SQLSRV_CURSOR_STATIC]);
        if ($res === false) {
            $conn->error = clms_db_sqlsrv_errors();
            return false;
        }
        $conn->refreshInsertId();
        $conn->affected_rows = (int)@sqlsrv_rows_affected($res);
        return $res === true ? true : new ClmsDbResult('sqlsrv', $res);
    }
    $res = mysqli_query($conn->link, $sql);
    if ($res === false) {
        $conn->error = clms_db_error($conn->link);
        return false;
    }
    $conn->insert_id = (int)clms_db_insert_id($conn->link);
    $conn->affected_rows = (int)mysqli_affected_rows($conn->link);
    return $res instanceof mysqli_result ? new ClmsDbResult('mysql', $res) : $res;
}

function clms_db_strip_mysql_upsert($sql) {
    return preg_replace('/\s+ON\s+DUPLICATE\s+KEY\s+UPDATE\s+.+$/is', '', $sql);
}

function clms_db_sqlsrv_should_skip_schema_sql($conn, $sql) {
    if (preg_match('/^\s*ALTER\s+TABLE\s+\[?([A-Za-z0-9_]+)\]?\s+MODIFY\b/i', $sql)) return true;
    if (preg_match('/^\s*CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+\[?([A-Za-z0-9_]+)\]?/i', $sql, $m)) {
        $table = str_replace("'", "''", $m[1]);
        $res = @sqlsrv_query($conn->link, "SELECT 1 AS found FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table'");
        $row = $res ? sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC) : null;
        return (bool)$row;
    }
    return false;
}

function clms_db_prepare($conn, $sql) {
    if ($conn instanceof ClmsDbConnection) return $conn->prepare($sql);
    return mysqli_prepare($conn, $sql);
}
function clms_db_stmt_bind_param($stmt, $types, &...$params) { return $stmt->bind_param($types, ...$params); }
function clms_db_stmt_execute($stmt) { return $stmt->execute(); }
function clms_db_stmt_get_result($stmt) { return $stmt->get_result(); }
function clms_db_stmt_close($stmt) { return $stmt ? $stmt->close() : true; }
function clms_db_stmt_error($stmt) { return $stmt->error ?? ''; }
function clms_db_stmt_store_result($stmt) { return true; }
function clms_db_stmt_result_metadata($stmt) { return null; }
function clms_db_stmt_fetch($stmt) { return $stmt ? $stmt->fetch() : false; }
function clms_db_stmt_bind_result($stmt, &...$vars) { return $stmt ? $stmt->bind_result(...$vars) : false; }
function clms_db_stmt_affected_rows($stmt) { return isset($stmt->affected_rows) ? (int)$stmt->affected_rows : 0; }

function clms_db_fetch_assoc($result) {
    if (!$result) return null;
    if ($result instanceof ClmsDbResult) {
        if ($result->driver === 'sqlsrv') return sqlsrv_fetch_array($result->result, SQLSRV_FETCH_ASSOC) ?: null;
        return mysqli_fetch_assoc($result->result);
    }
    if ($result instanceof mysqli_result) return mysqli_fetch_assoc($result);
    return null;
}

function clms_db_num_rows($result) {
    if (!$result) return 0;
    if ($result instanceof ClmsDbResult) return $result->num_rows;
    if ($result instanceof mysqli_result) return mysqli_num_rows($result);
    return 0;
}

function clms_db_real_escape_string($conn, $value) {
    if (($conn->driver ?? 'mysql') === 'sqlsrv') return str_replace("'", "''", (string)$value);
    return mysqli_real_escape_string($conn->link, (string)$value);
}

function clms_db_error($conn) { return $conn->error ?? ''; }
function clms_db_connect_error() { return function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'Database connection failed.'; }
function clms_db_insert_id($conn) { return (int)($conn->insert_id ?? 0); }
function clms_db_affected_rows($conn) {
    if ($conn instanceof ClmsDbConnection) return (int)($conn->affected_rows ?? 0);
    return function_exists('mysqli_affected_rows') ? (int)mysqli_affected_rows($conn) : 0;
}
function clms_db_begin_transaction($conn) {
    if (($conn->driver ?? 'mysql') === 'sqlsrv') return sqlsrv_begin_transaction($conn->link);
    return mysqli_begin_transaction($conn->link);
}
function clms_db_commit($conn) {
    if (($conn->driver ?? 'mysql') === 'sqlsrv') return sqlsrv_commit($conn->link);
    return mysqli_commit($conn->link);
}
function clms_db_rollback($conn) {
    if (($conn->driver ?? 'mysql') === 'sqlsrv') return sqlsrv_rollback($conn->link);
    return mysqli_rollback($conn->link);
}
function clms_db_close($conn) {
    if ($conn instanceof ClmsDbConnection) return $conn->close();
    return $conn ? mysqli_close($conn) : true;
}
function clms_db_fetch_field($meta) { return function_exists('mysqli_fetch_field') ? mysqli_fetch_field($meta) : false; }

?>
