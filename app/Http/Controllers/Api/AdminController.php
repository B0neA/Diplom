<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDeletionService;
use App\Services\ProductRatingService;
use App\Services\RestaurantRatingService;
use App\Services\ReviewGroupingService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    protected function recalcRestaurantRating(SupabaseService $supabase, int $restaurantId): void
    {
        if ($restaurantId <= 0) {
            return;
        }
        app(RestaurantRatingService::class)->recalcAndPersist($restaurantId);
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

    /** @return array<int, array<string, mixed>> */
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

    protected function restaurantTitleFromMap($restaurantMap, int $restaurantId): string
    {
        $row = $restaurantMap->get($restaurantId);
        if (!is_array($row)) {
            return $restaurantId > 0 ? "Ресторан #{$restaurantId}" : 'Без ресторана';
        }

        $title = trim((string) ($row['title'] ?? $row['name'] ?? ''));

        return $title !== '' ? $title : ($restaurantId > 0 ? "Ресторан #{$restaurantId}" : 'Без ресторана');
    }

    protected function verifyAdmin(Request $request): ?array
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

        $user = $userResponse->json();
        $userId = $user['id'] ?? null;
        if (!$userId) {
            return null;
        }

        $supabase = app(SupabaseService::class);
        $profiles = $supabase->get('profiles', [
            'id' => 'eq.' . $userId,
            'select' => 'is_admin',
        ])->json();

        if (empty($profiles[0]['is_admin'])) {
            return null;
        }

        return $user;
    }

    protected function productFields(Request $request): array
    {
        $fields = $request->only([
            'name', 'description', 'price', 'category', 'restaurant_id', 'img',
            'composition', 'calories', 'proteins', 'fats', 'carbs',
        ]);

        foreach (['price', 'calories', 'proteins', 'fats', 'carbs'] as $key) {
            if (isset($fields[$key]) && $fields[$key] !== null && $fields[$key] !== '' && (float) $fields[$key] < 0) {
                $fields[$key] = 0;
            }
        }

        return $fields;
    }

    public function restaurants(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $deletion = app(AdminDeletionService::class);
        $response = $supabase->get('restaurants', array_merge([
            'select' => '*',
            'order' => 'title.asc',
        ], $deletion->activeOnly()));

        return response()->json($response->json());
    }

    protected function restaurantFields(Request $request): array
    {
        $fields = $request->only(['title', 'rating', 'img', 'delivery_time', 'description']);
        if (isset($fields['rating']) && (float) $fields['rating'] < 0) {
            $fields['rating'] = 0;
        }
        if (isset($fields['delivery_time']) && (int) $fields['delivery_time'] < 0) {
            $fields['delivery_time'] = 0;
        }
        return $fields;
    }

    public function storeRestaurant(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $response = $supabase->post('restaurants', $this->restaurantFields($request));

        return response()->json($response->json(), $response->status());
    }

    public function updateRestaurant(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $response = $supabase->patch('restaurants', $this->restaurantFields($request), [
            'id' => 'eq.' . $id,
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function deleteRestaurant(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->softDeleteRestaurant((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function restoreRestaurant(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->restoreRestaurant((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function orders(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $deletion = app(AdminDeletionService::class);

        return response()->json($supabase->get('orders', array_merge([
            'select' => '*',
            'order' => 'created_at.desc',
        ], $deletion->activeOnly()))->json());
    }

    public function updateOrder(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $response = $supabase->patch('orders', [
            'status' => $request->input('status'),
        ], ['id' => 'eq.' . $id]);

        return response()->json($response->json(), $response->status());
    }

    public function deleteOrder(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->softDeleteOrder((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function restoreOrder(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->restoreOrder((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function products(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $deletion = app(AdminDeletionService::class);

        return response()->json($supabase->get('products', array_merge([
            'select' => '*',
            'order' => 'name.asc',
        ], $deletion->activeOnly()))->json());
    }

    public function storeProduct(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        return response()->json($supabase->post('products', $this->productFields($request))->json());
    }

    public function updateProduct(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $response = $supabase->patch('products', $this->productFields($request), [
            'id' => 'eq.' . $id,
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function deleteProduct(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->softDeleteProduct((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function restoreProduct(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $result = app(AdminDeletionService::class)->restoreProduct((int) $id);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json($result);
    }

    public function archiveItems(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json(app(AdminDeletionService::class)->archiveItems());
    }

    public function restorableItems(Request $request)
    {
        return $this->archiveItems($request);
    }

    public function feedback(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        return response()->json($supabase->get('feedback_messages', [
            'select' => '*',
            'order' => 'created_at.desc',
        ])->json());
    }

    public function deleteFeedback(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        return response()->json($supabase->delete('feedback_messages', [
            'id' => 'eq.' . $id,
        ])->json());
    }

    public function supportRequests(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);

        return response()->json($supabase->get('support_requests', [
            'select' => '*',
            'order' => 'created_at.desc',
        ])->json());
    }

    public function deleteSupportRequest(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);

        return response()->json($supabase->delete('support_requests', [
            'id' => 'eq.' . $id,
        ])->json());
    }

    public function reviewsGrouped(Request $request)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $sort = (string) $request->get('sort', 'new');
        $order = $this->reviewsOrder($sort);

        $reviews = $this->normalizeSupabaseList($supabase->get('reviews', [
            'select' => '*',
            'order' => $order,
        ])->json());
        $restaurantReviews = $this->normalizeSupabaseList($supabase->get('restaurant_reviews', [
            'select' => '*',
            'order' => $order,
        ])->json());

        $products = $this->normalizeSupabaseList($supabase->get('products', [
            'select' => 'id,name,restaurant_id',
        ])->json());
        $restaurants = $this->normalizeSupabaseList($supabase->get('restaurants', [
            'select' => '*',
        ])->json());

        return response()->json(
            app(ReviewGroupingService::class)->group($reviews, $restaurantReviews, $products, $restaurants)
        );
    }

    public function updateReview(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('reviews', [
            'id' => 'eq.' . $id,
            'select' => 'product_id',
        ])->json()[0] ?? null;
        $response = $supabase->patch('reviews', $request->only([
            'rating', 'comment', 'author_name',
        ]), ['id' => 'eq.' . $id]);
        $productId = (int) ($existing['product_id'] ?? 0);
        if ($productId > 0) {
            app(ProductRatingService::class)->recalcAndPersist($productId);
        }
        return response()->json($response->json());
    }

    public function deleteReview(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('reviews', [
            'id' => 'eq.' . $id,
            'select' => 'product_id',
        ])->json()[0] ?? null;
        $productId = (int) ($existing['product_id'] ?? 0);
        $response = $supabase->delete('reviews', ['id' => 'eq.' . $id]);
        if ($productId > 0) {
            app(ProductRatingService::class)->recalcAndPersist($productId);
        }
        return response()->json($response->json());
    }

    public function updateRestaurantReview(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('restaurant_reviews', [
            'id' => 'eq.' . $id,
            'select' => 'restaurant_id',
        ])->json()[0] ?? null;
        $response = $supabase->patch('restaurant_reviews', $request->only([
            'rating', 'comment', 'author_name',
        ]), ['id' => 'eq.' . $id]);
        $restaurantId = (int) ($existing['restaurant_id'] ?? 0);
        $this->recalcRestaurantRating($supabase, $restaurantId);
        return response()->json($response->json());
    }

    public function deleteRestaurantReview(Request $request, $id)
    {
        if (!$this->verifyAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $supabase = app(SupabaseService::class);
        $existing = $supabase->get('restaurant_reviews', [
            'id' => 'eq.' . $id,
            'select' => 'restaurant_id',
        ])->json()[0] ?? null;
        $response = $supabase->delete('restaurant_reviews', ['id' => 'eq.' . $id]);
        $restaurantId = (int) ($existing['restaurant_id'] ?? 0);
        $this->recalcRestaurantRating($supabase, $restaurantId);
        return response()->json($response->json());
    }
}
