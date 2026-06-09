<?php
if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/mysql_to_mssql_converter.php input.sql output.sql [DatabaseName]\n");
    exit(1);
}

$input = $argv[1];
$output = $argv[2];
$database = $argv[3] ?? 'new_clms';

if (!is_file($input)) {
    fwrite(STDERR, "Input file not found: {$input}\n");
    exit(1);
}

$lines = file($input, FILE_IGNORE_NEW_LINES);
$tableColumns = [];
$identityTables = [];
$currentTable = null;
$inCreate = false;

foreach ($lines as $line) {
    if (preg_match('/^CREATE TABLE `([^`]+)`/i', trim($line), $m)) {
        $currentTable = $m[1];
        $tableColumns[$currentTable] = [];
        $identityTables[$currentTable] = false;
        $inCreate = true;
        continue;
    }
    if (!$inCreate || $currentTable === null) {
        continue;
    }
    $trim = trim($line);
    if (preg_match('/^`([^`]+)`\s+/i', $trim, $m)) {
        $tableColumns[$currentTable][] = $m[1];
        if (stripos($trim, 'AUTO_INCREMENT') !== false) {
            $identityTables[$currentTable] = true;
        }
        continue;
    }
    if (preg_match('/^\)\s*ENGINE=/i', $trim)) {
        $currentTable = null;
        $inCreate = false;
    }
}

function qn($name) {
    return '[' . str_replace(']', ']]', $name) . ']';
}

function qnameList($sql) {
    return preg_replace_callback('/`([^`]+)`/', function ($m) {
        return qn($m[1]);
    }, $sql);
}

function cleanDefault($rest) {
    $rest = preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP\b/i', '', $rest);
    $rest = preg_replace('/\s+DEFAULT\s+CURRENT_TIMESTAMP\b/i', ' DEFAULT GETDATE()', $rest);
    $rest = preg_replace('/\s+DEFAULT\s+NULL\b/i', '', $rest);
    return trim($rest);
}

function convertType($type) {
    $type = trim(preg_replace('/\s+/', ' ', $type));
    $lower = strtolower($type);

    if (preg_match('/^enum\((.*)\)$/i', $type, $m)) {
        preg_match_all("/'((?:\\\\'|[^'])*)'/", $m[1], $values);
        $max = 50;
        foreach ($values[1] as $value) {
            $max = max($max, strlen(str_replace("\\'", "'", $value)));
        }
        return 'NVARCHAR(' . min(max($max, 1), 4000) . ')';
    }
    if (preg_match('/^bigint(?:\(\d+\))?/i', $lower)) return 'BIGINT';
    if (preg_match('/^int(?:\(\d+\))?/i', $lower)) return 'INT';
    if (preg_match('/^smallint(?:\(\d+\))?/i', $lower)) return 'SMALLINT';
    if (preg_match('/^tinyint\(1\)/i', $lower)) return 'BIT';
    if (preg_match('/^tinyint(?:\(\d+\))?/i', $lower)) return 'TINYINT';
    if (preg_match('/^decimal\(([^)]+)\)/i', $type, $m)) return 'DECIMAL(' . $m[1] . ')';
    if (preg_match('/^varchar\((\d+)\)/i', $type, $m)) return 'NVARCHAR(' . $m[1] . ')';
    if (preg_match('/^char\((\d+)\)/i', $type, $m)) return 'NCHAR(' . $m[1] . ')';
    if (preg_match('/^(longtext|mediumtext|text)/i', $lower)) return 'NVARCHAR(MAX)';
    if (preg_match('/^datetime/i', $lower)) return 'DATETIME2';
    if (preg_match('/^timestamp/i', $lower)) return 'DATETIME2';
    if (preg_match('/^date/i', $lower)) return 'DATE';
    if (preg_match('/^time/i', $lower)) return 'TIME';
    if (preg_match('/^year(?:\(\d+\))?/i', $lower)) return 'INT';
    if (preg_match('/^(double|float)/i', $lower)) return 'FLOAT';

    return strtoupper($type);
}

function convertColumnLine($line) {
    $line = rtrim(trim($line), ',');
    if (!preg_match('/^`([^`]+)`\s+(.+)$/', $line, $m)) {
        return null;
    }
    $column = $m[1];
    $rest = $m[2];
    $rest = preg_replace('/\s+CHARACTER SET\s+\w+/i', '', $rest);
    $rest = preg_replace('/\s+COLLATE\s+\w+/i', '', $rest);
    $rest = preg_replace('/\s+unsigned\b/i', '', $rest);

    $type = '';
    if (preg_match('/^enum\((?:\\\\\'|[^\)])+\)/i', $rest, $tm)) {
        $type = $tm[0];
        $rest = trim(substr($rest, strlen($type)));
    } elseif (preg_match('/^([a-z]+(?:\([^)]+\))?)/i', $rest, $tm)) {
        $type = $tm[1];
        $rest = trim(substr($rest, strlen($type)));
    } else {
        return null;
    }

    $isIdentity = stripos($rest, 'AUTO_INCREMENT') !== false;
    $rest = preg_replace('/\s+AUTO_INCREMENT\b/i', '', $rest);
    $rest = cleanDefault($rest);
    $converted = qn($column) . ' ' . convertType($type);
    if ($isIdentity) {
        $converted .= ' IDENTITY(1,1)';
    }
    if ($rest !== '') {
        $converted .= ' ' . $rest;
    }
    $converted = preg_replace('/\s+DEFAULT\s+NULL\b/i', '', $converted);
    $converted = preg_replace('/\s+DEFAULT\s+CURRENT_TIMESTAMP\b/i', ' DEFAULT GETDATE()', $converted);
    return $converted;
}

function convertInsert($line, $tableColumns, $identityTables) {
    if (!preg_match('/^INSERT INTO `([^`]+)` VALUES (.*);$/s', trim($line), $m)) {
        return null;
    }
    $table = $m[1];
    $values = $m[2];
    $values = str_replace("\\'", "''", $values);
    $values = str_replace('\\"', '"', $values);
    $columns = $tableColumns[$table] ?? [];
    $columnSql = $columns ? ' (' . implode(', ', array_map('qn', $columns)) . ')' : '';
    $out = [];
    if (!empty($identityTables[$table])) {
        $out[] = 'SET IDENTITY_INSERT [dbo].' . qn($table) . ' ON;';
    }
    $out[] = 'INSERT INTO [dbo].' . qn($table) . $columnSql . ' VALUES ' . $values . ';';
    if (!empty($identityTables[$table])) {
        $out[] = 'SET IDENTITY_INSERT [dbo].' . qn($table) . ' OFF;';
    }
    $out[] = 'GO';
    return implode(PHP_EOL, $out);
}

$out = [];
$out[] = '-- Converted from MySQL dump for Microsoft SQL Server / SSMS.';
$out[] = '-- Review skipped foreign keys near CREATE TABLE blocks before using in production.';
$out[] = "IF DB_ID(N'" . str_replace("'", "''", $database) . "') IS NULL CREATE DATABASE " . qn($database) . ';';
$out[] = 'GO';
$out[] = 'USE ' . qn($database) . ';';
$out[] = 'GO';
$out[] = 'SET NOCOUNT ON;';
$out[] = 'GO';
$out[] = '';

$inCreate = false;
$createTable = null;
$defs = [];
$indexes = [];

foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) {
        continue;
    }
    if (preg_match('/^\/\*!.*\*\/;?$/', $trim)) {
        continue;
    }
    if (preg_match('/^(LOCK TABLES|UNLOCK TABLES|SET\s+|\/\*!)/i', $trim)) {
        continue;
    }

    if (preg_match('/^DROP TABLE IF EXISTS `([^`]+)`;/i', $trim, $m)) {
        $out[] = "IF OBJECT_ID(N'[dbo]." . qn($m[1]) . "', N'U') IS NOT NULL DROP TABLE [dbo]." . qn($m[1]) . ';';
        $out[] = 'GO';
        continue;
    }

    if (preg_match('/^CREATE TABLE `([^`]+)`/i', $trim, $m)) {
        $inCreate = true;
        $createTable = $m[1];
        $defs = [];
        $indexes = [];
        continue;
    }

    if ($inCreate) {
        if (preg_match('/^\)\s*ENGINE=/i', $trim)) {
            $out[] = 'CREATE TABLE [dbo].' . qn($createTable) . ' (';
            foreach ($defs as $i => $def) {
                $out[] = '  ' . $def . ($i < count($defs) - 1 ? ',' : '');
            }
            $out[] = ');';
            $out[] = 'GO';
            foreach ($indexes as $idx) {
                $out[] = $idx;
                $out[] = 'GO';
            }
            $out[] = '';
            $inCreate = false;
            $createTable = null;
            $defs = [];
            $indexes = [];
            continue;
        }

        if (preg_match('/^`/', $trim)) {
            $column = convertColumnLine($trim);
            if ($column !== null) $defs[] = $column;
            continue;
        }
        if (preg_match('/^PRIMARY KEY\s+\((.+)\),?$/i', $trim, $m)) {
            $defs[] = 'CONSTRAINT ' . qn('PK_' . $createTable) . ' PRIMARY KEY (' . qnameList($m[1]) . ')';
            continue;
        }
        if (preg_match('/^UNIQUE KEY `([^`]+)` \((.+)\),?$/i', $trim, $m)) {
            $defs[] = 'CONSTRAINT ' . qn('UQ_' . $createTable . '_' . $m[1]) . ' UNIQUE (' . qnameList($m[2]) . ')';
            continue;
        }
        if (preg_match('/^KEY `([^`]+)` \((.+)\),?$/i', $trim, $m)) {
            $indexes[] = 'CREATE INDEX ' . qn('IX_' . $createTable . '_' . $m[1]) . ' ON [dbo].' . qn($createTable) . ' (' . qnameList($m[2]) . ');';
            continue;
        }
        if (preg_match('/^CONSTRAINT `/i', $trim) || preg_match('/^FOREIGN KEY/i', $trim)) {
            $out[] = '-- Skipped MySQL foreign key in ' . $createTable . ': ' . rtrim($trim, ',');
            continue;
        }
        continue;
    }

    $insert = convertInsert($trim, $tableColumns, $identityTables);
    if ($insert !== null) {
        $out[] = $insert;
        continue;
    }
}

file_put_contents($output, implode(PHP_EOL, $out) . PHP_EOL);
echo "Converted {$input} to {$output}\n";
echo "Tables: " . count($tableColumns) . "\n";
echo "Identity tables: " . count(array_filter($identityTables)) . "\n";
?>
