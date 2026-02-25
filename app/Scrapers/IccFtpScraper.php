<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ICC FTP scraper.
 *
 * Base URL: http://10.16.100.244 — a private BDIX intranet IP.
 * This server is reachable only when connected to the ICC ISP network.
 *
 * The server may run an h5ai directory listing, Apache autoindex, or an Emby instance.
 * On first contact, we detect the server type and pick the right parsing strategy.
 *
 * testConnection will always fail from outside BDIX — that is expected.
 */
class IccFtpScraper implements BaseScraperInterface
{
    private const VIDEO_EXTENSIONS = ['mp4', 'mkv', 'avi', 'mov', 'webm'];

    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'ICC FTP Scraper';
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(8)->get($this->source->base_url);
            return $response->status() > 0;
        } catch (\Exception $e) {
            Log::debug("ICC FTP unreachable (outside BDIX network)", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting ICC FTP crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $base = rtrim($this->source->base_url, '/');

        try {
            $response = Http::timeout(10)->get($base);
            if (!$response->successful()) {
                return $results;
            }

            $html = $response->body();

            // Detect server type and pick strategy
            if (stripos($html, 'h5ai') !== false) {
                $this->crawlH5ai($base, $html, $results);
            } elseif (stripos($html, 'ServerName') !== false || stripos($html, 'emby') !== false) {
                $this->crawlEmby($base, $results);
            } else {
                // Treat as Apache autoindex
                $this->crawlAutoindex($base, $html, $results);
            }

        } catch (\Exception $e) {
            Log::error("ICC FTP crawl failed", ['error' => $e->getMessage()]);
        }

        Log::info("ICC FTP crawl complete", ['found' => $results->count()]);
        return $results;
    }

    private function crawlH5ai(string $base, string $html, Collection &$results): void
    {
        preg_match_all('#href="(/[^"]+\.(mp4|mkv|avi|mov|webm))"#i', $html, $matches);
        foreach ($matches[1] as $i => $path) {
            $ext = strtolower($matches[2][$i]);
            $filename = urldecode(basename($path));
            $results->push([
                'path' => $base . $path,
                'filename' => $filename,
                'extension' => $ext,
                'size' => null,
            ]);
        }
    }

    private function crawlEmby(string $base, Collection &$results): void
    {
        $apiKey = $this->source->config['api_key'] ?? null;
        if (!$apiKey)
            return;

        $response = Http::timeout(15)->get("{$base}/Items", [
            'api_key' => $apiKey,
            'Recursive' => 'true',
            'IncludeItemTypes' => 'Movie,Episode',
            'Fields' => 'Path',
            'Limit' => 500,
        ]);

        if (!$response->successful())
            return;

        foreach ($response->json()['Items'] ?? [] as $item) {
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
    }

    private function crawlAutoindex(string $base, string $html, Collection &$results): void
    {
        preg_match_all('#href="([^"]+\.(mp4|mkv|avi|mov|webm))"#i', $html, $matches);
        foreach ($matches[1] as $i => $href) {
            $url = str_starts_with($href, 'http') ? $href : $base . '/' . ltrim($href, '/');
            $filename = urldecode(basename($href));
            $ext = strtolower($matches[2][$i]);
            $results->push([
                'path' => $url,
                'filename' => $filename,
                'extension' => $ext,
                'size' => null,
            ]);
        }
    }
}
