<?php
$pageTitle = 'Staff Management';
require_once __DIR__ . '/../includes/admin-auth.php';

$db = getDBConnection();
$stmt = $db->query('SELECT id, name, username, role, status, created_at FROM users ORDER BY id ASC');
$users = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">Staff & User Management</h1>
            <p class="text-sm text-brand-300">Manage store user accounts, system access roles, and login credentials.</p>
        </div>
        <a href="/admin/staff-create.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm rounded-md shadow-sm transition-colors">
            <i class="fas fa-plus"></i> Add New User / Staff
        </a>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <!-- Mobile Card View (hidden on md+) -->
        <div class="md:hidden p-6 space-y-3">
            <?php if (empty($users)): ?>
                <div class="text-center text-sm text-brand-300 italic py-8">No staff found.</div>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <div class="border border-brand-100 rounded-md p-4 space-y-2 hover:bg-brand-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Name</span>
                            <span class="font-bold text-brand-700 text-sm"><?= e($u['name']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Role</span>
                            <span class="text-sm text-brand-700"><?= e($u['role']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Status</span>
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>"><?= ucfirst($u['status']); ?></span>
                        </div>
                        <div class="pt-2 border-t border-brand-100 flex gap-2">
                            <a href="/admin/staff-edit.php?id=<?= $u['id']; ?>" class="flex-1 px-3 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors text-center">Edit</a>
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
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Full Name</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Username</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">System Role</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Account Status</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Date Added</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-brand-700"><?= e($u['name']); ?></td>
                            <td class="px-6 py-4 text-brand-700">@<?= e($u['username']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $u['role'] === 'admin' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-brand-100 text-brand-500 border border-brand-200'; ?>">
                                    <?= e($u['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $u['status'] === 'active' ? 'bg-brand-100 text-brand-500 border border-brand-200' : 'bg-brand-100 text-brand-500 border border-brand-200'; ?>">
                                    <?= e(ucfirst($u['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-brand-300 text-sm"><?= date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="/admin/staff-edit.php?id=<?= $u['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Edit Account</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
