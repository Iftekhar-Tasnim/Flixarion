<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DflixScraper implements BaseScraperInterface
{
    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'Dflix Scraper';
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->source->base_url);
            return $response->successful() && stripos($response->body(), 'dflix') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting Dflix live crawl", ['source_id' => $this->source->id]);
        $results = collect();

        try {
            // 1. Fetch homepage to get recent movie IDs
            $homeResponse = Http::timeout(15)->withUserAgent('Mozilla/5.0')->get($this->source->base_url);
            if (!$homeResponse->successful()) {
                Log::error("Dflix crawl failed to load homepage");
                return $results;
            }

            // Extract IDs from href="/m/view/12345"
            if (preg_match_all('#href="/m/view/(\d+)"#i', $homeResponse->body(), $matches)) {
                $ids = array_unique($matches[1]);
                $idsToCrawl = array_slice($ids, 0, 10); // Limit to 10 for demo speed

                foreach ($idsToCrawl as $id) {
                    $pageResponse = Http::timeout(10)->withUserAgent('Mozilla/5.0')->get($this->source->base_url . "/m/view/{$id}");
                    if (!$pageResponse->successful())
                        continue;

                    // Extract the actual CDN link: href="https://cdn1.discoveryftp.net/.../Movie.2023.mkv"
                    if (preg_match('#href="(https?://[^"]+\.(mp4|mkv|avi))"#i', $pageResponse->body(), $linkMatch)) {
                        $streamUrl = $linkMatch[1];
                        $extension = strtolower($linkMatch[2]);
                        $filename = basename(parse_url($streamUrl, PHP_URL_PATH));

                        $results->push([
                            'path' => $streamUrl,
                            'filename' => urldecode($filename),
                            'extension' => $extension,
                            'size' => null, // Size requires a HEAD request, skip for speed
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Dflix crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("Dflix crawl complete", ['found' => $results->count()]);
        return $results;
    }
}
