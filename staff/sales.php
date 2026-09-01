<?php
$pageTitle = 'My Sales History';
require_once __DIR__ . '/../includes/staff-auth.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');

$where = ['s.user_id = :user_id'];
$params = [':user_id' => $userId];

if (!empty($search)) {
    $where[] = 's.transaction_number LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.*
    FROM sales s
    $whereSql
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">My Sales History</h1>
            <p class="text-sm text-brand-300">View transactions you have recorded at the store.</p>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2 w-full">
            <input type="text" name="search" value="<?= e($search); ?>" placeholder="Transaction #" class="px-4 py-2 rounded-md border border-brand-200 text-sm focus:outline-none focus:border-brand-500 w-full sm:w-48 h-10">
            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-md text-sm font-semibold shadow-sm transition-colors h-10">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/staff/sales.php" class="text-sm font-semibold text-brand-300 hover:text-brand-500 px-2 py-2 text-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-full">
                <thead>
                    <tr class="border-b border-brand-200">
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Total Amount</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Amount Paid</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Change</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Date / Time</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Status</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-brand-300 italic">No sales history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4 font-semibold font-mono text-brand-700 text-sm"><?= e($s['transaction_number']); ?></td>
                                <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($s['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($s['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm">₱<?= number_format($s['change_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm"><?= date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $s['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                                        <?= e(ucfirst($s['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-1.5">
                                    <a href="/staff/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">View Receipt</a>
                                    <?php if ($s['status'] === 'completed'): ?>
                                        <button type="button" onclick="openStaffCancelModal(<?= $s['id']; ?>, '<?= e($s['transaction_number']); ?>')" class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-sm text-sm font-semibold transition-colors">Cancel Order</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Cancellation Modal Dialog for Staff -->
<div id="staffCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-brand-900/60 backdrop-blur-sm p-4 hidden">
    <div class="bg-white w-full max-w-md rounded-md p-6 shadow-card border border-brand-200 space-y-5">
        <div class="flex items-start justify-between border-b border-brand-200 pb-3">
            <div>
                <h3 class="text-lg font-bold text-brand-700">Cancel this order?</h3>
                <p class="text-xs text-brand-300 mt-0.5">Transaction: <span id="modalTxnNum" class="font-mono font-semibold text-brand-700"></span></p>
            </div>
            <button type="button" onclick="closeStaffCancelModal()" class="text-brand-300 hover:text-brand-500 text-lg font-bold">&times;</button>
        </div>

        <form action="/actions/cancel-sale.php" method="POST" class="space-y-4">
            <?= getCsrfField(); ?>
            <input type="hidden" name="sale_id" id="modalSaleId" value="">

            <p class="text-xs text-slate-600 font-medium">Are you sure you want to cancel this order? This action will mark the transaction as cancelled.</p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeStaffCancelModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-2xl transition">Keep Order</button>
                <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-2xl shadow-md transition active:scale-95">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStaffCancelModal(saleId, txnNum) {
    document.getElementById('modalSaleId').value = saleId;
    document.getElementById('modalTxnNum').textContent = txnNum;
    document.getElementById('staffCancelModal').classList.remove('hidden');
}

function closeStaffCancelModal() {
    document.getElementById('staffCancelModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
