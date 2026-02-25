<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CircleFTP scraper.
 *
 * CircleFTP runs a Node.js backend at http://new.circleftp.net:5000/api
 * The frontend is currently broken (static dist is missing), but the API
 * itself may still work on specific endpoints like /api/movies or /api/list.
 *
 * testConnection: checks if the server responds at all (any HTTP status).
 * crawl: attempts known working API endpoints and falls back gracefully.
 *
 * Known API paths (discovered via reverse engineering):
 *   GET /api/movies         — Returns JSON list of movies
 *   GET /api/movies?page=N  — Paginates
 *   GET /api/latest         — Recently added titles
 */
class CircleFtpScraper implements BaseScraperInterface
{
    private const KNOWN_ENDPOINTS = [
        '/movies',
        '/content/movies',
        '/list',
        '/latest',
        '/film',
    ];

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'CircleFTP Scraper';
    }

    public function testConnection(): bool
    {
        try {
            // Node.js API is up even when frontend is broken — any HTTP response means online
            $response = Http::timeout(10)->get($this->source->base_url);
            return $response->status() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting CircleFTP crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');

        try {
            // Try each known endpoint until one returns valid JSON
            foreach (self::KNOWN_ENDPOINTS as $endpoint) {
                $url = $base . $endpoint;
                $response = Http::timeout(10)->get($url);

                if (!$response->successful())
                    continue;

                $payload = $response->json();
                if (empty($payload))
                    continue;

                // Normalize: list could be top-level array or wrapped in data/movies key
                $items = $payload['data'] ?? $payload['movies'] ?? $payload['items'] ?? (is_array($payload) ? $payload : []);

                if (!is_array($items) || empty($items))
                    continue;

                foreach ($items as $item) {
                    $path = $item['stream_url'] ?? $item['url'] ?? $item['path'] ?? null;
                    $filename = $item['filename'] ?? $item['title'] ?? null;

                    if (!$path || !$filename)
                        continue;

                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'mp4');

                    $results->push([
                        'path' => $path,
                        'filename' => $filename,
                        'extension' => $ext,
                        'size' => $item['size'] ?? null,
                    ]);
                }

                if ($results->isNotEmpty())
                    break; // Found data, stop probing
            }

        } catch (\Exception $e) {
            Log::error("CircleFTP crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("CircleFTP crawl complete", ['found' => $results->count()]);
        return $results;
    }
}
