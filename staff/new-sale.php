<?php
$pageTitle = 'New Sale';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = getDBConnection();
$rows = $db->query('
    SELECT p.id AS product_id, p.name AS product_name, p.image AS product_image,
           pv.id AS variant_id, pv.variant_name, COALESCE(pv.quantity, 1) AS quantity, pv.price, pv.status AS variant_status
    FROM products p JOIN product_variants pv ON p.id = pv.product_id
    WHERE p.status = "active" AND pv.status = "active"
    ORDER BY p.name ASC, pv.price ASC
')->fetchAll();

$productsMap = [];
foreach ($rows as $r) {
    $pid = $r['product_id'];
    if (!isset($productsMap[$pid])) {
        $productsMap[$pid] = [
            'id'       => $pid,
            'name'     => $r['product_name'],
            'image'    => getProductImage($r['product_image'], $r['product_name']),
            'variants' => []
        ];
    }
    $productsMap[$pid]['variants'][] = [
        'id'           => $r['variant_id'],
        'variant_name' => $r['variant_name'],
        'quantity'     => intval($r['quantity'] ?? 1),
        'price'        => floatval($r['price']),
        'status'       => $r['variant_status']
    ];
}
$productsJson = json_encode(array_values($productsMap));
$completedSale = $_SESSION['completed_sale'] ?? null;
if ($completedSale) unset($_SESSION['completed_sale']);
include __DIR__ . '/../includes/header.php';
?>
<div class="py-6 space-y-6">
<?php if ($completedSale): ?>
    <div class="bg-brand-500 text-white rounded-md p-6 sm:p-8 text-center space-y-6 shadow-card">
        <div class="w-16 h-16 rounded-md bg-white text-brand-500 flex items-center justify-center font-bold text-3xl mx-auto">✓</div>
        <div>
            <h2 class="text-3xl font-bold tracking-tight">SALE COMPLETED!</h2>
            <p class="text-white/80 text-sm mt-1">Transaction recorded and saved to store records.</p>
        </div>
        
        <div class="max-w-sm mx-auto bg-white text-brand-700 rounded-md p-5 text-left space-y-3 shadow-card">
            <div class="flex justify-between border-b border-brand-200 pb-2 text-sm">
                <span class="text-brand-300 font-semibold">Transaction #:</span>
                <span class="font-mono font-bold text-brand-700"><?= e($completedSale['transaction_number']); ?></span>
            </div>
            <div class="flex justify-between border-b border-brand-200 pb-2 text-sm">
                <span class="text-brand-300 font-semibold">Total Amount:</span>
                <span class="font-bold text-brand-700">₱<?= number_format($completedSale['total_amount'], 2); ?></span>
            </div>
            <div class="flex justify-between border-b border-brand-200 pb-2 text-sm">
                <span class="text-brand-300 font-semibold">Amount Tendered:</span>
                <span class="font-bold text-brand-700">₱<?= number_format($completedSale['amount_paid'], 2); ?></span>
            </div>
            <div class="flex justify-between text-base pt-1">
                <span class="font-bold text-brand-700">Change Due:</span>
                <span class="font-bold text-green-600">₱<?= number_format($completedSale['change_amount'], 2); ?></span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-3 max-w-sm mx-auto">
            <a href="/staff/new-sale.php" class="px-6 py-3 bg-white text-brand-500 hover:bg-brand-50 font-bold text-sm rounded-md shadow-sm transition-colors text-center">
                + NEW TRANSACTION
            </a>
            <a href="/staff/sale-details.php?id=<?= $completedSale['sale_id']; ?>" class="px-6 py-3 bg-brand-700 hover:bg-brand-800 text-white font-bold text-sm rounded-md border border-brand-400 transition-colors text-center">
                VIEW RECEIPT
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- LEFT: PRODUCT CATALOG GRID -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white p-4 rounded-md border border-brand-200 shadow-card flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-brand-700">📦 Product Catalog</h2>
                    <p class="text-xs text-brand-300">Tap a product card to select variant & quantity</p>
                </div>
                <input type="text" id="productSearchInput" placeholder="Search product..." class="w-full sm:w-48 px-3 py-1.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
            </div>

            <!-- Product Cards Grid -->
            <div id="productGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                <?php foreach ($productsMap as $p): ?>
                    <?php 
                        $vCount = count($p['variants']);
                        $firstVariant = $p['variants'][0] ?? null;
                        $firstVariantName = $firstVariant ? $firstVariant['variant_name'] : 'Default';
                        $firstPrice = $firstVariant ? floatval($firstVariant['price']) : 0;
                    ?>
                    <button type="button" 
                            class="product-card group relative flex flex-col items-center justify-between text-center bg-white p-3.5 sm:p-4 rounded-md border border-brand-200 hover:border-brand-500 focus:border-brand-500 focus:outline-none hover:shadow-card active:scale-98 transition-all duration-150 cursor-pointer select-none min-h-[180px] sm:min-h-[210px]"
                            data-id="<?= $p['id']; ?>"
                            data-name="<?= e($p['name']); ?>"
                            aria-label="Select product <?= e($p['name']); ?>">
                        
                        <!-- Product Photo -->
                        <div class="w-full aspect-square max-h-24 sm:max-h-28 rounded-md overflow-hidden bg-brand-50 flex items-center justify-center border border-brand-100 group-hover:scale-105 transition-transform duration-200 mb-2">
                            <img src="<?= e($p['image']); ?>" alt="<?= e($p['name']); ?>" class="w-full h-full object-contain p-1">
                        </div>

                        <!-- Product Name & Variant Info -->
                        <div class="w-full space-y-1 my-auto">
                            <h3 class="font-bold text-brand-700 text-sm sm:text-base leading-tight uppercase tracking-tight group-hover:text-brand-500 transition-colors">
                                <?= e($p['name']); ?>
                            </h3>
                            <p class="text-xs font-semibold text-brand-300">
                                <?= e($firstVariantName); ?> • <span class="font-bold text-green-600">₱<?= number_format($firstPrice, 2); ?></span>
                            </p>
                            <?php if ($vCount > 1): ?>
                                <p class="text-[11px] font-semibold text-brand-500 bg-brand-100 rounded-md py-0.5 px-2 inline-block border border-brand-200">
                                    <?= $vCount; ?> variants
                                </p>
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT: CURRENT CART & CHECKOUT -->
        <div class="lg:col-span-5 space-y-4 sticky top-6">
            <form id="saleForm" action="/actions/save-sale.php" method="POST" class="bg-white p-5 rounded-md border border-brand-200 shadow-card space-y-4">
                <?= getCsrfField(); ?>
                <input type="hidden" name="cart_items" id="cartItemsInput">
                
                <div class="flex justify-between items-center border-b border-brand-200 pb-3">
                    <h2 class="text-base font-bold text-brand-700">🛒 CURRENT SALE</h2>
                    <span id="cartCountBadge" class="px-2.5 py-0.5 bg-brand-100 text-brand-500 rounded-md text-xs font-semibold">0 items</span>
                </div>

                <div class="overflow-x-auto border border-brand-200 rounded-md">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-brand-50 text-xs text-brand-300 font-semibold border-b border-brand-200">
                            <tr>
                                <th class="p-2">Item</th>
                                <th class="p-2">Qty</th>
                                <th class="p-2">Price</th>
                                <th class="p-2">Subtotal</th>
                                <th class="p-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody" class="divide-y divide-brand-100">
                            <tr id="emptyCartRow">
                                <td colspan="5" class="p-6 text-center text-brand-300 italic">
                                    No products selected yet.<br>
                                    <span class="text-xs">Tap any product card on the left to start.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-brand-50 p-4 rounded-md border border-brand-200 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-brand-700">TOTAL AMOUNT:</span>
                        <span id="totalAmountDisplay" class="text-3xl font-extrabold text-brand-500">₱0.00</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-brand-200">
                        <div>
                            <label for="amountPaidInput" class="block text-xs font-semibold text-brand-700 mb-1">Amount Paid (₱) *</label>
                            <input type="number" id="amountPaidInput" name="amount_paid" step="0.01" min="0" placeholder="0.00" required class="w-full p-2.5 rounded-md border border-brand-200 font-bold text-lg text-brand-700 focus:outline-none focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-brand-700 mb-1">Change</label>
                            <div id="changeAmountDisplay" class="text-2xl font-bold text-brand-300 py-1.5 px-2 bg-white rounded-md border border-brand-200">₱0.00</div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="completeSaleBtn" disabled class="w-full py-3.5 bg-brand-500 hover:bg-brand-600 disabled:bg-brand-200 disabled:text-brand-300 text-white font-bold text-base rounded-md shadow-sm transition-colors">
                    COMPLETE SALE &rarr;
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: VARIANT SELECTION & QUANTITY -->
    <div id="variantModal" class="fixed inset-0 bg-brand-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-md max-w-md w-full p-6 space-y-5 shadow-card border border-brand-200 transform transition-all scale-95 opacity-0 duration-200" id="variantModalContent">
            <div class="flex justify-between items-start border-b border-brand-200 pb-3">
                <div class="flex items-center gap-3">
                    <img id="modalProductImg" src="" alt="Product" class="w-14 h-14 rounded-md object-cover border border-brand-200 shadow-sm">
                    <div>
                        <h3 id="modalProductName" class="text-xl font-bold text-brand-700"></h3>
                        <p class="text-xs text-brand-300">Select variant & quantity</p>
                    </div>
                </div>
                <button type="button" id="closeVariantModalBtn" class="text-brand-300 hover:text-brand-500 text-2xl font-bold p-1">&times;</button>
            </div>

            <div class="space-y-3">
                <label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider">1. Select Variant</label>
                <div id="modalVariantList" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <!-- Dynamic variants pills -->
                </div>
            </div>

            <div class="space-y-2 border-t border-brand-200 pt-3">
                <label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider">2. Quantity</label>
                <div class="flex items-center gap-3">
                    <button type="button" id="modalQtyMinus" class="w-12 h-12 rounded-md bg-brand-100 hover:bg-brand-200 text-brand-700 font-bold text-xl flex items-center justify-center transition-colors">-</button>
                    <input type="number" id="modalQtyInput" value="1" min="1" class="w-full text-center py-2 text-xl font-bold rounded-md border border-brand-200 text-brand-700 focus:outline-none focus:border-brand-500">
                    <button type="button" id="modalQtyPlus" class="w-12 h-12 rounded-md bg-brand-100 hover:bg-brand-200 text-brand-700 font-bold text-xl flex items-center justify-center transition-colors">+</button>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-3 border-t border-brand-200">
                <button type="button" id="cancelVariantModalBtn" class="w-1/3 py-2.5 rounded-md border border-brand-200 font-semibold text-brand-700 text-sm hover:bg-brand-50 transition-colors">Cancel</button>
                <button type="button" id="confirmAddToCartBtn" class="w-2/3 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-sm transition-colors">
                    + Add to Cart
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
<script src="/assets/js/sales.js"></script>
<script>
let cartManager;
document.addEventListener('DOMContentLoaded', () => { cartManager = new SalesCartManager(<?= $productsJson; ?>); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

