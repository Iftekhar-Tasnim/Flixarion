<?php

use App\Models\Source;
use App\Models\SourceHealthReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── List Sources (Public) ──

test('anyone can list active sources', function () {
    Source::factory()->count(3)->create();
    Source::factory()->inactive()->create();

    $response = $this->getJson('/api/sources');

    $response->assertOk()
        ->assertJsonCount(3, 'data'); // only active sources
});

// ── Submit Health Report (Story #20) ──

test('anonymous user can submit health report', function () {
    $sources = Source::factory()->count(2)->create();

    $response = $this->postJson('/api/sources/health-report', [
        'isp_name' => 'Amber IT',
        'sources' => [
            ['source_id' => $sources[0]->id, 'is_reachable' => true, 'response_time_ms' => 45],
            ['source_id' => $sources[1]->id, 'is_reachable' => false, 'response_time_ms' => null],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.reported', 2);

    $this->assertDatabaseCount('source_health_reports', 2);
});

test('health report validates required fields', function () {
    $this->postJson('/api/sources/health-report', [])
        ->assertStatus(422);
});
