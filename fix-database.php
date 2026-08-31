<?php
/**
 * Database Schema Fix Script
 * This script fixes the column name mismatch between item_quantity and quantity
 */

require_once __DIR__ . '/config/database.php';

$output = [];
$success = true;

try {
    $db = getDBConnection();
    $output[] = "✓ Database connection successful\n";
    
    // Check current columns
    $columns = $db->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    $output[] = "\nCurrent columns in product_variants table:";
    foreach ($columnNames as $col) {
        $output[] = "  - " . $col;
    }
    
    $hasQuantity = in_array('quantity', $columnNames);
    $hasItemQuantity = in_array('item_quantity', $columnNames);
    
    $output[] = "\nAnalysis:";
    $output[] = "  - Has 'quantity': " . ($hasQuantity ? "YES" : "NO");
    $output[] = "  - Has 'item_quantity': " . ($hasItemQuantity ? "YES" : "NO");
    
    // Execute migration logic
    $output[] = "\nMigration Actions:";
    
    if ($hasQuantity && $hasItemQuantity) {
        $db->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
        $output[] = "  ✓ Dropped old 'item_quantity' column";
    } elseif ($hasItemQuantity && !$hasQuantity) {
        $db->exec("ALTER TABLE product_variants CHANGE COLUMN item_quantity quantity INT NOT NULL DEFAULT 1");
        $output[] = "  ✓ Renamed 'item_quantity' to 'quantity'";
    } elseif ($hasQuantity && !$hasItemQuantity) {
        $output[] = "  ✓ Schema is already correct";
    } else {
        $db->exec("ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER variant_name");
        $output[] = "  ✓ Added 'quantity' column";
    }
    
    // Verify final state
    $columns = $db->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    
    $output[] = "\nFinal schema:";
    foreach ($columns as $col) {
        $output[] = "  - {$col['Field']} ({$col['Type']})";
    }
    
    $output[] = "\n✓ SUCCESS! Schema fixed.";
    
} catch (Exception $e) {
    $success = false;
    $output[] = "✗ ERROR: " . $e->getMessage();
    error_log('Database fix error: ' . $e->getMessage());
}

$logText = implode("\n", $output);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .log {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        a {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .status-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-icon"><?php echo $success ? '✓' : '✗'; ?></div>
        <h1><?php echo $success ? 'Database Fixed!' : 'Error'; ?></h1>
        <p class="subtitle"><?php echo $success ? 'Migration completed' : 'Migration failed'; ?></p>
        
        <div class="log"><?php echo htmlspecialchars($logText); ?></div>
        
        <div class="button-group">
            <a href="/admin/products.php" class="btn-primary">Go to Products →</a>
            <a href="/" class="btn-secondary">Go Home</a>
        </div>
    </div>
</body>
</html>
