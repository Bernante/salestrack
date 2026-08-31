<?php
/**
 * Migration: Add sale_date column to sales table
 * Run this once to add the column to existing databases
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // Check if column already exists
    $columns = $db->query("SHOW COLUMNS FROM sales LIKE 'sale_date'")->fetchAll();
    
    if (empty($columns)) {
        // Column doesn't exist, add it
        $db->exec("ALTER TABLE sales ADD COLUMN sale_date DATE NOT NULL DEFAULT CURDATE() AFTER user_id");
        echo "<div style='background:green;color:white;padding:20px;border-radius:5px;'>";
        echo "✓ Success! Added 'sale_date' column to sales table.";
        echo "</div>";
    } else {
        echo "<div style='background:blue;color:white;padding:20px;border-radius:5px;'>";
        echo "ℹ Column 'sale_date' already exists. No action needed.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:red;color:white;padding:20px;border-radius:5px;'>";
    echo "✗ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
<hr style="margin-top:20px;">
<p><a href="/diagnose.php">← Back to Diagnostic</a></p>
