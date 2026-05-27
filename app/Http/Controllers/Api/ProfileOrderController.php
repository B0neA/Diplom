<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderModificationService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileOrderController extends Controller
{
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

    protected function fetchUserOrder(SupabaseService $supabase, string $userId, $id): ?array
    {
        $rows = $supabase->get('orders', [
            'id' => 'eq.' . $id,
            'user_id' => 'eq.' . $userId,
            'select' => '*',
        ])->json();

        return is_array($rows) && isset($rows[0]) ? $rows[0] : null;
    }

    public function cancel(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $order = $this->fetchUserOrder($supabase, $user['id'], $id);
        if (!$order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }

        $mod = app(OrderModificationService::class);
        $check = $mod->assertCanModify($order);
        if (!$check['ok']) {
            return response()->json(['error' => $check['error']], 422);
        }

        $response = $supabase->patch('orders', [
            'status' => 'cancelled',
        ], ['id' => 'eq.' . $id]);

        if (!$response->successful()) {
            return response()->json($response->json() ?: ['error' => 'Не удалось отменить заказ'], $response->status());
        }

        $updated = $response->json();
        $row = is_array($updated) && isset($updated[0]) ? $updated[0] : array_merge($order, ['status' => 'cancelled']);

        return response()->json(['ok' => true, 'order' => $row]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);
        $order = $this->fetchUserOrder($supabase, $user['id'], $id);
        if (!$order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }

        $mod = app(OrderModificationService::class);

        if (!$mod->isSingleRestaurantOrder($order)) {
            return response()->json([
                'error' => 'Изменить можно только заказ из одного ресторана. Вы можете отменить заказ целиком.',
            ], 422);
        }

        $check = $mod->assertCanModify($order);
        if (!$check['ok']) {
            return response()->json(['error' => $check['error']], 422);
        }

        $itemsResult = $mod->normalizeItemsForUpdate($request->input('items', []));
        if (!$itemsResult['ok']) {
            return response()->json(['error' => $itemsResult['error']], 422);
        }

        $items = $itemsResult['items'];
        $restaurantIds = $mod->restaurantIdsFromOrder(array_merge($order, ['items' => $items]));
        if (count($restaurantIds) > 1) {
            return response()->json(['error' => 'Все блюда должны быть из одного ресторана'], 422);
        }

        $total = $mod->calcOrderTotal($items);

        $patch = [
            'items' => $items,
            'total_amount' => $total,
        ];

        if ($request->has('deliveryAddress')) {
            $patch['delivery_address'] = trim((string) $request->input('deliveryAddress'));
        }
        if ($request->has('orderComment')) {
            $patch['comment'] = trim((string) $request->input('orderComment'));
        }
        if ($request->has('customerPhone')) {
            $patch['customer_phone'] = preg_replace('/\D+/', '', (string) $request->input('customerPhone'));
        }
        if ($request->has('customerName')) {
            $patch['customer_name'] = trim((string) $request->input('customerName'));
        }

        if (!empty($restaurantIds[0])) {
            $patch['restaurant_id'] = $restaurantIds[0];
        }

        $response = $supabase->patch('orders', $patch, ['id' => 'eq.' . $id]);

        if (!$response->successful()) {
            return response()->json($response->json() ?: ['error' => 'Не удалось сохранить изменения'], $response->status());
        }

        $updated = $response->json();
        $row = is_array($updated) && isset($updated[0]) ? $updated[0] : array_merge($order, $patch);

        return response()->json(['ok' => true, 'order' => $row]);
    }
}
