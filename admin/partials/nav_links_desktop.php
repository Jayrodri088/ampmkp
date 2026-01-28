<?php
require_once __DIR__ . '/../includes/admin_functions.php';

$links = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-speedometer2'],
    ['id' => 'products', 'title' => 'Products', 'href' => 'products.php', 'icon' => 'bi-box-seam'],
    ['id' => 'categories', 'title' => 'Categories', 'href' => 'categories.php', 'icon' => 'bi-tags'],
    ['id' => 'ads', 'title' => 'Advertisements', 'href' => 'ads.php', 'icon' => 'bi-megaphone'],
    ['id' => 'vendors', 'title' => 'Vendors', 'href' => 'vendors.php', 'icon' => 'bi-people'],
    ['id' => 'orders', 'title' => 'Orders', 'href' => 'orders.php', 'icon' => 'bi-receipt'],
    ['id' => 'contacts', 'title' => 'Contacts', 'href' => 'contacts.php', 'icon' => 'bi-envelope'],
    ['id' => 'settings', 'title' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
    ['id' => 'file_manager', 'title' => 'File Manager', 'href' => 'file-manager.php', 'icon' => 'bi-folder'],
];
?>
<?php foreach ($links as $link): ?>
    <?php $isActive = isActivePage($link['id'], $activePage ?? null); ?>
    <a href="<?= getAdminAbsoluteUrl($link['href']) ?>" class="flex items-center px-4 py-3 <?= $isActive ? 'text-white bg-folly hover:bg-folly-600' : 'text-charcoal-200 hover:text-white hover:bg-charcoal-700' ?> transition-colors">
        <i class="bi <?= $link['icon'] ?> mr-3 w-5 text-center"></i>
        <?= htmlspecialchars($link['title']) ?>
    </a>
<?php endforeach; ?>

<!-- View Site and Logout -->
<div class="border-t border-charcoal-500 my-4"></div>
<a href="<?= getMainSiteUrl() ?>" target="_blank" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors">
    <i class="bi bi-house mr-3 w-5 text-center"></i>
    View Site
</a>
<a href="<?= getAdminUrl('auth.php?logout=1') ?>" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors">
    <i class="bi bi-box-arrow-right mr-3 w-5 text-center"></i>
    Logout
</a>