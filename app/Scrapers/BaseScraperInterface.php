<?php

namespace App\Scrapers;

use Illuminate\Support\Collection;

interface BaseScraperInterface
{
    /**
     * Get the human-readable name of the scraper.
     */
    public function getName(): string;

    /**
     * Test connection to the FTP source.
     * 
     * @return bool True if reachable and seemingly valid.
     */
    public function testConnection(): bool;

    /**
     * Crawl the FTP source and return a collection of findings.
     * 
     * @return \Illuminate\Support\Collection  Collection of associative arrays matching ShadowContentSource format.
     */
    public function crawl(): Collection;
}
