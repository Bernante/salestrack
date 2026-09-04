<?php
$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../includes/staff-auth.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Staff stats today
$stmtMyToday = $db->prepare('
    SELECT COALESCE(SUM(total_amount), 0) AS total_sales, COUNT(id) AS transaction_count
    FROM sales
    WHERE user_id = :user_id AND status = "completed" AND DATE(created_at) = CURDATE()
');
$stmtMyToday->execute([':user_id' => $userId]);
$myStat = $stmtMyToday->fetch();

$mySalesToday = floatval($myStat['total_sales']);
$myTxnsToday = intval($myStat['transaction_count']);

// Staff recent transactions
$stmtRecent = $db->prepare('
    SELECT * FROM sales
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
');
$stmtRecent->execute([':user_id' => $userId]);
$myRecentSales = $stmtRecent->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <!-- Welcome CTA Banner -->
    <div class="bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 text-white p-6 sm:p-8 rounded-md shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white text-xs font-semibold mb-2">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span> Terminal Ready
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Welcome back, <?= e($_SESSION['user_name']); ?>! 👋</h1>
            <p class="text-white/80 text-sm mt-1">Easily select products and record customer sales transactions in seconds.</p>
        </div>
        <a href="/staff/new-sale.php" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white text-brand-500 hover:bg-brand-50 font-bold text-sm sm:text-base rounded-md shadow-md transition flex-shrink-0">
            <i class="fas fa-cash-register"></i>
            <span>RECORD NEW SALE</span>
        </a>
    </div>

    <!-- Summary Metrics Grid (DataAI style stat cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0">
                <div class="flex items-center justify-between border-b border-brand-200 pb-4">
                    <h5 class="items-center font-semibold inline-flex text-lg text-brand-700">
                        <span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-peso-sign"></i></span>
                        My Sales Today
                    </h5>
                    <i class="fas fa-ellipsis-h text-brand-300"></i>
                </div>
            </div>
            <div class="w-full p-6">
                <h4 class="font-bold text-brand-500 text-2xl mb-4">₱<?= number_format($mySalesToday, 2); ?></h4>
                <div class="flex items-center">
                    <span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-brand-100 text-brand-500 mr-2 text-sm">
                        <i class="fas mr-2 fa-chart-line"></i> Daily Total
                    </span>
                    <p class="font-semibold text-sm text-brand-300">Revenue collected today</p>
                </div>
            </div>
        </div>

        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0">
                <div class="flex items-center justify-between border-b border-brand-200 pb-4">
                    <h5 class="items-center font-semibold inline-flex text-lg text-brand-700">
                        <span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-receipt"></i></span>
                        My Transactions Today
                    </h5>
                    <i class="fas fa-ellipsis-h text-brand-300"></i>
                </div>
            </div>
            <div class="w-full p-6">
                <h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($myTxnsToday); ?></h4>
                <div class="flex items-center">
                    <span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-brand-100 text-brand-500 mr-2 text-sm">
                        <i class="fas mr-2 fa-check"></i> Completed
                    </span>
                    <p class="font-semibold text-sm text-brand-300">Receipts processed today</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Table Card -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
        <div class="w-full p-6 pb-0">
            <div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="font-semibold text-lg text-brand-700">My Recent Sales Transactions</h5>
                <a href="/staff/sales.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">View Full History &rarr;</a>
            </div>
        </div>
        <div class="w-full p-6">
            <!-- Mobile Card View (hidden on md+) -->
            <div class="md:hidden space-y-3">
                <?php if (empty($myRecentSales)): ?>
                    <div class="text-center text-sm text-brand-300 italic py-8">You have not recorded any sales yet today.</div>
                <?php else: ?>
                    <?php foreach ($myRecentSales as $s): ?>
                        <div class="border border-brand-100 rounded-md p-4 space-y-2 hover:bg-brand-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Transaction</span>
                                <span class="font-mono font-bold text-brand-700 text-sm"><?= e($s['transaction_number']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Total</span>
                                <span class="font-bold text-brand-500">₱<?= number_format($s['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Time</span>
                                <span class="text-sm text-brand-300"><?= date('h:i A', strtotime($s['created_at'])); ?></span>
                            </div>
                            <div class="pt-2 border-t border-brand-100">
                                <a href="/staff/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center px-3 py-2 w-full justify-center bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Receipt Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-brand-200">
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Total Amount</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Amount Paid</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Change</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Time</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myRecentSales)): ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-brand-300 italic">You have not recorded any sales yet today.</td></tr>
                        <?php else: ?>
                            <?php foreach ($myRecentSales as $s): ?>
                                <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                    <td class="px-6 py-4 font-semibold text-brand-700 font-mono text-sm"><?= e($s['transaction_number']); ?></td>
                                    <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($s['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($s['amount_paid'], 2); ?></td>
                                    <td class="px-6 py-4 text-brand-300 text-sm">₱<?= number_format($s['change_amount'], 2); ?></td>
                                    <td class="px-6 py-4 text-sm text-brand-300"><?= date('h:i A', strtotime($s['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="/staff/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center px-3 py-1 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Receipt Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
