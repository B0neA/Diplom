<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Страница-лендинг
Route::get('/', function () {
    return Inertia::render('HomePage');
});

Route::get('/auth', function () {
    return Inertia::render('Auth/AuthPage');
})->name('auth');

// Главная со списком ресторанов (бывшая главная)
Route::get('/restaurans', function () {
    return Inertia::render('ProductsView');
});

// Страница меню ресторана
Route::get('/product/{id}', function ($id) {
    return Inertia::render('ProductPage', ['id' => (int) $id]);
});

// Оформление заказа
Route::get('/check', function () {
    return Inertia::render('Checkout');
});

// Страница профиля (если нужна)
Route::get('/profile', function () {
    return Inertia::render('Auth/ProfilePage');
})->name('profile');

// Страница "О нас" (если нужна)
Route::get('/about', function () {
    return Inertia::render('AboutPage');
})->name('about');

// Страница контактов (если нужна)
Route::get('/contacts', function () {
    return Inertia::render('ContactsPage');
})->name('contacts');

// Админ-панель (если нужна)
Route::get('/admin', function () {
    return Inertia::render('Admin/Dashboard');
})->name('admin.dashboard');

// API-маршруты для работы с Supabase (опционально, через Laravel-сервис)
Route::prefix('api')->group(function () {
    
    // Получить список ресторанов
    Route::get('/restaurants', function () {
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('restaurants')
            ->select('*')
            ->order('rating', 'desc')
            ->get();
        return response()->json($response->json());
    });

    // Получить конкретный ресторан
    Route::get('/restaurants/{id}', function ($id) {
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('restaurants')
            ->select('*')
            ->eq('id', $id)
            ->single()
            ->get();
        return response()->json($response->json());
    });

    // Получить продукты ресторана
    Route::get('/restaurants/{id}/products', function ($id) {
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('products')
            ->select('*')
            ->eq('restaurant_id', $id)
            ->order('name', 'asc')
            ->get();
        return response()->json($response->json());
    });

    // Поиск ресторанов
    Route::get('/search/restaurants', function (\Illuminate\Http\Request $request) {
        $query = $request->get('q', '');
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('restaurants')
            ->select('*')
            ->ilike('title', "%$query%")
            ->limit(10)
            ->get();
        return response()->json($response->json());
    });

    // Поиск продуктов
    Route::get('/search/products', function (\Illuminate\Http\Request $request) {
        $query = $request->get('q', '');
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('products')
            ->select('*')
            ->ilike('name', "%$query%")
            ->limit(20)
            ->get();
        return response()->json($response->json());
    });

    // Создать заказ
    Route::post('/orders', function (\Illuminate\Http\Request $request) {
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('orders')
            ->insert([
                'customer_name' => $request->customerName,
                'customer_phone' => $request->customerPhone,
                'delivery_address' => $request->deliveryAddress,
                'comment' => $request->orderComment,
                'total_amount' => $request->total,
                'items' => json_encode($request->items),
                'status' => 'new',
                'created_at' => now()->toIso8601String(),
            ]);
        return response()->json($response->json());
    });

    // Получить заказы пользователя
    Route::get('/orders/{phone}', function ($phone) {
        $supabase = app(\App\Services\SupabaseService::class);
        $response = $supabase->from('orders')
            ->select('*')
            ->eq('customer_phone', $phone)
            ->order('created_at', 'desc')
            ->get();
        return response()->json($response->json());
    });
});

// Fallback — все остальные запросы на главную
Route::fallback(function () {
    return Inertia::render('ProductsView');
});