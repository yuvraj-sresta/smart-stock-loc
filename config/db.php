<?php
/**
 * config/db.php
 * Database connection using PDO.
 * All queries must use prepared statements — never concatenate user input.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_stock');
define('DB_USER', 'root');        // Change for production
define('DB_PASS', '');            // Change for production
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die('Database connection error. Please contact your administrator.');
        }
    }
    return $pdo;
}
