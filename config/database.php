<?php
/**
 * Database Configuration & Connection File
 * SalesTrack - PRODUCTION SAFE VERSION
 */

// PRODUCTION SAFETY: Load error handler FIRST before anything else
require_once __DIR__ . '/../includes/error-handler.php';

// Set default timezone for PHP date functions
date_default_timezone_set('Asia/Manila');

// Load custom database credentials if database.local.php exists
if (file_exists(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

// Database configuration - supports both MySQL and SQLite
if (!defined('DB_TYPE')) define('DB_TYPE', getenv('DB_TYPE') ?: 'mysql'); // 'sqlite' or 'mysql'

if (DB_TYPE === 'sqlite') {
    if (!defined('DB_SQLITE_PATH')) define('DB_SQLITE_PATH', __DIR__ . '/database.sqlite');
} else {
    // MySQL configuration - USE ENVIRONMENT VARIABLES ONLY IN PRODUCTION
    // Never hardcode credentials in source code
    if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
    if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'egg_ice_db');
    if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
    if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
}

/**
 * Auto-runs database migrations to ensure schema is up-to-date
 * 
 * @param PDO $pdo
 * @return void
 */
function runDatabaseMigrations($pdo) {
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $hasQuantity = in_array('quantity', $columnNames);
        $hasItemQuantity = in_array('item_quantity', $columnNames);
        
        if ($hasQuantity && $hasItemQuantity) {
            $pdo->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
        } elseif ($hasItemQuantity && !$hasQuantity) {
            $pdo->exec("ALTER TABLE product_variants CHANGE COLUMN item_quantity quantity INT NOT NULL DEFAULT 1");
        }
        
        if (!in_array('selling_unit', $columnNames)) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN selling_unit VARCHAR(20) NOT NULL DEFAULT 'piece' AFTER quantity");
        }
        
        if (!in_array('pieces_per_unit', $columnNames)) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN pieces_per_unit INT NOT NULL DEFAULT 1 AFTER selling_unit");
        }
        
    } catch (Exception $e) {
        // Silently fail - table may not exist on fresh installations
    }
}

/**
 * Returns a Singleton PDO Database Connection Instance
 * Supports both SQLite (local development) and MySQL (production)
 * 
 * @return PDO
 * @throws Exception
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (DB_TYPE === 'sqlite') {
                $dsn = 'sqlite:' . DB_SQLITE_PATH;
                $pdo = new PDO($dsn, null, null, $options);
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                runDatabaseMigrations($pdo);
            }
            
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please try again or contact support.');
        }
    }

    return $pdo;
}
?>
