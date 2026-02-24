<?php

namespace App\Scrapers;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CircleFtpScraper implements BaseScraperInterface
{
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
            $response = Http::timeout(10)->get($this->source->url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function crawl(): Collection
    {
        return collect();
    }
}
