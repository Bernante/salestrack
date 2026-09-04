<?php
$pageTitle = "Sales History";
require_once __DIR__ . "/../includes/staff-auth.php";

$db = getDBConnection();
date_default_timezone_set("Asia/Manila");

$filterDate = $_GET["date"] ?? "";
$filterMonth = $_GET["month"] ?? date("Y-m");

$where = " WHERE s.user_id = ? ";
$params = [$_SESSION["user_id"]];

if ($filterDate && preg_match("/^\\d{4}-\\d{2}-\\d{2}$/", $filterDate)) {
    $where .= " AND DATE(s.created_at) = ? ";
    $params[] = $filterDate;
} elseif ($filterMonth && preg_match("/^\\d{4}-\\d{2}$/", $filterMonth)) {
    $where .= " AND DATE_FORMAT(s.created_at, \"%Y-%m\") = ? ";
    $params[] = $filterMonth;
}

$stmt = $db->prepare("SELECT s.id, s.transaction_number, s.created_at, s.total_amount, s.amount_paid, s.change_amount, s.status, s.created_at FROM sales s $where ORDER BY s.created_at DESC, s.created_at DESC");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . "/../includes/header.php";
?>
<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">Sales History</h1>
            <p class="text-sm text-brand-300">View all your recorded transactions</p>
        </div>
        <a href="/staff/dashboard.php" class="inline-flex items-center gap-1 px-4 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 font-semibold text-sm rounded-sm transition-colors">&larr; Back to Dashboard</a>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider mb-2">Filter by Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate); ?>" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider mb-2">Or by Month</label>
                <input type="month" name="month" value="<?= htmlspecialchars($filterMonth); ?>" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm rounded-md shadow-sm transition-colors">Apply Filter</button>
            <a href="/staff/sales-history.php" class="px-5 py-2.5 bg-brand-100 hover:bg-brand-200 text-brand-500 font-semibold text-sm rounded-md transition-colors text-center">Clear</a>
        </form>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <?php if (empty($sales)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-brand-300 italic">No sales found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-full">
                    <thead>
                        <tr class="border-b border-brand-200">
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Sale Date</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Total</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Amount Paid</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Change</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Status</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4 font-semibold font-mono text-brand-700 text-sm"><?= htmlspecialchars($sale["transaction_number"]); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm"><?= date("M d, Y", strtotime($sale["created_at"])); ?></td>
                                <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($sale["total_amount"], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($sale["amount_paid"], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($sale["change_amount"], 2); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($sale["status"] === "completed"): ?>
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider bg-brand-100 text-brand-700 border border-brand-200">✓ Completed</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider bg-brand-100 text-brand-500 border border-brand-200">✕ Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/staff/sale-details.php?id=<?= $sale["id"]; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . "/../includes/footer.php"; ?>
