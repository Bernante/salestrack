<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) { header('Location: /staff/products.php'); exit; }

$db = getDBConnection();
$stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();
if (!$product) { header('Location: /staff/products.php'); exit; }

$stmtV = $db->prepare('SELECT * FROM product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY id ASC');
$stmtV->execute([':pid' => $productId]);
$variants = $stmtV->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 class="text-lg sm:text-xl font-bold text-brand-700">Edit Product: <?= e($product['name']); ?></h1>
        <a href="/staff/products.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">&larr; Back to Products</a>
    </div>

    <form action="/actions/update-product.php" method="POST" enctype="multipart/form-data" class="w-full rounded-md border border-brand-200 bg-white shadow-card p-4 sm:p-6 space-y-6">
        <?= getCsrfField(); ?>
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            <div class="md:col-span-2 space-y-4">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-brand-700 mb-1">Product Name</label>
                    <input type="text" name="name" value="<?= e($product['name']); ?>" required class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Product Photo Section -->
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200 text-center space-y-3">
                <label class="block text-xs sm:text-sm font-semibold text-brand-700">Product Photo</label>
                <div class="w-20 h-20 sm:w-28 sm:h-28 mx-auto rounded-md border-2 border-dashed border-brand-200 flex items-center justify-center bg-white overflow-hidden relative shadow-inner">
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
            <h3 class="text-base font-bold text-brand-700 mb-3">Existing Variants</h3>
            <div class="space-y-3">
                <?php 
                $variantCount = count($variants);
                foreach ($variants as $v): 
                ?>
                
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end bg-brand-50 p-4 rounded-md border border-brand-200">
                        
                        <!-- Variant Name -->
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-xs font-semibold text-brand-700 mb-1.5">Variant Name</label>
                            <input type="text" name="existing_variants[<?= $v['id']; ?>][name]" value="<?= e($v['variant_name']); ?>" required class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                        </div>
                        
                        <!-- Selling Unit -->
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-xs font-semibold text-brand-700 mb-1.5">Selling Unit</label>
                            <select name="existing_variants[<?= $v['id']; ?>][selling_unit]" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                                <option value="piece" <?= ($v['selling_unit'] ?? 'piece') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                                <option value="half_tray" <?= ($v['selling_unit'] ?? '') === 'half_tray' ? 'selected' : ''; ?>>Half Tray</option>
                                <option value="tray" <?= ($v['selling_unit'] ?? '') === 'tray' ? 'selected' : ''; ?>>Tray</option>
                                <option value="bundle" <?= ($v['selling_unit'] ?? '') === 'bundle' ? 'selected' : ''; ?>>Bundle</option>
                            </select>
                        </div>
                        
                        <!-- Qty per Unit -->
                        <div class="sm:col-span-1 lg:col-span-2">
                            <label class="block text-xs font-semibold text-brand-700 mb-1.5">Qty per Unit</label>
                            <input type="number" min="1" step="1" name="existing_variants[<?= $v['id']; ?>][pieces_per_unit]" value="<?= intval($v['pieces_per_unit'] ?? 1); ?>" required class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                        </div>
                        
                        <!-- Price -->
                        <div class="sm:col-span-1 lg:col-span-2">
                            <label class="block text-xs font-semibold text-brand-700 mb-1.5">Price (₱)</label>
                            <input type="number" step="0.01" min="0" name="existing_variants[<?= $v['id']; ?>][price]" value="<?= number_format($v['price'], 2, '.', ''); ?>" required class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                        </div>
                        
                        <!-- Remove Button (only show if 2+ variants exist) -->
                        <?php if ($variantCount > 1): ?>
                        <div class="sm:col-span-2 lg:col-span-2 pt-1 lg:pt-0">
                            <button type="button" class="removeVariantBtn w-full px-3 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 font-semibold text-sm rounded-md border border-brand-200 transition-colors focus:outline-none focus:border-brand-300" data-variant-id="<?= $v['id']; ?>">
                                Remove
                            </button>
                        </div>
                        <?php endif; ?>

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
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center new-variant-row bg-brand-50 p-3 rounded-md border border-brand-200">
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Variant Name</label>
                        <input type="text" name="new_variant_name[]" placeholder="Variant Name (e.g. Small Tray)" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Selling Unit</label>
                        <select name="new_selling_unit[]" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                            <option value="piece">Piece</option>
                            <option value="half_tray">Half Tray</option>
                            <option value="tray">Tray</option>
                            <option value="bundle">Bundle</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Selling Qty (pcs)</label>
                        <input type="number" min="1" step="1" name="new_variant_quantity[]" value="1" placeholder="Qty" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-brand-700 mb-1">Price (₱)</label>
                        <input type="number" step="0.01" min="0" name="new_variant_price[]" placeholder="Price (₱)" class="w-full px-3 py-2 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-brand-200 flex flex-col sm:flex-row sm:justify-between gap-3">
            <div>
                <button type="button" id="deleteProductBtn" class="px-4 sm:px-5 py-2.5 rounded-md bg-brand-900 hover:bg-brand-600 text-white font-semibold text-sm transition-colors">
                    🗑️ Delete Product
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="/staff/products.php" class="px-4 sm:px-5 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors text-center">Cancel</a>
                <button type="submit" class="px-4 sm:px-5 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-sm transition-colors">Save Changes</button>
            </div>
        </div>
    </form>
</div>

<!-- DELETE SUCCESS MODAL -->
<div id="deleteSuccessModal" class="hidden fixed inset-0 bg-brand-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-brand-100">
                <span class="text-brand-700 text-xl">✅</span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-brand-900">Product Deleted Successfully!</h3>
                <p id="successMessage" class="text-sm text-brand-600 mt-1">The product has been removed.</p>
            </div>
        </div>

        <div class="bg-brand-50 p-4 rounded-md border border-brand-200">
            <p class="text-sm"><span class="font-semibold text-brand-700">Product:</span> <span id="successProductName" class="text-brand-600"></span></p>
            <p class="text-sm mt-2"><span class="font-semibold text-brand-700">Variants deleted:</span> <span id="successVariantCount" class="text-brand-600">0</span></p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="button" id="okDeleteBtn" class="flex-1 px-4 py-2.5 rounded-md bg-brand-900 hover:bg-brand-600 text-white font-semibold text-sm transition-colors">
                OK
            </button>
        </div>
    </div>
</div>

<!-- REMOVE VARIANT CONFIRMATION MODAL -->
<div id="removeVariantModal" class="hidden fixed inset-0 bg-brand-900/60 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-brand-100">
                <span class="text-brand-500 text-xl">⚠️</span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-brand-900">Remove Variant?</h3>
                <p class="text-sm text-brand-600 mt-1">This will be deleted when you save the product.</p>
            </div>
        </div>

        <div class="bg-brand-50 p-4 rounded-md border border-brand-200">
            <p class="text-sm text-brand-700">
                <span class="font-semibold">Variant:</span> <span id="removeVariantName" class="text-brand-600"></span>
            </p>
            <p class="text-sm mt-2 text-brand-600">
                Only this variant will be removed. The product and other variants will remain.
            </p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="button" id="cancelRemoveVariantBtn" class="flex-1 px-4 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors">
                Cancel
            </button>
            <button type="button" id="confirmRemoveVariantBtn" class="flex-1 px-4 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm transition-colors">
                Remove Variant
            </button>
        </div>
    </div>
</div>

<!-- DELETE PRODUCT CONFIRMATION MODAL -->
<div id="deleteModal" class="hidden fixed inset-0 bg-brand-900/60 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-brand-200">
                <span class="text-brand-500 text-xl">⚠️</span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-brand-900">Delete Product?</h3>
                <p class="text-sm text-brand-600 mt-1">This action cannot be undone.</p>
            </div>
        </div>

        <div id="productDeleteInfo" class="bg-brand-50 p-4 rounded-md border border-brand-200">
            <p class="text-sm"><span class="font-semibold text-brand-700">Product:</span> <span id="deleteProductName" class="text-brand-600"></span></p>
            <p class="text-sm mt-2"><span class="font-semibold text-brand-700">Variants to delete:</span> <span id="deleteVariantCount" class="text-brand-600">0</span></p>
        </div>

        <div id="salesWarning" class="hidden bg-blue-50 p-4 rounded-md border border-blue-200">
            <p class="text-sm text-blue-800">
                <span class="font-semibold">ℹ️ Product has sales history:</span> This product will be hidden from listings and future sales, but old receipts will still show it correctly. This action cannot be undone.
            </p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="button" id="cancelDeleteBtn" class="flex-1 px-4 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors">
                Cancel
            </button>
            <button type="button" id="confirmDeleteBtn" class="flex-1 px-4 py-2.5 rounded-md bg-brand-900 hover:bg-brand-600 text-white font-semibold text-sm transition-colors">
                Delete Product
            </button>
        </div>
    </div>
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

    // ===== REMOVE VARIANT FUNCTIONALITY =====
    const removeVariantBtns = document.querySelectorAll('.removeVariantBtn');
    const removeVariantModal = document.getElementById('removeVariantModal');
    const cancelRemoveVariantBtn = document.getElementById('cancelRemoveVariantBtn');
    const confirmRemoveVariantBtn = document.getElementById('confirmRemoveVariantBtn');
    const removeVariantName = document.getElementById('removeVariantName');
    
    let pendingVariantBtn = null;
    let pendingVariantRow = null;
    
    removeVariantBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            const variantId = btn.getAttribute('data-variant-id');
            const variantRow = btn.closest('div[data-variant-id], .grid[data-variant-row]') || btn.closest('.grid');
            
            if (!variantRow) {
                console.error('Could not find variant row');
                return;
            }
            
            // Get variant name from the row for display
            const variantNameInput = variantRow.querySelector('input[name*="[name]"]');
            const variantName = variantNameInput ? variantNameInput.value : 'Variant';
            
            // Store pending variant for confirmation
            pendingVariantBtn = btn;
            pendingVariantRow = variantRow;
            
            // Update modal with variant info
            removeVariantName.textContent = variantName;
            
            // Show modal
            removeVariantModal.classList.remove('hidden');
        });
    });
    
    // Cancel remove variant
    cancelRemoveVariantBtn?.addEventListener('click', () => {
        removeVariantModal.classList.add('hidden');
        pendingVariantBtn = null;
        pendingVariantRow = null;
    });
    
    // Close modal when clicking outside
    removeVariantModal?.addEventListener('click', (e) => {
        if (e.target === removeVariantModal) {
            removeVariantModal.classList.add('hidden');
            pendingVariantBtn = null;
            pendingVariantRow = null;
        }
    });
    
    // Confirm remove variant
    confirmRemoveVariantBtn?.addEventListener('click', () => {
        if (!pendingVariantBtn || !pendingVariantRow) {
            console.error('No pending variant to remove');
            return;
        }
        
        const variantId = pendingVariantBtn.getAttribute('data-variant-id');
        
        // Get or create hidden input for tracking removed variants
        let removedInput = document.querySelector('input[name="removed_variant_ids"]');
        if (!removedInput) {
            removedInput = document.createElement('input');
            removedInput.type = 'hidden';
            removedInput.name = 'removed_variant_ids';
            removedInput.value = '';
            document.querySelector('form').appendChild(removedInput);
        }
        
        // Add this variant ID to the removed list
        const removedIds = removedInput.value ? removedInput.value.split(',') : [];
        if (!removedIds.includes(variantId)) {
            removedIds.push(variantId);
        }
        removedInput.value = removedIds.join(',');
        
        // Animate row out: fade + slide up with collapse
        pendingVariantRow.style.transition = 'all 300ms ease-out';
        pendingVariantRow.style.opacity = '0';
        pendingVariantRow.style.maxHeight = '0';
        pendingVariantRow.style.overflow = 'hidden';
        pendingVariantRow.style.marginBottom = '0';
        pendingVariantRow.style.paddingTop = '0';
        pendingVariantRow.style.paddingBottom = '0';
        
        // Remove row from DOM after animation completes
        setTimeout(() => {
            pendingVariantRow.remove();
            
            // After removal, check if only 1 variant remains and hide Remove buttons
            const remainingVariants = document.querySelectorAll('.grid[data-variant-row], .existing-variant-row, .grid:has(input[name*="existing_variants"])');
            const allRemoveButtons = document.querySelectorAll('.removeVariantBtn');
            
            if (remainingVariants.length <= 1 || allRemoveButtons.length === 1) {
                // Hide Remove button(s) - only 1 variant left
                allRemoveButtons.forEach(btn => {
                    btn.style.display = 'none';
                });
            }
        }, 300);
        
        // Close modal
        removeVariantModal.classList.add('hidden');
        pendingVariantBtn = null;
        pendingVariantRow = null;
    });

    // ===== DELETE PRODUCT FUNCTIONALITY =====
    const deleteBtn = document.getElementById('deleteProductBtn');
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteProductName = document.getElementById('deleteProductName');
    const deleteVariantCount = document.getElementById('deleteVariantCount');
    const salesWarning = document.getElementById('salesWarning');
    const productDeleteInfo = document.getElementById('productDeleteInfo');
    
    const productId = <?= $product['id']; ?>;
    const productName = <?= json_encode($product['name']); ?>;
    const variantCount = <?= count($variants); ?>;

    // Open delete modal
    deleteBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        deleteProductName.textContent = productName;
        deleteVariantCount.textContent = variantCount;
        salesWarning.classList.add('hidden');
        productDeleteInfo.classList.remove('hidden');
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.textContent = 'Delete Product';
        deleteModal.classList.remove('hidden');
    });

    // Close delete modal
    cancelDeleteBtn?.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
    });

    // Close modal when clicking outside
    deleteModal?.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });

    // Confirm and execute delete
    confirmDeleteBtn?.addEventListener('click', async () => {
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Deleting...';

        try {
            const response = await fetch('/actions/delete-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    product_id: productId,
                    csrf_token: <?= json_encode($_SESSION['csrf_token'] ?? ''); ?>
                })
            });

            const data = await response.json();

            if (data.success) {
                // Success - product deleted or soft-deleted
                if (data.wasSoftDeleted) {
                    // Soft-delete case: product has sales history, so it was hidden
                    document.getElementById('successProductName').textContent = data.productName || productName;
                    document.getElementById('successMessage').textContent = 
                        'Product hidden successfully (has sales history). Old receipts will still show it correctly.';
                    document.getElementById('successVariantCount').style.display = 'none';
                } else {
                    // Hard-delete case: product had no sales, was fully deleted
                    document.getElementById('successProductName').textContent = data.productName || productName;
                    document.getElementById('successMessage').textContent = 'Product deleted successfully';
                    document.getElementById('successVariantCount').textContent = data.variantsDeleted || variantCount;
                    document.getElementById('successVariantCount').style.display = 'block';
                }
                
                const deleteSuccessModal = document.getElementById('deleteSuccessModal');
                const okDeleteBtn = document.getElementById('okDeleteBtn');
                
                deleteSuccessModal.classList.remove('hidden');
                deleteModal.classList.add('hidden');
                
                // Handle OK button click - redirect to products list
                okDeleteBtn.onclick = () => {
                    window.location.href = '/staff/products.php';
                };
            } else {
                // Show error message
                alert('❌ Error: ' + (data.message || 'Failed to delete product'));
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Delete Product';
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('❌ Error: ' + error.message);
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Delete Product';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

