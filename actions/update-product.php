<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$userRole = $_SESSION['user_role'] ?? 'staff';
$productsUrl = ($userRole === 'admin') ? '/admin/products.php' : '/staff/products.php';
$editUrlBase = ($userRole === 'admin') ? '/admin/product-edit.php' : '/staff/product-edit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $productsUrl);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: ' . $productsUrl);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$productName = trim($_POST['name'] ?? '');

if ($productId <= 0 || empty($productName)) {
    $_SESSION['flash_error'] = 'Invalid product data.';
    header('Location: ' . $productsUrl);
    exit;
}

$db = getDBConnection();

try {
    // Check if product exists and get current image
    $stmtCurrent = $db->prepare('SELECT image FROM products WHERE id = :id');
    $stmtCurrent->execute([':id' => $productId]);
    $currentProduct = $stmtCurrent->fetch();

    $newImagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newImagePath = handleProductImageUpload($_FILES['image']);
    }

    $db->beginTransaction();

    // 1. Update Product Details
    if ($newImagePath !== null) {
        $stmt = $db->prepare('UPDATE products SET name = :name, image = :image WHERE id = :id');
        $stmt->execute([
            ':name'   => $productName,
            ':image'  => $newImagePath,
            ':id'     => $productId
        ]);
        // Optionally clean up old image file if custom
        if (!empty($currentProduct['image'])) {
            $oldFullPath = __DIR__ . '/../' . ltrim($currentProduct['image'], '/');
            if (file_exists($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }
    } else {
        $stmt = $db->prepare('UPDATE products SET name = :name WHERE id = :id');
        $stmt->execute([
            ':name'   => $productName,
            ':id'     => $productId
        ]);
    }

    // 1.5 SOFT-DELETE MARKED VARIANTS (set is_active = 0)
    // BUT: Prevent removing the last active variant for a product
    // First, fetch current active variants to validate the deletion
    $stmtGetVariants = $db->prepare('SELECT id FROM product_variants WHERE product_id = :pid AND is_active = 1');
    $stmtGetVariants->execute([':pid' => $productId]);
    $variants = $stmtGetVariants->fetchAll();
    
    // Handle both old format (deleted_variant_id[]) and new format (removed_variant_ids comma-separated)
    $deletedVariantIds = [];
    
    // Check for new format (comma-separated string)
    if (!empty($_POST['removed_variant_ids'])) {
        $idsString = $_POST['removed_variant_ids'];
        $deletedVariantIds = array_filter(array_map('intval', explode(',', $idsString)));
    } 
    // Fallback to old format (array)
    elseif (!empty($_POST['deleted_variant_id'])) {
        $deletedVariantIds = $_POST['deleted_variant_id'] ?? [];
        if (is_array($deletedVariantIds)) {
            $deletedVariantIds = array_map('intval', $deletedVariantIds);
        }
    }
    
    if (!empty($deletedVariantIds)) {
        // Count how many active variants would remain after deletion
        $remainingVariants = count($variants) - count($deletedVariantIds);
        
        // If this would result in 0 active variants, reject the request
        if ($remainingVariants <= 0) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'message' => 'Cannot remove the last variant. A product must have at least one active variant. Use the "Delete Product" button to remove the entire product.'
            ]));
        }
        
        $stmtSoftDeleteVariant = $db->prepare('UPDATE product_variants SET is_active = 0 WHERE id = :vid AND product_id = :pid');
        foreach ($deletedVariantIds as $vid) {
            $vid = intval($vid);
            if ($vid > 0) {
                $stmtSoftDeleteVariant->execute([
                    ':vid' => $vid,
                    ':pid' => $productId
                ]);
            }
        }
    }

    // 2. Update Existing Variants with selling_unit and pieces_per_unit
    // Support both Admin format (variant_id[], variant_name[], etc.) and Staff format (existing_variants[id][name], etc.)
    
    $stmtUpdateVariant = $db->prepare('UPDATE product_variants SET variant_name = :vname, selling_unit = :unit, pieces_per_unit = :pieces, price = :price WHERE id = :vid AND product_id = :pid');
    
    // Handle Admin format: variant_id[], variant_name[], selling_unit[], pieces_per_unit[], variant_price[]
    if (!empty($_POST['variant_id']) && is_array($_POST['variant_id'])) {
        $variantIds = $_POST['variant_id'];
        $variantNames = $_POST['variant_name'] ?? [];
        $sellingUnits = $_POST['selling_unit'] ?? [];
        $piecesPerUnit = $_POST['pieces_per_unit'] ?? [];
        $variantPrices = $_POST['variant_price'] ?? [];

        foreach ($variantIds as $idx => $vid) {
            $vid = intval($vid);
            if ($vid <= 0) continue;
            
            $vName = trim($variantNames[$idx] ?? '');
            $vUnit = trim($sellingUnits[$idx] ?? 'piece');
            $vPieces = max(1, intval($piecesPerUnit[$idx] ?? 1));
            $vPrice = max(0, floatval($variantPrices[$idx] ?? 0));

            if (!empty($vName)) {
                $stmtUpdateVariant->execute([
                    ':vname'    => $vName,
                    ':unit'     => $vUnit,
                    ':pieces'   => $vPieces,
                    ':price'    => $vPrice,
                    ':vid'      => $vid,
                    ':pid'      => $productId
                ]);
            }
        }
    }
    
    // Handle Staff format: existing_variants[id][name], existing_variants[id][selling_unit], etc.
    if (!empty($_POST['existing_variants']) && is_array($_POST['existing_variants'])) {
        foreach ($_POST['existing_variants'] as $vid => $variantData) {
            $vid = intval($vid);
            if ($vid <= 0) continue;
            
            $vName = trim($variantData['name'] ?? '');
            $vUnit = trim($variantData['selling_unit'] ?? 'piece');
            $vPieces = max(1, intval($variantData['pieces_per_unit'] ?? 1));
            $vPrice = max(0, floatval($variantData['price'] ?? 0));

            if (!empty($vName)) {
                $stmtUpdateVariant->execute([
                    ':vname'    => $vName,
                    ':unit'     => $vUnit,
                    ':pieces'   => $vPieces,
                    ':price'    => $vPrice,
                    ':vid'      => $vid,
                    ':pid'      => $productId
                ]);
            }
        }
    }

    // 3. Insert New Variants if provided
    $newVariantNames = $_POST['new_variant_name'] ?? [];
    $newSellingUnits = $_POST['new_selling_unit'] ?? [];
    $newPiecesPerUnit = $_POST['new_variant_quantity'] ?? [];
    $newVariantPrices = $_POST['new_variant_price'] ?? [];

    if (is_array($newVariantNames)) {
        $stmtInsertVariant = $db->prepare('INSERT INTO product_variants (product_id, variant_name, selling_unit, pieces_per_unit, price) VALUES (:pid, :vname, :unit, :pieces, :price)');
        foreach ($newVariantNames as $idx => $nName) {
            $nNameClean = trim($nName);
            if (empty($nNameClean)) continue;
            
            $nUnit = trim($newSellingUnits[$idx] ?? 'piece');
            $nPieces = max(1, intval($newPiecesPerUnit[$idx] ?? 1));
            $nPrice = max(0, floatval($newVariantPrices[$idx] ?? 0));

            $stmtInsertVariant->execute([
                ':pid'      => $productId,
                ':vname'    => $nNameClean,
                ':unit'     => $nUnit,
                ':pieces'   => $nPieces,
                ':price'    => $nPrice
            ]);
        }
    }

    $db->commit();
    $_SESSION['flash_success'] = 'Product and variants updated successfully!';
    header('Location: ' . $productsUrl);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update Product Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage() ?: 'Failed to update product. Please try again.';
    header('Location: ' . $editUrlBase . '?id=' . $productId);
    exit;
}
