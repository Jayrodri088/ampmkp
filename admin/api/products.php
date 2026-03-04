<?php
/**
 * Mobile Admin API - Products Endpoint
 * Handles product management for mobile admin access (uses MySQL)
 */

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/repositories/ProductRepository.php';
require_once __DIR__ . '/../../includes/repositories/CategoryRepository.php';
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

if (!hasPermission('mobile', 'products')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Product management not permitted']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!Database::isMySQLEnabled()) {
    http_response_code(503);
    echo json_encode(['error' => 'unavailable', 'message' => 'MySQL backend required']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'list') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $status = $_GET['status'] ?? '';

        $products = ProductRepository::getAllForAdmin($categoryId);

        if ($search !== '') {
            $searchLower = strtolower($search);
            $products = array_filter($products, function($p) use ($searchLower) {
                return strpos(strtolower($p['name'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($p['description'] ?? ''), $searchLower) !== false;
            });
        }

        if ($status === 'active') {
            $products = array_filter($products, fn($p) => !empty($p['active']));
        } elseif ($status === 'inactive') {
            $products = array_filter($products, fn($p) => empty($p['active']));
        }

        $products = array_values($products);
        $total = count($products);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($products, $offset, $limit);

        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $paged,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => $total > 0 ? (int)ceil($total / $limit) : 0
                ]
            ]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('list_products', 'product', null, ['page' => $page, 'limit' => $limit], 'mobile');
    } elseif ($action === 'view' && $id) {
        $product = ProductRepository::getById($id, false);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Product not found']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'data' => ['product' => $product]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('view_product', 'product', $id, null, 'mobile');
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_action', 'message' => 'Invalid action']);
    }
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        $data = $_POST;
    }

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Product name is required']);
        exit;
    }

    $slug = $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
    if (ProductRepository::slugExists($slug)) {
        $slug .= '-' . time();
    }

    $categoryId = (int)($data['category_id'] ?? 0);
    if ($categoryId < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Valid category_id is required']);
        exit;
    }

    $productData = [
        'name' => $data['name'],
        'slug' => $slug,
        'description' => $data['description'] ?? '',
        'price' => (float)($data['price'] ?? 0),
        'category_id' => $categoryId,
        'image' => $data['image'] ?? 'products/placeholder.jpg',
        'stock' => (int)($data['stock'] ?? 0),
        'featured' => !empty($data['featured']),
        'active' => isset($data['active']) ? !empty($data['active']) : true,
        'has_sizes' => !empty($data['has_sizes']),
        'has_colors' => !empty($data['has_colors']),
        'prices' => $data['prices'] ?? [],
        'available_sizes' => $data['available_sizes'] ?? [],
        'available_colors' => $data['available_colors'] ?? [],
        'features' => $data['features'] ?? [],
        'images' => $data['images'] ?? [],
    ];

    try {
        $newId = ProductRepository::create($productData);
        $product = ProductRepository::getById($newId, false);
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => ['product' => $product]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('create_product', 'product', $newId, ['name' => $productData['name']], 'mobile');
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Product ID is required']);
        exit;
    }

    $existing = ProductRepository::getById($id, false);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Product not found']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        parse_str($input, $data);
    }

    $allowed = ['name', 'slug', 'description', 'price', 'category_id', 'image', 'stock', 'featured', 'active', 'has_sizes', 'has_colors', 'prices', 'available_sizes', 'available_colors', 'features', 'images'];
    $updateData = ['id' => $id];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $updateData[$field] = $data[$field];
        }
    }

    try {
        ProductRepository::update($id, $updateData);
        $product = ProductRepository::getById($id, false);
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => ['product' => $product]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('update_product', 'product', $id, array_diff_key($updateData, ['id' => 1]), 'mobile');
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Product ID is required']);
        exit;
    }

    $existing = ProductRepository::getById($id, false);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Product not found']);
        exit;
    }

    $name = $existing['name'] ?? '';
    if (ProductRepository::delete($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ], JSON_PRETTY_PRINT);
        logAdminActivity('delete_product', 'product', $id, ['name' => $name], 'mobile');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => 'Failed to delete product']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed', 'message' => 'Method not allowed']);
