<?php
/**
 * Category repository for MySQL backend.
 * Stub implementation until MySQL queries are implemented.
 */
class CategoryRepository
{
    public static function getAll(): array
    {
        return [];
    }

    public static function getBySlug(string $slug)
    {
        return null;
    }

    public static function getById(int $id)
    {
        return null;
    }

    public static function getAllTotalProductCounts(): array
    {
        return [];
    }

    public static function getAllIncludingInactive(): array
    {
        return [];
    }

    public static function getTree(bool $includeInactive = false): array
    {
        return [];
    }

    public static function getSubcategories(int $parentId, bool $includeInactive = false): array
    {
        return [];
    }

    public static function getFeatured(): array
    {
        return [];
    }
}
