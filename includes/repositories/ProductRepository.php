<?php
/**
 * Product repository for MySQL backend.
 * Stub implementation: returns empty/null until MySQL queries are implemented.
 * Required by functions.php when STORAGE_BACKEND=mysql.
 */
class ProductRepository
{
    public static function getAll($categoryId = null, $featured = null, $limit = null): array
    {
        return [];
    }

    public static function getFeaturedByRating($limit = null): array
    {
        return [];
    }

    public static function getById(int $id)
    {
        return null;
    }

    public static function getBySlug(string $slug)
    {
        return null;
    }

    public static function search(string $query, $categoryId = null): array
    {
        return [];
    }

    public static function getLatest($limit = 6): array
    {
        return [];
    }

    public static function getByCategory(int $categoryId, $featured = null, $limit = null): array
    {
        return [];
    }
}
