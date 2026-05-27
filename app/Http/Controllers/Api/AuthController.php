<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    protected function serviceKey(): ?string
    {
        return env('SUPABASE_SERVICE_ROLE_KEY')
            ?: env('SUPABASE_SECRET')
            ?: env('SUPABASE_SERVICE_KEY');
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        return $digits;
    }

    public function forgotPasswordCheck(Request $request)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        if (strlen($phone) < 11) {
            return response()->json(['error' => 'Укажите корректный номер телефона'], 422);
        }

        $supabase = app(SupabaseService::class);
        $profiles = $supabase->get('profiles', [
            'phone' => 'eq.' . $phone,
            'select' => 'id,phone',
        ])->json() ?: [];

        if (empty($profiles[0]['id'])) {
            return response()->json(['error' => 'Пользователь с таким телефоном не найден'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function resetPasswordByPhone(Request $request)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        if (strlen($phone) < 11) {
            return response()->json(['error' => 'Укажите корректный номер телефона'], 422);
        }
        if (strlen($password) < 6) {
            return response()->json(['error' => 'Пароль не менее 6 символов'], 422);
        }
        if ($password !== $confirm) {
            return response()->json(['error' => 'Пароли не совпадают'], 422);
        }

        $serviceKey = $this->serviceKey();
        if (!$serviceKey) {
            return response()->json(['error' => 'Сброс пароля недоступен (нет service key)'], 503);
        }

        $supabase = app(SupabaseService::class);
        $profile = $supabase->get('profiles', [
            'phone' => 'eq.' . $phone,
            'select' => 'id',
        ])->json()[0] ?? null;

        if (empty($profile['id'])) {
            return response()->json(['error' => 'Пользователь с таким телефоном не найден'], 404);
        }

        $userId = $profile['id'];
        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');

        $response = Http::withHeaders([
            'apikey' => $serviceKey,
            'Authorization' => 'Bearer ' . $serviceKey,
            'Content-Type' => 'application/json',
        ])->put($url . '/auth/v1/admin/users/' . $userId, [
            'password' => $password,
        ]);

        if (!$response->successful()) {
            $msg = $response->json()['message'] ?? 'Не удалось сменить пароль';

            return response()->json(['error' => $msg], $response->status());
        }

        return response()->json(['ok' => true]);
    }
}
