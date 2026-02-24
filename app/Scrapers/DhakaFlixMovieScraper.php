<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DhakaFlixMovieScraper implements BaseScraperInterface
{
    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'DhakaFlix Movie Scraper';
    }

    public function testConnection(): bool
    {
        try {
            // As per project plan #4.3
            $url = rtrim($this->source->url, '/') . '/api/content/movies?page=1';
            $response = Http::timeout(10)->get($url);
            return $response->successful() && isset($response->json()['data']);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        Log::info("Starting DhakaFlix Movies crawl", ['source_id' => $this->source->id]);

        $results = collect();
        $baseUrl = rtrim($this->source->url, '/');
        $page = 1;

        try {
            do {
                $response = Http::timeout(15)->get("{$baseUrl}/api/content/movies", [
                    'page' => $page,
                ]);

                if (!$response->successful()) {
                    break;
                }

                $data = $response->json();
                $items = $data['data'] ?? [];

                if (empty($items)) {
                    break;
                }

                foreach ($items as $item) {
                    $results->push([
                        'path' => $item['stream_url'] ?? $item['url'] ?? "dhakaflix://movie/{$item['id']}",
                        'filename' => $item['title'] . '.' . ($item['year'] ?? date('Y')) . '.mp4',
                        'extension' => 'mp4',
                    ]);
                }

                $page++;

                // Safety limit for demo
                if ($page > 5)
                    break;

            } while (isset($data['next_page_url']));

        } catch (\Exception $e) {
            Log::error("DhakaFlix Movies crawl failed", ['error' => $e->getMessage()]);
        }

        return $results;
    }
}
