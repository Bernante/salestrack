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
$productStatus = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

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
        $stmt = $db->prepare('UPDATE products SET name = :name, status = :status, image = :image WHERE id = :id');
        $stmt->execute([
            ':name'   => $productName,
            ':status' => $productStatus,
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
        $stmt = $db->prepare('UPDATE products SET name = :name, status = :status WHERE id = :id');
        $stmt->execute([
            ':name'   => $productName,
            ':status' => $productStatus,
            ':id'     => $productId
        ]);
    }

    // 2. Update Existing Variants with selling_unit and pieces_per_unit
    // Support both Admin format (variant_id[], variant_name[], etc.) and Staff format (existing_variants[id][name], etc.)
    
    $stmtUpdateVariant = $db->prepare('UPDATE product_variants SET variant_name = :vname, selling_unit = :unit, pieces_per_unit = :pieces, price = :price, status = :vstatus WHERE id = :vid AND product_id = :pid');
    
    // Handle Admin format: variant_id[], variant_name[], selling_unit[], pieces_per_unit[], variant_price[], variant_status[]
    if (!empty($_POST['variant_id']) && is_array($_POST['variant_id'])) {
        $variantIds = $_POST['variant_id'];
        $variantNames = $_POST['variant_name'] ?? [];
        $sellingUnits = $_POST['selling_unit'] ?? [];
        $piecesPerUnit = $_POST['pieces_per_unit'] ?? [];
        $variantPrices = $_POST['variant_price'] ?? [];
        $variantStatuses = $_POST['variant_status'] ?? [];

        foreach ($variantIds as $idx => $vid) {
            $vid = intval($vid);
            if ($vid <= 0) continue;
            
            $vName = trim($variantNames[$idx] ?? '');
            $vUnit = trim($sellingUnits[$idx] ?? 'piece');
            $vPieces = max(1, intval($piecesPerUnit[$idx] ?? 1));
            $vPrice = max(0, floatval($variantPrices[$idx] ?? 0));
            $vStatus = ($variantStatuses[$idx] ?? 'active') === 'active' ? 'active' : 'inactive';

            if (!empty($vName)) {
                $stmtUpdateVariant->execute([
                    ':vname'    => $vName,
                    ':unit'     => $vUnit,
                    ':pieces'   => $vPieces,
                    ':price'    => $vPrice,
                    ':vstatus'  => $vStatus,
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
            $vStatus = ($variantData['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

            if (!empty($vName)) {
                $stmtUpdateVariant->execute([
                    ':vname'    => $vName,
                    ':unit'     => $vUnit,
                    ':pieces'   => $vPieces,
                    ':price'    => $vPrice,
                    ':vstatus'  => $vStatus,
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
        $stmtInsertVariant = $db->prepare('INSERT INTO product_variants (product_id, variant_name, selling_unit, pieces_per_unit, price, status) VALUES (:pid, :vname, :unit, :pieces, :price, "active")');
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
