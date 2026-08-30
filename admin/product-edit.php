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
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-brand-700">Edit Product: <?= e($product['name']); ?></h1>
        <a href="/admin/products.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">&larr; Back to Products</a>
    </div>

    <form action="/actions/update-product.php" method="POST" enctype="multipart/form-data" class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-6">
        <?= getCsrfField(); ?>
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            <div class="md:col-span-2 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-brand-700 mb-1">Product Name</label>
                    <input type="text" name="name" value="<?= e($product['name']); ?>" required class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-brand-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Product Photo Section -->
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200 text-center space-y-3">
                <label class="block text-sm font-semibold text-brand-700">Product Photo</label>
                <div class="w-28 h-28 mx-auto rounded-md border-2 border-dashed border-brand-200 flex items-center justify-center bg-white overflow-hidden relative shadow-inner">
                    <img id="photoPreview" src="<?= e(getProductImage($product['image'], $product['name'])); ?>" alt="<?= e($product['name']); ?>" class="w-full h-full object-cover">
                </div>
                <div>
                    <label for="image" class="inline-block px-3 py-1.5 bg-white border border-brand-200 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-md cursor-pointer transition-colors">
                        Change Photo
                    </label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                    <p class="text-xs text-brand-300 mt-1">JPG, PNG or WebP (Max 5MB)</p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-base font-bold text-slate-800 mb-3">Existing Variants</h3>
            <div class="space-y-3">
                <?php foreach ($variants as $v): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div class="sm:col-span-5">
                            <label class="block text-xs text-slate-500">Variant Name</label>
                            <input type="text" name="existing_variants[<?= $v['id']; ?>][name]" value="<?= e($v['variant_name']); ?>" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-xs text-slate-500">Price (₱)</label>
                            <input type="number" step="0.01" min="0" name="existing_variants[<?= $v['id']; ?>][price]" value="<?= number_format($v['price'], 2, '.', ''); ?>" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs text-slate-500">Status</label>
                            <select name="existing_variants[<?= $v['id']; ?>][status]" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm">
                                <option value="active" <?= $v['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?= $v['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-bold text-brand-700">Add New Variants</h3>
                <button type="button" id="addNewVariantBtn" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">+ Add Another</button>
            </div>
            <div id="newVariantsContainer" class="space-y-3">
                <div class="flex items-center gap-3 new-variant-row">
                    <input type="text" name="new_variant_name[]" placeholder="New Variant Name" class="flex-1 px-4 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    <input type="number" step="0.01" min="0" name="new_variant_price[]" placeholder="Price (₱)" class="w-36 px-4 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-brand-200 flex justify-end gap-3">
            <a href="/admin/products.php" class="px-5 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-sm transition-colors">Save Changes</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const imgInput = document.getElementById('image');
    const photoPreview = document.getElementById('photoPreview');
    if (imgInput && photoPreview) {
        imgInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (evt) => {
                    photoPreview.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    document.getElementById('addNewVariantBtn')?.addEventListener('click', () => {
        const container = document.getElementById('newVariantsContainer');
        const firstRow = container.querySelector('.new-variant-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(i => i.value = '');
        container.appendChild(newRow);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
