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

// Only Super Admin or roles with mobile.products can manage app reviews
$canManageReviews = !empty($_SESSION['admin_permissions']['is_super'])
    || !empty($_SESSION['admin_permissions']['mobile']['products']);
if (!$canManageReviews) {
    header('Location: ' . getAdminUrl('index.php?error=unauthorized'));
    exit;
}

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['csrf_token'])
    && hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
    if ($_POST['action'] === 'delete_review' && !empty($_POST['id'])) {
        if (adminDeleteMobileReview($_POST['id'])) {
            $message = 'Review deleted.';
            $message_type = 'success';
        } else {
            $message = 'Could not delete review.';
            $message_type = 'danger';
        }
    }
}

$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$productId = isset($_GET['product_id']) ? trim($_GET['product_id']) : null;
$offset = ($page - 1) * $perPage;
$result = adminGetMobileReviews($productId, $perPage, $offset);
$reviews = $result['reviews'];
$total = $result['total'];
$totalPages = $total ? (int)ceil($total / $perPage) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Reviews - Admin</title>
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
            <nav class="p-4 space-y-1"><?php $activePage = 'reviews'; include __DIR__ . '/partials/nav_links_desktop.php'; ?></nav>
        </div>
        <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')"></div>
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300">
            <div class="p-4 border-b border-charcoal-500 flex justify-between items-center">
                <h2 class="text-lg font-bold">Admin Panel</h2>
                <button type="button" onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')" class="text-white p-1"><i class="bi bi-x text-2xl"></i></button>
            </div>
            <nav class="p-2 overflow-y-auto" style="height: calc(100vh - 80px);"><?php $activePage = 'reviews'; include __DIR__ . '/partials/nav_links_mobile.php'; ?></nav>
        </div>
        <div class="flex-1 flex flex-col min-w-0">
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex justify-between items-center">
                <div class="flex items-center min-w-0">
                    <button type="button" onclick="document.getElementById('mobileSidebar').classList.toggle('-translate-x-full')" class="lg:hidden mr-3 p-2 text-charcoal-600"><i class="bi bi-list text-xl"></i></button>
                    <h1 class="text-xl font-bold text-charcoal truncate">Mobile App Reviews</h1>
                </div>
            </div>
            <div class="flex-1 p-4 lg:p-6 overflow-auto">
                <?php if ($message): ?>
                    <div class="mb-4 p-3 rounded <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if (!function_exists('adminTableExists') || !adminTableExists('reviews')): ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-amber-800">The <code>reviews</code> table was not found. Run the Node backend migrations to enable app reviews.</p>
                    </div>
                <?php else: ?>
                    <form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
                        <input type="hidden" name="page" value="1">
                        <label class="text-sm text-charcoal-600">Product ID</label>
                        <input type="number" name="product_id" value="<?= $productId !== null && $productId !== '' ? (int)$productId : '' ?>" placeholder="Filter by product ID" class="px-3 py-2 border border-gray-300 rounded w-40">
                        <button type="submit" class="px-4 py-2 bg-folly text-white rounded hover:bg-folly-600">Filter</button>
                        <?php if ($productId !== null && $productId !== ''): ?><a href="<?= getAdminUrl('reviews.php') ?>" class="px-4 py-2 bg-gray-200 text-charcoal rounded hover:bg-gray-300">Clear</a><?php endif; ?>
                    </form>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">User</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Rating</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Comment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Actions</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($reviews)): ?>
                                        <tr><td colspan="6" class="px-4 py-6 text-center text-charcoal-400">No reviews found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reviews as $r): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($r['product_name'] ?? 'Product #' . ($r['product_id'] ?? '')) ?></td>
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($r['user_name'] ?? $r['user_email'] ?? '—') ?></td>
                                                <td class="px-4 py-2 text-sm"><?= (int)($r['rating'] ?? 0) ?> ★</td>
                                                <td class="px-4 py-2 text-sm max-w-xs truncate"><?= htmlspecialchars($r['comment'] ?? '—') ?></td>
                                                <td class="px-4 py-2 text-sm"><?= !empty($r['created_at']) ? htmlspecialchars($r['created_at']) : '—' ?></td>
                                                <td class="px-4 py-2">
                                                    <form method="post" class="inline" onsubmit="return confirm('Delete this review?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="delete_review">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($r['id'] ?? '') ?>">
                                                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1): ?>
                            <div class="px-4 py-2 border-t border-gray-200 flex items-center justify-between text-sm">
                                <span class="text-charcoal-500"><?= $total ?> review(s)</span>
                                <div class="flex gap-1">
                                    <?php if ($page > 1): ?><a href="<?= getAdminUrl('reviews.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="px-2 py-1 text-folly hover:underline">Previous</a><?php endif; ?>
                                    <span class="px-2 py-1">Page <?= $page ?> of <?= $totalPages ?></span>
                                    <?php if ($page < $totalPages): ?><a href="<?= getAdminUrl('reviews.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="px-2 py-1 text-folly hover:underline">Next</a><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
