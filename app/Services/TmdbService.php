<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    private string $apiKey;
    private string $baseUrl;
    private int $rateLimit;

    public function __construct()
    {
        $this->apiKey = config('metadata.tmdb.api_key', '');
        $this->baseUrl = config('metadata.tmdb.base_url', 'https://api.themoviedb.org/3');
        $this->rateLimit = config('metadata.tmdb.rate_limit', 3);
    }

    /**
     * Search for a movie by title and optional year.
     */
    public function searchMovie(string $title, ?int $year = null): ?array
    {
        $params = ['query' => $title];
        if ($year) {
            $params['year'] = $year;
        }

        $results = $this->get('/search/movie', $params);

        return $results['results'][0] ?? null;
    }

    /**
     * Search for a TV series by title.
     */
    public function searchTv(string $title): ?array
    {
        $results = $this->get('/search/tv', ['query' => $title]);

        return $results['results'][0] ?? null;
    }

    /**
     * Get full movie details with credits and videos.
     */
    public function getMovieDetails(int $tmdbId): ?array
    {
        return $this->get("/movie/{$tmdbId}", [
            'append_to_response' => 'credits,videos,alternative_titles',
        ]);
    }

    /**
     * Get full TV series details.
     */
    public function getTvDetails(int $tmdbId): ?array
    {
        return $this->get("/tv/{$tmdbId}", [
            'append_to_response' => 'credits,videos,alternative_titles',
        ]);
    }

    /**
     * Get season details with episodes.
     */
    public function getSeasonDetails(int $tvId, int $seasonNumber): ?array
    {
        return $this->get("/tv/{$tvId}/season/{$seasonNumber}");
    }

    /**
     * Build image URL.
     */
    public function imageUrl(?string $path, string $size = 'w500'): ?string
    {
        if (!$path) {
            return null;
        }

        $baseUrl = config('metadata.tmdb.image_base_url', 'https://image.tmdb.org/t/p');
        return "{$baseUrl}/{$size}{$path}";
    }

    /**
     * Make a rate-limited GET request to TMDb API.
     */
    private function get(string $endpoint, array $params = []): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('TMDb API key not configured');
            return null;
        }

        $this->throttle();

        $params['api_key'] = $this->apiKey;

        try {
            $response = Http::timeout(10)
                ->retry(3, function (int $attempt, $exception) {
                    // Exponential backoff: 1s, 2s, 4s
                    return $attempt * 1000;
                }, function ($exception, $request) {
                    // Only retry on 429 (rate limit) or 5xx
                    return $exception instanceof \Illuminate\Http\Client\RequestException
                        && in_array($exception->response->status(), [429, 500, 502, 503]);
                })
                ->get("{$this->baseUrl}{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("TMDb API error: {$response->status()}", [
                'endpoint' => $endpoint,
                'params' => array_diff_key($params, ['api_key' => '']),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("TMDb API exception: {$e->getMessage()}", [
                'endpoint' => $endpoint,
            ]);
            return null;
        }
    }

    /**
     * Simple rate limiter — sleeps to maintain requests/second limit.
     */
    private function throttle(): void
    {
        if ($this->rateLimit > 0) {
            usleep((int) (1_000_000 / $this->rateLimit));
        }
    }
}
