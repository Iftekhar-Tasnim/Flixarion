<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EnrichBatchJob;
use App\Models\ShadowContentSource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EnrichmentController extends Controller
{
    use ApiResponse;

    /**
     * View enrichment worker status. Story #57
     */
    public function status(): JsonResponse
    {
        $statusCounts = ShadowContentSource::selectRaw('enrichment_status, count(*) as total')
            ->groupBy('enrichment_status')
            ->pluck('total', 'enrichment_status');

        return $this->successResponse([
            'paused' => Cache::get('enrichment:paused', false),
            'processing_rate' => Cache::get('enrichment:rate', 0),
            'shadow_counts' => $statusCounts,
        ]);
    }

    /**
     * Pause enrichment worker. Story #57
     */
    public function pause(): JsonResponse
    {
        Cache::put('enrichment:paused', true);
        return $this->successResponse(['message' => 'Enrichment paused.']);
    }

    /**
     * Resume enrichment worker. Story #57
     */
    public function resume(): JsonResponse
    {
        Cache::forget('enrichment:paused');
        return $this->successResponse(['message' => 'Enrichment resumed.']);
    }

    /**
     * Re-dispatch EnrichBatchJob for all PENDING shadow records.
     * Groups them by existing scan_batch_id so jobs are logically batched.
     */
    public function retryPending(): JsonResponse
    {
        $batches = ShadowContentSource::where('enrichment_status', 'pending')
            ->distinct()
            ->pluck('scan_batch_id');

        if ($batches->isEmpty()) {
            return $this->successResponse(['message' => 'No pending records found.', 'dispatched' => 0]);
        }

        foreach ($batches as $batchId) {
            EnrichBatchJob::dispatch($batchId);
        }

        return $this->successResponse([
            'message' => 'Enrichment re-dispatched for all pending records.',
            'dispatched' => $batches->count(),
            'batch_ids' => $batches->values(),
        ], status: 202);
    }

    /**
     * Re-queue UNMATCHED shadow records under a fresh batch so they are retried.
     * Useful after improving the filename parser or TMDb logic.
     */
    public function retryUnmatched(): JsonResponse
    {
        $count = ShadowContentSource::where('enrichment_status', 'unmatched')->count();

        if ($count === 0) {
            return $this->successResponse(['message' => 'No unmatched records found.', 'queued' => 0]);
        }

        // Reset status back to pending under a new shared batch ID
        $newBatchId = Str::uuid()->toString();

        ShadowContentSource::where('enrichment_status', 'unmatched')
            ->update([
                'enrichment_status' => 'pending',
                'scan_batch_id' => $newBatchId,
            ]);

        EnrichBatchJob::dispatch($newBatchId);

        return $this->successResponse([
            'message' => "{$count} unmatched records reset to pending and re-dispatched.",
            'queued' => $count,
            'new_batch_id' => $newBatchId,
        ], status: 202);
    }
}
