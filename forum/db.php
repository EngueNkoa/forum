<?php
// db.php — Database connection using PDO
// Put all forum files in the same folder and include this file wherever you need DB access.

// ── Your database credentials ──────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'forum_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default is empty

// ── Create and return a PDO connection ─────────────────────────
function get_db(): PDO {
    static $pdo = null;     // keep one connection alive per request

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // return arrays by default
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
