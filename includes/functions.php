<?php
/**
 * GLOBAL HELPER FUNCTIONS
 * Shared utility functions for the application
 * NOTE: getDBConnection() is already defined in config/database.php
 */

/**
 * HTML entity encode string for safe output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get product image path or default image
 */
function getProductImage($imagePath, $productName = '') {
    if (!empty($imagePath) && file_exists(__DIR__ . '/../' . $imagePath)) {
        return $imagePath;
    }
    return '/assets/images/no-product.png';
}

/**
 * Sanitize HTML input
 */
function sanitizeHtml($html) {
    return htmlspecialchars($html ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Log audit trail for actions
 */
function logAudit($userId, $action, $description, $productId = null) {
    try {
        $db = getDBConnection();
        
        // Check if audit_logs table exists
        $stmt = $db->query("SHOW TABLES LIKE 'audit_logs'");
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, silently skip
            return false;
        }
        
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_id, action, description, product_id, timestamp) 
             VALUES (:user_id, :action, :description, :product_id, NOW())'
        );
        return $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':product_id' => $productId
        ]);
    } catch (Exception $e) {
        error_log('Audit log error: ' . $e->getMessage());
        return false;
    }
}

?>

