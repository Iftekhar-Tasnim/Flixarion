<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Registration (Story #1) ──

test('user can register with valid data', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['user', 'token'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Another User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('registration fails with missing fields', function () {
    $response = $this->postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

// ── Login (Story #2) ──

test('user can login with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['user', 'token'],
        ]);
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'correctpassword',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

test('banned user cannot login', function () {
    User::factory()->create([
        'email' => 'banned@example.com',
        'password' => 'password123',
        'is_banned' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'banned@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

// ── Logout (Story #3) ──

test('user can logout and token is revoked', function () {
    $user = User::factory()->create();

    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/auth/logout')
        ->assertStatus(204);

    // Token should be deleted from database
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

// ── Me (Story #4) ──

test('authenticated user can get profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

test('unauthenticated request to me returns 401', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401);
});

// ── Admin Middleware (Story #71) ──

test('admin user can access admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // We'll test via a dummy inline route registered for testing
    \Illuminate\Support\Facades\Route::middleware(['auth:sanctum', 'admin'])
        ->get('/api/test-admin', fn() => response()->json(['ok' => true]));

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/test-admin')
        ->assertOk();
});

test('regular user cannot access admin routes', function () {
    $user = User::factory()->create(['role' => 'user']);

    \Illuminate\Support\Facades\Route::middleware(['auth:sanctum', 'admin'])
        ->get('/api/test-admin-deny', fn() => response()->json(['ok' => true]));

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/test-admin-deny')
        ->assertStatus(403);
});
