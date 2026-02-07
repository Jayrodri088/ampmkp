<?php
/**
 * JSON to MySQL Migration Script
 * Migrates data from JSON files to MySQL database
 *
 * Usage: php scripts/migrate_json_to_mysql.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to project root directory
chdir(__DIR__ . '/..');

// Include database class
require_once __DIR__ . '/../includes/database.php';

echo "===========================================\n";
echo "Angel Marketplace - JSON to MySQL Migration\n";
echo "===========================================\n\n";

// Test database connection
echo "Testing database connection...\n";
try {
    $pdo = Database::getInstance();
    echo "✓ Database connection successful!\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nMake sure you have:\n";
    echo "1. Created the database (run scripts/schema.sql in phpMyAdmin)\n";
    echo "2. Updated .env with correct database credentials\n";
    exit(1);
}

// Helper function to read JSON files
function readJsonFile($filename) {
    $filepath = __DIR__ . '/../data/' . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// Track migration statistics
$stats = [
    'categories' => 0,
    'products' => 0,
    'product_prices' => 0,
    'product_sizes' => 0,
    'product_colors' => 0,
    'product_features' => 0,
    'product_images' => 0,
    'orders' => 0,
    'order_addresses' => 0,
    'order_items' => 0,
    'ratings' => 0,
    'settings' => 0,
    'ads' => 0,
    'newsletter' => 0,
];

try {
    Database::beginTransaction();

    // ===========================================
    // Migrate Categories
    // ===========================================
    echo "Migrating categories...\n";
    $categories = readJsonFile('categories.json');

    foreach ($categories as $category) {
        Database::query(
            "INSERT INTO categories (id, name, slug, description, image, parent_id, active, featured, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $category['id'],
                $category['name'],
                $category['slug'],
                $category['description'] ?? '',
                $category['image'] ?? 'categories/default.jpg',
                $category['parent_id'] ?? 0,
                ($category['active'] ?? true) ? 1 : 0,
                ($category['featured'] ?? false) ? 1 : 0,
                0 // sort_order
            ]
        );
        $stats['categories']++;
    }
    echo "✓ Migrated {$stats['categories']} categories\n";

    // ===========================================
    // Migrate Products
    // ===========================================
    echo "Migrating products...\n";
    $products = readJsonFile('products.json');

    foreach ($products as $product) {
        // Insert main product
        Database::query(
            "INSERT INTO products (id, name, slug, price, category_id, description, image, stock, featured, active, has_sizes, has_colors, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $product['id'],
                $product['name'],
                $product['slug'],
                $product['price'] ?? 0,
                $product['category_id'],
                $product['description'] ?? '',
                $product['image'] ?? 'products/placeholder.jpg',
                $product['stock'] ?? 0,
                ($product['featured'] ?? false) ? 1 : 0,
                ($product['active'] ?? true) ? 1 : 0,
                ($product['has_sizes'] ?? false) ? 1 : 0,
                ($product['has_colors'] ?? false) ? 1 : 0,
                0 // sort_order
            ]
        );
        $stats['products']++;

        $productId = $product['id'];

        // Insert prices
        if (!empty($product['prices']) && is_array($product['prices'])) {
            foreach ($product['prices'] as $currency => $price) {
                Database::query(
                    "INSERT INTO product_prices (product_id, currency_code, price) VALUES (?, ?, ?)",
                    [$productId, $currency, $price]
                );
                $stats['product_prices']++;
            }
        }

        // Insert sizes
        if (!empty($product['available_sizes']) && is_array($product['available_sizes'])) {
            foreach ($product['available_sizes'] as $order => $size) {
                Database::query(
                    "INSERT INTO product_sizes (product_id, size, sort_order) VALUES (?, ?, ?)",
                    [$productId, $size, $order]
                );
                $stats['product_sizes']++;
            }
        }

        // Insert colors
        if (!empty($product['available_colors']) && is_array($product['available_colors'])) {
            foreach ($product['available_colors'] as $order => $color) {
                Database::query(
                    "INSERT INTO product_colors (product_id, color, sort_order) VALUES (?, ?, ?)",
                    [$productId, $color, $order]
                );
                $stats['product_colors']++;
            }
        }

        // Insert features
        if (!empty($product['features']) && is_array($product['features'])) {
            foreach ($product['features'] as $order => $feature) {
                if (!empty($feature['name']) && !empty($feature['value'])) {
                    Database::query(
                        "INSERT INTO product_features (product_id, feature_name, feature_value, sort_order) VALUES (?, ?, ?, ?)",
                        [$productId, $feature['name'], $feature['value'], $order]
                    );
                    $stats['product_features']++;
                }
            }
        }

        // Insert additional images
        if (!empty($product['images']) && is_array($product['images'])) {
            foreach ($product['images'] as $order => $imagePath) {
                Database::query(
                    "INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)",
                    [$productId, $imagePath, $order]
                );
                $stats['product_images']++;
            }
        }
    }
    echo "✓ Migrated {$stats['products']} products\n";
    echo "  - {$stats['product_prices']} price entries\n";
    echo "  - {$stats['product_sizes']} sizes\n";
    echo "  - {$stats['product_colors']} colors\n";
    echo "  - {$stats['product_features']} features\n";
    echo "  - {$stats['product_images']} additional images\n";

    // ===========================================
    // Migrate Orders
    // ===========================================
    echo "Migrating orders...\n";
    $orders = readJsonFile('orders.json');

    foreach ($orders as $order) {
        // Handle different order formats
        $customerName = $order['customer_name'] ?? '';
        $customerEmail = $order['customer_email'] ?? '';
        $customerPhone = $order['customer_phone'] ?? '';

        // Check for nested customer object (newer format)
        if (isset($order['customer']) && is_array($order['customer'])) {
            $customer = $order['customer'];
            $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $customerEmail = $customer['email'] ?? $customerEmail;
            $customerPhone = $customer['phone'] ?? $customerPhone;
        }

        // Insert main order
        Database::query(
            "INSERT INTO orders (id, customer_name, customer_email, customer_phone, subtotal, shipping_cost, tax, total, currency_code, status, payment_method, payment_status, stripe_payment_intent, shipping_method, notes, special_instructions, payment_confirmed_by_customer, account_holder, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $order['id'],
                $customerName,
                $customerEmail,
                $customerPhone,
                $order['subtotal'] ?? 0,
                $order['shipping_cost'] ?? 0,
                $order['tax'] ?? 0,
                $order['total'] ?? 0,
                $order['currency_code'] ?? ($order['customer']['selected_currency'] ?? 'GBP'),
                $order['status'] ?? 'pending',
                $order['payment_method'] ?? ($order['customer']['payment_method'] ?? null),
                $order['payment_status'] ?? 'pending',
                $order['stripe_payment_intent'] ?? null,
                $order['shipping_method'] ?? 'delivery',
                $order['notes'] ?? null,
                $order['special_instructions'] ?? ($order['customer']['special_instructions'] ?? null),
                ($order['payment_confirmed_by_customer'] ?? false) ? 1 : 0,
                $order['account_holder'] ?? ($order['customer']['account_holder'] ?? null),
                $order['date'] ?? date('Y-m-d H:i:s')
            ]
        );
        $stats['orders']++;

        $orderId = $order['id'];

        // Insert shipping address
        $shippingAddress = $order['shipping_address'] ?? null;
        if ($shippingAddress && is_array($shippingAddress)) {
            Database::query(
                "INSERT INTO order_addresses (order_id, address_type, line1, line2, city, postcode, country)
                 VALUES (?, 'shipping', ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $shippingAddress['line1'] ?? '',
                    $shippingAddress['line2'] ?? '',
                    $shippingAddress['city'] ?? '',
                    $shippingAddress['postcode'] ?? '',
                    $shippingAddress['country'] ?? ''
                ]
            );
            $stats['order_addresses']++;
        }

        // Insert billing address
        $billingAddress = $order['billing_address'] ?? null;
        if ($billingAddress && is_array($billingAddress)) {
            Database::query(
                "INSERT INTO order_addresses (order_id, address_type, line1, line2, city, postcode, country)
                 VALUES (?, 'billing', ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $billingAddress['line1'] ?? '',
                    $billingAddress['line2'] ?? '',
                    $billingAddress['city'] ?? '',
                    $billingAddress['postcode'] ?? '',
                    $billingAddress['country'] ?? ''
                ]
            );
            $stats['order_addresses']++;
        }

        // Insert order items
        if (!empty($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                // Handle both old format (product_id direct) and new format (product object)
                $productId = $item['product_id'] ?? ($item['product']['id'] ?? null);
                $productName = $item['product_name'] ?? ($item['product']['name'] ?? '');
                $productSlug = $item['product_slug'] ?? ($item['product']['slug'] ?? null);
                $productImage = $item['product_image'] ?? ($item['product']['image'] ?? null);

                Database::query(
                    "INSERT INTO order_items (order_id, product_id, product_name, product_slug, product_image, quantity, price, subtotal, size, color)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $productId,
                        $productName,
                        $productSlug,
                        $productImage,
                        $item['quantity'] ?? 1,
                        $item['price'] ?? 0,
                        $item['subtotal'] ?? ($item['price'] * ($item['quantity'] ?? 1)),
                        $item['size'] ?? null,
                        $item['color'] ?? null
                    ]
                );
                $stats['order_items']++;
            }
        }
    }
    echo "✓ Migrated {$stats['orders']} orders\n";
    echo "  - {$stats['order_addresses']} addresses\n";
    echo "  - {$stats['order_items']} order items\n";

    // ===========================================
    // Migrate Ratings
    // ===========================================
    echo "Migrating ratings...\n";
    $ratings = readJsonFile('ratings.json');

    foreach ($ratings as $rating) {
        Database::query(
            "INSERT INTO ratings (id, product_id, rating, review, reviewer_name, reviewer_email, date, verified_purchase, approved)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $rating['id'],
                $rating['product_id'],
                $rating['rating'],
                $rating['review'] ?? '',
                $rating['reviewer_name'] ?? '',
                $rating['reviewer_email'] ?? '',
                $rating['date'] ?? date('Y-m-d'),
                ($rating['verified_purchase'] ?? false) ? 1 : 0,
                1 // approved by default
            ]
        );
        $stats['ratings']++;
    }
    echo "✓ Migrated {$stats['ratings']} ratings\n";

    // ===========================================
    // Migrate Settings
    // ===========================================
    echo "Migrating settings...\n";
    $settings = readJsonFile('settings.json');

    foreach ($settings as $key => $value) {
        Database::query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)",
            [$key, json_encode($value)]
        );
        $stats['settings']++;
    }
    echo "✓ Migrated {$stats['settings']} settings\n";

    // ===========================================
    // Migrate Ads
    // ===========================================
    echo "Migrating advertisements...\n";
    $ads = readJsonFile('ads.json');

    foreach ($ads as $ad) {
        Database::query(
            "INSERT INTO ads (id, title, description, image, destination_type, product_id, category_id, search_query, custom_url, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $ad['id'],
                $ad['title'] ?? '',
                $ad['description'] ?? '',
                $ad['image'] ?? '',
                $ad['destination_type'] ?? 'product',
                $ad['product_id'] ?? null,
                $ad['category_id'] ?? null,
                $ad['search_query'] ?? null,
                $ad['custom_url'] ?? null,
                ($ad['active'] ?? true) ? 1 : 0
            ]
        );
        $stats['ads']++;
    }
    echo "✓ Migrated {$stats['ads']} advertisements\n";

    // ===========================================
    // Migrate Newsletter Subscribers
    // ===========================================
    echo "Migrating newsletter subscribers...\n";
    $newsletter = readJsonFile('newsletter.json');

    foreach ($newsletter as $subscriber) {
        Database::query(
            "INSERT INTO newsletter_subscribers (id, email, subscribed_at, ip_address, user_agent, active, unsubscribed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $subscriber['id'],
                $subscriber['email'],
                $subscriber['subscribed_at'] ?? date('Y-m-d H:i:s'),
                $subscriber['ip_address'] ?? null,
                $subscriber['user_agent'] ?? null,
                ($subscriber['active'] ?? true) ? 1 : 0,
                $subscriber['unsubscribed_at'] ?? null
            ]
        );
        $stats['newsletter']++;
    }
    echo "✓ Migrated {$stats['newsletter']} newsletter subscribers\n";

    // Commit transaction
    Database::commit();

    echo "\n===========================================\n";
    echo "Migration completed successfully!\n";
    echo "===========================================\n\n";

    // Print summary
    echo "Summary:\n";
    echo "--------\n";
    $total = 0;
    foreach ($stats as $type => $count) {
        echo "  $type: $count\n";
        $total += $count;
    }
    echo "--------\n";
    echo "  Total records: $total\n\n";

    echo "Next steps:\n";
    echo "1. Update .env: Set STORAGE_BACKEND=mysql\n";
    echo "2. Test the application thoroughly\n";
    echo "3. Keep JSON files as backup until verified\n\n";

} catch (Exception $e) {
    Database::rollback();
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No data was modified.\n\n";
    echo "Error trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
