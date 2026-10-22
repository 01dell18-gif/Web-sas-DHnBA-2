<?php
/**
 * Database Connection & Query Helper
 * Supports SQLite (default, zero configuration) & MySQL / MariaDB (configurable)
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

define('BASE_PATH', dirname(__DIR__));
define('DB_TYPE', 'sqlite'); // 'sqlite' or 'mysql'
define('DB_SQLITE_PATH', BASE_PATH . '/database/sas.sqlite');

// MySQL Settings (if used)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sas_smkn1klungkung');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_TYPE === 'sqlite') {
        $dbDir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $dsn = 'sqlite:' . DB_SQLITE_PATH;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
    }

    return $pdo;
}

/**
 * Run a SQL query with parameters
 */
function dbQuery(string $sql, array $params = []): PDOStatement {
    $db = getDb();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row
 */
function dbFetch(string $sql, array $params = []): ?array {
    $stmt = dbQuery($sql, $params);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Fetch all rows
 */
function dbFetchAll(string $sql, array $params = []): array {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Fetch a single scalar value
 */
function dbValue(string $sql, array $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert row and return last inserted ID
 */
function dbInsert(string $table, array $data): int {
    $db = getDb();
    $fields = array_keys($data);
    $placeholders = array_map(fn($f) => ':' . $f, $fields);
    
    $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    return (int)$db->lastInsertId();
}

/**
 * Update row(s)
 */
function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int {
    $db = getDb();
    $setParts = [];
    $params = [];
    foreach ($data as $field => $val) {
        $paramKey = 'set_' . $field;
        $setParts[] = "{$field} = :{$paramKey}";
        $params[$paramKey] = $val;
    }
    foreach ($whereParams as $k => $v) {
        $params[$k] = $v;
    }
    
    $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Delete row(s)
 */
function dbDelete(string $table, string $where, array $params = []): int {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = dbQuery($sql, $params);
    return $stmt->rowCount();
}
