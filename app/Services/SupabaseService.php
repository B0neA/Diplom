<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        // Используем секретный ключ для полного доступа к БД
        $this->key = env('SUPABASE_SECRET'); 
    }

    public function from(string $table)
    {
        // Возвращаем базовый HTTP-запрос для цепочки вызовов
        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ])->baseUrl($this->url . '/rest/v1/' . $table);
    }
}