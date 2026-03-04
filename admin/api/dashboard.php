<?php
/**
 * Mobile Admin API - Dashboard/Stats Endpoint
 * Provides dashboard statistics (uses MySQL)
 */

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/repositories/ProductRepository.php';
require_once __DIR__ . '/../../includes/repositories/CategoryRepository.php';
require_once __DIR__ . '/../../includes/repositories/OrderRepository.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/admin_functions.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'Authentication required']);
    exit;
}

if (!hasMobileAccess()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Mobile admin access not permitted']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed', 'message' => 'Only GET requests are allowed']);
    exit;
}

$stats = [
    'products' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0, 'low_stock' => 0, 'total_value' => 0],
    'categories' => ['total' => 0, 'active' => 0],
    'orders' => ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0, 'total_revenue' => 0, 'today_revenue' => 0],
    'mobile_orders' => ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0],
    'customers' => ['total' => 0, 'new_this_week' => 0],
    'ads' => ['total' => 0, 'active' => 0],
];

if (Database::isMySQLEnabled()) {
    $products = ProductRepository::getAllForAdmin();
    $stats['products']['total'] = count($products);
    $stats['products']['active'] = count(array_filter($products, fn($p) => !empty($p['active'])));
    $stats['products']['inactive'] = $stats['products']['total'] - $stats['products']['active'];
    $stats['products']['featured'] = count(array_filter($products, fn($p) => !empty($p['featured'])));
    $stats['products']['low_stock'] = count(array_filter($products, fn($p) => (int)($p['stock'] ?? 0) <= 10));
    $stats['products']['total_value'] = array_sum(array_map(fn($p) => (float)($p['price'] ?? 0) * (int)($p['stock'] ?? 0), $products));

    $categories = CategoryRepository::getAllIncludingInactive();
    $stats['categories']['total'] = count($categories);
    $stats['categories']['active'] = count(array_filter($categories, fn($c) => !empty($c['active'])));

    $orderStats = OrderRepository::getStats();
    $stats['orders']['total'] = (int)($orderStats['total'] ?? 0);
    $stats['orders']['pending'] = (int)($orderStats['pending'] ?? 0);
    $stats['orders']['processing'] = (int)($orderStats['processing'] ?? 0);
    $stats['orders']['completed'] = (int)($orderStats['completed'] ?? 0);
    $stats['orders']['cancelled'] = (int)($orderStats['cancelled'] ?? 0);
    $stats['orders']['total_revenue'] = (float)($orderStats['total_revenue'] ?? 0);
    $stats['orders']['today_revenue'] = 0;

    $mobileOrders = adminGetAllMobileOrders();
    $stats['mobile_orders']['total'] = count($mobileOrders);
    $stats['mobile_orders']['pending'] = count(array_filter($mobileOrders, fn($o) => ($o['status'] ?? '') === 'pending'));
    $stats['mobile_orders']['processing'] = count(array_filter($mobileOrders, fn($o) => ($o['status'] ?? '') === 'processing'));
    $stats['mobile_orders']['completed'] = count(array_filter($mobileOrders, fn($o) => ($o['status'] ?? '') === 'completed'));
    $stats['mobile_orders']['cancelled'] = count(array_filter($mobileOrders, fn($o) => ($o['status'] ?? '') === 'cancelled'));

    if (adminMobileOrdersTableExists()) {
        try {
            $todayStart = date('Y-m-d 00:00:00');
            $r = Database::fetchOne("SELECT COALESCE(SUM(total), 0) as today_revenue FROM orders WHERE date >= ?", [$todayStart]);
            $stats['orders']['today_revenue'] = (float)($r['today_revenue'] ?? 0);
        } catch (Exception $e) {
            // ignore
        }
    }

    if (Database::fetchOne("SHOW TABLES LIKE 'ads'") !== null) {
        $stats['ads']['total'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM ads");
        $stats['ads']['active'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM ads WHERE active = 1");
    }
    if (Database::fetchOne("SHOW TABLES LIKE 'users'") !== null) {
        $stats['customers']['total'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM users");
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats['customers']['new_this_week'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM users WHERE created_at >= ?", [$weekAgo]);
    }
}

if ($stats['ads']['total'] === 0 && file_exists(__DIR__ . '/../../data/ads.json')) {
    $ads = json_decode(file_get_contents(__DIR__ . '/../../data/ads.json'), true) ?: [];
    $stats['ads']['total'] = count($ads);
    $stats['ads']['active'] = count(array_filter($ads, fn($a) => !empty($a['active'])));
}

$products = Database::isMySQLEnabled() ? ProductRepository::getAllForAdmin() : [];
$orders = Database::isMySQLEnabled() ? OrderRepository::getAll() : [];
$recentOrders = array_slice($orders, 0, 5);
$lowStockProducts = array_values(array_filter($products, fn($p) => (int)($p['stock'] ?? 0) <= 10));
$lowStockProducts = array_slice($lowStockProducts, 0, 10);

$adminInfo = [
    'id' => $_SESSION['admin_id'] ?? null,
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? '',
    'role' => $_SESSION['admin_role'] ?? 'Administrator',
    'role_slug' => $_SESSION['admin_role_slug'] ?? '',
    'permissions' => $_SESSION['admin_permissions'] ?? [],
];

echo json_encode([
    'success' => true,
    'data' => [
        'stats' => $stats,
        'recent_orders' => $recentOrders,
        'low_stock_products' => $lowStockProducts,
        'admin' => $adminInfo
    ]
], JSON_PRETTY_PRINT);

logAdminActivity('view_dashboard', null, null, null, 'mobile');
