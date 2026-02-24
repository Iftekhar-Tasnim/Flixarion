<?php

namespace App\Services;

class FileValidator
{
    /**
     * Check if file has a valid video extension.
     */
    public function isValidVideo(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            config('sources.valid_extensions', ['mp4', 'mkv', 'avi', 'm3u8'])
        );
    }

    /**
     * Check if file has a subtitle extension.
     */
    public function isSubtitle(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            config('sources.subtitle_extensions', ['srt', 'vtt', 'ass', 'sub'])
        );
    }

    /**
     * Auto-link subtitle files to a video by filename similarity (>60%).
     *
     * @param  array  $allFiles  [{path, filename, extension}]
     * @param  string $videoFilename  The video filename (without extension)
     * @return array  Matched subtitle file paths
     */
    public function findSubtitles(array $allFiles, string $videoFilename): array
    {
        $threshold = config('metadata.matching.fuzzy_threshold', 60);
        $subtitles = [];

        $videoBase = pathinfo($videoFilename, PATHINFO_FILENAME);

        foreach ($allFiles as $file) {
            if (!$this->isSubtitle($file['extension'] ?? '')) {
                continue;
            }

            $subBase = pathinfo($file['filename'] ?? $file['path'], PATHINFO_FILENAME);
            similar_text(strtolower($videoBase), strtolower($subBase), $percent);

            if ($percent >= $threshold) {
                $subtitles[] = $file['path'];
            }
        }

        return $subtitles;
    }

    /**
     * Detect multi-part movie (CD1/CD2/Part 1/Part 2).
     *
     * @return int|null  Part number if detected, null otherwise
     */
    public function detectMultiPart(string $filename): ?int
    {
        // Match CD1, CD2, Part 1, Part 2, Part.1, Part.2, Pt1, Pt2
        if (preg_match('/(?:cd|part|pt)[.\s_-]*(\d+)/i', $filename, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract file extension from filename or path.
     */
    public function extractExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}
