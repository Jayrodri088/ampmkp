<?php
/**
 * Mobile Admin API - Ads Endpoint
 * Handles advertisement management for mobile admin access
 */

header('Content-Type: application/json');

// Allow cross-origin requests from mobile apps
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);
session_start();

// Authenticate and authorize
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'Authentication required']);
    exit;
}

// Check mobile API access
if (!hasMobileAccess()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Mobile admin access not permitted']);
    exit;
}

// Check ads permission
if (!hasPermission('mobile', 'ads')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'Ad management not permitted']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Get ads from data file
$adsFile = __DIR__ . '/../../data/ads.json';
$ads = file_exists($adsFile) ? json_decode(file_get_contents($adsFile), true) : [];

/**
 * GET - List or get single ad
 */
if ($method === 'GET') {
    if ($action === 'list') {
        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
        $status = $_GET['status'] ?? '';

        // Filter ads
        $filtered = $ads;

        if ($status === 'active') {
            $filtered = array_filter($filtered, function($a) {
                return !empty($a['active']);
            });
        } elseif ($status === 'inactive') {
            $filtered = array_filter($filtered, function($a) {
                return empty($a['active']);
            });
        }

        // Sort by created date descending
        usort($filtered, function($a, $b) {
            return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
        });

        // Paginate
        $filtered = array_values($filtered);
        $total = count($filtered);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($filtered, $offset, $limit);

        echo json_encode([
            'success' => true,
            'data' => [
                'ads' => $paged,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'summary' => [
                    'total' => count($ads),
                    'active' => count(array_filter($ads, fn($a) => !empty($a['active']))),
                    'inactive' => count(array_filter($ads, fn($a) => empty($a['active'])))
                ]
            ]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('list_ads', 'ad', null, ['page' => $page, 'limit' => $limit], 'mobile');
    }
    elseif ($action === 'view' && $id) {
        $ad = null;
        foreach ($ads as $a) {
            if (($a['id'] ?? '') == $id) {
                $ad = $a;
                break;
            }
        }

        if (!$ad) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Ad not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => ['ad' => $ad]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('view_ad', 'ad', $id, null, 'mobile');
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_action', 'message' => 'Invalid action']);
    }
}

/**
 * POST - Create new ad
 */
elseif ($method === 'POST') {
    // Handle file upload and form data
    $data = $_POST;

    // Handle JSON input
    if (empty($data)) {
        $input = file_get_contents('php://input');
        $jsonData = json_decode($input, true);
        if ($jsonData) $data = $jsonData;
    }

    // Validate required fields
    if (empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Ad title is required']);
        exit;
    }

    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../assets/images/ads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_file', 'message' => 'Invalid image format']);
            exit;
        }

        $fileName = 'ad_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = $fileName;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'upload_failed', 'message' => 'Failed to upload image']);
            exit;
        }
    } elseif (!empty($data['image_url'])) {
        $imagePath = $data['image_url'];
    }

    if (empty($imagePath)) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Ad image is required']);
        exit;
    }

    // Generate ad ID
    $newId = count($ads) > 0 ? max(array_column($ads, 'id')) + 1 : 1;

    // Create ad
    $ad = [
        'id' => $newId,
        'title' => $data['title'],
        'description' => $data['description'] ?? '',
        'image' => $imagePath,
        'destination_type' => $data['destination_type'] ?? 'custom',
        'active' => !empty($data['active']),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Add destination-specific fields
    if ($ad['destination_type'] === 'product' && !empty($data['product_id'])) {
        $ad['product_id'] = $data['product_id'];
    } elseif ($ad['destination_type'] === 'category' && !empty($data['category_id'])) {
        $ad['category_id'] = $data['category_id'];
    } elseif ($ad['destination_type'] === 'search' && !empty($data['search_query'])) {
        $ad['search_query'] = $data['search_query'];
    } elseif ($ad['destination_type'] === 'custom' && !empty($data['custom_url'])) {
        $ad['custom_url'] = $data['custom_url'];
    }

    $ads[] = $ad;

    // Save to file
    if (file_put_contents($adsFile, json_encode($ads, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'Ad created successfully',
            'data' => ['ad' => $ad]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('create_ad', 'ad', $newId, ['title' => $ad['title']], 'mobile');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => 'Failed to save ad']);
    }
}

/**
 * PUT - Update ad
 */
elseif ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Ad ID is required']);
        exit;
    }

    // Get input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    // Find and update ad
    $found = false;
    $oldImage = null;
    foreach ($ads as &$ad) {
        if (($ad['id'] ?? '') == $id) {
            $found = true;
            $oldImage = $ad['image'] ?? null;

            if (isset($data['title'])) $ad['title'] = $data['title'];
            if (isset($data['description'])) $ad['description'] = $data['description'];
            if (isset($data['destination_type'])) $ad['destination_type'] = $data['destination_type'];
            if (isset($data['active'])) $ad['active'] = !empty($data['active']);

            // Handle destination-specific fields
            if ($ad['destination_type'] === 'product' && isset($data['product_id'])) $ad['product_id'] = $data['product_id'];
            if ($ad['destination_type'] === 'category' && isset($data['category_id'])) $ad['category_id'] = $data['category_id'];
            if ($ad['destination_type'] === 'search' && isset($data['search_query'])) $ad['search_query'] = $data['search_query'];
            if ($ad['destination_type'] === 'custom' && isset($data['custom_url'])) $ad['custom_url'] = $data['custom_url'];

            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/images/ads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileInfo = pathinfo($_FILES['image']['name']);
                $extension = strtolower($fileInfo['extension']);

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $fileName = 'ad_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        // Delete old image
                        if ($oldImage && file_exists($uploadDir . $oldImage)) {
                            unlink($uploadDir . $oldImage);
                        }
                        $ad['image'] = $fileName;
                    }
                }
            }

            $ad['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Ad not found']);
        exit;
    }

    // Save to file
    if (file_put_contents($adsFile, json_encode($ads, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'Ad updated successfully',
            'data' => ['ad' => $ad]
        ], JSON_PRETTY_PRINT);

        logAdminActivity('update_ad', 'ad', $id, $data, 'mobile');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => 'Failed to save ad']);
    }
}

/**
 * DELETE - Delete ad
 */
elseif ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_error', 'message' => 'Ad ID is required']);
        exit;
    }

    // Find and remove ad
    $found = false;
    $deletedAd = null;
    $ads = array_filter($ads, function($a) use ($id, &$found, &$deletedAd) {
        if (($a['id'] ?? '') == $id) {
            $found = true;
            $deletedAd = $a;
            return false;
        }
        return true;
    });

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'message' => 'Ad not found']);
        exit;
    }

    // Delete image file
    if (!empty($deletedAd['image'])) {
        $imagePath = __DIR__ . '/../../assets/images/ads/' . $deletedAd['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Save to file
    if (file_put_contents($adsFile, json_encode(array_values($ads), JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'Ad deleted successfully'
        ], JSON_PRETTY_PRINT);

        logAdminActivity('delete_ad', 'ad', $id, ['title' => $deletedAd['title'] ?? ''], 'mobile');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed', 'message' => 'Failed to delete ad']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed', 'message' => 'Method not allowed']);
}
