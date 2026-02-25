<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RoarZone scraper — connects to a private Emby / Jellyfin media server.
 *
 * Emby API reference: https://dev.emby.media/doc/restapi/
 * Required config fields in Source.config JSON:
 *   - api_key: Emby API key (generated from Emby Dashboard → API Keys)
 *   - user_id: Emby user ID (optional, falls back to API key auth)
 *
 * testConnection uses /System/Info/Public (no auth required on most Emby installs).
 * crawl uses /Items?Recursive=true&IncludeItemTypes=Movie (requires API key).
 */
class RoarZoneScraper implements BaseScraperInterface
{
    private const MAX_ITEMS = 500;
    private const PAGE_SIZE = 100;

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'RoarZone (Emby) Scraper';
    }

    private function apiKey(): ?string
    {
        return $this->source->config['api_key'] ?? null;
    }

    public function testConnection(): bool
    {
        try {
            $base = rtrim($this->source->base_url, '/');
            // /System/Info/Public works without authentication on most Emby instances
            $response = Http::timeout(10)->get("{$base}/System/Info/Public");
            if ($response->successful()) {
                return isset($response->json()['ServerName']) || isset($response->json()['Version']);
            }
            // Fall back: check if server responds at all (could be auth-protected)
            return in_array($response->status(), [200, 401, 302]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting RoarZone (Emby) crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');
        $apiKey = $this->apiKey();

        if (!$apiKey) {
            Log::warning("RoarZone crawl skipped — no api_key in source config", [
                'source_id' => $this->source->id,
            ]);
            return $results;
        }

        try {
            $startIndex = 0;

            do {
                $response = Http::timeout(15)->get("{$base}/Items", [
                    'api_key' => $apiKey,
                    'Recursive' => 'true',
                    'IncludeItemTypes' => 'Movie',
                    'Fields' => 'Path,ProviderIds',
                    'StartIndex' => $startIndex,
                    'Limit' => self::PAGE_SIZE,
                ]);

                if (!$response->successful()) {
                    Log::error("RoarZone Items API failed", ['status' => $response->status()]);
                    break;
                }

                $data = $response->json();
                $items = $data['Items'] ?? [];

                foreach ($items as $item) {
                    $path = $item['Path'] ?? null;
                    if (!$path)
                        continue;

                    $filename = basename($path);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    $results->push([
                        'path' => $path,
                        'filename' => $filename,
                        'extension' => $ext,
                        'size' => null,
                    ]);
                }

                $startIndex += self::PAGE_SIZE;
                $total = $data['TotalRecordCount'] ?? 0;

            } while (count($items) === self::PAGE_SIZE && $startIndex < self::MAX_ITEMS && $startIndex < $total);

        } catch (\Exception $e) {
            Log::error("RoarZone crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("RoarZone crawl complete", ['found' => $results->count()]);
        return $results;
    }
}
