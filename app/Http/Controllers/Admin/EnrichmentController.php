<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class EnrichmentController extends Controller
{
    use ApiResponse;

    /**
     * View enrichment worker status. Story #57
     */
    public function status(): JsonResponse
    {
        return $this->successResponse([
            'paused' => Cache::get('enrichment:paused', false),
            'pending_count' => \App\Models\Content::where('enrichment_status', 'pending')->count(),
            'processing_rate' => Cache::get('enrichment:rate', 0),
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
}
