<?php
$host = 'localhost';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "[1/4] Connected to MySQL\n";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `salestrack2` CHARACTER SET utf8mb4");
    $pdo->exec("USE `salestrack2`");
    echo "[2/4] Database created\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS `sale_items`, `sales`, `product_variants`, `products`, `users`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $pdo->exec("CREATE TABLE `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'staff') DEFAULT 'staff',
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("CREATE TABLE `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("CREATE TABLE `product_variants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `variant_name` VARCHAR(100) NOT NULL,
        `quantity` INT DEFAULT 1,
        `selling_unit` ENUM('piece', 'half_tray', 'tray', 'bundle') DEFAULT 'piece',
        `pieces_per_unit` INT DEFAULT 1,
        `price` DECIMAL(10,2) DEFAULT 0,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("CREATE TABLE `sales` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_number` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `sale_date` DATE,
        `total_amount` DECIMAL(10,2) DEFAULT 0,
        `amount_paid` DECIMAL(10,2) DEFAULT 0,
        `change_amount` DECIMAL(10,2) DEFAULT 0,
        `payment_status` VARCHAR(20) DEFAULT 'paid',
        `status` ENUM('completed', 'cancelled') DEFAULT 'completed',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("CREATE TABLE `sale_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sale_id` INT NOT NULL,
        `product_variant_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `unit_price` DECIMAL(10,2) DEFAULT 0,
        `subtotal` DECIMAL(10,2) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "[3/4] Tables created\n";
    
    $adminHash = '$2y$10$ojpKUnjj8bm8hwKPjXPI8eT9ppLP2BRDMO0DkuMxvgwdeqD0VAr2i';
    $staffHash = '$2y$10$TczlR8lUj8kbGXAuj6KDrO0WqKq3DaUo/.jTQIlrHRtSAYA0zpG8O';
    
    $s = $pdo->prepare("INSERT INTO users VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $s->execute([1, 'Store Owner', 'admin', $adminHash, 'admin', 'active']);
    $s->execute([2, 'Juan Dela Cruz', 'staff', $staffHash, 'staff', 'active']);
    
    $s = $pdo->prepare("INSERT INTO products VALUES (?, ?, NULL, 'active', NOW())");
    $s->execute([1, 'Egg']);
    $s->execute([2, 'Ice']);
    
    $s = $pdo->prepare("INSERT INTO product_variants VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    $s->execute([1, 1, 'Small', 1, 'piece', 1, 7.00]);
    $s->execute([2, 1, 'Medium', 1, 'piece', 1, 8.00]);
    $s->execute([3, 1, 'Large', 1, 'piece', 1, 9.00]);
    $s->execute([4, 2, 'Default', 1, 'piece', 1, 20.00]);
    
    echo "[4/4] Data seeded\n\n";
    echo "✓ DATABASE READY!\n\n";
    echo "Login: admin / admin123\n";
    echo "Visit: http://localhost:8000/login.php\n";
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
