<?php
/**
 * Database Migration Script
 * Migrates item_quantity to quantity column in product_variants table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    echo "Starting database migration...\n\n";
    
    // Step 1: Check current schema
    $columns = $db->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    echo "Current columns in product_variants:\n";
    foreach ($columnNames as $col) {
        echo "  - $col\n";
    }
    echo "\n";
    
    $hasQuantity = in_array('quantity', $columnNames);
    $hasItemQuantity = in_array('item_quantity', $columnNames);
    
    // Step 2: Handle migration logic
    if ($hasQuantity && $hasItemQuantity) {
        // Both exist - drop the old one
        echo "✓ Both 'quantity' and 'item_quantity' exist. Removing old 'item_quantity'...\n";
        $db->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
        echo "✓ Successfully dropped 'item_quantity' column\n\n";
    } elseif ($hasItemQuantity && !$hasQuantity) {
        // Only old column exists - rename it
        echo "✓ Found 'item_quantity' column. Renaming to 'quantity'...\n";
        $db->exec("ALTER TABLE product_variants CHANGE COLUMN item_quantity quantity INT NOT NULL DEFAULT 1");
        echo "✓ Successfully renamed 'item_quantity' to 'quantity'\n\n";
    } elseif ($hasQuantity && !$hasItemQuantity) {
        // Only new column exists - we're good
        echo "✓ Quantity column already exists in correct format\n\n";
    } else {
        // Neither exists - add it
        echo "✓ Adding 'quantity' column...\n";
        $db->exec("ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER variant_name");
        echo "✓ Successfully added 'quantity' column\n\n";
    }
    
    // Step 3: Verify final schema
    $columns = $db->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
    echo "Final columns in product_variants:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
