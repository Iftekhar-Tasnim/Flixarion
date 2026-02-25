<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    use ApiResponse;

    /**
     * CORS Proxy — Story #82 (BR-06.12)
     *
     * Fetches a BDIX URL server-side and returns the raw response,
     * bypassing browser CORS restrictions so the frontend can read
     * FTP directory listings (h5ai HTML, Emby JSON, autoindex HTML).
     *
     * Security:
     *  - URL must start with a registered source's base_url (whitelist)
     *  - No auth required (public — any user on BDIX can trigger this)
     *  - Rate limited by the global 60 req/min middleware
     *
     * Usage:
     *   GET /api/proxy?url=http://172.16.50.14/DHAKA-FLIX-14/Hindi%20Movies/2025/
     */
    public function fetch(Request $request): Response|JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        $targetUrl = $request->input('url');

        // ── Whitelist check: URL must belong to a registered active source ──
        $allowedBases = Source::active()->pluck('base_url')->toArray();

        $allowed = collect($allowedBases)->contains(
            fn($base) => str_starts_with($targetUrl, rtrim($base, '/'))
        );

        if (!$allowed) {
            return $this->errorResponse(
                'URL is not from a registered BDIX source.',
                status: 403
            );
        }

        // ── Fetch the target URL server-side ──
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Flixarion/1.0)',
                    'Accept' => 'text/html,application/json,*/*',
                ])
                ->get($targetUrl);

            $contentType = $response->header('Content-Type') ?? 'text/html; charset=utf-8';
            $body = $response->body();

            return response($body, $response->status())
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('X-Proxied-Url', $targetUrl)
                ->header('X-Proxy-Status', 'ok');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to reach the source: ' . $e->getMessage(),
                status: 502
            );
        }
    }
}
