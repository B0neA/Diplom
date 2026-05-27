<?php

namespace App\Services;

class RestaurantTitleHelper
{
    /** @param array<string, mixed>|null $row */
    public static function extractName(?array $row): string
    {
        if (!is_array($row)) {
            return '';
        }

        foreach (['title', 'name', 'restaurant_name'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public static function displayName(?array $row, int $restaurantId): string
    {
        $name = self::extractName($row);

        return $name !== '' ? $name : ($restaurantId > 0 ? "Ресторан #{$restaurantId}" : 'Без ресторана');
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }
            $name = self::extractName($row);
            if ($name !== '') {
                $row['title'] = $name;
            }
        }
        unset($row);

        return $rows;
    }

    public static function isPlaceholder(string $title): bool
    {
        return (bool) preg_match('/^Ресторан #\d+$/u', trim($title));
    }
}
