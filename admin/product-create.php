<?php
$pageTitle = 'Add New Product';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-3xl mx-auto py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-brand-700">Add New Product</h1>
        <a href="/admin/products.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">&larr; Back to Products</a>
    </div>

    <form action="/actions/save-product.php" method="POST" enctype="multipart/form-data" class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-6">
        <?= getCsrfField(); ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            <div class="md:col-span-2 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-brand-700 mb-1">Product Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Egg, Ice, Salted Egg" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Product Photo Upload Section -->
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200 text-center space-y-3">
                <label class="block text-sm font-semibold text-brand-700">Product Photo</label>
                <div class="w-28 h-28 mx-auto rounded-md border-2 border-dashed border-brand-200 flex items-center justify-center bg-white overflow-hidden relative shadow-inner">
                    <img id="photoPreview" src="<?= getProductImage(null, 'Product'); ?>" alt="Photo Preview" class="w-full h-full object-cover">
                </div>
                <div>
                    <label for="image" class="inline-block px-3 py-1.5 bg-white border border-brand-200 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-md cursor-pointer transition-colors">
                        Choose Photo
                    </label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                    <p class="text-xs text-brand-300 mt-1">JPG, PNG or WebP (Max 5MB)</p>
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-semibold text-brand-700">Product Variants, Quantity & Prices *</label>
                <button type="button" id="addVariantRowBtn" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">+ Add Another Variant</button>
            </div>

            <div id="variantsContainer" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center variant-row bg-brand-50 p-3 rounded-md border border-brand-200">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Product Variant *</label>
                        <input type="text" name="variant_name[]" placeholder="Variant Name (e.g. Small, Small Tray, Bulk)" required class="w-full px-3.5 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Selling Qty (pcs) *</label>
                        <input type="number" min="1" step="1" name="item_quantity[]" value="1" placeholder="Qty (e.g. 1, 30)" required class="w-full px-3.5 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Price (₱) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-brand-300 text-sm">₱</span>
                            <input type="number" step="0.01" min="0" name="variant_price[]" placeholder="0.00" required class="w-full pl-7 pr-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                        </div>
                    </div>
                    <div class="sm:col-span-1 text-right sm:text-center pt-2 sm:pt-4">
                        <button type="button" class="remove-variant-btn text-red-500 hover:text-red-700 text-lg font-bold p-1" style="display:none;" title="Remove Variant">&times;</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-brand-200 flex justify-end gap-3">
            <a href="/admin/products.php" class="px-5 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-sm transition-colors">Save Product</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('variantsContainer');
    const addBtn = document.getElementById('addVariantRowBtn');

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.variant-row');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.remove-variant-btn');
            if (btn) btn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
    }

    addBtn.addEventListener('click', () => {
        const firstRow = container.querySelector('.variant-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(i => i.value = '');
        container.appendChild(newRow);
        updateRemoveButtons();
    });

    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-variant-btn')) {
            const row = e.target.closest('.variant-row');
            if (container.querySelectorAll('.variant-row').length > 1) {
                row.remove();
                updateRemoveButtons();
            }
        }
    });

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

    updateRemoveButtons();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
