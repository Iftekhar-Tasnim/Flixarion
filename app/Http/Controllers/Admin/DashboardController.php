<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Source;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Aggregated platform stats. Story #49
     */
    public function index(): JsonResponse
    {
        return $this->successResponse([
            'users' => User::count(),
            'content' => Content::count(),
            'published' => Content::where('is_published', true)->count(),
            'sources' => Source::count(),
            'active_sources' => Source::where('is_active', true)->count(),
            'review_queue' => Content::whereIn('enrichment_status', ['flagged', 'pending'])
                ->orWhere('confidence_score', '<', 80)
                ->count(),
        ]);
    }
}
