<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDeletionService;
use App\Services\ProductRatingService;
use App\Services\RestaurantRatingService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PublicApiController extends Controller
{
    protected function normalizeRestaurant(?array $row): ?array
    {
        if (!$row) {
            return null;
        }
        $row['title'] = trim((string) ($row['title'] ?? $row['name'] ?? ''));

        return $row;
    }

    protected function activeOnly(): array
    {
        return app(AdminDeletionService::class)->activeOnly();
    }

    public function siteSettings()
    {
        $supabase = app(SupabaseService::class);
        $row = $supabase->get('site_settings', [
            'id' => 'eq.1',
            'select' => '*',
        ])->json()[0] ?? [];

        $logo = trim((string) ($row['logo_icon'] ?? $row['logo_url'] ?? ''));
        if ($logo !== '') {
            $version = $row['updated_at'] ?? $row['created_at'] ?? $logo;
            $v = is_string($version) && strtotime($version)
                ? strtotime($version)
                : crc32($logo);
            $sep = str_contains($logo, '?') ? '&' : '?';
            $row['logo_icon'] = $logo . $sep . 'v=' . $v;
            $row['logo_url'] = $row['logo_icon'];
        }

        $row['favicon_icon'] = trim((string) ($row['favicon_icon'] ?? $row['favicon_url'] ?? ''));
        $row['redact_icon'] = trim((string) ($row['redact_icon'] ?? $row['redact_icon_url'] ?? ''));

        return response()->json($row);
    }

    public function restaurants(Request $request)
    {
        $supabase = app(SupabaseService::class);
        $ratingService = app(RestaurantRatingService::class);

        $rows = $supabase->get('restaurants', array_merge([
            'select' => '*',
            'order' => 'title.asc',
        ], $this->activeOnly()))->json() ?: [];

        foreach ($rows as &$row) {
            $row = $this->normalizeRestaurant($row);
        }

        $rows = $ratingService->attachComputedRatings($rows);

        return response()->json($rows);
    }

    public function restaurant(int $id)
    {
        $supabase = app(SupabaseService::class);
        $ratingService = app(RestaurantRatingService::class);

        $rows = $supabase->get('restaurants', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json();

        $restaurant = $this->normalizeRestaurant($rows[0] ?? null);
        if (!$restaurant || !empty($restaurant['deleted_at'])) {
            return response()->json(null, 404);
        }

        $map = $ratingService->aggregateRatings();
        $restaurant['rating'] = $ratingService->averageForRestaurant($id, $map);
        $restaurant['reviews_count'] = $map[$id]['count'] ?? 0;

        return response()->json($restaurant);
    }

    public function reviewsPageData(int $id)
    {
        $supabase = app(SupabaseService::class);
        $ratingService = app(RestaurantRatingService::class);
        $productRatingService = app(ProductRatingService::class);

        $rows = $supabase->get('restaurants', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json();

        $restaurant = $this->normalizeRestaurant($rows[0] ?? null);
        if (!$restaurant) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $map = $ratingService->aggregateRatings();
        $restaurant['rating'] = $ratingService->averageForRestaurant($id, $map);
        $restaurant['reviews_count'] = $map[$id]['count'] ?? 0;

        $restaurantReviews = $supabase->get('restaurant_reviews', [
            'restaurant_id' => 'eq.' . $id,
            'select' => '*',
            'order' => 'created_at.desc',
        ])->json() ?: [];

        $products = $supabase->get('products', array_merge([
            'restaurant_id' => 'eq.' . $id,
            'select' => 'id,name,category,price,img,rating',
            'order' => 'name.asc',
        ], $this->activeOnly()))->json() ?: [];

        $products = $productRatingService->attachToProducts($products);

        $productIds = array_map(fn ($p) => (int) $p['id'], $products);
        $productReviews = [];
        if ($productIds) {
            $productReviews = $supabase->get('reviews', [
                'select' => '*',
                'order' => 'created_at.desc',
                'product_id' => 'in.(' . implode(',', $productIds) . ')',
            ])->json() ?: [];
        }

        return response()->json([
            'restaurant' => $restaurant,
            'restaurant_reviews' => $restaurantReviews,
            'products' => $products,
            'product_reviews' => $productReviews,
        ]);
    }

    public function storeRestaurantReview(Request $request, int $id)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');
        $anon = env('SUPABASE_ANON_KEY', 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh');
        $userResponse = Http::withHeaders([
            'apikey' => $anon,
            'Authorization' => 'Bearer ' . $token,
        ])->get($url . '/auth/v1/user');

        if (!$userResponse->successful()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $userResponse->json();
        $supabase = app(SupabaseService::class);
        $ratingService = app(RestaurantRatingService::class);

        $response = $supabase->post('restaurant_reviews', [
            'restaurant_id' => $id,
            'user_id' => $user['id'],
            'author_name' => $request->input('author_name', 'Пользователь'),
            'rating' => $request->input('rating', 5),
            'comment' => $request->input('comment', ''),
        ]);

        $avg = $ratingService->recalcAndPersist($id);

        return response()->json([
            'review' => $response->json(),
            'rating' => $avg,
        ], $response->status());
    }

    public function restaurantProducts(int $id)
    {
        $supabase = app(SupabaseService::class);
        $products = $supabase->get('products', array_merge([
            'restaurant_id' => 'eq.' . $id,
            'select' => '*',
            'order' => 'category.asc,name.asc',
        ], $this->activeOnly()))->json() ?: [];

        return response()->json(
            app(ProductRatingService::class)->attachToProducts($products)
        );
    }

    public function productReviews(int $id)
    {
        $supabase = app(SupabaseService::class);

        return response()->json($supabase->get('reviews', [
            'product_id' => 'eq.' . $id,
            'select' => '*',
            'order' => 'created_at.desc',
        ])->json() ?: []);
    }

    public function restaurantProductReviews(int $id)
    {
        $supabase = app(SupabaseService::class);
        $products = $supabase->get('products', array_merge([
            'restaurant_id' => 'eq.' . $id,
            'select' => 'id',
        ], $this->activeOnly()))->json() ?: [];

        $productIds = array_map(fn ($p) => (int) $p['id'], $products);
        if (!$productIds) {
            return response()->json([]);
        }

        return response()->json($supabase->get('reviews', [
            'select' => '*',
            'order' => 'created_at.desc',
            'product_id' => 'in.(' . implode(',', $productIds) . ')',
        ])->json() ?: []);
    }

    public function product(int $id)
    {
        $supabase = app(SupabaseService::class);
        $ratingService = app(ProductRatingService::class);

        $rows = $supabase->get('products', array_merge([
            'id' => 'eq.' . $id,
            'select' => '*',
        ], $this->activeOnly()))->json();

        $product = $rows[0] ?? null;
        if (!$product) {
            return response()->json(null, 404);
        }

        $map = $ratingService->aggregateRatings();
        $product['rating'] = $ratingService->averageForProduct($id, $map);
        $product['reviews_count'] = $map[$id]['count'] ?? 0;

        return response()->json($product);
    }

    public function storeProductReview(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');
        $anon = env('SUPABASE_ANON_KEY', 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh');
        $userResponse = Http::withHeaders([
            'apikey' => $anon,
            'Authorization' => 'Bearer ' . $token,
        ])->get($url . '/auth/v1/user');

        if (!$userResponse->successful()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $userResponse->json();
        $supabase = app(SupabaseService::class);
        $productRatingService = app(ProductRatingService::class);

        $productId = (int) $request->input('product_id');

        $response = $supabase->post('reviews', [
            'product_id' => $productId,
            'user_id' => $user['id'],
            'author_name' => $request->input('author_name', 'Пользователь'),
            'rating' => $request->input('rating', 5),
            'comment' => $request->input('comment', ''),
        ]);

        if (!$response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        $created = $response->json();
        $review = is_array($created) && isset($created[0]) ? $created[0] : $created;
        $avg = $productId > 0 ? $productRatingService->recalcAndPersist($productId) : 0;

        return response()->json([
            'review' => $review,
            'product_rating' => $avg,
        ], $response->status());
    }

    public function validatePromo(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code', '')));
        $cartTotal = max(0, (float) $request->input('cartTotal', 0));
        $userId = $request->input('userId');

        $promos = [
            'PIZZA20' => ['type' => 'percent', 'value' => 20, 'first_order_only' => true],
            'FREE50' => ['type' => 'fixed', 'value' => 50, 'first_order_only' => false],
        ];

        if (!isset($promos[$code])) {
            return response()->json(['error' => 'Промокод не найден'], 404);
        }

        $promo = $promos[$code];

        if (!empty($promo['first_order_only'])) {
            if (!$userId) {
                return response()->json([
                    'error' => 'Войдите в аккаунт — промокод действует только на первый заказ',
                ], 401);
            }

            $supabase = app(SupabaseService::class);
            $existing = $supabase->get('orders', array_merge([
                'user_id' => 'eq.' . $userId,
                'select' => 'id',
                'limit' => 1,
            ], app(AdminDeletionService::class)->activeOnly()))->json() ?: [];

            if (!empty($existing)) {
                return response()->json([
                    'error' => 'Промокод PIZZA20 действует только на первый заказ для новых пользователей',
                ], 422);
            }
        }

        $discount = $promo['type'] === 'percent'
            ? (int) floor($cartTotal * $promo['value'] / 100)
            : min((int) $promo['value'], (int) $cartTotal);

        return response()->json([
            'code' => $code,
            'discount' => $discount,
            'message' => $promo['type'] === 'percent'
                ? "Скидка {$promo['value']}%"
                : "Скидка {$promo['value']} ₽",
        ]);
    }

    public function storeSupport(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $phone = preg_replace('/\D+/', '', (string) $request->input('phone', ''));
        $message = trim((string) $request->input('message', ''));

        if ($name === '' || strlen($phone) < 11 || $message === '') {
            return response()->json(['error' => 'Заполните все поля'], 422);
        }

        $payload = [
            'name' => $name,
            'phone' => $phone,
            'message' => $message,
        ];

        $userId = $request->input('userId');
        if ($userId) {
            $payload['user_id'] = $userId;
        }

        $supabase = app(SupabaseService::class);
        $response = $supabase->post('support_requests', $payload);

        if (!$response->successful()) {
            return response()->json($response->json() ?: ['error' => 'Не удалось отправить заявку'], $response->status());
        }

        return response()->json(['ok' => true], $response->status());
    }
}
