<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ApiResponse;

    /**
     * List all content with enrichment status. Story #52
     */
    public function index(Request $request): JsonResponse
    {
        $query = Content::query()->with('genres');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('enrichment_status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $contents = $query->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return $this->successResponse(
            $contents->items(),
            [
                'current_page' => $contents->currentPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
                'last_page' => $contents->lastPage(),
            ]
        );
    }

    /**
     * Update content flags. Story #54
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        $validated = $request->validate([
            'is_featured' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $content->update($validated);

        return $this->successResponse($content->fresh());
    }

    /**
     * Delete content. Story #52
     */
    public function destroy(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        $content->delete();

        return response()->json(null, 204);
    }

    /**
     * Force metadata re-sync. Story #53 (placeholder)
     */
    public function resync(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        // Placeholder — actual dispatch when Enricher is built
        return $this->successResponse([
            'content_id' => $content->id,
            'message' => 'Metadata re-sync queued (placeholder — enricher not yet implemented).',
        ], status: 202);
    }
}
