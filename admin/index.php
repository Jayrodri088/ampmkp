<?php
// Start session with secure settings that work in both development and production
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Use server's default session save path (avoid per-file overrides that break session sharing)

require_once __DIR__ . '/includes/admin_functions.php';
// Only require secure cookies if we're using HTTPS (robust detection)
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);

session_start();

// Enforce HTTPS in admin (skip on localhost) to ensure Secure cookies persist
if (!isRequestHttps() && !isLocalhost()) {
    $redirect = getAdminAbsoluteUrl('index.php', true);
    header('Location: ' . $redirect, true, 302);
    echo '<script>window.location.href = ' . json_encode($redirect) . ';</script>';
    exit;
}

// Check if user is authenticated - redirect to separate login page if not
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . getAdminAbsoluteUrl('auth.php'), true, 302);
    echo '<script>window.location.href = ' . json_encode(getAdminUrl('auth.php')) . ';</script>';
    exit;
}

// Check for session timeout (24 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
    session_destroy();
    header('Location: ' . getAdminAbsoluteUrl('auth.php?timeout=1'), true, 302);
    echo '<script>window.location.href = ' . json_encode(getAdminUrl('auth.php?timeout=1')) . ';</script>';
    exit;
}

// Load data for dashboard statistics
$products = json_decode(file_get_contents('../data/products.json'), true) ?? [];
$categories = json_decode(file_get_contents('../data/categories.json'), true) ?? [];
$orders = json_decode(file_get_contents('../data/orders.json'), true) ?? [];
$contacts = json_decode(file_get_contents('../data/contacts.json'), true) ?? [];

// Calculate statistics
$stats = [
    'total_products' => count($products),
    'active_products' => count(array_filter($products, fn($p) => $p['active'])),
    'featured_products' => count(array_filter($products, fn($p) => $p['featured'])),
    'total_categories' => count($categories),
    'total_orders' => count($orders),
    'pending_orders' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending')),
    'total_contacts' => count($contacts),
    'low_stock_products' => count(array_filter($products, fn($p) => $p['stock'] <= 10)),
    'total_inventory_value' => array_sum(array_map(fn($p) => $p['price'] * $p['stock'], $products))
];

$recent_orders = array_slice(array_reverse($orders), 0, 5);
$low_stock_products = array_filter($products, fn($p) => $p['stock'] <= 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Angel Marketplace</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'folly': {
                            DEFAULT: '#FF0055',
                            50: '#ffccdd',
                            100: '#ff99bb',
                            200: '#ff6699',
                            300: '#ff3377',
                            400: '#ff0055',
                            500: '#cc0044',
                            600: '#990033',
                            700: '#660022',
                            800: '#330011',
                            900: '#1a0008'
                        },
                        'charcoal': {
                            DEFAULT: '#3B4255',
                            50: '#f7f8fa',
                            100: '#ebeef3',
                            200: '#d4d7e1',
                            300: '#a8afc3',
                            400: '#7d88a5',
                            500: '#596380',
                            600: '#3b4255',
                            700: '#2f3443',
                            800: '#232733',
                            900: '#171a22'
                        },
                        'tangerine': {
                            DEFAULT: '#F5884B',
                            50: '#fde8db',
                            100: '#fbd0b8',
                            200: '#f9b994',
                            300: '#f7a270',
                            400: '#f5884b',
                            500: '#f16310',
                            600: '#b64a0b',
                            700: '#793107',
                            800: '#3d1904',
                            900: '#1e0c02'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block w-64 bg-charcoal text-white flex-shrink-0">
            <!-- Header -->
            <div class="p-6 border-b border-charcoal-500">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="bi bi-shield-check mr-3 text-folly"></i>
                    Admin Panel
                </h2>
                <p class="text-charcoal-200 text-sm mt-2">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_user']) ?>
                </p>
            </div>
            
            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <?php $activePage = 'dashboard'; include __DIR__ . '/partials/nav_links_desktop.php'; ?>
            </nav>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeMobileMenu()"></div>
        
        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
            <!-- Header -->
            <div class="p-4 sm:p-6 border-b border-charcoal-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg sm:text-xl font-bold flex items-center">
                        <i class="bi bi-shield-check mr-2 sm:mr-3 text-folly"></i>
                        Admin Panel
                    </h2>
                    <button onclick="closeMobileMenu()" class="text-white hover:text-gray-300 p-1">
                        <i class="bi bi-x text-xl sm:text-2xl"></i>
                    </button>
                </div>
                <p class="text-charcoal-200 text-xs sm:text-sm mt-2">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_user']) ?>
                </p>
            </div>
            
            <!-- Navigation -->
            <nav class="p-2 sm:p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-140px)]">
                <?php $activePage = 'dashboard'; include __DIR__ . '/partials/nav_links_mobile.php'; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <button onclick="openMobileMenu()" class="lg:hidden mr-3 p-2 text-charcoal-600 hover:text-folly touch-manipulation">
                            <i class="bi bi-list text-xl"></i>
                        </button>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal">Dashboard Overview</h1>
                    </div>
                    <div class="text-charcoal-400 flex items-center text-xs sm:text-sm">
                        <i class="bi bi-calendar3 mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline"><?= date('l, F j, Y') ?></span>
                        <span class="sm:hidden"><?= date('M j') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
                    <!-- Total Products -->
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 hover:shadow-lg transition-shadow touch-manipulation">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-400 uppercase tracking-wide truncate">Total Products</h3>
                                <p class="text-2xl sm:text-3xl font-bold text-charcoal mt-1 sm:mt-2"><?= $stats['total_products'] ?></p>
                                <p class="text-xs sm:text-sm text-charcoal-400 mt-1"><?= $stats['active_products'] ?> active</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-folly-50 ml-2 sm:ml-3 flex-shrink-0">
                                <i class="bi bi-box-seam text-lg sm:text-xl lg:text-2xl text-folly"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Orders -->
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 hover:shadow-lg transition-shadow touch-manipulation">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-400 uppercase tracking-wide truncate">Total Orders</h3>
                                <p class="text-2xl sm:text-3xl font-bold text-charcoal mt-1 sm:mt-2"><?= $stats['total_orders'] ?></p>
                                <p class="text-xs sm:text-sm text-charcoal-400 mt-1"><?= $stats['pending_orders'] ?> pending</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-50 ml-2 sm:ml-3 flex-shrink-0">
                                <i class="bi bi-receipt text-lg sm:text-xl lg:text-2xl text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 hover:shadow-lg transition-shadow touch-manipulation">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-400 uppercase tracking-wide truncate">Categories</h3>
                                <p class="text-2xl sm:text-3xl font-bold text-charcoal mt-1 sm:mt-2"><?= $stats['total_categories'] ?></p>
                                <p class="text-xs sm:text-sm text-charcoal-400 mt-1">Active categories</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-tangerine-50 ml-2 sm:ml-3 flex-shrink-0">
                                <i class="bi bi-tags text-lg sm:text-xl lg:text-2xl text-tangerine"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventory Value -->
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 hover:shadow-lg transition-shadow touch-manipulation">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-400 uppercase tracking-wide truncate">Inventory Value</h3>
                                <p class="text-2xl sm:text-3xl font-bold text-charcoal mt-1 sm:mt-2">£<?= number_format($stats['total_inventory_value'], 2) ?></p>
                                <p class="text-xs sm:text-sm text-charcoal-400 mt-1">Total stock value</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-50 ml-2 sm:ml-3 flex-shrink-0">
                                <i class="bi bi-currency-pound text-lg sm:text-xl lg:text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($stats['low_stock_products'] > 0): ?>
                <div class="bg-yellow-50 border border-yellow-200 p-3 sm:p-4 mb-4 sm:mb-6 rounded-lg">
                    <div class="flex items-start sm:items-center">
                        <i class="bi bi-exclamation-triangle text-yellow-600 mr-2 sm:mr-3 mt-1 sm:mt-0 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-yellow-800 font-medium text-sm sm:text-base">
                                <span class="hidden sm:inline">Low Stock Alert: </span>
                                <span class="sm:hidden">Alert: </span>
                                <?= $stats['low_stock_products'] ?> products have low stock (≤10 items).
                            </p>
                            <a href="products.php?filter=low_stock" class="text-yellow-700 underline hover:text-yellow-800 text-sm sm:text-base touch-manipulation">View Products</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Recent Orders -->
                    <div class="lg:col-span-2">
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
                                <h2 class="text-base sm:text-lg font-semibold text-charcoal">Recent Orders</h2>
                                <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' ?>orders.php" class="px-3 sm:px-4 py-1 sm:py-2 bg-folly text-white hover:bg-folly-600 transition-colors text-xs sm:text-sm font-medium rounded touch-manipulation">
                                    View All
                                </a>
                            </div>
                            <div class="p-3 sm:p-4 lg:p-6">
                                <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-6 sm:py-8">
                                        <i class="bi bi-receipt text-3xl sm:text-4xl text-charcoal-300 mb-3 sm:mb-4"></i>
                                        <p class="text-charcoal-400 text-sm sm:text-base">No orders yet</p>
                                    </div>
                                <?php else: ?>
                                    <!-- Mobile Card View -->
                                    <div class="block sm:hidden space-y-3">
                                        <?php foreach ($recent_orders as $order): ?>
                                        <div class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="font-medium text-charcoal text-sm">#<?= $order['id'] ?? 'N/A' ?></div>
                                                <span class="px-2 py-1 text-xs font-medium rounded <?= ($order['status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= ucfirst($order['status'] ?? 'pending') ?>
                                                </span>
                                            </div>
                                            <div class="text-sm text-charcoal-600 mb-1"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
                                            <div class="flex justify-between items-center">
                                                <div class="font-medium text-charcoal">£<?= number_format($order['total'] ?? 0, 2) ?></div>
                                                <div class="text-xs text-charcoal-500"><?= date('M j', strtotime($order['date'] ?? 'now')) ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Desktop Table View -->
                                    <div class="hidden sm:block overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="border-b border-gray-200">
                                                    <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-medium text-charcoal-600 text-xs sm:text-sm uppercase tracking-wide">Order ID</th>
                                                    <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-medium text-charcoal-600 text-xs sm:text-sm uppercase tracking-wide">Customer</th>
                                                    <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-medium text-charcoal-600 text-xs sm:text-sm uppercase tracking-wide">Amount</th>
                                                    <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-medium text-charcoal-600 text-xs sm:text-sm uppercase tracking-wide">Status</th>
                                                    <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-medium text-charcoal-600 text-xs sm:text-sm uppercase tracking-wide">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-xs sm:text-sm font-medium text-charcoal">#<?= $order['id'] ?? 'N/A' ?></td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-xs sm:text-sm text-charcoal"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-xs sm:text-sm font-medium text-charcoal">£<?= number_format($order['total'] ?? 0, 2) ?></td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4">
                                                        <span class="px-2 py-1 text-xs font-medium rounded <?= ($order['status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                            <?= ucfirst($order['status'] ?? 'pending') ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-xs sm:text-sm text-charcoal-600"><?= date('M j, Y', strtotime($order['date'] ?? 'now')) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Products -->
                    <div class="lg:col-span-1">
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
                                <h2 class="text-base sm:text-lg font-semibold text-charcoal">Low Stock Alert</h2>
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs sm:text-sm font-medium rounded"><?= count($low_stock_products) ?></span>
                            </div>
                            <div class="p-3 sm:p-4 lg:p-6">
                                <?php if (empty($low_stock_products)): ?>
                                    <div class="text-center py-6 sm:py-8">
                                        <i class="bi bi-check-circle text-3xl sm:text-4xl text-green-400 mb-3 sm:mb-4"></i>
                                        <p class="text-charcoal-400 text-sm sm:text-base">All products well stocked!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3 sm:space-y-4">
                                        <?php foreach (array_slice($low_stock_products, 0, 5) as $product): ?>
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                                            <div class="flex-1 min-w-0 pr-2">
                                                <h6 class="font-medium text-charcoal text-xs sm:text-sm truncate"><?= htmlspecialchars($product['name']) ?></h6>
                                                <p class="text-charcoal-400 text-xs truncate"><?= htmlspecialchars($product['slug']) ?></p>
                                            </div>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded flex-shrink-0"><?= $product['stock'] ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($low_stock_products) > 5): ?>
                                    <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200">
                                        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' ?>products.php?filter=low_stock" class="block w-full text-center px-3 sm:px-4 py-2 bg-red-50 text-red-700 hover:bg-red-100 transition-colors text-xs sm:text-sm font-medium rounded touch-manipulation">
                                            View All (<?= count($low_stock_products) ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </div>

                
                <!-- Quick Actions -->
                <div class="mt-6 sm:mt-8">
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                            <h2 class="text-base sm:text-lg font-semibold text-charcoal">Quick Actions</h2>
                        </div>
                        <div class="p-3 sm:p-4 lg:p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                                <a href="products.php?action=add" class="flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 bg-folly text-white hover:bg-folly-600 transition-colors text-center text-sm sm:text-base font-medium rounded touch-manipulation">
                                    <i class="bi bi-plus-circle mr-1 sm:mr-2"></i>
                                    Add Product
                                </a>
                                <a href="categories.php?action=add" class="flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 bg-green-600 text-white hover:bg-green-700 transition-colors text-center text-sm sm:text-base font-medium rounded touch-manipulation">
                                    <i class="bi bi-plus-circle mr-1 sm:mr-2"></i>
                                    Add Category
                                </a>
                                <a href="orders.php" class="flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 bg-tangerine text-white hover:bg-tangerine-600 transition-colors text-center text-sm sm:text-base font-medium rounded touch-manipulation">
                                    <i class="bi bi-receipt mr-1 sm:mr-2"></i>
                                    View Orders
                                </a>
                                <a href="settings.php" class="flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 bg-charcoal text-white hover:bg-charcoal-700 transition-colors text-center text-sm sm:text-base font-medium rounded touch-manipulation">
                                    <i class="bi bi-gear mr-1 sm:mr-2"></i>
                                    Site Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu functionality
        function openMobileMenu() {
            const mobileSidebar = document.getElementById('mobileSidebar');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            
            if (mobileSidebar && mobileMenuOverlay) {
                mobileSidebar.classList.remove('-translate-x-full');
                mobileMenuOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeMobileMenu() {
            const mobileSidebar = document.getElementById('mobileSidebar');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            
            if (mobileSidebar && mobileMenuOverlay) {
                mobileSidebar.classList.add('-translate-x-full');
                mobileMenuOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }
        
        // Close mobile menu when clicking on links
        document.addEventListener('DOMContentLoaded', function() {
            const mobileNavLinks = document.querySelectorAll('#mobileSidebar a');
            mobileNavLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Small delay to allow navigation to start
                    setTimeout(closeMobileMenu, 100);
                });
            });
            
            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileMenu();
                }
            });
            
            // Handle window resize - close menu if going to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) { // lg breakpoint
                    closeMobileMenu();
                }
            });
        });
        
        // Add touch-friendly hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const touchElements = document.querySelectorAll('.touch-manipulation');
            touchElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                    this.style.transition = 'transform 0.1s ease';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
                
                element.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html> 