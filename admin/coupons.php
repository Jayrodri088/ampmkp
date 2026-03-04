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

// Only Super Admin can manage coupons
$canManageCoupons = ($_SESSION['admin_role_slug'] ?? '') === 'super_admin';
if (!$canManageCoupons) {
    header('Location: ' . getAdminUrl('index.php?error=unauthorized'));
    exit;
}

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($postedToken) || !hash_equals($_SESSION['admin_csrf_token'], $postedToken)) {
        $message = 'Invalid request.';
        $message_type = 'danger';
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'delete_coupon' && !empty($_POST['id'])) {
                if (adminDeleteCoupon((int)$_POST['id'])) {
                    $message = 'Coupon deleted.';
                    $message_type = 'success';
                    $editId = null;
                } else {
                    $message = 'Could not delete coupon.';
                    $message_type = 'danger';
                }
            } elseif ($_POST['action'] === 'save_coupon') {
                $code = trim($_POST['code'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $discount_type = $_POST['discount_type'] ?? 'percentage';
                $discount_value = (float)($_POST['discount_value'] ?? 0);
                $min_purchase = isset($_POST['min_purchase']) && $_POST['min_purchase'] !== '' ? (float)$_POST['min_purchase'] : null;
                $max_discount = isset($_POST['max_discount']) && $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
                $valid_from = $_POST['valid_from'] ?? date('Y-m-d\TH:i');
                $valid_until = $_POST['valid_until'] ?? date('Y-m-d\TH:i', strtotime('+1 year'));
                $usage_limit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
                $is_active = !empty($_POST['is_active']);

                if ($code === '') {
                    $message = 'Code is required.';
                    $message_type = 'danger';
                } else {
                    $valid_from_sql = date('Y-m-d H:i:s', strtotime($valid_from));
                    $valid_until_sql = date('Y-m-d H:i:s', strtotime($valid_until));
                    $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
                    if ($couponId) {
                        $existing = adminGetCouponById($couponId);
                        if (!$existing) {
                            $message = 'Coupon not found.';
                            $message_type = 'danger';
                        } else {
                            $ok = adminUpdateCoupon($couponId, [
                                'code' => $code,
                                'description' => $description ?: null,
                                'discount_type' => $discount_type,
                                'discount_value' => $discount_value,
                                'min_purchase' => $min_purchase,
                                'max_discount' => $max_discount,
                                'valid_from' => $valid_from_sql,
                                'valid_until' => $valid_until_sql,
                                'usage_limit' => $usage_limit,
                                'is_active' => $is_active,
                            ]);
                            if ($ok) {
                                $message = 'Coupon updated.';
                                $message_type = 'success';
                                $editId = null;
                            } else {
                                $message = 'Could not update coupon (e.g. duplicate code).';
                                $message_type = 'danger';
                            }
                        }
                    } else {
                        $existingByCode = adminGetCouponByCode($code);
                        if ($existingByCode) {
                            $message = 'A coupon with this code already exists.';
                            $message_type = 'danger';
                        } elseif (adminCreateCoupon([
                            'code' => $code,
                            'description' => $description ?: null,
                            'discount_type' => $discount_type,
                            'discount_value' => $discount_value,
                            'min_purchase' => $min_purchase,
                            'max_discount' => $max_discount,
                            'valid_from' => $valid_from_sql,
                            'valid_until' => $valid_until_sql,
                            'usage_limit' => $usage_limit,
                            'is_active' => $is_active,
                        ])) {
                            $message = 'Coupon created.';
                            $message_type = 'success';
                        } else {
                            $message = 'Could not create coupon.';
                            $message_type = 'danger';
                        }
                    }
                }
            }
        }
    }
}

$result = adminGetCoupons(false, 100, 0);
$coupons = $result['coupons'];
$editCoupon = $editId ? adminGetCouponById($editId) : null;
if ($editId && !$editCoupon) $editId = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons - Admin</title>
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
            <nav class="p-4 space-y-1"><?php $activePage = 'coupons'; include __DIR__ . '/partials/nav_links_desktop.php'; ?></nav>
        </div>
        <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')"></div>
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300">
            <div class="p-4 border-b border-charcoal-500 flex justify-between items-center">
                <h2 class="text-lg font-bold">Admin Panel</h2>
                <button type="button" onclick="document.getElementById('mobileSidebar').classList.add('-translate-x-full')" class="text-white p-1"><i class="bi bi-x text-2xl"></i></button>
            </div>
            <nav class="p-2 overflow-y-auto" style="height: calc(100vh - 80px);"><?php $activePage = 'coupons'; include __DIR__ . '/partials/nav_links_mobile.php'; ?></nav>
        </div>
        <div class="flex-1 flex flex-col min-w-0">
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex justify-between items-center">
                <div class="flex items-center min-w-0">
                    <button type="button" onclick="document.getElementById('mobileSidebar').classList.toggle('-translate-x-full')" class="lg:hidden mr-3 p-2 text-charcoal-600"><i class="bi bi-list text-xl"></i></button>
                    <h1 class="text-xl font-bold text-charcoal truncate">Coupons</h1>
                </div>
            </div>
            <div class="flex-1 p-4 lg:p-6 overflow-auto">
                <?php if ($message): ?>
                    <div class="mb-4 p-3 rounded <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if (!function_exists('adminTableExists') || !adminTableExists('coupons')): ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-amber-800">The <code>coupons</code> table was not found. Run the Node backend migrations (or the coupons migration in <code>admin/database/migrations/</code>) to enable coupons.</p>
                    </div>
                <?php else: ?>
                    <!-- Add / Edit form -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-charcoal mb-4"><?= $editCoupon ? 'Edit coupon' : 'Add coupon' ?></h3>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                            <input type="hidden" name="action" value="save_coupon">
                            <?php if ($editCoupon): ?><input type="hidden" name="coupon_id" value="<?= (int)$editCoupon['id'] ?>"><?php endif; ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Code <span class="text-red-500">*</span></label>
                                    <input type="text" name="code" required value="<?= $editCoupon ? htmlspecialchars($editCoupon['code'] ?? '') : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly" placeholder="e.g. SAVE10">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Description</label>
                                    <input type="text" name="description" value="<?= $editCoupon ? htmlspecialchars($editCoupon['description'] ?? '') : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly" placeholder="Optional">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Discount type</label>
                                    <select name="discount_type" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly">
                                        <option value="percentage" <?= ($editCoupon['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                                        <option value="fixed" <?= ($editCoupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Discount value</label>
                                    <input type="number" name="discount_value" step="0.01" min="0" required value="<?= $editCoupon ? htmlspecialchars($editCoupon['discount_value'] ?? '0') : '0' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Min. purchase</label>
                                    <input type="number" name="min_purchase" step="0.01" min="0" value="<?= $editCoupon && isset($editCoupon['min_purchase']) && $editCoupon['min_purchase'] !== null ? htmlspecialchars($editCoupon['min_purchase']) : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly" placeholder="Optional">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Max discount (for %)</label>
                                    <input type="number" name="max_discount" step="0.01" min="0" value="<?= $editCoupon && isset($editCoupon['max_discount']) && $editCoupon['max_discount'] !== null ? htmlspecialchars($editCoupon['max_discount']) : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly" placeholder="Optional">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Valid from</label>
                                    <input type="datetime-local" name="valid_from" value="<?= $editCoupon ? (date('Y-m-d\TH:i', strtotime($editCoupon['valid_from'] ?? 'now'))) : date('Y-m-d\TH:i') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Valid until</label>
                                    <input type="datetime-local" name="valid_until" value="<?= $editCoupon ? (date('Y-m-d\TH:i', strtotime($editCoupon['valid_until'] ?? '+1 year'))) : date('Y-m-d\TH:i', strtotime('+1 year')) ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal mb-1">Usage limit</label>
                                    <input type="number" name="usage_limit" min="0" value="<?= $editCoupon && isset($editCoupon['usage_limit']) && $editCoupon['usage_limit'] !== null ? (int)$editCoupon['usage_limit'] : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-folly focus:border-folly" placeholder="Unlimited">
                                </div>
                                <div class="flex items-center pt-8">
                                    <label class="flex items-center"><input type="checkbox" name="is_active" value="1" <?= (!$editCoupon || !empty($editCoupon['is_active'])) ? 'checked' : '' ?> class="mr-2"> Active</label>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-folly text-white rounded hover:bg-folly-600"><?= $editCoupon ? 'Update' : 'Create' ?> coupon</button>
                                <?php if ($editCoupon): ?><a href="<?= getAdminUrl('coupons.php') ?>" class="px-4 py-2 bg-gray-200 text-charcoal rounded hover:bg-gray-300">Cancel</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <!-- List -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 font-semibold text-charcoal">Existing coupons (<?= count($coupons) ?>)</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Discount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Used</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Valid until</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Active</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-charcoal-500 uppercase">Actions</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($coupons)): ?>
                                        <tr><td colspan="6" class="px-4 py-6 text-center text-charcoal-400">No coupons yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($coupons as $c): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 font-mono text-sm"><?= htmlspecialchars($c['code'] ?? '') ?></td>
                                                <td class="px-4 py-2 text-sm"><?= ($c['discount_type'] ?? 'percentage') === 'percentage' ? ($c['discount_value'] ?? 0) . '%' : number_format((float)($c['discount_value'] ?? 0), 2) ?></td>
                                                <td class="px-4 py-2 text-sm"><?= (int)($c['used_count'] ?? 0) ?><?= isset($c['usage_limit']) && $c['usage_limit'] !== null ? ' / ' . (int)$c['usage_limit'] : '' ?></td>
                                                <td class="px-4 py-2 text-sm"><?= !empty($c['valid_until']) ? htmlspecialchars($c['valid_until']) : '—' ?></td>
                                                <td class="px-4 py-2 text-sm"><?= !empty($c['is_active']) ? 'Yes' : 'No' ?></td>
                                                <td class="px-4 py-2">
                                                    <a href="<?= getAdminUrl('coupons.php?edit=' . (int)($c['id'] ?? 0)) ?>" class="text-folly hover:underline text-sm mr-2">Edit</a>
                                                    <form method="post" class="inline" onsubmit="return confirm('Delete this coupon?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="delete_coupon">
                                                        <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">
                                                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
