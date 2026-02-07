<?php
/**
 * Rating repository for MySQL backend.
 * Stub implementation until MySQL queries are implemented.
 */
class RatingRepository
{
    public static function getAll(): array
    {
        return [];
    }

    public static function getByProductId(int $productId): array
    {
        return [];
    }

    public static function getProductStats(int $productId): array
    {
        return ['average' => 0, 'count' => 0, 'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
    }

    public static function getAllProductStats(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $data
     * @return int|false Created rating ID or false
     */
    public static function create(array $data)
    {
        return false;
    }

    public static function getById(int $id)
    {
        return null;
    }
}
