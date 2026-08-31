<?php
/**
 * DATABASE UPDATE SCRIPT
 * SalesTrack Bundle Pricing Feature
 * 
 * Visit: https://salestrack.infinityfreeapp.com/database-update.php?key=dbupdate2026
 */

define('UPDATE_KEY', 'dbupdate2026');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'if0_42783325_salestrack2');
define('DB_USER', 'if0_42783325');
define('DB_PASS', 'Patrick121603');

$key = $_GET['key'] ?? '';
if ($key !== UPDATE_KEY) die('Invalid key');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== DATABASE UPDATE ===\n\n";
    
    // Check existing columns
    $columns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Update 1: Add selling_unit column
    if (!in_array('selling_unit', $columnNames)) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN selling_unit VARCHAR(20) NOT NULL DEFAULT 'piece' AFTER quantity");
        echo "✅ Added selling_unit column\n";
    } else {
        echo "✅ selling_unit column already exists\n";
    }
    
    // Update 2: Add pieces_per_unit column
    if (!in_array('pieces_per_unit', $columnNames)) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN pieces_per_unit INT NOT NULL DEFAULT 1 AFTER selling_unit");
        echo "✅ Added pieces_per_unit column\n";
    } else {
        echo "✅ pieces_per_unit column already exists\n";
    }
    
    // Verify bundle product configuration
    echo "\n=== BUNDLE PRODUCTS ===\n";
    $bundles = $pdo->query("SELECT id, product_name, variant_name, selling_unit, pieces_per_unit, price FROM product_variants WHERE selling_unit = 'bundle'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bundles)) {
        echo "⚠️  No bundle products configured\n";
        echo "\nExample: Bulk Non-Mineral Ice (20-piece bundles at ₱5)\n";
        echo "- selling_unit: bundle\n";
        echo "- pieces_per_unit: 2\n";
        echo "- price: 5.00\n";
    } else {
        foreach ($bundles as $bundle) {
            echo "✅ " . $bundle['product_name'] . " - " . $bundle['variant_name'] . "\n";
            echo "   Pieces per bundle: " . $bundle['pieces_per_unit'] . "\n";
            echo "   Price: ₱" . $bundle['price'] . "\n";
        }
    }
    
    echo "\n✅ DATABASE UPDATE COMPLETE\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
