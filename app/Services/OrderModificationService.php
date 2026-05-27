<?php

namespace App\Services;

use Carbon\Carbon;

class OrderModificationService
{
    public const WINDOW_MINUTES = 5;

    public const SERVICE_FEE = 50;

    public function parseItems(mixed $items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($items) ? $items : [];
    }

    public function restaurantIdsFromOrder(array $order): array
    {
        if (!empty($order['restaurant_id'])) {
            return [(int) $order['restaurant_id']];
        }

        $ids = [];
        foreach ($this->parseItems($order['items'] ?? []) as $item) {
            $rid = (int) ($item['restaurantId'] ?? $item['restaurant_id'] ?? 0);
            if ($rid > 0) {
                $ids[$rid] = true;
            }
        }

        return array_keys($ids);
    }

    public function isSingleRestaurantOrder(array $order): bool
    {
        return count($this->restaurantIdsFromOrder($order)) <= 1;
    }

    public function minutesSinceCreated(array $order): float
    {
        $created = $order['created_at'] ?? null;
        if (!$created) {
            return self::WINDOW_MINUTES + 1;
        }

        return Carbon::parse($created)->diffInSeconds(now()) / 60;
    }

    public function secondsRemaining(array $order): int
    {
        $remaining = (self::WINDOW_MINUTES * 60) - (int) Carbon::parse($order['created_at'] ?? now())->diffInSeconds(now());

        return max(0, $remaining);
    }

    /**
     * @return array{ok: bool, error?: string, seconds_remaining?: int}
     */
    public function assertCanModify(array $order): array
    {
        if (($order['status'] ?? '') === 'cancelled') {
            return ['ok' => false, 'error' => 'Заказ уже отменён'];
        }

        if (($order['status'] ?? '') !== 'new') {
            return ['ok' => false, 'error' => 'Ресторан уже начал готовить заказ — изменить или отменить нельзя'];
        }

        if ($this->minutesSinceCreated($order) > self::WINDOW_MINUTES) {
            return ['ok' => false, 'error' => 'Прошло более ' . self::WINDOW_MINUTES . ' минут с момента оформления'];
        }

        return [
            'ok' => true,
            'seconds_remaining' => $this->secondsRemaining($order),
        ];
    }

    public function calcItemsTotal(array $items): int
    {
        $sum = 0;
        foreach ($items as $item) {
            $qty = max(0, (int) ($item['quantity'] ?? 0));
            $price = (float) ($item['price'] ?? 0);
            $sum += (int) round($price * $qty);
        }

        return $sum;
    }

    public function calcOrderTotal(array $items): int
    {
        return $this->calcItemsTotal($items) + self::SERVICE_FEE;
    }

    /**
     * @return array{ok: bool, error?: string, items?: array}
     */
    public function normalizeItemsForUpdate(array $rawItems): array
    {
        $items = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty < 1) {
                continue;
            }
            $items[] = [
                'id' => $item['id'] ?? null,
                'name' => trim((string) ($item['name'] ?? 'Блюдо')),
                'price' => (float) ($item['price'] ?? 0),
                'quantity' => $qty,
                'restaurantId' => $item['restaurantId'] ?? $item['restaurant_id'] ?? null,
                'restaurant_id' => $item['restaurantId'] ?? $item['restaurant_id'] ?? null,
            ];
        }

        if ($items === []) {
            return ['ok' => false, 'error' => 'В заказе должен остаться хотя бы один товар'];
        }

        return ['ok' => true, 'items' => $items];
    }
}
