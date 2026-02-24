<?php

namespace Database\Factories;

use App\Models\ShadowContentSource;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShadowContentSourceFactory extends Factory
{
    protected $model = ShadowContentSource::class;

    public function definition(): array
    {
        $filename = fake()->word() . '.' . fake()->year() . '.1080p.BluRay.mkv';

        return [
            'source_id' => Source::factory(),
            'raw_filename' => $filename,
            'file_path' => '/Movies/' . $filename,
            'file_extension' => 'mkv',
            'file_size' => fake()->numberBetween(500_000_000, 5_000_000_000),
            'detected_encoding' => null,
            'subtitle_paths' => null,
            'scan_batch_id' => Str::uuid()->toString(),
            'enrichment_status' => 'pending',
            'created_at' => now(),
        ];
    }
}
