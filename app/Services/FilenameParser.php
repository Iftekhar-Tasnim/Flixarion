<?php

namespace App\Services;

use App\DTOs\ParsedFilename;

class FilenameParser
{
    /**
     * Noise tokens to strip from filenames before title extraction.
     */
    private const NOISE_TOKENS = [
        'bluray',
        'brrip',
        'bdrip',
        'web-dl',
        'webdl',
        'webrip',
        'hdrip',
        'hdtv',
        'dvdrip',
        'dvdscr',
        'cam',
        'ts',
        'hdr',
        'sdr',
        'x264',
        'x265',
        'h264',
        'h265',
        'hevc',
        'avc',
        'xvid',
        'aac',
        'ac3',
        'dts',
        'flac',
        'mp3',
        'atmos',
        'ddp5',
        'ddp7',
        'dd5',
        'dd7',
        '5.1',
        '7.1',
        'yify',
        'yts',
        'rarbg',
        'etrg',
        'sparks',
        'geckos',
        'extended',
        'unrated',
        'directors',
        'cut',
        'remastered',
        'imax',
        'proper',
        'repack',
        'internal',
        'limited',
        'multi',
        'dual',
        'audio',
        'subbed',
        'dubbed',
    ];

    /**
     * Parse a raw filename into structured metadata.
     */
    public function parse(string $filename): ParsedFilename
    {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        $year = $this->extractYear($name);
        $quality = $this->extractQuality($name);
        $series = $this->extractSeries($name);
        $codec = $this->extractCodec($name);
        $sourceType = $this->extractSourceType($name);
        $partNumber = $this->extractPartNumber($name);

        if ($series) {
            $title = $this->cleanTitle($this->extractTitleBeforePattern($name, '/S\d{1,2}E\d{1,2}/i'));
            $type = 'series';
        } elseif ($year) {
            $title = $this->cleanTitle($this->extractTitleBeforeYear($name, $year));
            $type = 'movie';
        } else {
            $title = $this->cleanTitle($this->stripNoiseTokens($name));
            $type = 'movie';
        }

        $qualityScore = $this->calculateQualityScore($quality, $sourceType);

        return new ParsedFilename(
            title: $title,
            year: $year,
            quality: $quality,
            type: $type,
            season: $series['season'] ?? null,
            episode: $series['episode'] ?? null,
            codec: $codec,
            sourceType: $sourceType,
            qualityScore: $qualityScore,
            partNumber: $partNumber,
        );
    }

    /**
     * Extract year from filename.
     */
    public function extractYear(string $name): ?int
    {
        // \b treats _ as a word character, so \b2010\b fails for _2010_
        // Using [._\-\s] as boundary or start/end of string
        if (preg_match('/(?:^|[._\-\s])((?:19|20)\d{2})(?:$|[._\-\s])/', $name, $matches)) {
            $year = (int) $matches[1];
            if ($year >= 1900 && $year <= (int) date('Y') + 2) {
                return $year;
            }
        }
        return null;
    }

    /**
     * Extract video quality (resolution).
     */
    public function extractQuality(string $name): ?string
    {
        if (preg_match('/\b(2160p|1080p|720p|480p|4k|uhd)\b/i', $name, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }

    /**
     * Extract series season + episode info (S01E01 format).
     */
    public function extractSeries(string $name): ?array
    {
        if (preg_match('/S(\d{1,2})E(\d{1,2})/i', $name, $matches)) {
            return [
                'season' => (int) $matches[1],
                'episode' => (int) $matches[2],
            ];
        }
        return null;
    }

    /**
     * Extract video codec.
     */
    public function extractCodec(string $name): ?string
    {
        if (preg_match('/\b(x264|x265|h264|h265|hevc|avc|xvid)\b/i', $name, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }

    /**
     * Extract source type (BluRay, WEB-DL, etc).
     */
    public function extractSourceType(string $name): ?string
    {
        $patterns = [
            'imax' => '/\bimax\b/i',
            'BluRay' => '/\b(blu[\s.-]?ray|brrip|bdrip)\b/i',
            'WEB-DL' => '/\b(web[\s.-]?dl|webdl)\b/i',
            'WebRip' => '/\bwebrip\b/i',
            'HDRip' => '/\bhdrip\b/i',
            'HDTV' => '/\bhdtv\b/i',
            'DVDRip' => '/\bdvdrip\b/i',
        ];

        foreach ($patterns as $type => $regex) {
            if (preg_match($regex, $name)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Extract multi-part number (CD1, Part 2, etc).
     */
    public function extractPartNumber(string $name): ?int
    {
        if (preg_match('/(?:cd|part|pt)[.\s_-]*(\d+)/i', $name, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Clean a raw title string.
     */
    public function cleanTitle(string $raw): string
    {
        // Replace dots, underscores, dashes with spaces
        $clean = preg_replace('/[._-]+/', ' ', $raw);
        // Collapse whitespace
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }

    /**
     * Extract title portion before the series pattern (S##E##).
     */
    private function extractTitleBeforePattern(string $name, string $pattern): string
    {
        $parts = preg_split($pattern, $name, 2);
        return $parts[0] ?? $name;
    }

    /**
     * Extract title portion before the year.
     */
    private function extractTitleBeforeYear(string $name, int $year): string
    {
        $pos = strpos($name, (string) $year);
        if ($pos !== false && $pos > 0) {
            return substr($name, 0, $pos);
        }
        return $name;
    }

    /**
     * Strip known noise tokens from a string.
     */
    private function stripNoiseTokens(string $name): string
    {
        $clean = preg_replace('/[._-]+/', ' ', $name);
        $words = explode(' ', $clean);

        $filtered = array_filter($words, function ($word) {
            return !in_array(strtolower($word), self::NOISE_TOKENS);
        });

        return implode(' ', $filtered);
    }

    /**
     * Calculate quality score for source ranking.
     */
    public function calculateQualityScore(?string $quality, ?string $sourceType): int
    {
        $defaultQuality = ['4k' => 40, '2160p' => 40, '1080p' => 30, '720p' => 20, '480p' => 10];
        $defaultBonus = [
            'imax' => 20,
            'bluray' => 18,
            'brrip' => 17,
            'web-dl' => 16,
            'webrip' => 15,
            'hdrip' => 14,
            'hdtv' => 13,
            'dvdrip' => 12,
        ];

        $qualityScores = function_exists('config') ? config('sources.quality_scores', $defaultQuality) : $defaultQuality;
        $bonusScores = function_exists('config') ? config('sources.source_bonus', $defaultBonus) : $defaultBonus;

        $score = 0;

        if ($quality) {
            $score += $qualityScores[strtolower($quality)] ?? 0;
        }

        if ($sourceType) {
            $score += $bonusScores[strtolower($sourceType)] ?? 0;
        }

        return $score;
    }
}
