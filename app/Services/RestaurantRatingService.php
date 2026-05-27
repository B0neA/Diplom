<?php

namespace App\Services;

class RestaurantRatingService
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

        $restaurantReviews = $this->supabase->get('restaurant_reviews', [
            'select' => 'restaurant_id,rating',
        ])->json() ?: [];

        foreach ($restaurantReviews as $row) {
            $id = (int) ($row['restaurant_id'] ?? 0);
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

    public function averageForRestaurant(int $restaurantId, ?array $map = null): float
    {
        $map ??= $this->aggregateRatings();
        if (!isset($map[$restaurantId]) || $map[$restaurantId]['count'] === 0) {
            return 0;
        }
        return round($map[$restaurantId]['sum'] / $map[$restaurantId]['count'], 1);
    }

    public function recalcAndPersist(int $restaurantId): float
    {
        $avg = $this->averageForRestaurant($restaurantId);
        $this->supabase->patch('restaurants', ['rating' => $avg], [
            'id' => 'eq.' . $restaurantId,
        ]);
        return $avg;
    }

    /** @param array<int, array<string, mixed>> $restaurants */
    public function attachComputedRatings(array $restaurants): array
    {
        $map = $this->aggregateRatings();
        foreach ($restaurants as &$r) {
            $id = (int) ($r['id'] ?? 0);
            $r['rating'] = $this->averageForRestaurant($id, $map);
            $r['reviews_count'] = $map[$id]['count'] ?? 0;
        }
        return $restaurants;
    }
}
