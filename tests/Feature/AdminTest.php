<?php

use App\Models\Content;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function regularUser(): User
{
    return User::factory()->create(['role' => 'user']);
}

// ── Dashboard (Story #49) ──

test('admin can view dashboard stats', function () {
    Content::factory()->count(3)->create();
    Source::factory()->count(2)->create();
    User::factory()->count(5)->create();

    $response = $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['users', 'content', 'published', 'sources', 'active_sources', 'review_queue'],
        ]);
});

test('non-admin cannot access dashboard', function () {
    $this->actingAs(regularUser(), 'sanctum')
        ->getJson('/api/admin/dashboard')
        ->assertStatus(403);
});

// ── Sources CRUD (Stories #50-51) ──

test('admin can list sources', function () {
    Source::factory()->count(3)->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/sources')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin can create a source', function () {
    $response = $this->actingAs(adminUser(), 'sanctum')
        ->postJson('/api/admin/sources', [
            'name' => 'Test FTP',
            'base_url' => 'http://test.ftp.net:8096',
            'scraper_type' => 'emby',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test FTP')
        ->assertJsonPath('data.is_active', false);
});

test('admin can view source detail', function () {
    $source = Source::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson("/api/admin/sources/{$source->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $source->id);
});

test('admin can update a source', function () {
    $source = Source::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->putJson("/api/admin/sources/{$source->id}", [
            'name' => 'Updated Name',
            'is_active' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.is_active', true);
});

test('admin can delete a source', function () {
    $source = Source::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->deleteJson("/api/admin/sources/{$source->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('sources', ['id' => $source->id]);
});

test('admin can test source connection', function () {
    $source = Source::factory()->create();

    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response('dflix', 200),
    ]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/sources/{$source->id}/test")
        ->assertOk()
        ->assertJsonPath('data.success', true);
});

test('admin can trigger scan', function () {
    $source = Source::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/sources/{$source->id}/scan")
        ->assertStatus(202);
});

// ── Content Management (Stories #52-54) ──

test('admin can list content', function () {
    Content::factory()->count(5)->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/contents')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

test('admin can search content', function () {
    Content::factory()->create(['title' => 'Inception 2010']);
    Content::factory()->create(['title' => 'The Matrix']);

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/contents?search=Inception')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('admin can update content flags', function () {
    $content = Content::factory()->create(['is_featured' => false]);

    $this->actingAs(adminUser(), 'sanctum')
        ->patchJson("/api/admin/contents/{$content->id}", ['is_featured' => true])
        ->assertOk()
        ->assertJsonPath('data.is_featured', true);
});

test('admin can delete content', function () {
    $content = Content::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->deleteJson("/api/admin/contents/{$content->id}")
        ->assertStatus(204);
});

test('admin can trigger resync', function () {
    $content = Content::factory()->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/contents/{$content->id}/resync")
        ->assertStatus(202);
});

// ── Review Queue (Story #55) ──

test('admin can view review queue', function () {
    Content::factory()->create(['enrichment_status' => 'flagged', 'confidence_score' => 50]);
    Content::factory()->create(['enrichment_status' => 'completed', 'confidence_score' => 95]);

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/review-queue')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('admin can approve content', function () {
    $content = Content::factory()->create(['enrichment_status' => 'flagged', 'confidence_score' => 50, 'is_published' => false]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/review-queue/{$content->id}/approve")
        ->assertOk();

    expect((float) $content->fresh()->confidence_score)->toBe(100.0);
    expect($content->fresh()->enrichment_status)->toBe('approved');
    expect($content->fresh()->is_published)->toBe(true);
});

test('admin can correct content tmdb_id', function () {
    $content = Content::factory()->create(['tmdb_id' => 111]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/review-queue/{$content->id}/correct", ['tmdb_id' => 999])
        ->assertOk();

    expect($content->fresh()->tmdb_id)->toBe(999);
});

test('admin can reject content', function () {
    $content = Content::factory()->create(['is_published' => true]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/review-queue/{$content->id}/reject")
        ->assertOk();

    expect($content->fresh()->is_published)->toBe(false);
    expect($content->fresh()->enrichment_status)->toBe('rejected');
});

// ── User Management (Story #58) ──

test('admin can list users', function () {
    User::factory()->count(3)->create();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/users')
        ->assertOk();
});

test('admin can ban a user', function () {
    $user = User::factory()->create(['is_banned' => false]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/users/{$user->id}/ban")
        ->assertOk();

    expect($user->fresh()->is_banned)->toBe(true);
});

test('admin can unban a user', function () {
    $user = User::factory()->create(['is_banned' => true]);

    $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/users/{$user->id}/unban")
        ->assertOk();

    expect($user->fresh()->is_banned)->toBe(false);
});

test('admin can reset user password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs(adminUser(), 'sanctum')
        ->postJson("/api/admin/users/{$user->id}/reset-password");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['temp_password']]);
});

// ── Enrichment (Story #57) ──

test('admin can view enrichment status', function () {
    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/enrichment')
        ->assertOk()
        ->assertJsonStructure(['data' => ['paused', 'pending_count', 'processing_rate']]);
});

test('admin can pause and resume enrichment', function () {
    $admin = adminUser();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/enrichment/pause')
        ->assertOk();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/enrichment/resume')
        ->assertOk();
});

// ── Settings (Story #59) ──

test('admin can view settings', function () {
    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/admin/settings')
        ->assertOk();
});

test('admin can update settings', function () {
    $this->actingAs(adminUser(), 'sanctum')
        ->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'site_name', 'value' => 'Flixarion'],
                ['key' => 'scan_interval', 'value' => '6'],
            ],
        ])
        ->assertOk();

    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Flixarion']);
});
