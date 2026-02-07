<?php
/**
 * Rating Repository
 * Data access layer for product ratings/reviews using MySQL
 */

require_once __DIR__ . '/../database.php';

class RatingRepository {

    /**
     * Get all ratings
     */
    public static function getAll(): array {
        $sql = "SELECT * FROM ratings ORDER BY date DESC, id DESC";
        return Database::fetchAll($sql);
    }

    /**
     * Get ratings for a specific product
     */
    public static function getByProductId(int $productId, bool $approvedOnly = true): array {
        $sql = "SELECT * FROM ratings WHERE product_id = ?";
        if ($approvedOnly) {
            $sql .= " AND approved = 1";
        }
        $sql .= " ORDER BY date DESC, id DESC";

        return Database::fetchAll($sql, [$productId]);
    }

    /**
     * Get rating by ID
     */
    public static function getById(int $id): ?array {
        $sql = "SELECT * FROM ratings WHERE id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Get rating statistics for a product
     */
    public static function getProductStats(int $productId): array {
        $sql = "
            SELECT
                COUNT(*) as count,
                COALESCE(ROUND(AVG(rating), 1), 0) as average,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM ratings
            WHERE product_id = ? AND approved = 1
        ";

        $stats = Database::fetchOne($sql, [$productId]);

        return [
            'count' => (int)$stats['count'],
            'average' => (float)$stats['average'],
            'distribution' => [
                5 => (int)$stats['five_star'],
                4 => (int)$stats['four_star'],
                3 => (int)$stats['three_star'],
                2 => (int)$stats['two_star'],
                1 => (int)$stats['one_star'],
            ]
        ];
    }

    /**
     * Get rating statistics for all products
     */
    public static function getAllProductStats(): array {
        $sql = "
            SELECT
                product_id,
                COUNT(*) as count,
                ROUND(AVG(rating), 1) as average,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM ratings
            WHERE approved = 1
            GROUP BY product_id
        ";

        $results = Database::fetchAll($sql);
        $stats = [];

        foreach ($results as $row) {
            $stats[(int)$row['product_id']] = [
                'count' => (int)$row['count'],
                'average' => (float)$row['average'],
                'distribution' => [
                    5 => (int)$row['five_star'],
                    4 => (int)$row['four_star'],
                    3 => (int)$row['three_star'],
                    2 => (int)$row['two_star'],
                    1 => (int)$row['one_star'],
                ]
            ];
        }

        return $stats;
    }

    /**
     * Add a new rating
     */
    public static function create(array $data): int {
        $insertData = [
            'product_id' => $data['product_id'],
            'rating' => $data['rating'],
            'review' => $data['review'] ?? '',
            'reviewer_name' => $data['reviewer_name'],
            'reviewer_email' => $data['reviewer_email'],
            'date' => $data['date'] ?? date('Y-m-d'),
            'verified_purchase' => $data['verified_purchase'] ?? 0,
            'approved' => $data['approved'] ?? 1,
        ];

        return (int)Database::insert('ratings', $insertData);
    }

    /**
     * Update a rating
     */
    public static function update(int $id, array $data): bool {
        $updateData = [];
        $allowedFields = ['rating', 'review', 'reviewer_name', 'reviewer_email', 'verified_purchase', 'approved'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update('ratings', $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete a rating
     */
    public static function delete(int $id): bool {
        return Database::delete('ratings', 'id = ?', [$id]) > 0;
    }

    /**
     * Approve a rating
     */
    public static function approve(int $id): bool {
        return Database::update('ratings', ['approved' => 1], 'id = ?', [$id]) > 0;
    }

    /**
     * Reject/unapprove a rating
     */
    public static function reject(int $id): bool {
        return Database::update('ratings', ['approved' => 0], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark rating as verified purchase
     */
    public static function markVerified(int $id): bool {
        return Database::update('ratings', ['verified_purchase' => 1], 'id = ?', [$id]) > 0;
    }

    /**
     * Get pending (unapproved) ratings
     */
    public static function getPending(): array {
        $sql = "SELECT r.*, p.name as product_name FROM ratings r
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.approved = 0
                ORDER BY r.date DESC";
        return Database::fetchAll($sql);
    }

    /**
     * Count ratings for a product
     */
    public static function countByProduct(int $productId): int {
        $sql = "SELECT COUNT(*) FROM ratings WHERE product_id = ? AND approved = 1";
        return (int)Database::fetchColumn($sql, [$productId]);
    }

    /**
     * Get average rating for a product
     */
    public static function getAverageRating(int $productId): float {
        $sql = "SELECT COALESCE(AVG(rating), 0) FROM ratings WHERE product_id = ? AND approved = 1";
        return (float)Database::fetchColumn($sql, [$productId]);
    }

    /**
     * Check if email has already reviewed a product
     */
    public static function hasReviewed(int $productId, string $email): bool {
        $sql = "SELECT 1 FROM ratings WHERE product_id = ? AND reviewer_email = ? LIMIT 1";
        return Database::fetchOne($sql, [$productId, $email]) !== null;
    }

    /**
     * Get recent ratings with product info
     */
    public static function getRecent(int $limit = 10): array {
        $sql = "SELECT r.*, p.name as product_name, p.slug as product_slug
                FROM ratings r
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.approved = 1
                ORDER BY r.date DESC, r.id DESC
                LIMIT ?";
        return Database::fetchAll($sql, [$limit]);
    }
}
