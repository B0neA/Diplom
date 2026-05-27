<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
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

    protected function serviceKey(): ?string
    {
        return env('SUPABASE_SERVICE_ROLE_KEY')
            ?: env('SUPABASE_SECRET')
            ?: env('SUPABASE_SERVICE_KEY');
    }

    protected function verifyPassword(string $email, string $password): bool
    {
        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');
        $anon = env('SUPABASE_ANON_KEY', 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh');

        $response = Http::withHeaders(['apikey' => $anon])
            ->asForm()
            ->post($url . '/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);

        return $response->successful();
    }

    public function destroy(Request $request)
    {
        $user = $this->verifyUser($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $password = (string) $request->input('password', '');
        if ($password === '') {
            return response()->json(['error' => 'Введите пароль для подтверждения'], 422);
        }

        $email = (string) ($user['email'] ?? '');
        if ($email === '' || !$this->verifyPassword($email, $password)) {
            return response()->json(['error' => 'Неверный пароль'], 403);
        }

        $userId = $user['id'] ?? null;
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $supabase = app(SupabaseService::class);

        $supabase->delete('reviews', ['user_id' => 'eq.' . $userId]);
        $supabase->delete('restaurant_reviews', ['user_id' => 'eq.' . $userId]);
        $supabase->delete('orders', ['user_id' => 'eq.' . $userId]);
        $supabase->delete('profiles', ['id' => 'eq.' . $userId]);

        $serviceKey = $this->serviceKey();
        $url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');

        if ($serviceKey) {
            Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->delete($url . '/auth/v1/admin/users/' . $userId);
        }

        return response()->json(['ok' => true]);
    }
}
