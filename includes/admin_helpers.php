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
