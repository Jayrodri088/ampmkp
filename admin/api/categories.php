<?php
/**
 * Mobile Admin API - Categories Endpoint
 * Handles category management for mobile admin access (uses MySQL)
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

if (!hasPermission('mobile', 'categories')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Category management not permitted']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!Database::isMySQLEnabled()) {
    http_response_code(503);
    echo json_encode(['error' => 'unavailable', 'message' => 'MySQL backend required']);
    exit;
}

if ($method === 'GET') {
    if ($id) {
        $category = CategoryRepository::getById($id);
        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Category not found']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'data' => ['category' => $category]
        ], JSON_PRETTY_PRINT);
    } else {
        $categories = CategoryRepository::getAllIncludingInactive();
        echo json_encode([
            'success' => true,
            'data' => ['categories' => $categories]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('list_categories', 'category', null, null, 'mobile');
    }
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? $_POST;

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Category name is required']);
        exit;
    }

    $slug = $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
    if (CategoryRepository::slugExists($slug)) {
        $slug .= '-' . time();
    }

    $insertData = [
        'name' => $data['name'],
        'slug' => $slug,
        'description' => $data['description'] ?? '',
        'image' => $data['image'] ?? 'categories/default.jpg',
        'parent_id' => (int)($data['parent_id'] ?? 0),
        'active' => isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
        'featured' => isset($data['featured']) ? ($data['featured'] ? 1 : 0) : 0,
        'sort_order' => (int)($data['sort_order'] ?? 0),
    ];

    try {
        $newId = CategoryRepository::create($insertData);
        $category = CategoryRepository::getById($newId);
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => ['category' => $category]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('create_category', 'category', $newId, ['name' => $insertData['name']], 'mobile');
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Category ID is required']);
        exit;
    }

    $existing = CategoryRepository::getById($id);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Category not found']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
    if (empty($data)) {
        parse_str($input, $data);
    }

    $updateData = [];
    foreach (['name', 'slug', 'description', 'image', 'parent_id', 'active', 'featured', 'sort_order'] as $field) {
        if (array_key_exists($field, $data)) {
            $updateData[$field] = in_array($field, ['active', 'featured']) ? ($data[$field] ? 1 : 0) : $data[$field];
        }
    }

    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'No fields to update']);
        exit;
    }

    try {
        CategoryRepository::update($id, $updateData);
        $category = CategoryRepository::getById($id);
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => ['category' => $category]
        ], JSON_PRETTY_PRINT);
        logAdminActivity('update_category', 'category', $id, $updateData, 'mobile');
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Category ID is required']);
        exit;
    }

    $existing = CategoryRepository::getById($id);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Category not found']);
        exit;
    }

    $name = $existing['name'] ?? '';
    try {
        if (CategoryRepository::delete($id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ], JSON_PRETTY_PRINT);
            logAdminActivity('delete_category', 'category', $id, ['name' => $name], 'mobile');
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'save_failed', 'message' => 'Failed to delete category (may have products or subcategories)']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed', 'message' => 'Method not allowed']);
