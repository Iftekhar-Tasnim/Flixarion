<?php

namespace App\Jobs;

use App\Models\ShadowContentSource;
use App\Models\Source;
use App\Models\SourceScanLog;
use App\Scrapers\ScraperFactory;
use App\Services\FileValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScanSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour max

    public function __construct(
        private int $sourceId,
    ) {
    }

    public function handle(FileValidator $validator): void
    {
        $source = Source::find($this->sourceId);
        if (!$source) {
            return;
        }

        $now = now();
        $batchId = Str::uuid()->toString();

        $scanLog = SourceScanLog::create([
            'source_id' => $source->id,
            'phase' => 'collector',
            'status' => 'pending',
            'started_at' => $now,
        ]);

        try {
            $scraper = ScraperFactory::make($source);
            $files = $scraper->crawl();

            $inserted = 0;
            $skipped = 0;
            $invalid = 0;

            // Get existing file paths for delta sync
            $existingPaths = ShadowContentSource::where('source_id', $source->id)
                ->pluck('file_path')
                ->flip()
                ->all();

            $rows = [];
            $allFiles = $files->toArray(); // Needed for subtitle auto-linking

            foreach ($files as $file) {
                if (!$validator->isValidVideo($file['extension'] ?? '')) {
                    $invalid++;
                    continue;
                }

                if (isset($existingPaths[$file['path']])) {
                    $skipped++;
                    continue;
                }

                $subtitles = $validator->findSubtitles($allFiles, $file['filename'] ?? '');

                $rows[] = [
                    'source_id' => $source->id,
                    'raw_filename' => $file['filename'],
                    'file_path' => $file['path'],
                    'file_extension' => strtolower($file['extension']),
                    'file_size' => $file['size'] ?? null,
                    'detected_encoding' => null,
                    'subtitle_paths' => !empty($subtitles) ? json_encode($subtitles) : null,
                    'scan_batch_id' => $batchId,
                    'enrichment_status' => 'pending',
                    'created_at' => $now,
                ];

                $inserted++;
            }

            if (!empty($rows)) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    ShadowContentSource::insert($chunk);
                }
            }

            // Update log
            $scanLog->update([
                'status' => 'completed',
                'items_found' => $files->count(),
                'items_matched' => $inserted,
                'items_failed' => $invalid,
                'completed_at' => now(),
            ]);

            $source->update(['last_scan_at' => $now]);

            // Kick off enricher for this batch
            if ($inserted > 0) {
                EnrichBatchJob::dispatch($batchId);
            }

        } catch (\Exception $e) {
            Log::error("Scraper failed for source {$source->id}: " . $e->getMessage());
            $scanLog->update([
                'status' => 'failed',
                'error_log' => current(explode("\n", $e->getMessage())),
                'completed_at' => now(),
            ]);
        }
    }
}
