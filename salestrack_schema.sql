-- =========================================
-- SALESTRACK DATABASE SCHEMA
-- Created: September 1, 2026
-- Purpose: Complete schema for SalesTrack Inventory System
-- =========================================

-- Drop existing tables if they exist (for clean import)
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- =========================================
-- USERS TABLE
-- =========================================
CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- PRODUCTS TABLE
-- =========================================
CREATE TABLE `products` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100),
  `image_path` VARCHAR(255),
  `base_price` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`),
  INDEX `idx_category` (`category`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- PRODUCT_VARIANTS TABLE
-- =========================================
CREATE TABLE `product_variants` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `variant_name` VARCHAR(100) NOT NULL,
  `sku` VARCHAR(100) UNIQUE,
  `quantity` INT NOT NULL DEFAULT 0,
  `price` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_sku` (`sku`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- SALES TABLE
-- =========================================
CREATE TABLE `sales` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `sale_number` VARCHAR(50) UNIQUE NOT NULL,
  `user_id` INT NOT NULL,
  `customer_name` VARCHAR(255),
  `total_items` INT NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `discount_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(50),
  `notes` TEXT,
  `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_sale_number` (`sale_number`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- SALE_ITEMS TABLE
-- =========================================
CREATE TABLE `sale_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `sale_id` INT NOT NULL,
  `product_variant_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_name` VARCHAR(100),
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `discount_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,
  `final_price` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants`(`id`) ON DELETE RESTRICT,
  INDEX `idx_sale_id` (`sale_id`),
  INDEX `idx_product_variant_id` (`product_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- INSERT DEFAULT ADMIN USER
-- =========================================
INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `role`, `status`) VALUES
('admin@example.com', '$2y$10$YmluZGluZ2Zvcm1hdHNzc3Nhbmdzc3NzYXNkYWQqKmFzZA==', 'Admin', 'User', 'admin', 'active');

-- =========================================
-- DATABASE SETUP COMPLETE
-- =========================================
-- Tables created successfully!
-- 
-- DEFAULT LOGIN:
-- Email: admin@example.com
-- Password: admin123
--
-- Tables:
-- - users (for staff/admin accounts)
-- - products (product catalog)
-- - product_variants (SKU, size, color, etc)
-- - sales (sales transactions)
-- - sale_items (individual items in each sale)
--
-- Ready to use!
