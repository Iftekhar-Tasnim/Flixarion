<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class RoarZoneScraper implements BaseScraperInterface
{
    public function __construct(
        private Source $source
    ) {
    }

    public function getName(): string
    {
        return 'RoarZone (Emby) Scraper';
    }

    public function testConnection(): bool
    {
        try {
            // Emby System Info endpoint
            $url = rtrim($this->source->url, '/') . '/System/Info/Public';
            $response = Http::timeout(10)->get($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        return collect([
            // Mock data for RoarZone
            [
                'path' => 'emby://item/12345',
                'filename' => 'The.Batman.2022.1080p.mkv',
                'extension' => 'mkv',
            ]
        ]);
    }
}
