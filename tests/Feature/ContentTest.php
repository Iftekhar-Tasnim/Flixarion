<?php

use App\Models\Content;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Index — Pagination (Story #5) ──

test('content index returns paginated results', function () {
    Content::factory()->count(25)->create();

    $response = $this->getJson('/api/contents');

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);

    expect($response->json('meta.total'))->toBe(25);
    expect($response->json('meta.last_page'))->toBe(2);
});

// ── Index — Filter by Type (Story #6) ──

test('content can be filtered by type', function () {
    Content::factory()->movie()->count(3)->create();
    Content::factory()->series()->count(2)->create();

    $response = $this->getJson('/api/contents?type=movie');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

// ── Index — Filter by Genre (Story #6) ──

test('content can be filtered by genre', function () {
    $genre = Genre::create(['name' => 'Action', 'slug' => 'action', 'tmdb_id' => 28]);
    $content = Content::factory()->create();
    $content->genres()->attach($genre);

    Content::factory()->count(2)->create(); // no genre

    $response = $this->getJson('/api/contents?genre=action');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

// ── Index — Filter by Year (Story #6) ──

test('content can be filtered by year', function () {
    Content::factory()->create(['year' => 2024]);
    Content::factory()->create(['year' => 2023]);

    $response = $this->getJson('/api/contents?year=2024');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

// ── Index — Sort (Story #8) ──

test('content can be sorted by trending', function () {
    Content::factory()->create(['title' => 'Low', 'watch_count' => 10]);
    Content::factory()->create(['title' => 'High', 'watch_count' => 1000]);

    $response = $this->getJson('/api/contents?sort=trending');

    $response->assertOk();
    expect($response->json('data.0.title'))->toBe('High');
});

// ── Index — Unpublished hidden ──

test('unpublished content is not returned', function () {
    Content::factory()->create(['is_published' => true]);
    Content::factory()->unpublished()->create();

    $response = $this->getJson('/api/contents');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

// ── Search (Story #7) ──

test('search finds content by title', function () {
    Content::factory()->create(['title' => 'The Dark Knight']);
    Content::factory()->create(['title' => 'Inception']);

    $response = $this->getJson('/api/contents/search?q=Dark');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.title'))->toBe('The Dark Knight');
});

test('search requires at least 2 characters', function () {
    $this->getJson('/api/contents/search?q=a')
        ->assertStatus(422);
});

// ── Show (Story #9) ──

test('show returns full content detail with genres', function () {
    $content = Content::factory()->create();
    $genre = Genre::create(['name' => 'Drama', 'slug' => 'drama', 'tmdb_id' => 18]);
    $content->genres()->attach($genre);

    $response = $this->getJson("/api/contents/{$content->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $content->id)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'type', 'genres'],
        ]);
});

test('show returns 404 for non-existent content', function () {
    $this->getJson('/api/contents/999999')
        ->assertStatus(404);
});
