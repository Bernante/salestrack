<?php
$userRole = strtolower($currentUser['role'] ?? 'staff');
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
if (!function_exists('isActiveNav')) {
    function isActiveNav(string $uri, string $currentUri): bool {
        $isActive = str_contains($currentUri, $uri);
        if (!$isActive) {
            if ($uri === '/admin/sales.php' && str_contains($currentUri, '/admin/sale-details.php')) $isActive = true;
            elseif ($uri === '/staff/sales.php' && str_contains($currentUri, '/staff/sale-details.php')) $isActive = true;
            elseif ($uri === '/admin/products.php' && (str_contains($currentUri, '/admin/product-create.php') || str_contains($currentUri, '/admin/product-edit.php'))) $isActive = true;
            elseif ($uri === '/admin/staff.php' && (str_contains($currentUri, '/admin/staff-create.php') || str_contains($currentUri, '/admin/staff-edit.php'))) $isActive = true;
        }
        return $isActive;
    }
}
$navCls = fn(string $uri) => isActiveNav($uri, $currentUri) ? 'active' : 'text-brand-300 hover:text-brand-500';
?>
<div id="mobileMenuBackdrop" class="fixed inset-0 bg-brand-900/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>
<aside id="sidebarNav" class="fixed md:static inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white flex flex-col h-full max-h-screen overflow-y-auto border-r border-brand-200 transform -translate-x-full md:translate-x-0 transition-transform duration-300 shadow-xl md:shadow-none flex-shrink-0">
    <nav class="px-6 py-6 sm:px-8 sm:py-8 flex-1 flex flex-col min-h-full">
        <div class="flex items-center justify-between mb-8">
            <a href="<?= $userRole === 'admin' ? '/admin/dashboard.php' : '/staff/dashboard.php'; ?>" class="flex items-center group">
                <span class="font-extrabold text-2xl tracking-tight text-[#6e4598] hover:text-[#5e3a82] transition-colors">SalesTrack</span>
            </a>
            <button id="mobileMenuCloseBtn" class="md:hidden text-brand-500 hover:text-brand-700 p-1.5 rounded-md hover:bg-brand-50 focus:outline-none"><i class="fas fa-times text-lg"></i></button>
        </div>
        <ul class="space-y-1">
            <?php if ($userRole === 'admin'): ?>
                <li class="w-full py-3 relative"><a href="/admin/dashboard.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/admin/dashboard.php'); ?>"><i class="fas fa-home w-6"></i><span class="ml-1">Dashboard</span></a></li>
                <li class="w-full py-3 relative"><a href="/admin/sales.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/admin/sales.php'); ?>"><i class="fas fa-receipt w-6"></i><span class="ml-1">Sales History</span></a></li>
                <li class="w-full py-3 relative"><a href="/admin/products.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/admin/products.php'); ?>"><i class="fas fa-box w-6"></i><span class="ml-1">Products & Prices</span></a></li>
                <li class="w-full py-3 relative"><a href="/admin/reports.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/admin/reports.php'); ?>"><i class="fas fa-chart-pie w-6"></i><span class="ml-1">Sales Reports</span></a></li>
                <li class="w-full py-3 relative"><a href="/admin/staff.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/admin/staff.php'); ?>"><i class="fas fa-users w-6"></i><span class="ml-1">Staff Management</span></a></li>
            <?php else: ?>
                <li class="w-full py-3 relative"><a href="/staff/dashboard.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/staff/dashboard.php'); ?>"><i class="fas fa-home w-6"></i><span class="ml-1">Dashboard</span></a></li>
                <li class="w-full py-3 relative"><a href="/staff/new-sale.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/staff/new-sale.php'); ?>"><i class="fas fa-cash-register w-6"></i><span class="ml-1">Record New Sale</span></a></li>
                <li class="w-full py-3 relative"><a href="/staff/sales.php" class="nav-item flex items-center text-base font-semibold capitalize transition-colors duration-300 <?= $navCls('/staff/sales.php'); ?>"><i class="fas fa-clipboard-list w-6"></i><span class="ml-1">My Sales History</span></a></li>
            <?php endif; ?>
        </ul>
        <div class="mt-auto pt-6 pb-8">
            <div class="p-5 rounded-lg bg-brand-500 text-center shadow-sm">
                <h5 class="font-bold text-white text-base mb-1">SalesTrack</h5>
                <p class="mb-3 text-sm text-white/80">Welcome, <?= e($currentUser['name'] ?? 'User'); ?>!</p>
                <a href="/actions/logout.php" class="inline-flex items-center justify-center font-semibold text-brand-500 bg-white py-2.5 px-4 rounded-md w-full text-sm hover:bg-brand-50 transition-colors shadow-sm"><i class="fas fa-sign-out-alt mr-2"></i> Sign Out</a>
            </div>
        </div>
    </nav>
</aside>
