<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductRatingService;
use App\Services\RestaurantRatingService;
use App\Services\ReviewGroupingService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileReviewController extends Controller
{
    protected function normalizeSupabaseList(mixed $data): array
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

    protected function verifyUser(Request $request): ?array
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');
        $anon = env('SUPABASE_ANON_KEY', 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh');

        $userResponse = Http::withHeaders([
            'apikey' => $anon,
            'Authorization' => 'Bearer ' . $token,
        ])->get($url . '/auth/v1/user');

        if (!$userResponse->successful()) {
            return null;
        }

        return $userResponse->json();
    }

    protected function reviewsOrder(string $sort): string
    {
        return match ($sort) {
            'old' => 'created_at.asc',
            'good' => 'rating.desc',
            'bad' => 'rating.asc',
            default => 'created_at.desc',
        };
    }

    public function index(Request $request)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user['id'];
        $supabase = app(SupabaseService::class);
        $sort = (string) $request->get('sort', 'new');
        $order = $this->reviewsOrder($sort);

        $reviews = $this->normalizeSupabaseList($supabase->get('reviews', [
            'user_id' => 'eq.' . $userId,
            'select' => '*',
            'order' => $order,
        ])->json());

        $restaurantReviews = $this->normalizeSupabaseList($supabase->get('restaurant_reviews', [
            'user_id' => 'eq.' . $userId,
            'select' => '*',
            'order' => $order,
        ])->json());

        $productIds = array_unique(array_filter(array_map(
            fn ($r) => (int) ($r['product_id'] ?? 0),
            $reviews
        )));
        $restaurantIds = array_unique(array_filter(array_merge(
            array_map(fn ($r) => (int) ($r['restaurant_id'] ?? 0), $restaurantReviews),
            $this->restaurantIdsFromProducts($supabase, $productIds)
        )));

        $products = $this->normalizeSupabaseList($this->fetchProducts($supabase, $productIds));
        $restaurants = $this->normalizeSupabaseList($this->fetchRestaurants($supabase, $restaurantIds));

        $grouped = app(ReviewGroupingService::class)->group(
            $reviews,
            $restaurantReviews,
            $products,
            $restaurants
        );

        return response()->json($grouped);
    }

    protected function restaurantIdsFromProducts(SupabaseService $supabase, array $productIds): array
    {
        if (!$productIds) {
            return [];
        }
        $products = $supabase->get('products', [
            'select' => 'restaurant_id',
            'id' => 'in.(' . implode(',', $productIds) . ')',
        ])->json() ?: [];

        return array_unique(array_map(fn ($p) => (int) ($p['restaurant_id'] ?? 0), $products));
    }

    protected function fetchProducts(SupabaseService $supabase, array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        return $supabase->get('products', [
            'select' => 'id,name,restaurant_id',
            'id' => 'in.(' . implode(',', $productIds) . ')',
        ])->json() ?: [];
    }

    protected function fetchRestaurants(SupabaseService $supabase, array $restaurantIds): array
    {
        if (!$restaurantIds) {
            return [];
        }

        return $supabase->get('restaurants', [
            'select' => '*',
            'id' => 'in.(' . implode(',', $restaurantIds) . ')',
        ])->json() ?: [];
    }

    public function updateProductReview(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('reviews', [
            'id' => 'eq.' . $id,
            'user_id' => 'eq.' . $user['id'],
            'select' => 'product_id',
        ])->json()[0] ?? null;

        if (!$existing) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $response = $supabase->patch('reviews', [
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => 'eq.' . $id]);

        $productId = (int) ($existing['product_id'] ?? 0);
        if ($productId > 0) {
            app(ProductRatingService::class)->recalcAndPersist($productId);
        }

        return response()->json($response->json());
    }

    public function deleteProductReview(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('reviews', [
            'id' => 'eq.' . $id,
            'user_id' => 'eq.' . $user['id'],
            'select' => 'product_id',
        ])->json()[0] ?? null;

        if (!$existing) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $productId = (int) ($existing['product_id'] ?? 0);
        $response = $supabase->delete('reviews', ['id' => 'eq.' . $id]);

        if ($productId > 0) {
            app(ProductRatingService::class)->recalcAndPersist($productId);
        }

        return response()->json($response->json());
    }

    public function updateRestaurantReview(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('restaurant_reviews', [
            'id' => 'eq.' . $id,
            'user_id' => 'eq.' . $user['id'],
            'select' => 'restaurant_id',
        ])->json()[0] ?? null;

        if (!$existing) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $response = $supabase->patch('restaurant_reviews', [
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => 'eq.' . $id]);

        $restaurantId = (int) ($existing['restaurant_id'] ?? 0);
        if ($restaurantId > 0) {
            app(RestaurantRatingService::class)->recalcAndPersist($restaurantId);
        }

        return response()->json($response->json());
    }

    public function deleteRestaurantReview(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('restaurant_reviews', [
            'id' => 'eq.' . $id,
            'user_id' => 'eq.' . $user['id'],
            'select' => 'restaurant_id',
        ])->json()[0] ?? null;

        if (!$existing) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $restaurantId = (int) ($existing['restaurant_id'] ?? 0);
        $response = $supabase->delete('restaurant_reviews', ['id' => 'eq.' . $id]);

        if ($restaurantId > 0) {
            app(RestaurantRatingService::class)->recalcAndPersist($restaurantId);
        }

        return response()->json($response->json());
    }
}
