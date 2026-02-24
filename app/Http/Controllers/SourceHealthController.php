<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\SourceHealthReport;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SourceHealthController extends Controller
{
    use ApiResponse;

    /**
     * List all sources (public, for frontend Race Strategy ping targets).
     */
    public function index(): JsonResponse
    {
        $sources = Source::query()
            ->active()
            ->select('id', 'name', 'base_url', 'health_score', 'priority')
            ->orderBy('priority')
            ->get();

        return $this->successResponse($sources);
    }

    /**
     * Accept anonymous health reports from frontend Race Strategy.
     * Story #20 — No auth required, no IP logging.
     *
     * Body: { isp_name, sources: [{ source_id, is_reachable, response_time_ms }] }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isp_name' => ['required', 'string', 'max:100'],
            'sources' => ['required', 'array', 'min:1', 'max:50'],
            'sources.*.source_id' => ['required', 'exists:sources,id'],
            'sources.*.is_reachable' => ['required', 'boolean'],
            'sources.*.response_time_ms' => ['nullable', 'integer', 'min:0', 'max:30000'],
        ]);

        $reports = [];
        $now = now();

        foreach ($validated['sources'] as $entry) {
            $reports[] = [
                'source_id' => $entry['source_id'],
                'isp_name' => $validated['isp_name'],
                'is_reachable' => $entry['is_reachable'],
                'response_time_ms' => $entry['response_time_ms'] ?? null,
                'reported_at' => $now,
            ];
        }

        SourceHealthReport::insert($reports);

        return $this->successResponse(['reported' => count($reports)], status: 201);
    }
}
