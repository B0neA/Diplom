<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfileOrderController;
use App\Http\Controllers\Api\ProfileReviewController;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Controllers\SeoController;
use App\Services\RestaurantRatingService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/favicon.svg', [SeoController::class, 'favicon']);
Route::get('/favicon.ico', [SeoController::class, 'favicon']);

Route::get('/', fn () => Inertia::render('HomePage'));

Route::get('/auth', fn () => Inertia::render('Auth/AuthPage'))->name('auth');
Route::get('/forgot-password', fn () => Inertia::render('Auth/ForgotPasswordPage'))->name('auth.forgot');

Route::get('/restaurans', fn () => Inertia::render('ProductsView'));

Route::get('/product/{id}', fn ($id) => Inertia::render('ProductPage', ['id' => (int) $id]));
Route::get('/restaurant/{id}/reviews', function ($id) {
    $supabase = app(SupabaseService::class);
    $row = $supabase->get('restaurants', [
        'id' => 'eq.' . (int) $id,
        'select' => 'id,title,name',
    ])->json()[0] ?? null;
    $title = trim((string) ($row['title'] ?? $row['name'] ?? ''));

    return Inertia::render('RestaurantReviewsPage', [
        'id' => (int) $id,
        'initialRestaurantTitle' => $title ?: null,
    ]);
});

Route::get('/dish/{id}', fn ($id) => Inertia::render('DishPage', ['id' => (int) $id]));

Route::get('/check', fn () => Inertia::render('Checkout'));

Route::get('/profile', fn () => Inertia::render('Auth/ProfilePage'))->name('profile');

Route::get('/admin', fn () => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');

Route::prefix('api')->group(function () {
    Route::get('/site-settings', [PublicApiController::class, 'siteSettings']);
    Route::get('/restaurants', [PublicApiController::class, 'restaurants']);
    Route::get('/restaurants/{id}', [PublicApiController::class, 'restaurant']);
    Route::get('/restaurants/{id}/reviews-page', [PublicApiController::class, 'reviewsPageData']);

    Route::get('/restaurants/{id}/products', [PublicApiController::class, 'restaurantProducts']);
    Route::get('/products/{id}', [PublicApiController::class, 'product']);
    Route::get('/products/{id}/reviews', [PublicApiController::class, 'productReviews']);
    Route::get('/restaurants/{id}/product-reviews', [PublicApiController::class, 'restaurantProductReviews']);
    Route::get('/restaurants/{id}/reviews', function ($id) {
        $supabase = app(SupabaseService::class);
        return response()->json($supabase->get('restaurant_reviews', [
            'restaurant_id' => 'eq.' . $id,
            'select' => '*',
            'order' => 'created_at.desc',
        ])->json());
    });
    Route::post('/restaurants/{id}/reviews', [PublicApiController::class, 'storeRestaurantReview']);
    Route::post('/reviews', [PublicApiController::class, 'storeProductReview']);

    Route::post('/orders', function (Request $request) {
        $supabase = app(SupabaseService::class);
        $payload = [
            'customer_name' => $request->input('customerName'),
            'customer_phone' => $request->input('customerPhone'),
            'delivery_address' => $request->input('deliveryAddress'),
            'comment' => $request->input('orderComment'),
            'total_amount' => $request->input('total'),
            'items' => $request->input('items'),
            'status' => 'new',
        ];
        if ($request->filled('userId')) {
            $payload['user_id'] = $request->input('userId');
        }
        if ($request->filled('restaurantId')) {
            $payload['restaurant_id'] = $request->input('restaurantId');
        }
        $response = $supabase->post('orders', $payload);
        return response()->json($response->json(), $response->status());
    });

    Route::post('/promo/validate', [PublicApiController::class, 'validatePromo']);
    Route::post('/auth/forgot-password/check', [AuthController::class, 'forgotPasswordCheck']);
    Route::post('/auth/forgot-password/reset', [AuthController::class, 'resetPasswordByPhone']);

    Route::post('/support', [PublicApiController::class, 'storeSupport']);

    Route::post('/feedback', function (Request $request) {
        $supabase = app(SupabaseService::class);
        $response = $supabase->post('feedback_messages', [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message'),
        ]);
        return response()->json($response->json(), $response->status());
    });

    Route::get('/faq', function () {
        $supabase = app(SupabaseService::class);
        return response()->json($supabase->get('faq', [
            'is_active' => 'eq.true',
            'select' => '*',
            'order' => 'sort_order.asc',
        ])->json());
    });

    Route::post('/profile/orders/{id}/cancel', [ProfileOrderController::class, 'cancel']);
    Route::patch('/profile/orders/{id}', [ProfileOrderController::class, 'update']);

    Route::get('/profile/reviews', [ProfileReviewController::class, 'index']);
    Route::patch('/profile/reviews/{id}', [ProfileReviewController::class, 'updateProductReview']);
    Route::delete('/profile/reviews/{id}', [ProfileReviewController::class, 'deleteProductReview']);
    Route::patch('/profile/restaurant-reviews/{id}', [ProfileReviewController::class, 'updateRestaurantReview']);
    Route::delete('/profile/restaurant-reviews/{id}', [ProfileReviewController::class, 'deleteRestaurantReview']);

    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::patch('/profile', function (Request $request) {
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

        $userId = $userResponse->json()['id'] ?? null;
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $fields = $request->only(['full_name', 'phone', 'address', 'birth_date', 'payment_card', 'payment_expiry', 'payment_cvc']);

        $profileResponse = $supabase->get('profiles', [
            'id' => 'eq.' . $userId,
            'select' => 'birth_date',
        ]);
        $existingBirthDate = null;
        if ($profileResponse->successful()) {
            $rows = $profileResponse->json();
            $existingBirthDate = is_array($rows) && isset($rows[0]['birth_date'])
                ? $rows[0]['birth_date']
                : null;
        }

        if ($existingBirthDate) {
            unset($fields['birth_date']);
        } elseif (array_key_exists('birth_date', $fields) && empty($fields['birth_date'])) {
            unset($fields['birth_date']);
        }

        $response = $supabase->patch('profiles', $fields, ['id' => 'eq.' . $userId]);

        return response()->json($response->json(), $response->status());
    });

    Route::get('/admin/restaurants', [AdminController::class, 'restaurants']);
    Route::post('/admin/restaurants', [AdminController::class, 'storeRestaurant']);
    Route::patch('/admin/restaurants/{id}', [AdminController::class, 'updateRestaurant']);
    Route::delete('/admin/restaurants/{id}', [AdminController::class, 'deleteRestaurant']);
    Route::post('/admin/restaurants/{id}/restore', [AdminController::class, 'restoreRestaurant']);
    Route::get('/admin/orders', [AdminController::class, 'orders']);
    Route::patch('/admin/orders/{id}', [AdminController::class, 'updateOrder']);
    Route::delete('/admin/orders/{id}', [AdminController::class, 'deleteOrder']);
    Route::post('/admin/orders/{id}/restore', [AdminController::class, 'restoreOrder']);
    Route::get('/admin/products', [AdminController::class, 'products']);
    Route::post('/admin/products', [AdminController::class, 'storeProduct']);
    Route::patch('/admin/products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('/admin/products/{id}', [AdminController::class, 'deleteProduct']);
    Route::post('/admin/products/{id}/restore', [AdminController::class, 'restoreProduct']);
    Route::get('/admin/archive', [AdminController::class, 'archiveItems']);
    Route::get('/admin/restorable', [AdminController::class, 'restorableItems']);
    Route::get('/admin/feedback', [AdminController::class, 'feedback']);
    Route::delete('/admin/feedback/{id}', [AdminController::class, 'deleteFeedback']);
    Route::get('/admin/support', [AdminController::class, 'supportRequests']);
    Route::delete('/admin/support/{id}', [AdminController::class, 'deleteSupportRequest']);
    Route::get('/admin/reviews', [AdminController::class, 'reviewsGrouped']);
    Route::patch('/admin/reviews/{id}', [AdminController::class, 'updateReview']);
    Route::delete('/admin/reviews/{id}', [AdminController::class, 'deleteReview']);
    Route::patch('/admin/restaurant-reviews/{id}', [AdminController::class, 'updateRestaurantReview']);
    Route::delete('/admin/restaurant-reviews/{id}', [AdminController::class, 'deleteRestaurantReview']);
});

Route::fallback(fn () => Inertia::render('NotFoundPage'));
