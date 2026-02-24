<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewQueueController extends Controller
{
    use ApiResponse;

    /**
     * List low-confidence / flagged content. Story #55
     */
    public function index(Request $request): JsonResponse
    {
        $items = Content::query()
            ->where(function ($q) {
                $q->whereIn('enrichment_status', ['flagged', 'pending'])
                    ->orWhere('confidence_score', '<', 80);
            })
            ->with('genres')
            ->orderBy('confidence_score')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return $this->successResponse(
            $items->items(),
            [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ]
        );
    }

    /**
     * Approve match. Story #55
     */
    public function approve(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        $content->update([
            'confidence_score' => 100,
            'enrichment_status' => 'approved',
            'is_published' => true,
        ]);

        return $this->successResponse($content->fresh());
    }

    /**
     * Correct match with new TMDb ID. Story #55
     */
    public function correct(Request $request, int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        $validated = $request->validate([
            'tmdb_id' => ['required', 'integer'],
        ]);

        $content->update([
            'tmdb_id' => $validated['tmdb_id'],
            'enrichment_status' => 'pending',
            'confidence_score' => 0,
        ]);

        // Placeholder — will dispatch SyncContentMetadataJob when enricher is built
        return $this->successResponse([
            'content_id' => $content->id,
            'message' => 'TMDb ID updated. Re-enrichment queued (placeholder).',
        ]);
    }

    /**
     * Reject match. Story #55
     */
    public function reject(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        $content->update([
            'is_published' => false,
            'enrichment_status' => 'rejected',
        ]);

        return $this->successResponse($content->fresh());
    }
}
