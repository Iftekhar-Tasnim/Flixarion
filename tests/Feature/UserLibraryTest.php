<?php

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Combined Library (Matrix #12) ──

test('user can get combined library', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    // Add to watchlist
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/watchlist', ['content_id' => $content->id]);

    // Add to favorites
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/favorites', ['content_id' => $content->id]);

    // Record play
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/history', ['content_id' => $content->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user/library');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['watchlist', 'favorites', 'recently_watched'],
        ])
        ->assertJsonCount(1, 'data.watchlist')
        ->assertJsonCount(1, 'data.favorites')
        ->assertJsonCount(1, 'data.recently_watched');
});

// ── Watchlist — POST + DELETE (Story #14) ──

test('user can add to watchlist via POST', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/watchlist', ['content_id' => $content->id]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('watchlists', [
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
});

test('user can remove from watchlist via DELETE', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/watchlist', ['content_id' => $content->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/user/watchlist/{$content->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('watchlists', [
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
});

test('user can view watchlist', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/watchlist', ['content_id' => $content->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user/watchlist');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// ── Favorites — POST + DELETE (Story #15) ──

test('user can add to favorites via POST', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/favorites', ['content_id' => $content->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('favorites', [
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
});

test('user can remove from favorites via DELETE', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/favorites', ['content_id' => $content->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/user/favorites/{$content->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('favorites', [
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
});

// ── Watch History (Stories #16, #18) ──

test('user can record play and watch_count increments', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['watch_count' => 0]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/history', ['content_id' => $content->id]);

    $response->assertStatus(201)
        ->assertJsonPath('data.recorded', true);

    $this->assertDatabaseHas('watch_history', [
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);

    // watch_count should increment on play, not on view
    expect($content->fresh()->watch_count)->toBe(1);
});

test('user can view watch history', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/user/history', ['content_id' => $content->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user/history');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// ── Auth required ──

test('unauthenticated user cannot access library', function () {
    $this->getJson('/api/user/library')->assertStatus(401);
    $this->getJson('/api/user/watchlist')->assertStatus(401);
    $this->getJson('/api/user/favorites')->assertStatus(401);
    $this->getJson('/api/user/history')->assertStatus(401);
});
