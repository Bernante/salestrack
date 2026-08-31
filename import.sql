-- ============================================================
-- SalesTrack Bundle Pricing Fix - Database Update
-- Import this to InfinityFree phpMyAdmin
-- Database: if0_42783325_salestrack2
-- Date: 2026-08-31
-- ============================================================

-- Step 1: Add selling_unit column if it doesn't exist
ALTER TABLE `product_variants` 
ADD COLUMN `selling_unit` ENUM('piece', 'half_tray', 'tray', 'bundle') NOT NULL DEFAULT 'piece' 
AFTER `quantity`;

-- Step 2: Add pieces_per_unit column if it doesn't exist
ALTER TABLE `product_variants` 
ADD COLUMN `pieces_per_unit` INT NOT NULL DEFAULT 1 
AFTER `selling_unit`;

-- Step 3: Create index for performance
CREATE INDEX `idx_selling_unit` ON `product_variants` (`selling_unit`);

-- Step 4: Update existing bundle products (Bulk Non-Mineral Ice)
-- Set bundle configuration: 2 pieces per bundle at ₱5 each
UPDATE `product_variants` 
SET 
  `selling_unit` = 'bundle',
  `pieces_per_unit` = 2
WHERE `product_id` IN (
  SELECT `id` FROM `products` 
  WHERE `name` LIKE '%Bulk%' OR `name` LIKE '%Non-Mineral%' OR `name` LIKE '%Ice%'
)
AND `variant_name` = 'Bundle';

-- Verify the updates
SELECT `id`, `product_id`, `variant_name`, `selling_unit`, `pieces_per_unit`, `price` 
FROM `product_variants` 
WHERE `selling_unit` = 'bundle';

-- ============================================================
-- COMPLETE - Database is now configured for bundle pricing
-- ============================================================
