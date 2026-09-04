<?php
/**
 * SOFT-DELETE SCHEMA MIGRATION
 * Adds is_active column to product_variants table for soft-delete support
 * 
 * This allows variant removal without breaking sales history integrity
 * 
 * Migration: 2026-09-03-add-soft-delete
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    echo "Starting soft-delete migration...\n";
    
    // Check if column already exists
    $cols = $db->query("SHOW COLUMNS FROM product_variants LIKE 'is_active'")->fetchAll();
    
    if (empty($cols)) {
        echo "[1/3] Adding is_active column to product_variants...\n";
        $db->exec("ALTER TABLE `product_variants` 
                   ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `updated_at`,
                   ADD INDEX `idx_is_active` (`is_active`)");
        echo "      ✓ Column added successfully\n";
    } else {
        echo "[1/3] Column is_active already exists, skipping...\n";
    }
    
    echo "[2/3] Setting all existing variants as active...\n";
    $result = $db->exec("UPDATE `product_variants` SET `is_active` = 1");
    echo "      ✓ Updated variants\n";
    
    echo "[3/3] Verifying migration...\n";
    $checkStmt = $db->query("SHOW COLUMNS FROM product_variants WHERE Field = 'is_active'");
    $check = $checkStmt->fetch();
    
    if ($check) {
        echo "      ✓ Migration verified successfully\n";
        echo "\n✓ Soft-delete migration completed!\n";
        echo "\nSchema changes:\n";
        echo "  - Added is_active TINYINT(1) DEFAULT 1 to product_variants\n";
        echo "  - Added idx_is_active index for performance\n";
        echo "\nNext steps:\n";
        echo "  1. Reload database connection in app\n";
        echo "  2. Test variant removal on both admin and staff sides\n";
        echo "  3. Verify deleted variants don't appear in new-sale.php\n";
        echo "  4. Verify deleted variants still show in sale history\n";
    } else {
        echo "      ✗ Migration verification failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
