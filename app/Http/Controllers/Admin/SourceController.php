<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    use ApiResponse;

    /**
     * List all sources with scan logs and health. Story #50
     */
    public function index(): JsonResponse
    {
        $sources = Source::query()
            ->with('latestScanLog')
            ->withCount('sourceLinks')
            ->orderBy('priority')
            ->get();

        return $this->successResponse($sources);
    }

    /**
     * Create a new source. Story #50
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:sources,name'],
            'base_url' => ['required', 'url', 'max:255'],
            'scraper_type' => ['required', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $source = Source::create(array_merge($validated, [
            'is_active' => false,
        ]));

        return $this->successResponse($source, status: 201);
    }

    /**
     * Show source detail with scan history and health reports. Stories #50, #51
     */
    public function show(int $id): JsonResponse
    {
        $source = Source::with(['scanLogs', 'healthReports' => fn($q) => $q->latest('reported_at')->limit(50)])
            ->withCount('sourceLinks')
            ->find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        return $this->successResponse($source);
    }

    /**
     * Update a source. Story #50
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $source = Source::find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'unique:sources,name,' . $id],
            'base_url' => ['sometimes', 'url', 'max:255'],
            'scraper_type' => ['sometimes', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $source->update($validated);

        return $this->successResponse($source->fresh());
    }

    /**
     * Delete a source. Story #50
     */
    public function destroy(int $id): JsonResponse
    {
        $source = Source::find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        $source->delete();

        return response()->json(null, 204);
    }

    /**
     * Test source connection. Story #50
     */
    public function testConnection(int $id): JsonResponse
    {
        $source = Source::find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        try {
            $scraper = \App\Scrapers\ScraperFactory::make($source);
            $success = $scraper->testConnection();

            return $this->successResponse([
                'source_id' => $source->id,
                'scraper' => $scraper->getName(),
                'success' => $success,
                'message' => $success ? 'Connection successful.' : 'Connection failed.',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Scraper error: ' . $e->getMessage(), status: 500);
        }
    }

    /**
     * Trigger manual scan. Story #30 (Admin-triggered scan)
     */
    public function triggerScan(int $id): JsonResponse
    {
        $source = Source::find($id);

        if (!$source) {
            return $this->errorResponse('Source not found.', status: 404);
        }

        \App\Jobs\ScanSourceJob::dispatch($source->id);

        return $this->successResponse([
            'source_id' => $source->id,
            'message' => 'Scan job dispatched successfully.',
        ], status: 202);
    }
}
