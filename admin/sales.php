<?php
$pageTitle = 'All Sales History';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = getDBConnection();
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = '(s.transaction_number LIKE :search OR u.name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if (!empty($statusFilter) && in_array($statusFilter, ['completed', 'cancelled'])) {
    $where[] = 's.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT s.*, u.name AS staff_name
    FROM sales s
    JOIN users u ON s.user_id = u.id
    $whereSql
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">All Sales Transactions History</h1>
            <p class="text-sm text-brand-300">Monitor all completed and cancelled customer receipts in real-time.</p>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <input type="text" name="search" value="<?= e($search); ?>" placeholder="Search Txn # or Staff" class="px-4 py-2 rounded-md border border-brand-200 text-sm focus:outline-none focus:border-brand-500 w-full sm:w-56">
            <select name="status" class="px-3.5 py-2 rounded-md border border-brand-200 text-sm font-semibold text-brand-700 focus:outline-none focus:border-brand-500">
                <option value="">All Statuses</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-md text-sm font-semibold shadow-sm transition-colors">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="/admin/sales.php" class="text-sm font-semibold text-brand-300 hover:text-brand-500 px-2">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-brand-200">
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Staff</th>
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
                        <tr><td colspan="8" class="px-6 py-8 text-center text-sm text-brand-300 italic">No transaction records found matching search filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4 font-semibold font-mono text-brand-700 text-sm"><?= e($s['transaction_number']); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm"><?= e($s['staff_name']); ?></td>
                                <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($s['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($s['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm">₱<?= number_format($s['change_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm"><?= date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $s['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                                        <?= e(ucfirst($s['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Receipt Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
