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
     *
     * Default: shows ALL published content (no FTP filter).
     * Optional: ?sources=1,3,7 → filter to only content reachable on those sources.
     * Optional: ?only_available=true → same as above but simpler toggle for frontend.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Content::query()
            ->published()
            ->with(['genres', 'sourceLinks' => fn($q) => $q->active()->select('id', 'linkable_id', 'linkable_type', 'source_id', 'quality', 'status')]);

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

        // ── Filter by reachable sources (Story #10 — opt-in, NOT default) ──
        // Frontend sends reachable source IDs from Race Strategy: ?sources=1,3,7
        // OR uses ?only_available=true with ?sources= to filter unavailable content
        $sourceIds = null;
        if ($request->filled('sources')) {
            $sourceIds = array_map('intval', explode(',', $request->input('sources')));
        }

        if ($sourceIds && $request->boolean('only_available')) {
            // Hard filter: hide content not on any reachable source
            $query->whereHas('sourceLinks', function ($q) use ($sourceIds) {
                $q->where('status', 'active')
                    ->whereIn('source_id', $sourceIds);
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

        // ── Annotate each item with source availability info ──
        // Frontend uses source_ids to mark content as reachable/unavailable
        // without hiding it — users can still see what's there but not accessible
        $items = collect($contents->items())->map(function ($content) use ($sourceIds) {
            $contentSourceIds = $content->sourceLinks->pluck('source_id')->unique()->values()->toArray();
            $data = $content->toArray();
            $data['source_ids'] = $contentSourceIds;
            $data['has_any_source'] = !empty($contentSourceIds);

            // If frontend sent reachable source IDs, mark whether this content is reachable
            if ($sourceIds !== null) {
                $data['is_reachable'] = !empty(array_intersect($contentSourceIds, $sourceIds));
            }

            unset($data['source_links']); // already abstracted into source_ids
            return $data;
        });

        return $this->successResponse(
            $items,
            [
                'current_page' => $contents->currentPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
                'last_page' => $contents->lastPage(),
                'filter_mode' => ($sourceIds && $request->boolean('only_available')) ? 'available_only' : 'all',
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
                'sourceLinks' => fn($q) => $q->active()->with('source:id,name,base_url,scraper_type,priority'),
                'seasons.episodes.sourceLinks' => fn($q) => $q->active()->with('source:id,name,base_url,scraper_type,priority'),
            ])
            ->find($id);

        if (!$content) {
            return $this->errorResponse('Content not found.', status: 404);
        }

        return $this->successResponse($content);
    }
}

