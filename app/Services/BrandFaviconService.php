<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BrandFaviconService
{
    private const ICON_PATH = 'M32 8c-2 0-3.5 1.2-4.2 3.1L22 28h-4.5c-1.9 0-3.5 1.6-3.5 3.5v3c0 1.9 1.6 3.5 3.5 3.5H20l-1.2 8.2c-.2 1.4.9 2.8 2.4 2.8h21.6c1.5 0 2.6-1.4 2.4-2.8L44 38h2.5c1.9 0 3.5-1.6 3.5-3.5v-3c0-1.9-1.6-3.5-3.5-3.5H42l-5.8-16.9C35.5 9.2 34 8 32 8zm0 6.2 4.6 13.3H27.4L32 14.2zM24 33h16v3H24v-3z';

    public function svgFromLogoUrl(?string $logoUrl): string
    {
        $logoUrl = trim((string) $logoUrl);
        if ($logoUrl === '') {
            return $this->svgWithMask(null);
        }

        try {
            $response = Http::timeout(8)->get($logoUrl);
            if (!$response->successful()) {
                return $this->svgWithMask(null);
            }

            $mime = $response->header('Content-Type') ?: 'image/png';
            if (str_contains($mime, 'svg')) {
                $mime = 'image/svg+xml';
            } elseif (str_contains($mime, 'png')) {
                $mime = 'image/png';
            } elseif (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
                $mime = 'image/jpeg';
            }

            $dataUri = 'data:' . $mime . ';base64,' . base64_encode($response->body());

            return $this->svgWithMask($dataUri);
        } catch (\Throwable) {
            return $this->svgWithMask(null);
        }
    }

    private function svgWithMask(?string $imageDataUri): string
    {
        $maskInner = $imageDataUri
            ? '<image href="' . htmlspecialchars($imageDataUri, ENT_QUOTES, 'UTF-8') . '" width="64" height="64" preserveAspectRatio="xMidYMid meet"/>'
            : '<path fill="white" d="' . self::ICON_PATH . '"/>';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <defs>
    <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffc46b"/>
      <stop offset="40%" stop-color="#ff9f2f"/>
      <stop offset="70%" stop-color="#ff6b00"/>
      <stop offset="100%" stop-color="#e65100"/>
    </linearGradient>
    <mask id="logoMask">{$maskInner}</mask>
  </defs>
  <rect width="64" height="64" fill="url(#brandGrad)" mask="url(#logoMask)"/>
</svg>
SVG;
    }
}
