<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(env('SUPABASE_URL', 'https://cuibxmcjdkgjffmmzwgd.supabase.co'), '/');
        // Для админ-операций и архива нужен service role; anon-ключ не видит soft-deleted строки при RLS.
        $this->key = env('SUPABASE_SERVICE_ROLE_KEY')
            ?: env('SUPABASE_SECRET')
            ?: env('SUPABASE_SERVICE_KEY')
            ?: env('SUPABASE_ANON_KEY', 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh');
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ], $extra);
    }

    protected function tableUrl(string $table, array $query = []): string
    {
        $url = $this->url . '/rest/v1/' . $table;
        if ($query) {
            $url .= '?' . $this->encodePostgrestQuery($query);
        }

        return $url;
    }

    /** PostgREST: значения вроде not.is.null не должны кодироваться через http_build_query. */
    protected function encodePostgrestQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = rawurlencode((string) $key) . '=' . (string) $value;
        }

        return implode('&', $parts);
    }

    public function get(string $table, array $query = [])
    {
        return Http::withHeaders($this->headers())
            ->get($this->tableUrl($table, $query));
    }

    public function post(string $table, array $body)
    {
        return Http::withHeaders($this->headers())
            ->post($this->url . '/rest/v1/' . $table, $body);
    }

    public function patch(string $table, array $body, array $query = [])
    {
        return Http::withHeaders($this->headers())
            ->patch($this->tableUrl($table, $query), $body);
    }

    public function delete(string $table, array $query = [])
    {
        return Http::withHeaders($this->headers())
            ->delete($this->tableUrl($table, $query));
    }

    /** @deprecated use get/post/patch */
    public function from(string $table)
    {
        return Http::withHeaders($this->headers())
            ->baseUrl($this->url . '/rest/v1/' . $table);
    }
}
