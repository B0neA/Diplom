<?php

namespace App\Services;

class ReviewGroupingService
{
    public function group(
        array $reviews,
        array $restaurantReviews,
        array $products,
        array $restaurants
    ): array {
        $restaurants = RestaurantTitleHelper::normalizeRows($restaurants);
        $productMap = collect($products)->keyBy(fn ($p) => (int) ($p['id'] ?? 0));
        $restaurantMap = collect($restaurants)->keyBy(fn ($r) => (int) ($r['id'] ?? 0));

        $titleFor = function (int $restaurantId) use ($restaurantMap): string {
            return RestaurantTitleHelper::displayName($restaurantMap->get($restaurantId), $restaurantId);
        };

        $grouped = [];

        foreach ($reviews as $review) {
            $product = $productMap->get((int) ($review['product_id'] ?? 0));
            $restaurantId = (int) ($product['restaurant_id'] ?? 0);
            $dishName = $product['name'] ?? 'Удалённое блюдо';

            if (!isset($grouped[$restaurantId])) {
                $grouped[$restaurantId] = [
                    'restaurant_id' => $restaurantId,
                    'restaurant_title' => $titleFor($restaurantId),
                    'dishes' => [],
                ];
            }

            $productId = $review['product_id'] ?? 0;
            if (!isset($grouped[$restaurantId]['dishes'][$productId])) {
                $grouped[$restaurantId]['dishes'][$productId] = [
                    'product_id' => $productId,
                    'product_name' => $dishName,
                    'review_type' => 'dish',
                    'reviews' => [],
                ];
            }

            $review['source'] = 'dish';
            $grouped[$restaurantId]['dishes'][$productId]['reviews'][] = $review;
        }

        foreach ($restaurantReviews as $review) {
            $restaurantId = (int) ($review['restaurant_id'] ?? 0);
            if (!isset($grouped[$restaurantId])) {
                $grouped[$restaurantId] = [
                    'restaurant_id' => $restaurantId,
                    'restaurant_title' => $titleFor($restaurantId),
                    'dishes' => [],
                ];
            }

            $specialKey = 'restaurant';
            if (!isset($grouped[$restaurantId]['dishes'][$specialKey])) {
                $grouped[$restaurantId]['dishes'][$specialKey] = [
                    'product_id' => null,
                    'product_name' => 'Отзывы о ресторане',
                    'review_type' => 'restaurant',
                    'reviews' => [],
                ];
            }
            $review['source'] = 'restaurant';
            $grouped[$restaurantId]['dishes'][$specialKey]['reviews'][] = $review;
        }

        $result = array_values(array_map(function ($restaurant) {
            $restaurant['dishes'] = array_values($restaurant['dishes']);

            return $restaurant;
        }, $grouped));

        foreach ($result as &$group) {
            $id = (int) ($group['restaurant_id'] ?? 0);
            $group['restaurant_title'] = $titleFor($id);
        }
        unset($group);

        usort($result, fn ($a, $b) => strcmp($a['restaurant_title'], $b['restaurant_title']));

        return $result;
    }
}
