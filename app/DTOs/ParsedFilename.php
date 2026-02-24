<?php

namespace App\DTOs;

class ParsedFilename
{
    public function __construct(
        public string $title,
        public ?int $year = null,
        public ?string $quality = null,
        public string $type = 'movie',  // 'movie' or 'series'
        public ?int $season = null,
        public ?int $episode = null,
        public ?string $codec = null,
        public ?string $sourceType = null, // BluRay, WEB-DL, etc.
        public int $qualityScore = 0,
        public ?int $partNumber = null,
    ) {
    }

    public function isSeries(): bool
    {
        return $this->type === 'series';
    }

    public function isMovie(): bool
    {
        return $this->type === 'movie';
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'quality' => $this->quality,
            'type' => $this->type,
            'season' => $this->season,
            'episode' => $this->episode,
            'codec' => $this->codec,
            'source_type' => $this->sourceType,
            'quality_score' => $this->qualityScore,
            'part_number' => $this->partNumber,
        ];
    }
}
