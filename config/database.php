<?php
/**
 * Database Configuration & Connection File
 * SalesTrack
 */

// Set default timezone for PHP date functions
date_default_timezone_set('Asia/Manila');

// Load custom database credentials if database.local.php exists
if (file_exists(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'if0_42783325_salestrack2');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'if0_42783325');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Patrick121603');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Auto-runs database migrations to ensure schema is up-to-date
 * This handles:
 * - Renaming item_quantity to quantity in product_variants table
 * - Adding selling_unit and pieces_per_unit columns for selling units feature
 * 
 * @param PDO $pdo
 * @return void
 */
function runDatabaseMigrations($pdo) {
    try {
        // Check if migration has already been run (using a flag file or check the schema)
        $columns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $hasQuantity = in_array('quantity', $columnNames);
        $hasItemQuantity = in_array('item_quantity', $columnNames);
        
        // If both exist or neither exist in a weird state, handle it
        if ($hasQuantity && $hasItemQuantity) {
            // Both exist - drop the old one
            $pdo->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
        } elseif ($hasItemQuantity && !$hasQuantity) {
            // Only old column exists - rename it
            $pdo->exec("ALTER TABLE product_variants CHANGE COLUMN item_quantity quantity INT NOT NULL DEFAULT 1");
        }
        
        // Add selling_unit column if it doesn't exist
        if (!in_array('selling_unit', $columnNames)) {
            $pdo->exec("
                ALTER TABLE product_variants 
                ADD COLUMN selling_unit VARCHAR(20) NOT NULL DEFAULT 'piece' 
                AFTER quantity
            ");
            error_log('Database Migration: Added selling_unit column to product_variants');
        }
        
        // Add pieces_per_unit column if it doesn't exist
        if (!in_array('pieces_per_unit', $columnNames)) {
            $pdo->exec("
                ALTER TABLE product_variants 
                ADD COLUMN pieces_per_unit INT NOT NULL DEFAULT 1 
                AFTER selling_unit
            ");
            error_log('Database Migration: Added pieces_per_unit column to product_variants');
        }
        
    } catch (Exception $e) {
        // Silently fail - the table may not exist yet on fresh installations
        error_log('Database Migration Notice: ' . $e->getMessage());
    }
}

/**
 * Returns a Singleton PDO Database Connection Instance
 * 
 * @return PDO
 * @throws Exception
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Run any pending migrations
            runDatabaseMigrations($pdo);
            
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
