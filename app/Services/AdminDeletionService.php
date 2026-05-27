<?php

namespace App\Services;

use Illuminate\Support\Str;

class AdminDeletionService
{
    public function __construct(protected SupabaseService $supabase)
    {
    }

    /** @return array<int, array<string, mixed>> */
    protected function normalizeList(mixed $data): array
    {
        if (!is_array($data) || isset($data['message']) || isset($data['code'])) {
            return [];
        }
        if ($data === []) {
            return [];
        }
        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        return is_array($data) ? [$data] : [];
    }

    protected function patchFailed($response, string $context): ?array
    {
        if ($response->successful()) {
            return null;
        }

        $body = $response->json();
        $message = is_array($body) ? ($body['message'] ?? $body['hint'] ?? null) : null;

        return [
            'error' => $message
                ? "Не удалось обновить {$context}: {$message}"
                : "Не удалось обновить {$context} (HTTP {$response->status()})",
            'status' => $response->status() >= 400 ? $response->status() : 500,
        ];
    }

    public function activeOnly(): array
    {
        return ['deleted_at' => 'is.null'];
    }

    public function archivedOnly(): array
    {
        return ['deleted_at' => 'not.is.null'];
    }

    public function softDeleteRestaurant(int $id): array
    {
        $restaurant = $this->supabase->get('restaurants', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json()[0] ?? null;

        if (!$restaurant) {
            return ['error' => 'Ресторан не найден', 'status' => 404];
        }

        $now = now()->toIso8601String();
        $batchId = (string) Str::uuid();

        $patchRestaurant = $this->supabase->patch('restaurants', [
            'deleted_at' => $now,
            'deletion_batch_id' => $batchId,
        ], ['id' => 'eq.' . $id]);
        if ($err = $this->patchFailed($patchRestaurant, 'ресторан')) {
            return $err;
        }

        $products = $this->normalizeList($this->supabase->get('products', array_merge([
            'restaurant_id' => 'eq.' . $id,
            'select' => 'id',
        ], $this->activeOnly()))->json());

        if ($products) {
            $patchProducts = $this->supabase->patch('products', [
                'deleted_at' => $now,
                'deletion_batch_id' => $batchId,
            ], array_merge([
                'restaurant_id' => 'eq.' . $id,
            ], $this->activeOnly()));
            if ($err = $this->patchFailed($patchProducts, 'блюда ресторана')) {
                return $err;
            }
        }

        $title = trim((string) ($restaurant['title'] ?? $restaurant['name'] ?? ''));

        return [
            'status' => 200,
            'entity' => [
                'type' => 'restaurant',
                'id' => $id,
                'title' => $title ?: "Ресторан #{$id}",
                'products_count' => count($products),
                'deleted_at' => $now,
            ],
            'deleted_at' => $now,
        ];
    }

    public function restoreRestaurant(int $id): array
    {
        $restaurant = $this->supabase->get('restaurants', [
            'id' => 'eq.' . $id,
            'select' => '*',
        ])->json()[0] ?? null;

        if (!$restaurant || empty($restaurant['deleted_at'])) {
            return ['error' => 'Ресторан не найден в архиве', 'status' => 404];
        }

        $batchId = $restaurant['deletion_batch_id'] ?? null;

        $archivedProducts = $this->normalizeList($this->supabase->get('products', array_merge([
            'restaurant_id' => 'eq.' . $id,
            'select' => 'id',
        ], $this->archivedOnly()))->json());

        if ($archivedProducts === [] && $batchId) {
            $archivedProducts = $this->normalizeList($this->supabase->get('products', array_merge([
                'deletion_batch_id' => 'eq.' . $batchId,
                'select' => 'id',
            ], $this->archivedOnly()))->json());
        }

        $patchRestaurant = $this->supabase->patch('restaurants', [
            'deleted_at' => null,
            'deletion_batch_id' => null,
        ], ['id' => 'eq.' . $id]);
        if ($err = $this->patchFailed($patchRestaurant, 'ресторан')) {
            return $err;
        }

        $patchByRestaurant = $this->supabase->patch('products', [
            'deleted_at' => null,
            'deletion_batch_id' => null,
        ], array_merge([
            'restaurant_id' => 'eq.' . $id,
        ], $this->archivedOnly()));
        if ($err = $this->patchFailed($patchByRestaurant, 'блюда ресторана')) {
            return $err;
        }

        if ($batchId) {
            $patchByBatch = $this->supabase->patch('products', [
                'deleted_at' => null,
                'deletion_batch_id' => null,
            ], array_merge([
                'deletion_batch_id' => 'eq.' . $batchId,
            ], $this->archivedOnly()));
            if ($err = $this->patchFailed($patchByBatch, 'блюда по пакету удаления')) {
                return $err;
            }
        }

        foreach ($archivedProducts as $row) {
            $productId = (int) ($row['id'] ?? 0);
            if ($productId > 0) {
                app(ProductRatingService::class)->recalcAndPersist($productId);
            }
        }

        app(RestaurantRatingService::class)->recalcAndPersist($id);

        return [
            'status' => 200,
            'restored' => true,
            'products_restored' => count($archivedProducts),
        ];
    }

    public function softDeleteProduct(int $id): array
    {
        $product = $this->supabase->get('products', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json()[0] ?? null;

        if (!$product) {
            return ['error' => 'Товар не найден', 'status' => 404];
        }

        $now = now()->toIso8601String();
        $batchId = (string) Str::uuid();

        $patchProduct = $this->supabase->patch('products', [
            'deleted_at' => $now,
            'deletion_batch_id' => $batchId,
        ], ['id' => 'eq.' . $id]);
        if ($err = $this->patchFailed($patchProduct, 'товар')) {
            return $err;
        }

        return [
            'status' => 200,
            'entity' => [
                'type' => 'product',
                'id' => $id,
                'title' => $product['name'] ?? "Товар #{$id}",
                'deleted_at' => $now,
            ],
            'deleted_at' => $now,
        ];
    }

    public function restoreProduct(int $id): array
    {
        $product = $this->supabase->get('products', [
            'id' => 'eq.' . $id,
            'select' => '*',
        ])->json()[0] ?? null;

        if (!$product || empty($product['deleted_at'])) {
            return ['error' => 'Товар не найден в архиве', 'status' => 404];
        }

        $this->supabase->patch('products', [
            'deleted_at' => null,
            'deletion_batch_id' => null,
        ], ['id' => 'eq.' . $id]);

        app(ProductRatingService::class)->recalcAndPersist($id);

        $restaurantId = (int) ($product['restaurant_id'] ?? 0);
        if ($restaurantId > 0) {
            app(RestaurantRatingService::class)->recalcAndPersist($restaurantId);
        }

        return ['status' => 200, 'restored' => true];
    }

    /**
     * Строки с заполненным deleted_at. Сначала фильтр PostgREST, иначе выборка всех и фильтр в PHP.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchSoftDeletedRows(string $table, string $select): array
    {
        $withFilter = $this->normalizeList($this->supabase->get($table, array_merge([
            'select' => $select,
            'order' => 'deleted_at.desc',
        ], $this->archivedOnly()))->json());

        if ($withFilter !== []) {
            return array_values(array_filter($withFilter, fn ($row) => !empty($row['deleted_at'])));
        }

        $all = $this->normalizeList($this->supabase->get($table, [
            'select' => $select,
            'order' => 'deleted_at.desc',
        ])->json());

        return array_values(array_filter($all, fn ($row) => !empty($row['deleted_at'])));
    }

    /** @return array<int, array<string, mixed>> */
    public function archiveItems(): array
    {
        $restaurants = $this->fetchSoftDeletedRows('restaurants', 'id,title,name,deleted_at,deletion_batch_id');
        $products = $this->fetchSoftDeletedRows('products', 'id,name,restaurant_id,deleted_at,deletion_batch_id');

        $items = [];

        foreach ($restaurants as $row) {
            if (empty($row['deleted_at'])) {
                continue;
            }
            $id = (int) $row['id'];
            $title = trim((string) ($row['title'] ?? $row['name'] ?? ''));
            $batchId = $row['deletion_batch_id'] ?? null;
            $linkedProducts = $batchId
                ? count(array_filter($products, fn ($p) => ($p['deletion_batch_id'] ?? null) === $batchId))
                : 0;

            $items[] = [
                'type' => 'restaurant',
                'id' => $id,
                'title' => $title ?: "Ресторан #{$id}",
                'deleted_at' => $row['deleted_at'],
                'products_count' => $linkedProducts,
            ];
        }

        foreach ($products as $row) {
            if (empty($row['deleted_at'])) {
                continue;
            }
            $batchId = $row['deletion_batch_id'] ?? null;
            $restaurantId = (int) ($row['restaurant_id'] ?? 0);
            $restaurantDeleted = collect($restaurants)->first(
                fn ($r) => (int) ($r['id'] ?? 0) === $restaurantId && !empty($r['deleted_at'])
            );
            if ($restaurantDeleted && $batchId && ($restaurantDeleted['deletion_batch_id'] ?? null) === $batchId) {
                continue;
            }

            $id = (int) $row['id'];
            $items[] = [
                'type' => 'product',
                'id' => $id,
                'title' => $row['name'] ?? "Товар #{$id}",
                'deleted_at' => $row['deleted_at'],
                'restaurant_id' => $restaurantId > 0 ? $restaurantId : null,
            ];
        }

        $orders = $this->fetchSoftDeletedRows('orders', 'id,customer_name,total_amount,deleted_at');
        foreach ($orders as $row) {
            if (empty($row['deleted_at'])) {
                continue;
            }
            $id = (int) $row['id'];
            $name = trim((string) ($row['customer_name'] ?? ''));
            $items[] = [
                'type' => 'order',
                'id' => $id,
                'title' => $name ? "Заказ #{$id} — {$name}" : "Заказ #{$id}",
                'deleted_at' => $row['deleted_at'],
                'total_amount' => $row['total_amount'] ?? null,
            ];
        }

        return $items;
    }

    public function softDeleteOrder(int $id): array
    {
        $order = $this->supabase->get('orders', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json()[0] ?? null;

        if (!$order) {
            return ['error' => 'Заказ не найден', 'status' => 404];
        }

        $now = now()->toIso8601String();
        $patch = $this->supabase->patch('orders', ['deleted_at' => $now], ['id' => 'eq.' . $id]);
        if ($err = $this->patchFailed($patch, 'заказ')) {
            return $err;
        }

        $title = trim((string) ($order['customer_name'] ?? ''));

        return [
            'status' => 200,
            'entity' => [
                'type' => 'order',
                'id' => $id,
                'title' => $title ? "Заказ #{$id} — {$title}" : "Заказ #{$id}",
                'deleted_at' => $now,
            ],
            'deleted_at' => $now,
        ];
    }

    public function restoreOrder(int $id): array
    {
        $order = $this->supabase->get('orders', [
            'id' => 'eq.' . $id,
            'select' => '*',
        ])->json()[0] ?? null;

        if (!$order || empty($order['deleted_at'])) {
            return ['error' => 'Заказ не найден в архиве', 'status' => 404];
        }

        $patch = $this->supabase->patch('orders', ['deleted_at' => null], ['id' => 'eq.' . $id]);
        if ($err = $this->patchFailed($patch, 'заказ')) {
            return $err;
        }

        return ['status' => 200, 'restored' => true];
    }

    /** @deprecated Используйте archiveItems() */
    public function restorableItems(): array
    {
        return $this->archiveItems();
    }
}
