<?php
/**
 * Category Repository
 * Data access layer for categories using MySQL
 */

require_once __DIR__ . '/../database.php';

class CategoryRepository {

    /**
     * Get all active categories
     */
    public static function getAll(): array {
        $sql = "SELECT * FROM categories WHERE active = 1 ORDER BY sort_order, name";
        return Database::fetchAll($sql);
    }

    /**
     * Get all categories including inactive (for admin)
     */
    public static function getAllIncludingInactive(): array {
        $sql = "SELECT * FROM categories ORDER BY sort_order, name";
        return Database::fetchAll($sql);
    }

    /**
     * Get category by ID
     */
    public static function getById(int $id): ?array {
        $sql = "SELECT * FROM categories WHERE id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Get category by slug
     */
    public static function getBySlug(string $slug): ?array {
        $sql = "SELECT * FROM categories WHERE slug = ? AND active = 1";
        return Database::fetchOne($sql, [$slug]);
    }

    /**
     * Get direct subcategories of a parent
     */
    public static function getSubcategories(int $parentId, bool $includeInactive = false): array {
        $sql = "SELECT * FROM categories WHERE parent_id = ?";
        if (!$includeInactive) {
            $sql .= " AND active = 1";
        }
        $sql .= " ORDER BY sort_order, name";
        return Database::fetchAll($sql, [$parentId]);
    }

    /**
     * Get all descendant category IDs (recursive)
     */
    public static function getDescendantIds(int $parentId, bool $includeInactive = false): array {
        $ids = [];
        $children = self::getSubcategories($parentId, $includeInactive);

        foreach ($children as $child) {
            $ids[] = $child['id'];
            $childDescendants = self::getDescendantIds($child['id'], $includeInactive);
            $ids = array_merge($ids, $childDescendants);
        }

        return $ids;
    }

    /**
     * Get category tree (hierarchical structure)
     */
    public static function getTree(bool $includeInactive = false): array {
        $categories = $includeInactive ? self::getAllIncludingInactive() : self::getAll();
        return self::buildTree($categories);
    }

    /**
     * Build tree structure from flat array
     */
    private static function buildTree(array $categories, int $parentId = 0): array {
        $tree = [];
        foreach ($categories as $category) {
            $catParentId = (int)($category['parent_id'] ?? 0);
            if ($catParentId === $parentId) {
                $category['children'] = self::buildTree($categories, (int)$category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Get category breadcrumb path
     */
    public static function getBreadcrumb(int $categoryId): array {
        $breadcrumb = [];
        $categories = self::getAllIncludingInactive();

        $currentId = $categoryId;
        while ($currentId > 0) {
            foreach ($categories as $cat) {
                if ((int)$cat['id'] === $currentId) {
                    array_unshift($breadcrumb, $cat);
                    $currentId = (int)($cat['parent_id'] ?? 0);
                    break;
                }
            }
            if ($currentId === $categoryId) break; // Safety check for infinite loops
        }

        return $breadcrumb;
    }

    /**
     * Get featured categories
     */
    public static function getFeatured(): array {
        $sql = "SELECT * FROM categories WHERE active = 1 AND featured = 1 ORDER BY sort_order, name";
        return Database::fetchAll($sql);
    }

    /**
     * Get product count for a category (direct only)
     */
    public static function getProductCount(int $categoryId): int {
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = ? AND active = 1";
        return (int)Database::fetchColumn($sql, [$categoryId]);
    }

    /**
     * Get total product count including subcategories
     */
    public static function getTotalProductCount(int $categoryId): int {
        $categoryIds = array_merge([$categoryId], self::getDescendantIds($categoryId));
        $in = Database::buildInClause($categoryIds);

        $sql = "SELECT COUNT(*) FROM products WHERE category_id IN {$in['clause']} AND active = 1";
        return (int)Database::fetchColumn($sql, $in['params']);
    }

    /**
     * Get product counts for all categories
     */
    public static function getAllProductCounts(): array {
        $sql = "SELECT category_id, COUNT(*) as count FROM products WHERE active = 1 GROUP BY category_id";
        $results = Database::fetchAll($sql);

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['category_id']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Get total product counts for all categories including subcategories
     */
    public static function getAllTotalProductCounts(): array {
        $categories = self::getAllIncludingInactive();
        $directCounts = self::getAllProductCounts();
        $totalCounts = [];

        // Build parent-child map
        $childrenByParent = [];
        foreach ($categories as $cat) {
            $parentId = (int)($cat['parent_id'] ?? 0);
            if (!isset($childrenByParent[$parentId])) {
                $childrenByParent[$parentId] = [];
            }
            $childrenByParent[$parentId][] = (int)$cat['id'];
        }

        // Compute totals recursively
        $computed = [];
        $compute = function($categoryId) use (&$compute, &$totalCounts, &$childrenByParent, &$directCounts, &$computed) {
            if (isset($computed[$categoryId])) {
                return $totalCounts[$categoryId] ?? 0;
            }
            $computed[$categoryId] = true;

            $total = $directCounts[$categoryId] ?? 0;
            foreach (($childrenByParent[$categoryId] ?? []) as $childId) {
                $total += $compute($childId);
            }
            $totalCounts[$categoryId] = $total;
            return $total;
        };

        foreach ($categories as $cat) {
            $compute((int)$cat['id']);
        }

        return $totalCounts;
    }

    /**
     * Create a new category
     */
    public static function create(array $data): int {
        $insertData = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? 'categories/default.jpg',
            'parent_id' => $data['parent_id'] ?? 0,
            'active' => $data['active'] ?? 1,
            'featured' => $data['featured'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
        ];

        return (int)Database::insert('categories', $insertData);
    }

    /**
     * Update a category
     */
    public static function update(int $id, array $data): bool {
        $updateData = [];
        $allowedFields = ['name', 'slug', 'description', 'image', 'parent_id', 'active', 'featured', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update('categories', $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete a category
     */
    public static function delete(int $id): bool {
        return Database::delete('categories', 'id = ?', [$id]) > 0;
    }

    /**
     * Check if category has subcategories
     */
    public static function hasSubcategories(int $categoryId): bool {
        $sql = "SELECT 1 FROM categories WHERE parent_id = ? LIMIT 1";
        return Database::fetchOne($sql, [$categoryId]) !== null;
    }

    /**
     * Check if category has products
     */
    public static function hasProducts(int $categoryId): bool {
        $sql = "SELECT 1 FROM products WHERE category_id = ? LIMIT 1";
        return Database::fetchOne($sql, [$categoryId]) !== null;
    }

    /**
     * Check if slug exists
     */
    public static function slugExists(string $slug, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM categories WHERE slug = ?";
        $params = [$slug];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return Database::fetchOne($sql, $params) !== null;
    }

    /**
     * Get category path string (Parent > Child > Grandchild)
     */
    public static function getPath(int $categoryId, string $separator = ' > '): string {
        $breadcrumb = self::getBreadcrumb($categoryId);
        $names = array_map(fn($cat) => $cat['name'], $breadcrumb);
        return implode($separator, $names);
    }
}
