<?php
/**
 * Mobile Admin API - Orders Endpoint
 * Handles order management for mobile admin (MySQL: website orders + mobile_orders)
 */

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
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

if (!hasPermission('mobile', 'orders')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Order management not permitted']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$source = $_GET['source'] ?? 'all'; // 'web', 'mobile', 'all'

if (!Database::isMySQLEnabled()) {
    http_response_code(503);
    echo json_encode(['error' => 'unavailable', 'message' => 'MySQL backend required']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'list') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
        $status = $_GET['status'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $orders = [];
        if ($source === 'all' || $source === 'web') {
            $webOrders = OrderRepository::getFiltered(
                $status !== '' ? $status : null,
                null,
                $search !== '' ? $search : null,
                'date_desc',
                $source === 'all' ? 500 : $limit,
                0
            );
            foreach ($webOrders as $o) {
                $o['source'] = 'web';
                $o['date'] = $o['date'] ?? $o['created_at'] ?? null;
                $orders[] = $o;
            }
        }

        if ($source === 'all' || $source === 'mobile') {
            $mobileOrders = adminGetAllMobileOrders();
            foreach ($mobileOrders as $o) {
                $o['date'] = $o['created_at'] ?? null;
                if ($status !== '' && ($o['status'] ?? '') !== $status) {
                    continue;
                }
                if ($search !== '') {
                    $searchLower = strtolower($search);
                    $match = strpos(strtolower($o['id'] ?? ''), $searchLower) !== false
                        || strpos(strtolower($o['customer_email'] ?? ''), $searchLower) !== false
                        || strpos(strtolower($o['customer_name'] ?? ''), $searchLower) !== false;
                    if (!$match) {
                        continue;
                    }
                }
                $orders[] = $o;
            }
        }

        usort($orders, function ($a, $b) {
            $tA = strtotime($a['date'] ?? '0');
            $tB = strtotime($b['date'] ?? '0');
            return $tB - $tA;
        });

        $total = count($orders);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($orders, $offset, $limit);

        $summary = [
            'total' => $total,
            'pending' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending')),
            'processing' => count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'processing')),
            'completed' => count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'completed')),
            'cancelled' => count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'cancelled')),
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'orders' => $paged,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => $total > 0 ? (int)ceil($total / $limit) : 0
                ],
                'summary' => $summary
            ]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('list_orders', 'order', null, ['page' => $page, 'source' => $source], 'mobile');
    } elseif ($action === 'view' && $id) {
        $order = null;
        $order = OrderRepository::getById($id);
        if ($order) {
            $order['source'] = 'web';
        }
        if (!$order && function_exists('adminGetMobileOrderById')) {
            $order = adminGetMobileOrderById($id);
        }

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Order not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => ['order' => $order]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('view_order', 'order', $id, null, 'mobile');
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_action', 'message' => 'Invalid action']);
    }
    exit;
}

if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Order ID is required']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        parse_str($input, $data);
    }

    $validStatuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
    $newStatus = $data['status'] ?? null;
    if (!$newStatus || !in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'validation_error',
            'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
        ]);
        exit;
    }

    $updated = false;
    if (OrderRepository::exists($id)) {
        $updated = OrderRepository::updateStatus($id, $newStatus);
    } elseif (function_exists('adminGetMobileOrderById') && adminGetMobileOrderById($id)) {
        $updated = adminUpdateMobileOrderStatus($id, $newStatus);
    }

    if (!$updated) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Order not found']);
        exit;
    }

    $order = OrderRepository::getById($id);
    if (!$order) {
        $order = adminGetMobileOrderById($id);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'data' => ['order' => $order, 'new_status' => $newStatus]
    ], JSON_PRETTY_PRINT);

    logAdminActivity('update_order_status', 'order', $id, ['new_status' => $newStatus], 'mobile');
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed', 'message' => 'Method not allowed']);
