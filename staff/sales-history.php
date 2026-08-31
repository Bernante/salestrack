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
    $where .= " AND DATE(s.sale_date) = ? ";
    $params[] = $filterDate;
} elseif ($filterMonth && preg_match("/^\\d{4}-\\d{2}$/", $filterMonth)) {
    $where .= " AND DATE_FORMAT(s.sale_date, \"%Y-%m\") = ? ";
    $params[] = $filterMonth;
}

$stmt = $db->prepare("SELECT s.id, s.transaction_number, s.sale_date, s.total_amount, s.amount_paid, s.change_amount, s.status, s.created_at FROM sales s $where ORDER BY s.sale_date DESC, s.created_at DESC");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . "/../includes/header.php";
?>
<div class="py-6 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div><h1 class="text-3xl font-bold text-brand-700">Sales History</h1>
            <p class="text-brand-300 mt-1">View all your recorded transactions</p></div>
        <a href="/staff/dashboard.php" class="px-4 py-2 bg-brand-100 hover:bg-brand-200 text-brand-700 font-semibold rounded-md transition-colors">← Back</a>
    </div>
    <div class="bg-white rounded-md border border-brand-200 p-4 shadow-card">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1"><label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider mb-2">Filter by Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate); ?>" class="w-full px-4 py-2 rounded-md border border-brand-200 text-brand-700"></div>
            <div class="flex-1"><label class="block text-xs font-semibold text-brand-700 uppercase tracking-wider mb-2">Or by Month</label>
                <input type="month" name="month" value="<?= htmlspecialchars($filterMonth); ?>" class="w-full px-4 py-2 rounded-md border border-brand-200 text-brand-700"></div>
            <button type="submit" class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-md">Apply</button>
            <a href="/staff/sales-history.php" class="px-6 py-2 bg-brand-100 hover:bg-brand-200 text-brand-700 font-semibold rounded-md text-center">Clear</a>
        </form>
    </div>
    <div class="bg-white rounded-md border border-brand-200 shadow-card overflow-hidden">
        <?php if (empty($sales)): ?>
            <div class="p-8 text-center"><p class="text-brand-300 text-lg">No sales found.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-brand-50 text-xs text-brand-300 font-semibold border-b border-brand-200">
                        <tr><th class="p-3">Transaction #</th><th class="p-3">Sale Date</th><th class="p-3">Total</th><th class="p-3">Paid</th><th class="p-3">Change</th><th class="p-3">Status</th><th class="p-3">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-brand-100">
                        <?php foreach ($sales as $sale): ?>
                            <tr class="hover:bg-brand-50">
                                <td class="p-3"><span class="font-mono font-bold text-brand-700"><?= htmlspecialchars($sale['transaction_number']); ?></span></td>
                                <td class="p-3"><span class="font-semibold text-brand-700"><?= date('M d, Y', strtotime($sale['sale_date'])); ?></span></td>
                                <td class="p-3"><span class="font-bold text-brand-500">₱<?= number_format($sale['total_amount'], 2); ?></span></td>
                                <td class="p-3"><span class="text-brand-700">₱<?= number_format($sale['amount_paid'], 2); ?></span></td>
                                <td class="p-3"><span class="text-green-600">₱<?= number_format($sale['change_amount'], 2); ?></span></td>
                                <td class="p-3"><?php if ($sale['status'] === "completed"): ?><span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">✓ Done</span><?php else: ?><span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">✕ Cancelled</span><?php endif; ?></td>
                                <td class="p-3"><a href="/staff/sale-details.php?id=<?= $sale["id"]; ?>" class="px-3 py-1 bg-brand-500 hover:bg-brand-600 text-white text-xs rounded">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . "/../includes/footer.php"; ?>
