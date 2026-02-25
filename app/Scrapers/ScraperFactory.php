<?php

namespace App\Scrapers;

use App\Models\Source;
use InvalidArgumentException;

class ScraperFactory
{
    /**
     * Create a scraper instance based on the source's scraper_type.
     */
    public static function make(Source $source): BaseScraperInterface
    {
        return match ($source->scraper_type) {
            'dflix' => new DflixScraper($source),
            'dhakaflix' => new DhakaFlixMovieScraper($source),
            'roarzone' => new RoarZoneScraper($source),
            'ftpbd' => new FtpbdScraper($source),
            'circleftp' => new CircleFtpScraper($source),
            'iccftp' => new IccFtpScraper($source),
            'ihub' => new IhubScraper($source),
            default => throw new InvalidArgumentException("Unknown scraper type: {$source->scraper_type}"),
        };
    }
}
