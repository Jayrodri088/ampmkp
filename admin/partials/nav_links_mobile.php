<?php
require_once __DIR__ . '/../includes/admin_functions.php';
if (!function_exists('hasPermission')) {
    require_once __DIR__ . '/../includes/rbac.php';
}
$isSuperAdmin = ($_SESSION['admin_role_slug'] ?? '') === 'super_admin';
// Mobile-only nav items: only Super Admin or roles with mobile.* permission
$canViewCustomers = $isSuperAdmin || !empty($_SESSION['admin_permissions']['mobile']['customers']);
$canManageReviews = $isSuperAdmin || !empty($_SESSION['admin_permissions']['mobile']['products']);
$canManageCoupons = $isSuperAdmin;

$links = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-speedometer2'],
    ['id' => 'products', 'title' => 'Products', 'href' => 'products.php', 'icon' => 'bi-box-seam'],
    ['id' => 'categories', 'title' => 'Categories', 'href' => 'categories.php', 'icon' => 'bi-tags'],
    ['id' => 'ads', 'title' => 'Advertisements', 'href' => 'ads.php', 'icon' => 'bi-megaphone'],
    ['id' => 'vendors', 'title' => 'Vendors', 'href' => 'vendors.php', 'icon' => 'bi-people'],
    ['id' => 'orders', 'title' => 'Orders', 'href' => 'orders.php', 'icon' => 'bi-receipt'],
    ['id' => 'contacts', 'title' => 'Contacts', 'href' => 'contacts.php', 'icon' => 'bi-envelope'],
    ['id' => 'settings', 'title' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
];
if ($canViewCustomers) $links[] = ['id' => 'customers', 'title' => 'Mobile Users', 'href' => 'customers.php', 'icon' => 'bi-people-fill'];
if ($canManageReviews) $links[] = ['id' => 'reviews', 'title' => 'App Reviews', 'href' => 'reviews.php', 'icon' => 'bi-star'];
if ($canManageCoupons) $links[] = ['id' => 'coupons', 'title' => 'Coupons', 'href' => 'coupons.php', 'icon' => 'bi-ticket-perforated'];
?>
<?php foreach ($links as $link): ?>
    <?php $isActive = isActivePage($link['id'], $activePage ?? null); ?>
    <a href="<?= getAdminAbsoluteUrl($link['href']) ?>" class="flex items-center px-3 sm:px-4 py-3 sm:py-3 <?= $isActive ? 'text-white bg-folly hover:bg-folly-600' : 'text-charcoal-200 hover:text-white hover:bg-charcoal-700' ?> transition-colors text-sm sm:text-base">
        <i class="bi <?= $link['icon'] ?> mr-2 sm:mr-3 w-4 sm:w-5 text-center"></i>
        <?= htmlspecialchars($link['title']) ?>
    </a>
<?php endforeach; ?>

<?php if ($isSuperAdmin): ?>
<!-- Admin Management (Super Admin only) -->
<a href="<?= getAdminUrl('admins.php') ?>" class="flex items-center px-3 sm:px-4 py-3 sm:py-3 <?= isActivePage('admins', $activePage ?? null) ? 'text-white bg-folly hover:bg-folly-600' : 'text-charcoal-200 hover:text-white hover:bg-charcoal-700' ?> transition-colors text-sm sm:text-base">
    <i class="bi bi-shield-fill-check mr-2 sm:mr-3 w-4 sm:w-5 text-center"></i>
    Admin Users
</a>
<?php endif; ?>

<!-- View Site and Logout -->
<div class="border-t border-charcoal-500 my-3 sm:my-4"></div>
<a href="<?= getMainSiteUrl() ?>" target="_blank" class="flex items-center px-3 sm:px-4 py-3 sm:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors text-sm sm:text-base">
    <i class="bi bi-house mr-2 sm:mr-3 w-4 sm:w-5 text-center"></i>
    View Site
</a>
<a href="<?= getAdminUrl('auth.php?logout=1') ?>" class="flex items-center px-3 sm:px-4 py-3 sm:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors text-sm sm:text-base">
    <i class="bi bi-box-arrow-right mr-2 sm:mr-3 w-4 sm:w-5 text-center"></i>
    Logout
</a>