<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DhakaFlix scraper — crawls the h5ai HTTP directory listing.
 *
 * Structure:
 *   Base URL: http://172.16.50.14 (Movie) or http://172.16.50.12 (Series)
 *   Movie root : /DHAKA-FLIX-14/{Category}/{Year}/
 *   Series root: /DHAKA-FLIX-12/TV-WEB-Series/{Show}/{Season}/
 *
 * h5ai renders standard HTML anchor tags for each file/folder.
 * We walk the directory tree by following href entries that end in "/"
 * and collecting entries that end in known video extensions.
 */
class DhakaFlixMovieScraper implements BaseScraperInterface
{
    private const VIDEO_EXTENSIONS = ['mp4', 'mkv', 'avi', 'mov', 'webm'];
    private const MAX_DEPTH = 3;   // year/movie/file
    private const MAX_FILES = 200; // safety cap per crawl

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'DhakaFlix (h5ai) Scraper';
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->source->base_url);
            // h5ai dir listing contains its own branding
            return $response->successful() && stripos($response->body(), 'h5ai') !== false
                || $response->successful() && stripos($response->body(), 'DhakaFlix') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting DhakaFlix crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');

        try {
            // Discover category roots from the homepage navlinks
            $homeHtml = Http::timeout(10)->get($base)->body();
            $roots = $this->extractCategoryRoots($homeHtml, $base);

            foreach ($roots as $rootUrl) {
                $this->crawlDirectory($rootUrl, $base, $results, 0);
                if ($results->count() >= self::MAX_FILES)
                    break;
            }
        } catch (\Exception $e) {
            Log::error("DhakaFlix crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("DhakaFlix crawl complete", ['found' => $results->count()]);
        return $results;
    }

    /**
     * Extract category directory URLs from the homepage <a href> links.
     * Targets entries like /DHAKA-FLIX-14/Hindi%20Movies/
     */
    private function extractCategoryRoots(string $html, string $base): array
    {
        preg_match_all('#href="(https?://[^"]+/)"#i', $html, $matches);
        $roots = [];
        foreach ($matches[1] as $url) {
            if (str_contains($url, 'DHAKA-FLIX')) {
                $roots[] = $url;
            }
        }
        return array_unique($roots);
    }

    /**
     * Recursively crawl an h5ai directory listing.
     * Entries ending with "/" are sub-directories; others are checked for video extensions.
     */
    private function crawlDirectory(string $dirUrl, string $base, Collection &$results, int $depth): void
    {
        if ($depth > self::MAX_DEPTH || $results->count() >= self::MAX_FILES) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($dirUrl);
            if (!$response->successful())
                return;

            $html = $response->body();

            // Extract all href links
            preg_match_all('#href="(/[^"]+)"#i', $html, $matches);
            $links = array_unique($matches[1]);

            foreach ($links as $path) {
                $url = $base . $path;

                if (str_ends_with($path, '/')) {
                    // Skip navigation/system paths
                    if (str_contains($path, '_h5ai') || $path === '/')
                        continue;
                    // Recurse into subdirectory (only non-parent)
                    if (substr_count($path, '/') > substr_count(parse_url($dirUrl, PHP_URL_PATH), '/')) {
                        $this->crawlDirectory($url, $base, $results, $depth + 1);
                    }
                } else {
                    // Check if it's a video file
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($ext, self::VIDEO_EXTENSIONS)) {
                        $filename = urldecode(basename($path));
                        $results->push([
                            'path' => $url,
                            'filename' => $filename,
                            'extension' => $ext,
                            'size' => null,
                        ]);
                        if ($results->count() >= self::MAX_FILES)
                            return;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("DhakaFlix dir crawl failed", ['url' => $dirUrl, 'error' => $e->getMessage()]);
        }
    }
}
