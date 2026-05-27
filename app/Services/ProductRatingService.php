<?php

namespace App\Services;

class ProductRatingService
{
    /** @var array<int, array{sum: float, count: int}>|null */
    protected ?array $ratingsCache = null;

    public function __construct(protected SupabaseService $supabase)
    {
    }

    /** @return array<int, array{sum: float, count: int}> */
    public function aggregateRatings(): array
    {
        if ($this->ratingsCache !== null) {
            return $this->ratingsCache;
        }

        $map = [];
        $rows = $this->supabase->get('reviews', [
            'select' => 'product_id,rating',
        ])->json() ?: [];

        foreach ($rows as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = ['sum' => 0, 'count' => 0];
            }
            $map[$id]['sum'] += (float) ($row['rating'] ?? 0);
            $map[$id]['count']++;
        }

        return $this->ratingsCache = $map;
    }

    public function averageForProduct(int $productId, ?array $map = null): float
    {
        $map ??= $this->aggregateRatings();
        if (!isset($map[$productId]) || $map[$productId]['count'] === 0) {
            return 0;
        }

        return round($map[$productId]['sum'] / $map[$productId]['count'], 1);
    }

    public function recalcAndPersist(int $productId): float
    {
        $avg = $this->averageForProduct($productId);
        $this->supabase->patch('products', ['rating' => $avg], [
            'id' => 'eq.' . $productId,
        ]);

        return $avg;
    }

    /** @param array<int, array<string, mixed>> $products */
    public function attachToProducts(array $products): array
    {
        $map = $this->aggregateRatings();
        foreach ($products as &$p) {
            $id = (int) ($p['id'] ?? 0);
            $p['rating'] = $this->averageForProduct($id, $map);
            $p['reviews_count'] = $map[$id]['count'] ?? 0;
        }

        return $products;
    }
}
