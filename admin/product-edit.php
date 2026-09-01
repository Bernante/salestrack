<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) { header('Location: /admin/products.php'); exit; }

$db = getDBConnection();
$stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();
if (!$product) { header('Location: /admin/products.php'); exit; }

$stmtV = $db->prepare('SELECT * FROM product_variants WHERE product_id = :pid ORDER BY id ASC');
$stmtV->execute([':pid' => $productId]);
$variants = $stmtV->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 class="text-lg sm:text-xl font-bold text-brand-700">Edit: <?= e($product['name']); ?></h1>
        <a href="/admin/products.php" class="text-sm font-semibold text-brand-500">&larr; Back</a>
    </div>

    <form action="/actions/update-product.php" method="POST" enctype="multipart/form-data" class="w-full rounded-md border border-brand-200 bg-white shadow-card p-4 sm:p-6 space-y-6">
        <?= getCsrfField(); ?>
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-4">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-brand-700 mb-1">Product Name</label>
                    <input type="text" name="name" value="<?= e($product['name']); ?>" required class="w-full px-3 sm:px-4 py-2 sm:py-2.5 rounded-md border border-brand-200 text-sm text-brand-700">
                </div>
            </div>

            <div class="bg-brand-50 p-4 rounded-md border border-brand-200 text-center">
                <label class="block text-xs sm:text-sm font-semibold text-brand-700 mb-3">Photo</label>
                <img id="photoPreview" src="<?= e(getProductImage($product['image'], $product['name'])); ?>" alt="" class="w-20 h-20 sm:w-28 sm:h-28 object-cover rounded mx-auto mb-3">
                <label for="image" class="inline-block px-3 py-1.5 bg-white border border-brand-200 text-brand-700 text-xs font-semibold rounded cursor-pointer hover:bg-brand-100">Change</label>
                <input type="file" id="image" name="image" accept="image/*" class="hidden">
            </div>
        </div>

        <div>
            <h3 class="text-base font-bold text-brand-700 mb-3">Variants</h3>
            <div class="space-y-3">
                <?php foreach ($variants as $v): ?>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end bg-brand-50 p-3 rounded border border-brand-200">
                    <input type="hidden" name="variant_id[]" value="<?= $v['id']; ?>">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Variant Name</label>
                        <input type="text" name="variant_name[]" value="<?= e($v['variant_name']); ?>" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Selling Unit</label>
                        <select name="selling_unit[]" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700">
                            <option value="piece" <?= ($v['selling_unit'] ?? 'piece') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                            <option value="half_tray" <?= ($v['selling_unit'] ?? '') === 'half_tray' ? 'selected' : ''; ?>>Half Tray</option>
                            <option value="tray" <?= ($v['selling_unit'] ?? '') === 'tray' ? 'selected' : ''; ?>>Tray</option>
                            <option value="bundle" <?= ($v['selling_unit'] ?? '') === 'bundle' ? 'selected' : ''; ?>>Bundle</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Qty per Unit</label>
                        <input type="number" name="pieces_per_unit[]" value="<?= intval($v['pieces_per_unit'] ?? 1); ?>" min="1" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Price (₱)</label>
                        <input type="number" name="variant_price[]" value="<?= number_format($v['price'], 2); ?>" step="0.01" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Status</label>
                        <select name="variant_status[]" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700">
                            <option value="active" <?= $v['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pt-4 border-t border-brand-200 flex flex-col sm:flex-row sm:justify-end gap-3">
            <a href="/admin/products.php" class="px-4 sm:px-5 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors text-center">Cancel</a>
            <button type="submit" class="px-4 sm:px-5 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm">Save</button>
        </div>
    </form>
</div>

<script>
document.getElementById('image')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (evt) => {
            document.getElementById('photoPreview').src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
