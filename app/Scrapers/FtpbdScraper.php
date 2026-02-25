<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FTPBD scraper — connects to an Emby/Jellyfin server.
 *
 * Base URL: http://media.ftpbd.net:8096
 * Type    : Emby Media Server (private BDIX network)
 *
 * This server is only reachable from within the BDIX network.
 * testConnection returns false when running from outside the network.
 *
 * Config fields (stored in Source.config JSON):
 *   - api_key  : Emby API key
 *   - user_id  : Emby user ID (optional)
 */
class FtpbdScraper implements BaseScraperInterface
{
    private const PAGE_SIZE = 100;
    private const MAX_ITEMS = 1000;

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'FTPBD (Emby) Scraper';
    }

    private function apiKey(): ?string
    {
        return $this->source->config['api_key'] ?? null;
    }

    public function testConnection(): bool
    {
        try {
            $base = rtrim($this->source->base_url, '/');
            $response = Http::timeout(8)->get("{$base}/System/Info/Public");
            // 200 with ServerName = Emby is definitely up
            if ($response->successful() && isset($response->json()['ServerName'])) {
                return true;
            }
            // 401 = Server is up but requires auth (still counts as online)
            return $response->status() === 401;
        } catch (\Exception $e) {
            Log::debug("FTPBD testConnection failed (likely unreachable from outside BDIX)", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting FTPBD (Emby) crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');
        $apiKey = $this->apiKey();

        if (!$apiKey) {
            Log::warning("FTPBD crawl skipped — no api_key configured", ['source_id' => $this->source->id]);
            return $results;
        }

        try {
            $startIndex = 0;

            do {
                $response = Http::timeout(15)->get("{$base}/Items", [
                    'api_key' => $apiKey,
                    'Recursive' => 'true',
                    'IncludeItemTypes' => 'Movie,Episode',
                    'Fields' => 'Path',
                    'StartIndex' => $startIndex,
                    'Limit' => self::PAGE_SIZE,
                ]);

                if (!$response->successful())
                    break;

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

            } while (count($items) === self::PAGE_SIZE && $startIndex < $total && $startIndex < self::MAX_ITEMS);

        } catch (\Exception $e) {
            Log::error("FTPBD crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("FTPBD crawl complete", ['found' => $results->count()]);
        return $results;
    }
}
