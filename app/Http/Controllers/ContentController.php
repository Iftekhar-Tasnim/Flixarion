<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ApiResponse;

    /**
     * Browse paginated content list with filters and sorting.
     * Stories #5, #6, #8, #10
     */
    public function index(Request $request): JsonResponse
    {
        $query = Content::query()
            ->published()
            ->with('genres');

        // ── Filters (Story #6) ──
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('slug', $request->input('genre'));
            });
        }

        if ($request->filled('year')) {
            $query->where('year', $request->input('year'));
        }

        // ── Available Only (Story #10) ──
        if ($request->boolean('available_only')) {
            $query->whereHas('sourceLinks', function ($q) {
                $q->where('status', 'active');
            });
        }

        // ── Sorting (Story #8) ──
        $sort = $request->input('sort', 'recent');
        $query = match ($sort) {
            'trending' => $query->orderByDesc('watch_count'),
            'popular' => $query->orderByDesc('rating'),
            'recent' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        // ── Pagination (Story #5) ──
        $perPage = min((int) $request->input('per_page', 20), 50);
        $contents = $query->paginate($perPage);

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
     * Search content by title and alternative titles.
     * Story #7
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $query = $request->input('q');
        $perPage = min((int) $request->input('per_page', 20), 50);

        $contents = Content::query()
            ->published()
            ->with('genres')
            ->where(function ($q) use ($query) {
                $driver = $q->getConnection()->getDriverName();

                if ($driver === 'pgsql') {
                    $q->where('title', 'ILIKE', "%{$query}%")
                        ->orWhere('original_title', 'ILIKE', "%{$query}%")
                        ->orWhereRaw("alternative_titles::text ILIKE ?", ["%{$query}%"]);
                } else {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('original_title', 'LIKE', "%{$query}%");
                }
            })
            ->orderByDesc('rating')
            ->paginate($perPage);

        return $this->successResponse(
            $contents->items(),
            [
                'query' => $query,
                'current_page' => $contents->currentPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
                'last_page' => $contents->lastPage(),
            ]
        );
    }

    /**
     * Get full content detail with seasons, episodes, and source links.
     * Story #9
     */
    public function show(int $id): JsonResponse
    {
        $content = Content::query()
            ->published()
            ->with([
                'genres',
                'sourceLinks' => fn($q) => $q->active()->with('source'),
                'seasons.episodes.sourceLinks' => fn($q) => $q->active()->with('source'),
            ])
            ->find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        return $this->successResponse($content);
    }
}
