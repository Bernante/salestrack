-- Add is_active column to product_variants table for soft-delete support
-- Migration: 2026-09-03-add-soft-delete

ALTER TABLE `product_variants` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `updated_at`,
ADD INDEX `idx_is_active` (`is_active`);

-- Mark all existing variants as active
UPDATE `product_variants` SET `is_active` = 1 WHERE `is_active` IS NULL;
