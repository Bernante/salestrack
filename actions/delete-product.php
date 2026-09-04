<?php
/**
 * DELETE PRODUCT ACTION
 * 
 * Handles safe deletion of products with:
 * - Authorization checks (admin/staff only)
 * - Sales history verification
 * - Cascade deletion of variants
 * - Image cleanup
 * - CSRF protection
 * 
 * Returns JSON response ONLY
 */

// Set content type to JSON immediately - MUST be before any output
header('Content-Type: application/json; charset=utf-8');

try {
    // Start output buffering to catch any unexpected output
    ob_start();
    
    // Require database config which has getDBConnection()
    require_once __DIR__ . '/../config/database.php';
    
    // Require helper functions (but NOT getDBConnection - already in database.php)
    require_once __DIR__ . '/../includes/functions.php';
    
    // Only accept AJAX POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Check authentication
    session_start();
    $isAdmin = isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
    $isStaff = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    
    if (!$isAdmin && !$isStaff) {
        http_response_code(403);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // CSRF validation
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
        exit;
    }
    
    // Get and validate product ID
    $productId = intval($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $db = getDBConnection();
    
    // ===== FETCH PRODUCT =====
    $stmt = $db->prepare('SELECT id, name, image FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // ===== CHECK FOR SALES HISTORY =====
    $stmtSales = $db->prepare(
        'SELECT COUNT(*) as sale_count FROM sale_items 
         WHERE product_variant_id IN (
             SELECT id FROM product_variants WHERE product_id = :product_id
         )'
    );
    $stmtSales->execute([':product_id' => $productId]);
    $salesResult = $stmtSales->fetch();
    $saleCount = intval($salesResult['sale_count'] ?? 0);
    
    // ===== BEGIN TRANSACTION =====
    $db->beginTransaction();
    
    try {
        if ($saleCount > 0) {
            // Product has sales history → SOFT DELETE (set is_active = 0)
            $stmtSoftDelete = $db->prepare('UPDATE products SET is_active = 0 WHERE id = :id');
            $stmtSoftDelete->execute([':id' => $productId]);
            
            // Also cascade soft-delete to all variants
            $stmtCascade = $db->prepare('UPDATE product_variants SET is_active = 0 WHERE product_id = :product_id');
            $stmtCascade->execute([':product_id' => $productId]);
            
            // ===== LOG ACTION =====
            logAudit(
                $isAdmin ? $_SESSION['admin_id'] : $_SESSION['user_id'],
                'SOFT_DELETE_PRODUCT',
                'Product soft-deleted (has sales): ' . $product['name'] . ' (ID: ' . $productId . ')',
                $productId
            );
            
            // Commit transaction
            $db->commit();
            
            // Clear output buffer and send success response
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Product hidden successfully (has sales history)',
                'productName' => $product['name'],
                'saleCount' => $saleCount,
                'wasSoftDeleted' => true
            ]);
            
        } else {
            // No sales history → HARD DELETE (original behavior)
            // ===== DELETE VARIANTS =====
            $stmtDelVariants = $db->prepare('DELETE FROM product_variants WHERE product_id = :product_id');
            $stmtDelVariants->execute([':product_id' => $productId]);
            $variantsDeleted = $stmtDelVariants->rowCount();
            
            // ===== DELETE PRODUCT IMAGE =====
            if (!empty($product['image'])) {
                $imagePath = __DIR__ . '/../' . ltrim($product['image'], '/');
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            // ===== DELETE PRODUCT =====
            $stmtDelProduct = $db->prepare('DELETE FROM products WHERE id = :id');
            $stmtDelProduct->execute([':id' => $productId]);
            
            // ===== LOG ACTION =====
            logAudit(
                $isAdmin ? $_SESSION['admin_id'] : $_SESSION['user_id'],
                'DELETE_PRODUCT',
                'Product deleted: ' . $product['name'] . ' (ID: ' . $productId . ', Variants: ' . $variantsDeleted . ')',
                $productId
            );
            
            // Commit transaction
            $db->commit();
            
            // Clear output buffer and send success response
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully',
                'productName' => $product['name'],
                'variantsDeleted' => $variantsDeleted,
                'wasSoftDeleted' => false
            ]);
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Delete product database error: ' . $e->getMessage());
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Delete product exception: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
}
?>


