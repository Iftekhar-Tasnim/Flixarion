<?php

use App\Jobs\EnrichBatchJob;
use App\Jobs\ScanSourceJob;
use App\Models\ShadowContentSource;
use App\Models\Source;
use App\Models\SourceScanLog;
use App\Scrapers\BaseScraperInterface;
use App\Scrapers\ScraperFactory;
use App\Services\FileValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('scan source job successfully crawls and handles delta sync', function () {
    Queue::fake([EnrichBatchJob::class]);

    $source = Source::factory()->create([
        'scraper_type' => 'dflix',
    ]);

    // Create an existing shadow entry for delta sync testing
    ShadowContentSource::factory()->create([
        'source_id' => $source->id,
        'file_path' => '/movies/OldMovie.mp4',
    ]);

    // Mock ScraperFactory & Scraper
    $mockScraper = Mockery::mock(BaseScraperInterface::class);
    $mockScraper->shouldReceive('crawl')->once()->andReturn(collect([
        [
            'path' => '/movies/OldMovie.mp4', // Should be skipped
            'filename' => 'OldMovie.mp4',
            'extension' => 'mp4',
            'size' => 100,
        ],
        [
            'path' => '/movies/NewMovie.mkv', // Should be added
            'filename' => 'NewMovie.mkv',
            'extension' => 'mkv',
            'size' => 200,
        ],
        [
            'path' => '/movies/BadFile.exe', // Should be ignored
            'filename' => 'BadFile.exe',
            'extension' => 'exe',
            'size' => 10,
        ]
    ]));

    // Bind custom factory logic for the test using alias
    Mockery::mock('alias:' . ScraperFactory::class)
        ->shouldReceive('make')
        ->with(Mockery::on(fn($s) => $s->id === $source->id))
        ->andReturn($mockScraper);

    // Run Job
    $job = new ScanSourceJob($source->id);
    $job->handle(app(FileValidator::class));

    // Assert shadow mapping
    expect(ShadowContentSource::where('source_id', $source->id)->count())->toBe(2); // 1 old + 1 new
    $this->assertDatabaseHas('shadow_content_sources', [
        'file_path' => '/movies/NewMovie.mkv',
        'enrichment_status' => 'pending',
    ]);

    // Assert logs
    $log = SourceScanLog::where('source_id', $source->id)->first();
    expect($log->status)->toBe('completed');
    expect($log->items_found)->toBe(3);
    expect($log->items_matched)->toBe(1); // 1 new item inserted
    expect($log->items_failed)->toBe(1);  // 1 invalid extension

    // Assert Queue
    Queue::assertPushed(EnrichBatchJob::class);
});
