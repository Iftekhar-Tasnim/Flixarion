<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Favorite;
use App\Models\Watchlist;
use App\Models\WatchHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLibraryController extends Controller
{
    use ApiResponse;

    /**
     * Combined library endpoint — watchlist + favorites + recent history.
     * Matrix #12, API #8
     */
    public function library(Request $request): JsonResponse
    {
        $user = $request->user();

        $watchlist = $user->watchlists()
            ->with('content.genres')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $favorites = $user->favorites()
            ->with('content.genres')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $recentlyWatched = $user->watchHistory()
            ->with(['content.genres', 'episode'])
            ->orderByDesc('played_at')
            ->limit(10)
            ->get();

        return $this->successResponse([
            'watchlist' => $watchlist,
            'favorites' => $favorites,
            'recently_watched' => $recentlyWatched,
        ]);
    }

    // ── Watchlist (Story #14) ──

    public function watchlist(Request $request): JsonResponse
    {
        $items = $request->user()
            ->watchlists()
            ->with('content.genres')
            ->latest('created_at')
            ->paginate(20);

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

    public function addToWatchlist(Request $request): JsonResponse
    {
        $request->validate(['content_id' => ['required', 'exists:contents,id']]);

        $watchlist = Watchlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'content_id' => $request->input('content_id'),
        ]);

        return $this->successResponse($watchlist, status: 201);
    }

    public function removeFromWatchlist(Request $request, int $contentId): JsonResponse
    {
        $deleted = Watchlist::where('user_id', $request->user()->id)
            ->where('content_id', $contentId)
            ->delete();

        if (!$deleted) {
            return $this->errorResponse('Not found in watchlist.', status: 404);
        }

        return response()->json(null, 204);
    }

    // ── Favorites (Story #15) ──

    public function favorites(Request $request): JsonResponse
    {
        $items = $request->user()
            ->favorites()
            ->with('content.genres')
            ->latest('created_at')
            ->paginate(20);

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

    public function addToFavorites(Request $request): JsonResponse
    {
        $request->validate(['content_id' => ['required', 'exists:contents,id']]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'content_id' => $request->input('content_id'),
        ]);

        return $this->successResponse($favorite, status: 201);
    }

    public function removeFromFavorites(Request $request, int $contentId): JsonResponse
    {
        $deleted = Favorite::where('user_id', $request->user()->id)
            ->where('content_id', $contentId)
            ->delete();

        if (!$deleted) {
            return $this->errorResponse('Not found in favorites.', status: 404);
        }

        return response()->json(null, 204);
    }

    // ── Watch History (Stories #16, #17, #18) ──

    public function recordPlay(Request $request): JsonResponse
    {
        $request->validate([
            'content_id' => ['required', 'exists:contents,id'],
            'episode_id' => ['nullable', 'exists:episodes,id'],
        ]);

        WatchHistory::create([
            'user_id' => $request->user()->id,
            'content_id' => $request->input('content_id'),
            'episode_id' => $request->input('episode_id'),
        ]);

        // Increment watch count on actual play (Matrix #17)
        Content::where('id', $request->input('content_id'))->increment('watch_count');

        return $this->successResponse(['recorded' => true], status: 201);
    }

    public function history(Request $request): JsonResponse
    {
        $items = $request->user()
            ->watchHistory()
            ->with(['content.genres', 'episode'])
            ->orderByDesc('played_at')
            ->paginate(20);

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
}
