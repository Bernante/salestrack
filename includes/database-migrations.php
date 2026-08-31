<?php
/**
 * Database Migrations for Selling Units Feature
 * Handles adding selling_unit and pieces_per_unit columns to product_variants
 */

function migrateSellingu Units($pdo) {
    try {
        // Get existing columns
        $columns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Add selling_unit column if it doesn't exist
        if (!in_array('selling_unit', $columnNames)) {
            $pdo->exec("
                ALTER TABLE product_variants 
                ADD COLUMN selling_unit VARCHAR(20) NOT NULL DEFAULT 'piece' 
                AFTER quantity,
                ADD KEY idx_selling_unit (selling_unit)
            ");
            error_log('Migration: Added selling_unit column to product_variants');
        }
        
        // Add pieces_per_unit column if it doesn't exist
        if (!in_array('pieces_per_unit', $columnNames)) {
            $pdo->exec("
                ALTER TABLE product_variants 
                ADD COLUMN pieces_per_unit INT NOT NULL DEFAULT 1 
                AFTER selling_unit
            ");
            error_log('Migration: Added pieces_per_unit column to product_variants');
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Database Migration Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get selling unit options
 */
function getSellingUnitOptions() {
    return [
        'piece'     => 'Piece',
        'half_tray' => 'Half Tray',
        'tray'      => 'Tray',
        'bundle'    => 'Bundle'
    ];
}

/**
 * Get fixed pieces per unit for Eggs
 */
function getEggPiecesPerUnit($sellingUnit) {
    $eggUnits = [
        'piece'     => 1,
        'half_tray' => 15,
        'tray'      => 30
    ];
    return $eggUnits[$sellingUnit] ?? 1;
}

/**
 * Check if a product is Eggs
 */
function isEggProduct($db, $productId) {
    $stmt = $db->prepare('SELECT name FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    return $product && stripos($product['name'], 'egg') !== false;
}

