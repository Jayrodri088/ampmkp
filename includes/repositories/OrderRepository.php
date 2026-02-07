<?php
/**
 * Order Repository
 * Data access layer for orders using MySQL
 */

require_once __DIR__ . '/../database.php';

class OrderRepository {

    /**
     * Get all orders
     */
    public static function getAll(): array {
        $sql = "SELECT * FROM orders ORDER BY date DESC";
        $orders = Database::fetchAll($sql);
        return array_map([self::class, 'hydrateOrder'], $orders);
    }

    /**
     * Get orders with filters
     */
    public static function getFiltered(
        ?string $status = null,
        ?string $paymentStatus = null,
        ?string $search = null,
        string $sortBy = 'date_desc',
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM orders WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($paymentStatus !== null && $paymentStatus !== 'all') {
            $sql .= " AND payment_status = ?";
            $params[] = $paymentStatus;
        }

        if ($search !== null && $search !== '') {
            $searchTerm = '%' . Database::escapeLike($search) . '%';
            $sql .= " AND (id LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Sorting
        switch ($sortBy) {
            case 'date_asc':
                $sql .= " ORDER BY date ASC";
                break;
            case 'amount_asc':
                $sql .= " ORDER BY total ASC";
                break;
            case 'amount_desc':
                $sql .= " ORDER BY total DESC";
                break;
            case 'status':
                $sql .= " ORDER BY status, date DESC";
                break;
            case 'date_desc':
            default:
                $sql .= " ORDER BY date DESC";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $orders = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrateOrder'], $orders);
    }

    /**
     * Count orders with filters
     */
    public static function countFiltered(
        ?string $status = null,
        ?string $paymentStatus = null,
        ?string $search = null
    ): int {
        $sql = "SELECT COUNT(*) FROM orders WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($paymentStatus !== null && $paymentStatus !== 'all') {
            $sql .= " AND payment_status = ?";
            $params[] = $paymentStatus;
        }

        if ($search !== null && $search !== '') {
            $searchTerm = '%' . Database::escapeLike($search) . '%';
            $sql .= " AND (id LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        return (int)Database::fetchColumn($sql, $params);
    }

    /**
     * Get order by ID
     */
    public static function getById(string $id): ?array {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $order = Database::fetchOne($sql, [$id]);
        return $order ? self::hydrateOrder($order) : null;
    }

    /**
     * Get orders by customer email
     */
    public static function getByEmail(string $email): array {
        $sql = "SELECT * FROM orders WHERE customer_email = ? ORDER BY date DESC";
        $orders = Database::fetchAll($sql, [$email]);
        return array_map([self::class, 'hydrateOrder'], $orders);
    }

    /**
     * Create a new order
     */
    public static function create(array $data): string {
        return Database::transaction(function() use ($data) {
            // Generate order ID if not provided
            $orderId = $data['id'] ?? self::generateOrderId();

            // Insert main order
            $orderData = [
                'id' => $orderId,
                'customer_name' => $data['customer_name'] ?? '',
                'customer_email' => $data['customer_email'] ?? '',
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_country_code' => $data['customer_country_code'] ?? null,
                'subtotal' => $data['subtotal'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'total' => $data['total'] ?? 0,
                'currency_code' => $data['currency_code'] ?? 'GBP',
                'status' => $data['status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => $data['payment_status'] ?? 'pending',
                'stripe_payment_intent' => $data['stripe_payment_intent'] ?? null,
                'shipping_method' => $data['shipping_method'] ?? 'delivery',
                'notes' => $data['notes'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'payment_confirmed_by_customer' => $data['payment_confirmed_by_customer'] ?? 0,
                'account_holder' => $data['account_holder'] ?? null,
                'date' => $data['date'] ?? date('Y-m-d H:i:s'),
            ];

            Database::insertWithId('orders', $orderData);

            // Insert addresses
            if (!empty($data['shipping_address'])) {
                self::saveAddress($orderId, 'shipping', $data['shipping_address']);
            }
            if (!empty($data['billing_address'])) {
                self::saveAddress($orderId, 'billing', $data['billing_address']);
            }

            // Insert items
            if (!empty($data['items'])) {
                self::saveItems($orderId, $data['items']);
            }

            return $orderId;
        });
    }

    /**
     * Update an order
     */
    public static function update(string $id, array $data): bool {
        return Database::transaction(function() use ($id, $data) {
            $updateData = [];
            $allowedFields = [
                'customer_name', 'customer_email', 'customer_phone', 'customer_country_code',
                'subtotal', 'shipping_cost', 'tax', 'total', 'currency_code',
                'status', 'payment_method', 'payment_status', 'stripe_payment_intent',
                'shipping_method', 'notes', 'special_instructions',
                'payment_confirmed_by_customer', 'account_holder'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                Database::update('orders', $updateData, 'id = ?', [$id]);
            }

            // Update addresses if provided
            if (array_key_exists('shipping_address', $data)) {
                Database::delete('order_addresses', 'order_id = ? AND address_type = ?', [$id, 'shipping']);
                if (!empty($data['shipping_address'])) {
                    self::saveAddress($id, 'shipping', $data['shipping_address']);
                }
            }
            if (array_key_exists('billing_address', $data)) {
                Database::delete('order_addresses', 'order_id = ? AND address_type = ?', [$id, 'billing']);
                if (!empty($data['billing_address'])) {
                    self::saveAddress($id, 'billing', $data['billing_address']);
                }
            }

            // Update items if provided
            if (array_key_exists('items', $data)) {
                Database::delete('order_items', 'order_id = ?', [$id]);
                if (!empty($data['items'])) {
                    self::saveItems($id, $data['items']);
                }
            }

            return true;
        });
    }

    /**
     * Update order status
     */
    public static function updateStatus(string $id, string $status): bool {
        return Database::update('orders', ['status' => $status], 'id = ?', [$id]) > 0;
    }

    /**
     * Update payment status
     */
    public static function updatePaymentStatus(string $id, string $paymentStatus): bool {
        return Database::update('orders', ['payment_status' => $paymentStatus], 'id = ?', [$id]) > 0;
    }

    /**
     * Delete an order
     */
    public static function delete(string $id): bool {
        return Database::delete('orders', 'id = ?', [$id]) > 0;
    }

    /**
     * Get order statistics
     */
    public static function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN payment_status = 'completed' THEN total ELSE 0 END) as total_revenue,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payment,
                SUM(CASE WHEN payment_confirmed_by_customer = 1 AND payment_status = 'pending' THEN 1 ELSE 0 END) as payment_confirmed_by_customer
            FROM orders
        ";
        return Database::fetchOne($sql) ?? [];
    }

    /**
     * Hydrate an order with addresses and items
     */
    public static function hydrateOrder(array $order): array {
        $orderId = $order['id'];

        // Get addresses
        $order['shipping_address'] = self::getAddress($orderId, 'shipping');
        $order['billing_address'] = self::getAddress($orderId, 'billing');

        // Get items
        $order['items'] = self::getItems($orderId);

        // Convert numeric types
        $order['subtotal'] = (float)$order['subtotal'];
        $order['shipping_cost'] = (float)$order['shipping_cost'];
        $order['tax'] = (float)$order['tax'];
        $order['total'] = (float)$order['total'];
        $order['payment_confirmed_by_customer'] = (bool)$order['payment_confirmed_by_customer'];

        return $order;
    }

    /**
     * Get address for an order
     */
    private static function getAddress(string $orderId, string $type): ?array {
        $sql = "SELECT line1, line2, city, state, postcode, country FROM order_addresses WHERE order_id = ? AND address_type = ?";
        return Database::fetchOne($sql, [$orderId, $type]);
    }

    /**
     * Save address for an order
     */
    private static function saveAddress(string $orderId, string $type, array $address): void {
        Database::insert('order_addresses', [
            'order_id' => $orderId,
            'address_type' => $type,
            'line1' => $address['line1'] ?? null,
            'line2' => $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'postcode' => $address['postcode'] ?? null,
            'country' => $address['country'] ?? null,
        ]);
    }

    /**
     * Get items for an order
     */
    private static function getItems(string $orderId): array {
        $sql = "SELECT * FROM order_items WHERE order_id = ?";
        $items = Database::fetchAll($sql, [$orderId]);

        return array_map(function($item) {
            return [
                'product_id' => (int)$item['product_id'],
                'product_name' => $item['product_name'],
                'product_slug' => $item['product_slug'],
                'product_image' => $item['product_image'],
                'quantity' => (int)$item['quantity'],
                'price' => (float)$item['price'],
                'subtotal' => (float)$item['subtotal'],
                'size' => $item['size'],
                'color' => $item['color'],
            ];
        }, $items);
    }

    /**
     * Save items for an order
     */
    private static function saveItems(string $orderId, array $items): void {
        foreach ($items as $item) {
            // Handle both old format (product_id direct) and new format (product object)
            $productId = $item['product_id'] ?? ($item['product']['id'] ?? null);
            $productName = $item['product_name'] ?? ($item['product']['name'] ?? '');
            $productSlug = $item['product_slug'] ?? ($item['product']['slug'] ?? null);
            $productImage = $item['product_image'] ?? ($item['product']['image'] ?? null);

            Database::insert('order_items', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'product_name' => $productName,
                'product_slug' => $productSlug,
                'product_image' => $productImage,
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'subtotal' => $item['subtotal'] ?? ($item['price'] * ($item['quantity'] ?? 1)),
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
            ]);
        }
    }

    /**
     * Generate a unique order ID
     */
    private static function generateOrderId(): string {
        $year = date('Y');
        $random = mt_rand(100000, 999999);
        return "AMP{$year}{$random}";
    }

    /**
     * Check if order exists
     */
    public static function exists(string $id): bool {
        return Database::exists('orders', 'id = ?', [$id]);
    }
}
