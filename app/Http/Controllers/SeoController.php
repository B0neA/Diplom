<?php

namespace App\Http\Controllers;

use App\Services\AdminDeletionService;
use App\Services\SupabaseService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $host = rtrim(config('app.url'), '/');

        $lines = [
            'User-agent: *',
            'Disallow: /check',
            'Disallow: /auth',
            'Disallow: /profile',
            'Disallow: /admin',
            'Disallow: /forgot-password',
            'Disallow: /register',
            'Disallow: /signup',
            '',
            'Host: ' . $host,
            'Sitemap: ' . $host . '/sitemap.xml',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $host = rtrim(config('app.url'), '/');
        $supabase = app(SupabaseService::class);
        $active = app(AdminDeletionService::class)->activeOnly();

        $restaurants = $supabase->get('restaurants', array_merge([
            'select' => 'id,updated_at',
            'order' => 'id.asc',
        ], $active))->json() ?: [];

        $products = $supabase->get('products', array_merge([
            'select' => 'id,updated_at',
            'order' => 'id.asc',
        ], $active))->json() ?: [];

        $urls = [
            ['loc' => $host . '/', 'priority' => '1.0'],
            ['loc' => $host . '/restaurans', 'priority' => '0.9'],
        ];

        foreach ($restaurants as $r) {
            if (empty($r['id'])) {
                continue;
            }
            $urls[] = ['loc' => $host . '/restaurant/' . $r['id'] . '/reviews', 'priority' => '0.6'];
        }
        foreach ($products as $p) {
            if (empty($p['id'])) {
                continue;
            }
            $urls[] = ['loc' => $host . '/dish/' . $p['id'], 'priority' => '0.7'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc']) . '</loc>';
            $xml .= '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function favicon()
    {
        $supabase = app(SupabaseService::class);
        $row = $supabase->get('site_settings', [
            'id' => 'eq.1',
            'select' => 'favicon_icon,favicon_url',
        ])->json()[0] ?? [];

        $url = trim((string) ($row['favicon_icon'] ?? $row['favicon_url'] ?? ''));
        if ($url === '') {
            return response('', 404);
        }

        return redirect()->away($url);
    }
}
