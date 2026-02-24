<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmdbService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('metadata.omdb.api_key', '');
        $this->baseUrl = config('metadata.omdb.base_url', 'https://www.omdbapi.com');
    }

    /**
     * Search for content by title and optional year.
     * Returns normalized result compatible with TMDb format.
     */
    public function search(string $title, ?int $year = null): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('OMDb API key not configured');
            return null;
        }

        $params = [
            'apikey' => $this->apiKey,
            't' => $title,
            'type' => 'movie',
        ];

        if ($year) {
            $params['y'] = $year;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl, $params);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (($data['Response'] ?? 'False') === 'False') {
                return null;
            }

            // Normalize to a TMDb-like format
            return [
                'title' => $data['Title'] ?? null,
                'year' => isset($data['Year']) ? (int) $data['Year'] : null,
                'imdb_id' => $data['imdbID'] ?? null,
                'overview' => $data['Plot'] ?? null,
                'vote_average' => isset($data['imdbRating']) && $data['imdbRating'] !== 'N/A'
                    ? (float) $data['imdbRating']
                    : null,
                'poster_path' => $data['Poster'] !== 'N/A' ? $data['Poster'] : null,
                'runtime' => $this->parseRuntime($data['Runtime'] ?? null),
                'genres' => isset($data['Genre']) ? explode(', ', $data['Genre']) : [],
                'director' => $data['Director'] ?? null,
                'cast' => isset($data['Actors']) ? explode(', ', $data['Actors']) : [],
                'source' => 'omdb',
            ];
        } catch (\Exception $e) {
            Log::error("OMDb API exception: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Parse runtime string (e.g., "148 min") to integer minutes.
     */
    private function parseRuntime(?string $runtime): ?int
    {
        if (!$runtime || $runtime === 'N/A') {
            return null;
        }

        if (preg_match('/(\d+)/', $runtime, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
