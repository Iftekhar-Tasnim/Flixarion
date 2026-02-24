<?php

use App\Jobs\EnrichBatchJob;
use App\Models\ShadowContentSource;
use App\Models\Source;
use App\Models\SourceScanLog;
use App\Services\ContentEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('enrich batch job processes pending shadows sequentially', function () {
    $source = Source::factory()->create();
    $batchId = 'batch-123';

    ShadowContentSource::factory()->create(['source_id' => $source->id, 'scan_batch_id' => $batchId, 'enrichment_status' => 'pending']);
    ShadowContentSource::factory()->create(['source_id' => $source->id, 'scan_batch_id' => $batchId, 'enrichment_status' => 'pending']);
    ShadowContentSource::factory()->create(['source_id' => $source->id, 'scan_batch_id' => 'batch-456', 'enrichment_status' => 'pending']); // different batch

    SourceScanLog::create([
        'source_id' => $source->id,
        'phase' => 'collector',
        'status' => 'completed',
        'items_found' => 2,
        'started_at' => now(),
        'completed_at' => now()
    ]);

    // Mock the enricher service
    $enricher = Mockery::mock(ContentEnricher::class);
    // Should be called 2 times for batch-123
    $enricher->shouldReceive('enrich')->twice()->andReturnUsing(function ($shadow) {
        $shadow->update(['enrichment_status' => 'completed']);
    });

    // Run job
    $job = new EnrichBatchJob($batchId);
    $job->handle($enricher);

    // Verify
    expect(ShadowContentSource::where('scan_batch_id', $batchId)->where('enrichment_status', 'completed')->count())->toBe(2);
    // Test the different batch is still pending
    expect(ShadowContentSource::where('scan_batch_id', 'batch-456')->first()->enrichment_status)->toBe('pending');

    // Scan log updated
    $log = SourceScanLog::first();
    expect($log->items_matched)->toBe(2);
});

test('enrich batch job stops if paused via cache', function () {
    $source = Source::factory()->create();
    $batchId = 'batch-123';

    ShadowContentSource::factory()->count(3)->create(['source_id' => $source->id, 'scan_batch_id' => $batchId, 'enrichment_status' => 'pending']);

    // Set pause flag
    Cache::put('enrichment:paused', true);

    $enricher = Mockery::mock(ContentEnricher::class);
    // Should NOT be called because it's paused
    $enricher->shouldReceive('enrich')->never();

    $job = new EnrichBatchJob($batchId);
    $job->handle($enricher);

    // Verification
    expect(ShadowContentSource::where('scan_batch_id', $batchId)->where('enrichment_status', 'pending')->count())->toBe(3);
});
