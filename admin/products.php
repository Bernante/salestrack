<?php
$pageTitle = 'Products & Prices';
require_once __DIR__ . '/../includes/admin-auth.php';

$db = getDBConnection();
$rows = $db->query('
    SELECT p.id AS product_id, p.name AS product_name, p.image AS product_image, p.status AS product_status,
           pv.id AS variant_id, pv.variant_name, pv.quantity, pv.selling_unit, pv.pieces_per_unit, pv.price, pv.status AS variant_status
    FROM products p
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    ORDER BY p.id ASC, pv.id ASC
')->fetchAll();

$products = [];
foreach ($rows as $row) {
    $pid = $row['product_id'];
    if (!isset($products[$pid])) {
        $products[$pid] = [
            'id'       => $pid,
            'name'     => $row['product_name'],
            'image'    => $row['product_image'],
            'status'   => $row['product_status'],
            'variants' => []
        ];
    }
    if ($row['variant_id']) {
        $products[$pid]['variants'][] = [
            'id'              => $row['variant_id'],
            'name'            => $row['variant_name'],
            'quantity'        => intval($row['quantity'] ?? 1),
            'selling_unit'    => $row['selling_unit'] ?? 'piece',
            'pieces_per_unit' => intval($row['pieces_per_unit'] ?? 1),
            'price'           => $row['price'],
            'status'          => $row['variant_status']
        ];
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">Products & Price Management</h1>
            <p class="text-sm text-brand-300">Manage store item catalog, variant prices, photos, and active status.</p>
        </div>
        <a href="/admin/product-create.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm rounded-md shadow-sm transition-colors">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-full">
                <thead>
                    <tr class="border-b border-brand-200">
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Photo</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Product Name</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Variant Name</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Selling Unit</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Pieces</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Price (₱)</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Variant Status</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <?php $vCount = count($prod['variants']); ?>
                        <?php $imgUrl = getProductImage($prod['image'], $prod['name']); ?>
                        <?php if ($vCount === 0): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4">
                                    <img src="<?= e($imgUrl); ?>" alt="<?= e($prod['name']); ?>" class="w-12 h-12 rounded-md object-cover border border-brand-200">
                                </td>
                                <td class="px-6 py-4 font-bold text-brand-700"><?= e($prod['name']); ?></td>
                                <td class="px-6 py-4 text-brand-300 italic" colspan="4">No variants configured</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/product-edit.php?id=<?= $prod['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Edit</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prod['variants'] as $i => $v): ?>
                                <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                    <?php if ($i === 0): ?>
                                        <td rowspan="<?= $vCount; ?>" class="px-6 py-4 border-r border-brand-200 align-top">
                                            <img src="<?= e($imgUrl); ?>" alt="<?= e($prod['name']); ?>" class="w-14 h-14 rounded-md object-cover border border-brand-200">
                                        </td>
                                        <td rowspan="<?= $vCount; ?>" class="px-6 py-4 border-r border-brand-200 align-top">
                                            <span class="font-bold text-base text-brand-700 block"><?= e($prod['name']); ?></span>
                                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider <?= $prod['status'] === 'active' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-brand-100 text-brand-400'; ?>">
                                                <?= e(ucfirst($prod['status'])); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-3.5 font-semibold text-brand-700"><?= e($v['name']); ?></td>
                                    <td class="px-6 py-3.5 text-brand-700 font-medium">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-brand-100 text-brand-700 border border-brand-200">
                                            <?php
                                                $unit = $v['selling_unit'] ?? 'piece';
                                                $unitLabels = [
                                                    'piece' => 'Piece',
                                                    'half_tray' => 'Half Tray',
                                                    'tray' => 'Tray',
                                                    'bundle' => 'Bundle'
                                                ];
                                                echo e($unitLabels[$unit] ?? ucfirst(str_replace('_', ' ', $unit)));
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-brand-700 font-medium">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-brand-100 text-brand-700 border border-brand-200">
                                            <?= intval($v['pieces_per_unit'] ?? 1); ?> <?= intval($v['pieces_per_unit'] ?? 1) === 1 ? 'pc' : 'pcs'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-brand-500 font-bold text-base">₱<?= number_format($v['price'], 2); ?></td>
                                    <td class="px-6 py-3.5">
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider <?= $v['status'] === 'active' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                                            <?= e(ucfirst($v['status'])); ?>
                                        </span>
                                    </td>
                                    <?php if ($i === 0): ?>
                                        <td rowspan="<?= $vCount; ?>" class="px-6 py-4 text-right border-l border-brand-200 align-top">
                                            <a href="/admin/product-edit.php?id=<?= $prod['id']; ?>" class="inline-flex items-center px-3.5 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">
                                                Edit Product
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
