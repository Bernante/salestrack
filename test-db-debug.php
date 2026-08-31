<?php
/**
 * Database Debugging & Diagnostic Script
 * This script helps identify and fix database schema issues
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        pre { overflow-x: auto; background: #f8f9fa; padding: 10px; border-radius: 3px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    </style>
</head>
<body>

<h1>🔧 Database Diagnostic Tool</h1>

<?php
try {
    echo '<div class="box">';
    echo '<h2>1️⃣ Attempting Database Connection...</h2>';
    
    $pdo = getDBConnection();
    
    echo '<div class="box success">';
    echo '✅ <strong>Connection Successful!</strong><br>';
    echo 'Database: ' . DB_NAME . '<br>';
    echo 'Host: ' . DB_HOST . ':' . DB_PORT . '<br>';
    echo '</div>';
    
    // Check product_variants table structure
    echo '<div class="box">';
    echo '<h2>2️⃣ Checking product_variants Table Structure...</h2>';
    
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<strong>Current Columns:</strong><pre>';
        foreach ($columns as $col) {
            echo $col['Field'] . ' (' . $col['Type'] . ')' . "\n";
        }
        echo '</pre>';
        
        $columnNames = array_column($columns, 'Field');
        $hasQuantity = in_array('quantity', $columnNames);
        $hasItemQuantity = in_array('item_quantity', $columnNames);
        
        echo '<div class="box ' . ($hasQuantity ? 'success' : 'error') . '">';
        echo 'Has "quantity" column: ' . ($hasQuantity ? '✅ YES' : '❌ NO') . '<br>';
        echo 'Has "item_quantity" column: ' . ($hasItemQuantity ? '✅ YES' : '❌ NO') . '<br>';
        echo '</div>';
        
        // Attempt fix if needed
        if (!$hasQuantity && $hasItemQuantity) {
            echo '<div class="box warning">';
            echo '<h3>🔧 Fixing: Renaming item_quantity to quantity...</h3>';
            $pdo->exec("ALTER TABLE product_variants CHANGE COLUMN item_quantity quantity INT NOT NULL DEFAULT 1");
            echo '✅ <strong>Column renamed successfully!</strong>';
            echo '</div>';
        } elseif (!$hasQuantity && !$hasItemQuantity) {
            echo '<div class="box warning">';
            echo '<h3>🔧 Fixing: Adding quantity column...</h3>';
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER price");
            echo '✅ <strong>Column added successfully!</strong>';
            echo '</div>';
        } elseif ($hasQuantity && $hasItemQuantity) {
            echo '<div class="box warning">';
            echo '<h3>🔧 Fixing: Dropping redundant item_quantity column...</h3>';
            $pdo->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
            echo '✅ <strong>Redundant column dropped!</strong>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="box error">';
        echo '❌ <strong>Error checking columns:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
    }
    
    // Test the query from products.php
    echo '<div class="box">';
    echo '<h2>3️⃣ Testing Products Query...</h2>';
    
    try {
        $result = $pdo->query('
            SELECT p.id AS product_id, p.name AS product_name, p.image AS product_image, p.status AS product_status,
                   pv.id AS variant_id, pv.variant_name, pv.quantity, pv.price, pv.status AS variant_status
            FROM products p
            LEFT JOIN product_variants pv ON p.id = pv.product_id
            ORDER BY p.id ASC, pv.id ASC
        ')->fetchAll();
        
        echo '<div class="box success">';
        echo '✅ <strong>Query executed successfully!</strong><br>';
        echo 'Rows returned: ' . count($result) . '<br>';
        if (count($result) > 0) {
            echo '<pre>' . json_encode(array_slice($result, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="box error">';
        echo '❌ <strong>Query failed:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="box error">';
    echo '❌ <strong>Critical Error:</strong><br>';
    echo $e->getMessage();
    echo '</div>';
}
?>

<div class="box success" style="margin-top: 30px;">
    <h2>✅ Diagnostic Complete</h2>
    <p>If all tests passed, you can now:</p>
    <ol>
        <li><a href="/admin/products.php">Visit Products & Pricing Page</a></li>
        <li><a href="/">Return to Dashboard</a></li>
    </ol>
</div>

</body>
</html>
