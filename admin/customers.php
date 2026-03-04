<?php
session_start();
require_once __DIR__ . '/includes/admin_functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . getAdminAbsoluteUrl('auth.php'));
    exit;
}

require_once __DIR__ . '/includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

// Only Super Admin or roles with mobile.customers can view mobile app users
$canViewCustomers = !empty($_SESSION['admin_permissions']['is_super'])
    || !empty($_SESSION['admin_permissions']['mobile']['customers']);
if (!$canViewCustomers) {
    header('Location: ' . getAdminUrl('index.php?error=unauthorized'));
    exit;
}

$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$viewId = isset($_GET['id']) ? trim($_GET['id']) : null;

if ($viewId) {
    $user = adminGetMobileUserById($viewId);
    if (!$user) {
        header('Location: ' . getAdminUrl('customers.php'));
        exit;
    }
} else {
    $offset = ($page - 1) * $perPage;
    $result = adminGetMobileUsers($search, $perPage, $offset);
    $users = $result['users'];
    $total = $result['total'];
    $totalPages = $total ? (int)ceil($total / $perPage) : 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $viewId ? 'User details' : 'Mobile Users' ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'folly': { DEFAULT: '#FF0055', 400: '#ff0055', 600: '#cc0044' }, 'charcoal': { DEFAULT: '#3B4255', 200: '#d4d7e1', 400: '#7d88a5', 500: '#596380', 600: '#3b4255', 700: '#2f3443' } } } } }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <div class="hidden lg:block w-64 bg-charcoal text-white flex-shrink-0">
            <div class="p-4 lg:p-6 border-b border-charcoal-500">
                <h2 class="text-lg font-bold flex items-center"><i class="bi bi-shield-check mr-2 text-folly"></i><span>Admin Panel</span></h2>
                <p class="text-charcoal-200 text-sm mt-1">Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user'] ?? 'Admin') ?></p>
            </div>
            <nav class="p-4 space-y-1"><?php $activePage = 'customers'; include __DIR__ . '/partials/nav_links_desktop.php'; ?></nav>
        </div>
        <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')"></div>
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300">
            <div class="p-4 border-b border-charcoal-500 flex justify-between items-center">
                <h2 class="text-lg font-bold">Admin Panel</h2>
                <button onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')" class="text-white p-1"><i class="bi bi-x text-2xl"></i></button>
            </div>
            <nav class="p-2 overflow-y-auto" style="height: calc(100vh - 80px);"><?php $activePage = 'customers'; include __DIR__ . '/partials/nav_links_mobile.php'; ?></nav>
        </div>
        <div class="flex-1 flex flex-col min-w-0">
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex justify-between items-center">
                <div class="flex items-center min-w-0">
                    <button type="button" onclick="document.getElementById('mobileSidebar').classList.toggle('-translate-x-full')" class="lg:hidden mr-3 p-2 text-charcoal-600"><i class="bi bi-list text-xl"></i></button>
                    <h1 class="text-xl font-bold text-charcoal truncate"><?= $viewId ? 'User details' : 'Mobile App Users' ?></h1>
                </div>
            </div>
            <div class="flex-1 p-4 lg:p-6 overflow-auto">
                <?php if ($viewId): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
                        <a href="<?= getAdminUrl('customers.php') ?>" class="text-folly hover:underline text-sm mb-4 inline-block"><i class="bi bi-arrow-left mr-1"></i>Back to list</a>
                        <dl class="space-y-3">
                            <div><dt class="text-charcoal-500 text-sm">Email</dt><dd class="font-medium"><?= htmlspecialchars($user['email'] ?? '') ?></dd></div>
                            <div><dt class="text-charcoal-500 text-sm">Name</dt><dd class="font-medium"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: '—' ?></dd></div>
                            <div><dt class="text-charcoal-500 text-sm">Phone</dt><dd><?= htmlspecialchars($user['phone'] ?? '—') ?></dd></div>
                            <div><dt class="text-charcoal-500 text-sm">Gender</dt><dd><?= htmlspecialchars($user['gender'] ?? '—') ?></dd></div>
                            <div><dt class="text-charcoal-500 text-sm">Date of birth</dt><dd><?= !empty($user['date_of_birth']) ? htmlspecialchars($user['date_of_birth']) : '—' ?></dd></div>
                            <div><dt class="text-charcoal-500 text-sm">Registered</dt><dd><?= !empty($user['created_at']) ? htmlspecialchars($user['created_at']) : '—' ?></dd></div>
                        </dl>
                    </div>
                <?php else: ?>
                    <?php if (!function_exists('adminTableExists') || !adminTableExists('users')): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <p class="text-amber-800">The <code>users</code> table was not found. Run the Node backend migrations (e.g. <code>npx prisma migrate dev</code>) to enable mobile app users data.</p>
                        </div>
                    <?php else: ?>
                        <form method="get" class="mb-4 flex flex-wrap gap-2">
                            <input type="hidden" name="page" value="1">
                            <input type="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by email or name..." class="px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly w-64">
                            <button type="submit" class="px-4 py-2 bg-folly text-white rounded hover:bg-folly-600">Search</button>
                            <?php if ($search !== null && $search !== ''): ?><a href="<?= getAdminUrl('customers.php') ?>" class="px-4 py-2 bg-gray-200 text-charcoal rounded hover:bg-gray-300">Clear</a><?php endif; ?>
                        </form>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Email</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Name</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Phone</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Registered</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Actions</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php if (empty($users)): ?>
                                            <tr><td colspan="5" class="px-4 py-6 text-center text-charcoal-400">No users found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $u): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: '—' ?></td>
                                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                                                    <td class="px-4 py-2 text-sm"><?= !empty($u['created_at']) ? htmlspecialchars($u['created_at']) : '—' ?></td>
                                                    <td class="px-4 py-2"><a href="<?= getAdminUrl('customers.php?id=' . urlencode($u['id'])) ?>" class="text-folly hover:underline text-sm">View</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($totalPages > 1): ?>
                                <div class="px-4 py-2 border-t border-gray-200 flex items-center justify-between text-sm">
                                    <span class="text-charcoal-500"><?= $total ?> user(s)</span>
                                    <div class="flex gap-1">
                                        <?php if ($page > 1): ?><a href="<?= getAdminUrl('customers.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="px-2 py-1 text-folly hover:underline">Previous</a><?php endif; ?>
                                        <span class="px-2 py-1">Page <?= $page ?> of <?= $totalPages ?></span>
                                        <?php if ($page < $totalPages): ?><a href="<?= getAdminUrl('customers.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="px-2 py-1 text-folly hover:underline">Next</a><?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
