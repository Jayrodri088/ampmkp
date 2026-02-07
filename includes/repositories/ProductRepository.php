<?php
/**
 * Product Repository
 * Data access layer for products using MySQL
 */

require_once __DIR__ . '/../database.php';

class ProductRepository {

    /**
     * Get all active products with optional filters
     */
    public static function getAll(?int $categoryId = null, ?bool $featured = null, ?int $limit = null): array {
        $sql = "SELECT * FROM products WHERE active = 1";
        $params = [];

        if ($categoryId !== null) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        if ($featured !== null) {
            $sql .= " AND featured = ?";
            $params[] = $featured ? 1 : 0;
        }

        $sql .= " ORDER BY sort_order, id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        $products = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Get product by ID
     */
    public static function getById(int $id, bool $activeOnly = true): ?array {
        $sql = "SELECT * FROM products WHERE id = ?";
        if ($activeOnly) {
            $sql .= " AND active = 1";
        }

        $product = Database::fetchOne($sql, [$id]);
        return $product ? self::hydrateProduct($product) : null;
    }

    /**
     * Get product by slug
     */
    public static function getBySlug(string $slug, bool $activeOnly = true): ?array {
        $sql = "SELECT * FROM products WHERE slug = ?";
        if ($activeOnly) {
            $sql .= " AND active = 1";
        }

        $product = Database::fetchOne($sql, [$slug]);
        return $product ? self::hydrateProduct($product) : null;
    }

    /**
     * Search products by name or description
     */
    public static function search(string $query, ?int $categoryId = null): array {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $searchTerm = '%' . Database::escapeLike($query) . '%';
        $sql = "SELECT * FROM products WHERE active = 1 AND (name LIKE ? OR description LIKE ?)";
        $params = [$searchTerm, $searchTerm];

        if ($categoryId !== null) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY CASE WHEN name LIKE ? THEN 0 ELSE 1 END, sort_order, name";
        $params[] = $searchTerm;

        $products = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Get products by category including subcategories
     */
    public static function getByCategory(int $categoryId, ?bool $featured = null, ?int $limit = null): array {
        require_once __DIR__ . '/CategoryRepository.php';

        $categoryIds = array_merge([$categoryId], CategoryRepository::getDescendantIds($categoryId));
        $in = Database::buildInClause($categoryIds);

        $sql = "SELECT * FROM products WHERE active = 1 AND category_id IN {$in['clause']}";
        $params = $in['params'];

        if ($featured !== null) {
            $sql .= " AND featured = ?";
            $params[] = $featured ? 1 : 0;
        }

        $sql .= " ORDER BY sort_order, id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $products = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Get featured products sorted by rating count and average
     */
    public static function getFeaturedByRating(?int $limit = null): array {
        $sql = "
            SELECT p.*,
                   COALESCE(r.rating_count, 0) as rating_count,
                   COALESCE(r.average_rating, 0) as rating_average
            FROM products p
            LEFT JOIN v_product_ratings r ON p.id = r.product_id
            WHERE p.active = 1 AND p.featured = 1
            ORDER BY rating_count DESC, rating_average DESC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $products = Database::fetchAll($sql);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Get latest products
     */
    public static function getLatest(int $limit = 6): array {
        $sql = "SELECT * FROM products WHERE active = 1 ORDER BY id DESC LIMIT ?";
        $products = Database::fetchAll($sql, [$limit]);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Get all products including inactive (for admin)
     */
    public static function getAllForAdmin(?int $categoryId = null): array {
        $sql = "SELECT * FROM products";
        $params = [];

        if ($categoryId !== null) {
            $sql .= " WHERE category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY id DESC";

        $products = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrateProduct'], $products);
    }

    /**
     * Create a new product with all related data
     */
    public static function create(array $data): int {
        return Database::transaction(function() use ($data) {
            // Insert main product
            $productData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'price' => $data['price'] ?? 0,
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? '',
                'image' => $data['image'] ?? 'products/placeholder.jpg',
                'stock' => $data['stock'] ?? 0,
                'featured' => isset($data['featured']) ? ($data['featured'] ? 1 : 0) : 0,
                'active' => isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
                'has_sizes' => isset($data['has_sizes']) ? ($data['has_sizes'] ? 1 : 0) : 0,
                'has_colors' => isset($data['has_colors']) ? ($data['has_colors'] ? 1 : 0) : 0,
                'sort_order' => $data['sort_order'] ?? 0,
            ];

            $productId = (int)Database::insert('products', $productData);

            // Insert prices
            if (!empty($data['prices'])) {
                self::savePrices($productId, $data['prices']);
            }

            // Insert sizes
            if (!empty($data['available_sizes'])) {
                self::saveSizes($productId, $data['available_sizes']);
            }

            // Insert colors
            if (!empty($data['available_colors'])) {
                self::saveColors($productId, $data['available_colors']);
            }

            // Insert features
            if (!empty($data['features'])) {
                self::saveFeatures($productId, $data['features']);
            }

            // Insert additional images
            if (!empty($data['images'])) {
                self::saveImages($productId, $data['images']);
            }

            return $productId;
        });
    }

    /**
     * Update a product with all related data
     */
    public static function update(int $id, array $data): bool {
        return Database::transaction(function() use ($id, $data) {
            // Update main product
            $updateData = [];
            $allowedFields = ['name', 'slug', 'price', 'category_id', 'description', 'image',
                              'stock', 'featured', 'active', 'has_sizes', 'has_colors', 'sort_order'];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    if (in_array($field, ['featured', 'active', 'has_sizes', 'has_colors'])) {
                        $value = $value ? 1 : 0;
                    }
                    $updateData[$field] = $value;
                }
            }

            if (!empty($updateData)) {
                Database::update('products', $updateData, 'id = ?', [$id]);
            }

            // Update prices
            if (array_key_exists('prices', $data)) {
                Database::delete('product_prices', 'product_id = ?', [$id]);
                if (!empty($data['prices'])) {
                    self::savePrices($id, $data['prices']);
                }
            }

            // Update sizes
            if (array_key_exists('available_sizes', $data)) {
                Database::delete('product_sizes', 'product_id = ?', [$id]);
                if (!empty($data['available_sizes'])) {
                    self::saveSizes($id, $data['available_sizes']);
                }
            }

            // Update colors
            if (array_key_exists('available_colors', $data)) {
                Database::delete('product_colors', 'product_id = ?', [$id]);
                if (!empty($data['available_colors'])) {
                    self::saveColors($id, $data['available_colors']);
                }
            }

            // Update features
            if (array_key_exists('features', $data)) {
                Database::delete('product_features', 'product_id = ?', [$id]);
                if (!empty($data['features'])) {
                    self::saveFeatures($id, $data['features']);
                }
            }

            // Update additional images
            if (array_key_exists('images', $data)) {
                Database::delete('product_images', 'product_id = ?', [$id]);
                if (!empty($data['images'])) {
                    self::saveImages($id, $data['images']);
                }
            }

            return true;
        });
    }

    /**
     * Delete a product
     */
    public static function delete(int $id): bool {
        return Database::delete('products', 'id = ?', [$id]) > 0;
    }

    /**
     * Hydrate a product with all related data
     */
    public static function hydrateProduct(array $product): array {
        $productId = (int)$product['id'];

        // Get prices
        $product['prices'] = self::getPrices($productId);

        // Get sizes
        $product['available_sizes'] = self::getSizes($productId);

        // Get colors
        $product['available_colors'] = self::getColors($productId);

        // Get features
        $product['features'] = self::getFeatures($productId);

        // Get additional images
        $product['images'] = self::getImages($productId);

        // Convert numeric types
        $product['id'] = (int)$product['id'];
        $product['price'] = (float)$product['price'];
        $product['category_id'] = (int)$product['category_id'];
        $product['stock'] = (int)$product['stock'];
        $product['featured'] = (bool)$product['featured'];
        $product['active'] = (bool)$product['active'];
        $product['has_sizes'] = (bool)$product['has_sizes'];
        $product['has_colors'] = (bool)$product['has_colors'];

        return $product;
    }

    /**
     * Get prices for a product
     */
    private static function getPrices(int $productId): array {
        $sql = "SELECT currency_code, price FROM product_prices WHERE product_id = ?";
        $rows = Database::fetchAll($sql, [$productId]);

        $prices = [];
        foreach ($rows as $row) {
            $prices[$row['currency_code']] = (float)$row['price'];
        }
        return $prices;
    }

    /**
     * Save prices for a product
     */
    private static function savePrices(int $productId, array $prices): void {
        foreach ($prices as $currency => $price) {
            Database::insert('product_prices', [
                'product_id' => $productId,
                'currency_code' => $currency,
                'price' => $price,
            ]);
        }
    }

    /**
     * Get sizes for a product
     */
    private static function getSizes(int $productId): array {
        $sql = "SELECT size FROM product_sizes WHERE product_id = ? ORDER BY sort_order";
        $rows = Database::fetchAll($sql, [$productId]);
        return array_column($rows, 'size');
    }

    /**
     * Save sizes for a product
     */
    private static function saveSizes(int $productId, array $sizes): void {
        foreach ($sizes as $order => $size) {
            Database::insert('product_sizes', [
                'product_id' => $productId,
                'size' => $size,
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * Get colors for a product
     */
    private static function getColors(int $productId): array {
        $sql = "SELECT color FROM product_colors WHERE product_id = ? ORDER BY sort_order";
        $rows = Database::fetchAll($sql, [$productId]);
        return array_column($rows, 'color');
    }

    /**
     * Save colors for a product
     */
    private static function saveColors(int $productId, array $colors): void {
        foreach ($colors as $order => $color) {
            Database::insert('product_colors', [
                'product_id' => $productId,
                'color' => $color,
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * Get features for a product
     */
    private static function getFeatures(int $productId): array {
        $sql = "SELECT feature_name as name, feature_value as value FROM product_features WHERE product_id = ? ORDER BY sort_order";
        return Database::fetchAll($sql, [$productId]);
    }

    /**
     * Save features for a product
     */
    private static function saveFeatures(int $productId, array $features): void {
        foreach ($features as $order => $feature) {
            if (!empty($feature['name']) && !empty($feature['value'])) {
                Database::insert('product_features', [
                    'product_id' => $productId,
                    'feature_name' => $feature['name'],
                    'feature_value' => $feature['value'],
                    'sort_order' => $order,
                ]);
            }
        }
    }

    /**
     * Get additional images for a product
     */
    private static function getImages(int $productId): array {
        $sql = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order";
        $rows = Database::fetchAll($sql, [$productId]);
        return array_column($rows, 'image_path');
    }

    /**
     * Save additional images for a product
     */
    private static function saveImages(int $productId, array $images): void {
        foreach ($images as $order => $imagePath) {
            Database::insert('product_images', [
                'product_id' => $productId,
                'image_path' => $imagePath,
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * Check if slug exists
     */
    public static function slugExists(string $slug, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM products WHERE slug = ?";
        $params = [$slug];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return Database::fetchOne($sql, $params) !== null;
    }

    /**
     * Update stock for a product
     */
    public static function updateStock(int $productId, int $quantity): bool {
        return Database::update('products', ['stock' => $quantity], 'id = ?', [$productId]) > 0;
    }

    /**
     * Decrease stock for a product
     */
    public static function decreaseStock(int $productId, int $quantity): bool {
        $sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt = Database::query($sql, [$quantity, $productId, $quantity]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get products count by category
     */
    public static function countByCategory(int $categoryId): int {
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = ? AND active = 1";
        return (int)Database::fetchColumn($sql, [$categoryId]);
    }
}
