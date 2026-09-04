SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `selling_unit` enum('piece','half_tray','tray','bundle') NOT NULL DEFAULT 'piece',
  `pieces_per_unit` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_variants_product` (`product_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(20) NOT NULL DEFAULT 'paid',
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  KEY `fk_sales_user` (`user_id`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_items_sale` (`sale_id`),
  KEY `fk_items_variant` (`product_variant_id`),
  CONSTRAINT `fk_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Store Owner', 'admin', '$2y$10$2cQkRfqfYXhFuZvd8mRvIeqr588rRbw7BVLy.hX43F7Fffy/xAXIG', 'admin', 'active', '2026-09-04 11:10:18', '2026-09-04 13:40:39'),
('2', 'Juan Dela Cruz', 'staff', '$2y$10$KjJki92Kk9UEmQzZu2Q2Muqtmf0qDyfvVhSoXmuZimrnxNX6VENs6', 'staff', 'active', '2026-09-04 11:10:18', '2026-09-04 13:40:39');

COMMIT;

