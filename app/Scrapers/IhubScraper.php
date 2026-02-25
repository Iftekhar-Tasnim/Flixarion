<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * iHub scraper — http://ihub.live
 *
 * iHub presents a web portal. The site appears to timeout from outside the
 * BDIX network. When accessible, we scrape it like a standard HTML portal
 * looking for direct video links or category pages.
 *
 * testConnection: basic HTTP check.
 * crawl: fetch homepage, extract direct video links or follow category links.
 */
class IhubScraper implements BaseScraperInterface
{
    private const VIDEO_EXTENSIONS = ['mp4', 'mkv', 'avi', 'mov'];
    private const MAX_FILES = 200;

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'iHub Scraper';
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(8)->withUserAgent('Mozilla/5.0')->get($this->source->base_url);
            return $response->status() > 0;
        } catch (\Exception $e) {
            Log::debug("iHub unreachable", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting iHub crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');

        try {
            $response = Http::timeout(10)->withUserAgent('Mozilla/5.0')->get($base);
            if (!$response->successful())
                return $results;

            $html = $response->body();

            // 1. Direct video file links on the homepage
            preg_match_all('#href="(https?://[^"]+\.(mp4|mkv|avi|mov))"#i', $html, $direct);
            foreach ($direct[1] as $i => $url) {
                $ext = strtolower($direct[2][$i]);
                $filename = urldecode(basename(parse_url($url, PHP_URL_PATH)));
                $results->push([
                    'path' => $url,
                    'filename' => $filename,
                    'extension' => $ext,
                    'size' => null,
                ]);
                if ($results->count() >= self::MAX_FILES)
                    break;
            }

            // 2. If no direct links, follow category pages
            if ($results->isEmpty()) {
                preg_match_all('#href="(/(?:movies|films?|content|browse)[^"]*)"#i', $html, $catMatches);
                foreach (array_unique($catMatches[1]) as $catPath) {
                    $catUrl = $base . $catPath;
                    $catResponse = Http::timeout(8)->withUserAgent('Mozilla/5.0')->get($catUrl);
                    if (!$catResponse->successful())
                        continue;

                    preg_match_all('#href="(https?://[^"]+\.(mp4|mkv|avi|mov))"#i', $catResponse->body(), $catVideos);
                    foreach ($catVideos[1] as $j => $url) {
                        $ext = strtolower($catVideos[2][$j]);
                        $filename = urldecode(basename(parse_url($url, PHP_URL_PATH)));
                        $results->push([
                            'path' => $url,
                            'filename' => $filename,
                            'extension' => $ext,
                            'size' => null,
                        ]);
                        if ($results->count() >= self::MAX_FILES)
                            break 2;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("iHub crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("iHub crawl complete", ['found' => $results->count()]);
        return $results;
    }
}
