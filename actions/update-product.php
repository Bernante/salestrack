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

    // 2. Update Existing Variants
    $existingVariants = $_POST['existing_variants'] ?? []; // array of id => ['name' => ..., 'quantity' => ..., 'price' => ..., 'status' => ...]
    if (is_array($existingVariants)) {
        $stmtUpdateVariant = $db->prepare('UPDATE product_variants SET variant_name = :vname, quantity = :quantity, price = :price, status = :vstatus WHERE id = :vid AND product_id = :pid');
        foreach ($existingVariants as $vid => $vData) {
            $vName = trim($vData['name'] ?? '');
            $vQty = max(1, intval($vData['quantity'] ?? $vData['item_quantity'] ?? $vData['qty'] ?? 1));
            $vPrice = max(0, floatval($vData['price'] ?? 0));
            $vStatus = ($vData['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

            if (!empty($vName)) {
                $stmtUpdateVariant->execute([
                    ':vname'    => $vName,
                    ':quantity' => $vQty,
                    ':price'    => $vPrice,
                    ':vstatus'  => $vStatus,
                    ':vid'      => intval($vid),
                    ':pid'      => $productId
                ]);
            }
        }
    }

    // 3. Insert New Variants if provided
    $newVariantNames = $_POST['new_variant_name'] ?? [];
    $newVariantQuantities = $_POST['new_variant_quantity'] ?? $_POST['new_item_quantity'] ?? [];
    $newVariantPrices = $_POST['new_variant_price'] ?? [];

    if (is_array($newVariantNames)) {
        $stmtInsertVariant = $db->prepare('INSERT INTO product_variants (product_id, variant_name, quantity, price, status) VALUES (:pid, :vname, :quantity, :price, "active")');
        foreach ($newVariantNames as $idx => $nName) {
            $nNameClean = trim($nName);
            if (empty($nNameClean)) continue;
            $nQty = max(1, intval($newVariantQuantities[$idx] ?? 1));
            $nPrice = max(0, floatval($newVariantPrices[$idx] ?? 0));

            $stmtInsertVariant->execute([
                ':pid'      => $productId,
                ':vname'    => $nNameClean,
                ':quantity' => $nQty,
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
