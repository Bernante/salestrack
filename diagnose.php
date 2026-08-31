<?php
/**
 * Complete Diagnostic Tool
 * Shows exactly what's wrong
 */

echo "<pre style='background:#f5f5f5; padding:20px; font-family:monospace;'>";

echo "=== SALESTRACK DIAGNOSTIC ===\n\n";

// 1. Check PHP version
echo "1. PHP Version: " . phpversion() . "\n";
echo "2. PDO Extension: " . (extension_loaded('pdo') ? '✓ Available' : '✗ MISSING') . "\n";
echo "3. PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✓ Available' : '✗ MISSING') . "\n\n";

// 2. Check file structure
echo "=== FILE STRUCTURE ===\n";
$requiredFiles = [
    'config/database.php',
    'includes/auth.php',
    'includes/header.php',
    'admin/products.php',
    'staff/products.php',
    'staff/new-sale.php',
    'login.php',
    'database/import.sql'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo ($exists ? '✓' : '✗') . " " . $file . "\n";
}

echo "\n=== DATABASE CONNECTION ===\n";

// 3. Try database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "✓ Database Connection: SUCCESS\n";
    echo "  Host: " . DB_HOST . "\n";
    echo "  Database: " . DB_NAME . "\n";
    echo "  User: " . DB_USER . "\n\n";
    
    // 4. Check tables
    echo "=== TABLES ===\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "✓ " . $table . "\n";
    }
    
    echo "\n=== PRODUCT_VARIANTS COLUMNS ===\n";
    $columns = $db->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  • " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $products = $db->query("SELECT COUNT(*) as cnt FROM products")->fetch();
    echo "Products: " . ($products['cnt'] ?? 0) . "\n";
    
    $variants = $db->query("SELECT COUNT(*) as cnt FROM product_variants")->fetch();
    echo "Variants: " . ($variants['cnt'] ?? 0) . "\n";
    
    $users = $db->query("SELECT COUNT(*) as cnt FROM users")->fetch();
    echo "Users: " . ($users['cnt'] ?? 0) . "\n";
    
} catch (Exception $e) {
    echo "✗ Database Connection FAILED\n";
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "SOLUTION:\n";
    echo "1. Check database credentials in config/database.php\n";
    echo "2. Ensure database exists and is accessible\n";
    echo "3. Run the import.sql file manually in phpMyAdmin\n";
}

echo "\n</pre>";
