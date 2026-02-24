<?php

namespace Database\Factories;

use App\Models\Source;
use App\Models\SourceHealthReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceHealthReportFactory extends Factory
{
    protected $model = SourceHealthReport::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'isp_name' => fake()->randomElement(['BTCL', 'Amber IT', 'Link3', 'Carnival', 'DOT Internet']),
            'is_reachable' => true,
            'response_time_ms' => fake()->numberBetween(10, 500),
            'reported_at' => now(),
        ];
    }

    public function unreachable(): static
    {
        return $this->state(['is_reachable' => false, 'response_time_ms' => null]);
    }
}
