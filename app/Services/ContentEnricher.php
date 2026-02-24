<?php

namespace App\Services;

use App\DTOs\ParsedFilename;
use App\Models\Content;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Season;
use App\Models\ShadowContentSource;
use App\Models\SourceLink;
use Illuminate\Support\Facades\Log;

class ContentEnricher
{
    public function __construct(
        private FilenameParser $parser,
        private TmdbService $tmdb,
        private OmdbService $omdb,
    ) {
    }

    /**
     * Enrich a shadow entry: Parse → Match → Save to contents/source_links.
     */
    public function enrich(ShadowContentSource $shadow): void
    {
        $shadow->update(['enrichment_status' => 'processing']);

        try {
            // Step 1: Parse filename
            $parsed = $this->parser->parse($shadow->raw_filename);

            if (empty($parsed->title)) {
                $shadow->update(['enrichment_status' => 'failed']);
                Log::warning("Enricher: empty title from filename", ['file' => $shadow->raw_filename]);
                return;
            }

            // Step 2: Search metadata
            $metadata = $this->findMetadata($parsed);

            if (!$metadata) {
                $shadow->update(['enrichment_status' => 'unmatched']);
                Log::info("Enricher: no match found", ['title' => $parsed->title, 'year' => $parsed->year]);
                return;
            }

            // Step 3: Calculate confidence
            $confidence = $this->calculateConfidence($parsed->title, $metadata['title'] ?? '');

            // Step 4: Create or find Content record (dedup by tmdb_id)
            $content = $this->upsertContent($parsed, $metadata, $confidence);

            // Step 5: Create SourceLink
            if ($parsed->isSeries() && $parsed->season && $parsed->episode) {
                $this->linkEpisode($shadow, $parsed, $content);
            } else {
                $this->linkContent($shadow, $parsed, $content);
            }

            $shadow->update(['enrichment_status' => 'completed']);

        } catch (\Exception $e) {
            $shadow->update(['enrichment_status' => 'failed']);
            Log::error("Enricher failed: {$e->getMessage()}", [
                'shadow_id' => $shadow->id,
                'file' => $shadow->raw_filename,
            ]);
        }
    }

    /**
     * Find metadata from TMDb (primary) or OMDb (fallback).
     */
    private function findMetadata(ParsedFilename $parsed): ?array
    {
        // Try TMDb first
        if ($parsed->isSeries()) {
            $match = $this->tmdb->searchTv($parsed->title);
            if ($match) {
                $details = $this->tmdb->getTvDetails($match['id']);
                return $details ? array_merge($match, $details, ['source' => 'tmdb']) : $match;
            }
        } else {
            $match = $this->tmdb->searchMovie($parsed->title, $parsed->year);
            if ($match) {
                $details = $this->tmdb->getMovieDetails($match['id']);
                return $details ? array_merge($match, $details, ['source' => 'tmdb']) : $match;
            }
        }

        // Fallback to OMDb
        $omdbResult = $this->omdb->search($parsed->title, $parsed->year);
        if ($omdbResult) {
            return $omdbResult;
        }

        return null;
    }

    /**
     * Calculate confidence score using Levenshtein/similar_text.
     */
    private function calculateConfidence(string $parsedTitle, string $apiTitle): float
    {
        if (empty($apiTitle)) {
            return 0;
        }

        similar_text(
            strtolower(trim($parsedTitle)),
            strtolower(trim($apiTitle)),
            $percent
        );

        return round($percent, 2);
    }

    /**
     * Create or update Content record, deduped by tmdb_id.
     */
    private function upsertContent(ParsedFilename $parsed, array $metadata, float $confidence): Content
    {
        $tmdbId = $metadata['id'] ?? null;
        $threshold = config('metadata.matching.confidence_threshold', 80);

        $contentData = [
            'type' => $parsed->type,
            'title' => $metadata['title'] ?? $metadata['name'] ?? $parsed->title,
            'original_title' => $metadata['original_title'] ?? $metadata['original_name'] ?? null,
            'year' => $parsed->year ?? $this->extractYear($metadata),
            'overview' => $metadata['overview'] ?? null,
            'poster_path' => $this->tmdb->imageUrl($metadata['poster_path'] ?? null),
            'backdrop_path' => $this->tmdb->imageUrl($metadata['backdrop_path'] ?? null, 'original'),
            'rating' => $metadata['vote_average'] ?? null,
            'runtime' => $metadata['runtime'] ?? null,
            'imdb_id' => $metadata['imdb_id'] ?? $metadata['external_ids']['imdb_id'] ?? null,
            'confidence_score' => $confidence,
            'enrichment_status' => $confidence >= $threshold ? 'completed' : 'flagged',
            'is_published' => $confidence >= $threshold,
            'cast' => $this->extractCast($metadata),
            'alternative_titles' => $this->extractAlternativeTitles($metadata),
        ];

        if ($tmdbId) {
            $content = Content::updateOrCreate(
                ['tmdb_id' => $tmdbId],
                $contentData
            );
        } else {
            $content = Content::create(array_merge($contentData, ['tmdb_id' => null]));
        }

        // Sync genres
        $this->syncGenres($content, $metadata);

        return $content;
    }

    /**
     * Create SourceLink for a movie content.
     */
    private function linkContent(ShadowContentSource $shadow, ParsedFilename $parsed, Content $content): void
    {
        SourceLink::updateOrCreate(
            [
                'linkable_type' => Content::class,
                'linkable_id' => $content->id,
                'source_id' => $shadow->source_id,
                'file_path' => $shadow->file_path,
            ],
            [
                'quality' => $parsed->quality,
                'file_size' => $shadow->file_size,
                'codec_info' => $parsed->codec,
                'part_number' => $parsed->partNumber,
                'subtitle_paths' => $shadow->subtitle_paths,
                'status' => 'active',
                'last_verified_at' => now(),
            ]
        );
    }

    /**
     * Create Season/Episode and link SourceLink for a series episode.
     */
    private function linkEpisode(ShadowContentSource $shadow, ParsedFilename $parsed, Content $content): void
    {
        // Create or find Season
        $season = Season::firstOrCreate(
            ['content_id' => $content->id, 'season_number' => $parsed->season],
            ['title' => "Season {$parsed->season}"]
        );

        // Create or find Episode
        $episode = Episode::firstOrCreate(
            ['season_id' => $season->id, 'episode_number' => $parsed->episode],
            ['content_id' => $content->id, 'title' => "Episode {$parsed->episode}"]
        );

        // Link source to episode
        SourceLink::updateOrCreate(
            [
                'linkable_type' => Episode::class,
                'linkable_id' => $episode->id,
                'source_id' => $shadow->source_id,
                'file_path' => $shadow->file_path,
            ],
            [
                'quality' => $parsed->quality,
                'file_size' => $shadow->file_size,
                'codec_info' => $parsed->codec,
                'subtitle_paths' => $shadow->subtitle_paths,
                'status' => 'active',
                'last_verified_at' => now(),
            ]
        );
    }

    /**
     * Sync genre names from metadata to content.
     */
    private function syncGenres(Content $content, array $metadata): void
    {
        $genreNames = [];

        if (isset($metadata['genres'])) {
            foreach ($metadata['genres'] as $genre) {
                $genreNames[] = is_array($genre) ? ($genre['name'] ?? '') : $genre;
            }
        }

        if (empty($genreNames)) {
            return;
        }

        $genreIds = [];
        foreach ($genreNames as $name) {
            if (empty($name)) {
                continue;
            }
            $genre = Genre::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            );
            $genreIds[] = $genre->id;
        }

        $content->genres()->syncWithoutDetaching($genreIds);
    }

    /**
     * Extract cast array from metadata.
     */
    private function extractCast(array $metadata): ?array
    {
        if (isset($metadata['credits']['cast'])) {
            return collect($metadata['credits']['cast'])
                ->take(10)
                ->map(fn($c) => ['name' => $c['name'], 'character' => $c['character'] ?? null])
                ->all();
        }

        if (isset($metadata['cast']) && is_array($metadata['cast'])) {
            return array_map(fn($name) => ['name' => $name], array_slice($metadata['cast'], 0, 10));
        }

        return null;
    }

    /**
     * Extract alternative titles from metadata.
     */
    private function extractAlternativeTitles(array $metadata): ?array
    {
        $titles = [];

        if (isset($metadata['alternative_titles']['titles'])) {
            $titles = collect($metadata['alternative_titles']['titles'])
                ->pluck('title')
                ->unique()
                ->take(20)
                ->all();
        } elseif (isset($metadata['alternative_titles']['results'])) {
            $titles = collect($metadata['alternative_titles']['results'])
                ->pluck('title')
                ->unique()
                ->take(20)
                ->all();
        }

        return !empty($titles) ? $titles : null;
    }

    /**
     * Extract year from metadata.
     */
    private function extractYear(array $metadata): ?int
    {
        $date = $metadata['release_date'] ?? $metadata['first_air_date'] ?? null;
        if ($date && preg_match('/(\d{4})/', $date, $m)) {
            return (int) $m[1];
        }
        return $metadata['year'] ?? null;
    }
}
