<?php

namespace App\Http\Controllers;

use App\Models\ShadowContentSource;
use App\Models\Source;
use App\Models\SourceScanLog;
use App\Services\FileValidator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScanResultController extends Controller
{
    use ApiResponse;

    public function __construct(
        private FileValidator $validator,
    ) {
    }

    /**
     * Receive scan results from frontend (client-triggered scan).
     * Story #22 — No auth required. Anyone on BDIX can contribute.
     *
     * POST /api/sources/{id}/scan-results
     * Body: { files: [{ path, filename, extension, size?, subtitle_paths? }] }
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $source = Source::find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:5000'],
            'files.*.path' => ['required', 'string', 'max:2048'],
            'files.*.filename' => ['required', 'string', 'max:2048'],
            'files.*.extension' => ['required', 'string', 'max:10'],
            'files.*.size' => ['nullable', 'integer', 'min:0'],
        ]);

        $batchId = Str::uuid()->toString();
        $now = now();
        $inserted = 0;
        $skipped = 0;
        $invalid = 0;

        // Get existing file paths for this source to do delta sync
        $existingPaths = ShadowContentSource::where('source_id', $id)
            ->pluck('file_path')
            ->flip()
            ->all();

        $rows = [];

        foreach ($validated['files'] as $file) {
            // Skip invalid video extensions
            if (!$this->validator->isValidVideo($file['extension'])) {
                $invalid++;
                continue;
            }

            // Skip already-known files (delta sync)
            if (isset($existingPaths[$file['path']])) {
                $skipped++;
                continue;
            }

            // Auto-link subtitles
            $subtitles = $this->validator->findSubtitles($validated['files'], $file['filename']);

            $rows[] = [
                'source_id' => $id,
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

        // Bulk insert
        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                ShadowContentSource::insert($chunk);
            }
        }

        // Log scan
        SourceScanLog::create([
            'source_id' => $id,
            'phase' => 'collector',
            'status' => 'completed',
            'items_found' => count($validated['files']),
            'items_matched' => $inserted,
            'items_failed' => $invalid,
            'started_at' => $now,
            'completed_at' => now(),
        ]);

        // Update source last_scan_at
        $source->update(['last_scan_at' => $now]);

        // Dispatch EnrichBatchJob
        \App\Jobs\EnrichBatchJob::dispatch($batchId);

        return $this->successResponse([
            'batch_id' => $batchId,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'invalid' => $invalid,
            'total' => count($validated['files']),
        ], status: 201);
    }
}
