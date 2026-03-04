<?php
/**
 * Admin Helper Functions
 * Provides CRUD operations that work with both JSON and MySQL backends
 */

require_once __DIR__ . '/functions.php';

/**
 * Get all products for admin (including inactive)
 */
function adminGetAllProducts($categoryId = null) {
    if (isMySQLBackend()) {
        return ProductRepository::getAllForAdmin($categoryId);
    }

    $products = readJsonFile('products.json');
    if ($categoryId !== null) {
        $products = array_filter($products, function($p) use ($categoryId) {
            return $p['category_id'] == $categoryId;
        });
    }
    return array_values($products);
}

/**
 * Get product by ID for admin (including inactive)
 */
function adminGetProductById($id) {
    if (isMySQLBackend()) {
        return ProductRepository::getById((int)$id, false);
    }

    $products = readJsonFile('products.json');
    foreach ($products as $product) {
        if ($product['id'] == $id) {
            return $product;
        }
    }
    return null;
}

/**
 * Save a product (create or update)
 */
function adminSaveProduct($data, $isNew = false) {
    if (isMySQLBackend()) {
        if ($isNew) {
            return ProductRepository::create($data);
        } else {
            return ProductRepository::update((int)$data['id'], $data);
        }
    }

    // JSON backend
    $products = readJsonFile('products.json');

    if ($isNew) {
        // Generate new ID
        $maxId = 0;
        foreach ($products as $p) {
            if ($p['id'] > $maxId) {
                $maxId = $p['id'];
            }
        }
        $data['id'] = $maxId + 1;
        $products[] = $data;
    } else {
        // Update existing
        foreach ($products as &$product) {
            if ($product['id'] == $data['id']) {
                $product = array_merge($product, $data);
                break;
            }
        }
    }

    return writeJsonFile('products.json', $products) ? ($isNew ? $data['id'] : true) : false;
}

/**
 * Delete a product
 */
function adminDeleteProduct($id) {
    if (isMySQLBackend()) {
        return ProductRepository::delete((int)$id);
    }

    // JSON backend
    $products = readJsonFile('products.json');
    $products = array_filter($products, function($p) use ($id) {
        return $p['id'] != $id;
    });
    return writeJsonFile('products.json', array_values($products));
}

/**
 * Get all categories for admin (including inactive)
 */
function adminGetAllCategories() {
    if (isMySQLBackend()) {
        return CategoryRepository::getAllIncludingInactive();
    }
    return readJsonFile('categories.json');
}

/**
 * Get category by ID for admin (including inactive)
 */
function adminGetCategoryById($id) {
    if (isMySQLBackend()) {
        return CategoryRepository::getById((int)$id);
    }

    $categories = readJsonFile('categories.json');
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

/**
 * Save a category (create or update)
 */
function adminSaveCategory($data, $isNew = false) {
    if (isMySQLBackend()) {
        if ($isNew) {
            return CategoryRepository::create($data);
        } else {
            return CategoryRepository::update((int)$data['id'], $data);
        }
    }

    // JSON backend
    $categories = readJsonFile('categories.json');

    if ($isNew) {
        // Generate new ID
        $maxId = 0;
        if (!empty($categories)) {
            $maxId = max(array_column($categories, 'id'));
        }
        $data['id'] = $maxId + 1;
        $categories[] = $data;
    } else {
        // Update existing
        foreach ($categories as &$category) {
            if ($category['id'] == $data['id']) {
                $category = array_merge($category, $data);
                break;
            }
        }
    }

    return writeJsonFile('categories.json', $categories) ? ($isNew ? $data['id'] : true) : false;
}

/**
 * Delete a category
 */
function adminDeleteCategory($id) {
    if (isMySQLBackend()) {
        return CategoryRepository::delete((int)$id);
    }

    // JSON backend
    $categories = readJsonFile('categories.json');
    $categories = array_filter($categories, function($c) use ($id) {
        return $c['id'] != $id;
    });
    return writeJsonFile('categories.json', array_values($categories));
}

/**
 * Check if category has products
 */
function adminCategoryHasProducts($categoryId) {
    if (isMySQLBackend()) {
        return CategoryRepository::hasProducts((int)$categoryId);
    }

    $products = readJsonFile('products.json');
    foreach ($products as $p) {
        if ($p['category_id'] == $categoryId) {
            return true;
        }
    }
    return false;
}

/**
 * Check if category has subcategories
 */
function adminCategoryHasSubcategories($categoryId) {
    if (isMySQLBackend()) {
        return CategoryRepository::hasSubcategories((int)$categoryId);
    }

    $categories = readJsonFile('categories.json');
    foreach ($categories as $c) {
        if (($c['parent_id'] ?? 0) == $categoryId) {
            return true;
        }
    }
    return false;
}

/**
 * Get all orders for admin
 */
function adminGetAllOrders() {
    if (isMySQLBackend()) {
        return OrderRepository::getAll();
    }
    return readJsonFile('orders.json');
}

/**
 * Get order by ID
 */
function adminGetOrderById($id) {
    if (isMySQLBackend()) {
        return OrderRepository::getById($id);
    }

    $orders = readJsonFile('orders.json');
    foreach ($orders as $order) {
        if ($order['id'] === $id) {
            return $order;
        }
    }
    return null;
}

/**
 * Save an order (create or update)
 */
function adminSaveOrder($data, $isNew = false) {
    if (isMySQLBackend()) {
        if ($isNew) {
            return OrderRepository::create($data);
        } else {
            return OrderRepository::update($data['id'], $data);
        }
    }

    // JSON backend
    $orders = readJsonFile('orders.json');

    if ($isNew) {
        $orders[] = $data;
    } else {
        foreach ($orders as &$order) {
            if ($order['id'] === $data['id']) {
                $order = array_merge($order, $data);
                break;
            }
        }
    }

    return writeJsonFile('orders.json', $orders);
}

/**
 * Update order status
 */
function adminUpdateOrderStatus($orderId, $status) {
    if (isMySQLBackend()) {
        return OrderRepository::updateStatus($orderId, $status);
    }

    // JSON backend
    $orders = readJsonFile('orders.json');
    foreach ($orders as &$order) {
        if ($order['id'] === $orderId) {
            $order['status'] = $status;
            $order['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    return writeJsonFile('orders.json', $orders);
}

/**
 * Update order payment status
 */
function adminUpdatePaymentStatus($orderId, $paymentStatus) {
    if (isMySQLBackend()) {
        return OrderRepository::updatePaymentStatus($orderId, $paymentStatus);
    }

    // JSON backend
    $orders = readJsonFile('orders.json');
    foreach ($orders as &$order) {
        if ($order['id'] === $orderId) {
            $order['payment_status'] = $paymentStatus;
            $order['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    return writeJsonFile('orders.json', $orders);
}

/**
 * Delete an order
 */
function adminDeleteOrder($orderId) {
    if (isMySQLBackend()) {
        return OrderRepository::delete($orderId);
    }

    // JSON backend
    $orders = readJsonFile('orders.json');
    $orders = array_filter($orders, function($o) use ($orderId) {
        return $o['id'] !== $orderId;
    });
    return writeJsonFile('orders.json', array_values($orders));
}

/**
 * Get order statistics
 */
function adminGetOrderStats() {
    if (isMySQLBackend()) {
        return OrderRepository::getStats();
    }

    // JSON backend
    $orders = readJsonFile('orders.json');
    return [
        'total' => count($orders),
        'pending' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending')),
        'processing' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'processing')),
        'completed' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'completed')),
        'cancelled' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'cancelled')),
        'total_revenue' => array_sum(array_map(fn($o) => $o['total'] ?? 0, array_filter($orders, fn($o) => ($o['payment_status'] ?? 'pending') === 'completed'))),
        'pending_payment' => count(array_filter($orders, fn($o) => ($o['payment_status'] ?? 'pending') === 'pending')),
        'payment_confirmed_by_customer' => count(array_filter($orders, fn($o) => !empty($o['payment_confirmed_by_customer']) && ($o['payment_status'] ?? 'pending') === 'pending'))
    ];
}

/**
 * Check if mobile_orders table exists (Node backend)
 */
function adminMobileOrdersTableExists() {
    if (!isMySQLBackend()) {
        return false;
    }
    try {
        $r = Database::fetchOne("SHOW TABLES LIKE 'mobile_orders'");
        return $r !== null;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all mobile orders (from Node backend table)
 */
function adminGetAllMobileOrders() {
    if (!adminMobileOrdersTableExists()) {
        return [];
    }
    try {
        $sql = "SELECT mo.*, u.email as customer_email, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as customer_name
                FROM mobile_orders mo
                LEFT JOIN users u ON u.id = mo.user_id
                ORDER BY mo.created_at DESC";
        $rows = Database::fetchAll($sql);
        foreach ($rows as &$row) {
            $row['items'] = adminGetMobileOrderItems($row['id']);
            $row['source'] = 'mobile';
        }
        return $rows;
    } catch (Exception $e) {
        error_log('adminGetAllMobileOrders: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get items for a mobile order
 */
function adminGetMobileOrderItems($orderId) {
    if (!adminMobileOrdersTableExists()) {
        return [];
    }
    try {
        $sql = "SELECT mi.*, p.name as product_name, p.image as product_image
                FROM mobile_order_items mi
                LEFT JOIN products p ON p.id = mi.product_id
                WHERE mi.order_id = ?
                ORDER BY mi.id";
        return Database::fetchAll($sql, [$orderId]);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get mobile order by ID
 */
function adminGetMobileOrderById($id) {
    if (!adminMobileOrdersTableExists()) {
        return null;
    }
    try {
        $sql = "SELECT mo.*, u.email as customer_email, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as customer_name
                FROM mobile_orders mo
                LEFT JOIN users u ON u.id = mo.user_id
                WHERE mo.id = ?";
        $order = Database::fetchOne($sql, [$id]);
        if ($order) {
            $order['items'] = adminGetMobileOrderItems($order['id']);
            $order['source'] = 'mobile';
        }
        return $order;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Update mobile order status
 */
function adminUpdateMobileOrderStatus($orderId, $status) {
    if (!adminMobileOrdersTableExists()) {
        return false;
    }
    try {
        return Database::update('mobile_orders', ['status' => $status], 'id = ?', [$orderId]) > 0;
    } catch (Exception $e) {
        error_log('adminUpdateMobileOrderStatus: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if table exists (for mobile/Node backend tables)
 */
function adminTableExists($table) {
    if (!isMySQLBackend()) return false;
    try {
        return Database::fetchOne("SHOW TABLES LIKE ?", [$table]) !== null;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get mobile app users (Node backend users table)
 */
function adminGetMobileUsers($search = null, $limit = 50, $offset = 0) {
    if (!adminTableExists('users')) return ['users' => [], 'total' => 0];
    try {
        $where = '1=1';
        $params = [];
        if ($search !== null && $search !== '') {
            $term = '%' . Database::escapeLike($search) . '%';
            $where .= ' AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
            $params = array_merge($params, [$term, $term, $term]);
        }
        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM users WHERE $where", $params);
        $sql = "SELECT id, email, first_name, last_name, phone, avatar, date_of_birth, gender, created_at FROM users WHERE $where ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $users = Database::fetchAll($sql, $params);
        return ['users' => $users, 'total' => $total];
    } catch (Exception $e) {
        error_log('adminGetMobileUsers: ' . $e->getMessage());
        return ['users' => [], 'total' => 0];
    }
}

function adminGetMobileUserById($id) {
    if (!adminTableExists('users')) return null;
    try {
        return Database::fetchOne("SELECT id, email, first_name, last_name, phone, avatar, date_of_birth, gender, created_at FROM users WHERE id = ?", [$id]);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get mobile app reviews (Node backend reviews table)
 */
function adminGetMobileReviews($productId = null, $limit = 50, $offset = 0) {
    if (!adminTableExists('reviews')) return ['reviews' => [], 'total' => 0];
    try {
        $where = '1=1';
        $params = [];
        if ($productId !== null && $productId !== '') {
            $where .= ' AND r.product_id = ?';
            $params[] = $productId;
        }
        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM reviews r WHERE $where", $params);
        $sql = "SELECT r.*, p.name as product_name, u.email as user_email,
                CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as user_name
                FROM reviews r
                LEFT JOIN products p ON p.id = r.product_id
                LEFT JOIN users u ON u.id = r.user_id
                WHERE $where ORDER BY r.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $reviews = Database::fetchAll($sql, $params);
        return ['reviews' => $reviews, 'total' => $total];
    } catch (Exception $e) {
        error_log('adminGetMobileReviews: ' . $e->getMessage());
        return ['reviews' => [], 'total' => 0];
    }
}

function adminDeleteMobileReview($id) {
    if (!adminTableExists('reviews')) return false;
    try {
        return Database::delete('reviews', 'id = ?', [$id]) > 0;
    } catch (Exception $e) {
        error_log('adminDeleteMobileReview: ' . $e->getMessage());
        return false;
    }
}

/**
 * Coupons (Node backend coupons table) - full CRUD
 */
function adminGetCoupons($activeOnly = false, $limit = 100, $offset = 0) {
    if (!adminTableExists('coupons')) return ['coupons' => [], 'total' => 0];
    try {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM coupons $where");
        $sql = "SELECT * FROM coupons $where ORDER BY valid_until DESC, id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $coupons = Database::fetchAll($sql);
        return ['coupons' => $coupons, 'total' => $total];
    } catch (Exception $e) {
        error_log('adminGetCoupons: ' . $e->getMessage());
        return ['coupons' => [], 'total' => 0];
    }
}

function adminGetCouponById($id) {
    if (!adminTableExists('coupons')) return null;
    try {
        return Database::fetchOne("SELECT * FROM coupons WHERE id = ?", [$id]);
    } catch (Exception $e) {
        return null;
    }
}

function adminGetCouponByCode($code) {
    if (!adminTableExists('coupons')) return null;
    try {
        return Database::fetchOne("SELECT * FROM coupons WHERE code = ?", [$code]);
    } catch (Exception $e) {
        return null;
    }
}

function adminCreateCoupon($data) {
    if (!adminTableExists('coupons')) return false;
    try {
        $insert = [
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'] ?? 'percentage',
            'discount_value' => $data['discount_value'] ?? 0,
            'min_purchase' => $data['min_purchase'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'valid_from' => $data['valid_from'] ?? date('Y-m-d H:i:s'),
            'valid_until' => $data['valid_until'] ?? date('Y-m-d H:i:s', strtotime('+1 year')),
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => 0,
            'is_active' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Database::insert('coupons', $insert);
        return true;
    } catch (Exception $e) {
        error_log('adminCreateCoupon: ' . $e->getMessage());
        return false;
    }
}

function adminUpdateCoupon($id, $data) {
    if (!adminTableExists('coupons')) return false;
    try {
        $allowed = ['code', 'description', 'discount_type', 'discount_value', 'min_purchase', 'max_discount', 'valid_from', 'valid_until', 'usage_limit', 'is_active'];
        $update = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = in_array($f, ['is_active']) ? ($data[$f] ? 1 : 0) : $data[$f];
            }
        }
        if (empty($update)) return true;
        return Database::update('coupons', $update, 'id = ?', [$id]) > 0;
    } catch (Exception $e) {
        error_log('adminUpdateCoupon: ' . $e->getMessage());
        return false;
    }
}

function adminDeleteCoupon($id) {
    if (!adminTableExists('coupons')) return false;
    try {
        return Database::delete('coupons', 'id = ?', [$id]) > 0;
    } catch (Exception $e) {
        error_log('adminDeleteCoupon: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all ratings for admin
 */
function adminGetAllRatings() {
    if (isMySQLBackend()) {
        return RatingRepository::getAll();
    }
    return readJsonFile('ratings.json');
}

/**
 * Delete a rating
 */
function adminDeleteRating($id) {
    if (isMySQLBackend()) {
        return RatingRepository::delete((int)$id);
    }

    // JSON backend
    $ratings = readJsonFile('ratings.json');
    $ratings = array_filter($ratings, function($r) use ($id) {
        return $r['id'] != $id;
    });
    return writeJsonFile('ratings.json', array_values($ratings));
}

/**
 * Save settings
 */
function adminSaveSettings($settings) {
    if (isMySQLBackend()) {
        return SettingsRepository::updateMultiple($settings);
    }
    return writeJsonFile('settings.json', $settings);
}
