<?php

namespace App\Jobs;

use App\Models\ShadowContentSource;
use App\Models\SourceScanLog;
use App\Services\ContentEnricher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnrichBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour max

    public function __construct(
        private string $batchId,
    ) {
    }

    public function handle(ContentEnricher $enricher): void
    {
        Log::info("EnrichBatchJob started", ['batch_id' => $this->batchId]);

        $processed = 0;
        $matched = 0;
        $failed = 0;

        // Process pending entries — newest first (Story #38)
        $entries = ShadowContentSource::where('scan_batch_id', $this->batchId)
            ->pending()
            ->orderByDesc('id')
            ->get();

        foreach ($entries as $shadow) {
            /** @var \App\Models\ShadowContentSource $shadow */
            // Check pause flag (Story #57)
            if (Cache::get('enrichment:paused', false)) {
                Log::info("Enrichment paused, stopping batch", [
                    'batch_id' => $this->batchId,
                    'processed' => $processed,
                ]);
                break;
            }

            $enricher->enrich($shadow);
            $processed++;

            $status = $shadow->fresh()->enrichment_status;
            if ($status === 'completed') {
                $matched++;
            } elseif (in_array($status, ['failed', 'unmatched'])) {
                $failed++;
            }

            // Update processing rate in cache
            Cache::put('enrichment:rate', $processed, 300);
        }

        // Update scan log
        SourceScanLog::where('source_id', function ($query) {
            $query->select('source_id')
                ->from('shadow_content_sources')
                ->where('scan_batch_id', $this->batchId)
                ->limit(1);
        })
            ->where('phase', 'collector')
            ->latest('started_at')
            ->first()
                ?->update([
                'items_matched' => $matched,
                'items_failed' => $failed,
            ]);

        Log::info("EnrichBatchJob completed", [
            'batch_id' => $this->batchId,
            'processed' => $processed,
            'matched' => $matched,
            'failed' => $failed,
        ]);
    }
}
