<?php
/**
 * Database Schema Fixer
 * Ensures all required columns exist in product_variants table
 */

require 'config/database.php';

try {
    $db = getDBConnection();
    echo "Checking and fixing product_variants schema...\n\n";
    
    // Check existing columns
    $cols = $db->query("DESCRIBE product_variants")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($cols, 'Field');
    
    echo "Current columns: " . implode(', ', $columnNames) . "\n\n";
    
    // Add missing columns
    $added = [];
    
    if (!in_array('quantity', $columnNames)) {
        echo "Adding quantity column...\n";
        $db->exec("ALTER TABLE product_variants ADD COLUMN quantity INT DEFAULT 1 AFTER variant_name");
        $added[] = 'quantity';
    }
    
    if (!in_array('selling_unit', $columnNames)) {
        echo "Adding selling_unit column...\n";
        $db->exec("ALTER TABLE product_variants ADD COLUMN selling_unit ENUM('piece', 'half_tray', 'tray', 'bundle') DEFAULT 'piece' AFTER quantity");
        $added[] = 'selling_unit';
    }
    
    if (!in_array('pieces_per_unit', $columnNames)) {
        echo "Adding pieces_per_unit column...\n";
        $db->exec("ALTER TABLE product_variants ADD COLUMN pieces_per_unit INT DEFAULT 1 AFTER selling_unit");
        $added[] = 'pieces_per_unit';
    }
    
    if (!in_array('sale_date', $columnNames)) {
        // Check sales table for sale_date
        $saleCols = $db->query("DESCRIBE sales")->fetchAll(PDO::FETCH_ASSOC);
        $saleColumnNames = array_column($saleCols, 'Field');
        
        if (!in_array('sale_date', $saleColumnNames)) {
            echo "Adding sale_date column to sales table...\n";
            $db->exec("ALTER TABLE sales ADD COLUMN sale_date DATE AFTER transaction_number");
        }
    }
    
    echo "\n✓ Schema check complete\n";
    if (count($added) > 0) {
        echo "✓ Added columns: " . implode(', ', $added) . "\n";
    } else {
        echo "✓ No new columns needed\n";
    }
    
    // Verify final schema
    echo "\nFinal product_variants columns:\n";
    $finalCols = $db->query("DESCRIBE product_variants")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($finalCols as $col) {
        echo "  • " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n✓✓ Database schema is now compatible with the application!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
