<?php

use App\Models\ShadowContentSource;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Submit Scan Results ──

test('client can submit scan results with valid video files', function () {
    $source = Source::factory()->create();

    $response = $this->postJson("/api/sources/{$source->id}/scan-results", [
        'files' => [
            ['path' => '/Movies/Inception.2010.1080p.mkv', 'filename' => 'Inception.2010.1080p.mkv', 'extension' => 'mkv', 'size' => 2000000000],
            ['path' => '/Movies/Batman.2022.720p.mp4', 'filename' => 'Batman.2022.720p.mp4', 'extension' => 'mp4', 'size' => 1500000000],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.inserted', 2)
        ->assertJsonPath('data.invalid', 0);

    $this->assertDatabaseCount('shadow_content_sources', 2);
    $this->assertDatabaseCount('source_scan_logs', 1);
});

test('invalid extensions are filtered out', function () {
    $source = Source::factory()->create();

    $response = $this->postJson("/api/sources/{$source->id}/scan-results", [
        'files' => [
            ['path' => '/Movies/Inception.mkv', 'filename' => 'Inception.mkv', 'extension' => 'mkv', 'size' => null],
            ['path' => '/Movies/readme.txt', 'filename' => 'readme.txt', 'extension' => 'txt', 'size' => 100],
            ['path' => '/Movies/poster.jpg', 'filename' => 'poster.jpg', 'extension' => 'jpg', 'size' => 50000],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.inserted', 1)
        ->assertJsonPath('data.invalid', 2);

    $this->assertDatabaseCount('shadow_content_sources', 1);
});

test('duplicate files are skipped on delta sync', function () {
    $source = Source::factory()->create();

    // First submission
    $this->postJson("/api/sources/{$source->id}/scan-results", [
        'files' => [
            ['path' => '/Movies/Inception.mkv', 'filename' => 'Inception.mkv', 'extension' => 'mkv'],
        ],
    ])->assertStatus(201);

    // Second submission with same + new files
    $response = $this->postJson("/api/sources/{$source->id}/scan-results", [
        'files' => [
            ['path' => '/Movies/Inception.mkv', 'filename' => 'Inception.mkv', 'extension' => 'mkv'],
            ['path' => '/Movies/Batman.mp4', 'filename' => 'Batman.mp4', 'extension' => 'mp4'],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.inserted', 1)
        ->assertJsonPath('data.skipped', 1);

    $this->assertDatabaseCount('shadow_content_sources', 2);
});

test('scan results require files array', function () {
    $source = Source::factory()->create();

    $this->postJson("/api/sources/{$source->id}/scan-results", [])
        ->assertStatus(422);
});

test('scan results 404 for non-existent source', function () {
    $this->postJson('/api/sources/999/scan-results', [
        'files' => [
            ['path' => '/test.mkv', 'filename' => 'test.mkv', 'extension' => 'mkv'],
        ],
    ])->assertStatus(404);
});

test('scan updates source last_scan_at', function () {
    $source = Source::factory()->create(['last_scan_at' => null]);

    $this->postJson("/api/sources/{$source->id}/scan-results", [
        'files' => [
            ['path' => '/test.mkv', 'filename' => 'test.mkv', 'extension' => 'mkv'],
        ],
    ])->assertStatus(201);

    expect($source->fresh()->last_scan_at)->not->toBeNull();
});
