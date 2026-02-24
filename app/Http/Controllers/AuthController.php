<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     * Story #1 — FR-AUTH-01
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Hashed automatically via cast
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], status: 201);
    }

    /**
     * Login and return Sanctum token.
     * Story #2 — FR-AUTH-02
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials.', status: 401);
        }

        $user = Auth::user();

        if ($user->is_banned) {
            Auth::logout();
            return $this->errorResponse('Your account has been banned.', status: 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout — revoke current token.
     * Story #3 — FR-AUTH-04
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, status: 204);
    }

    /**
     * Get authenticated user profile.
     * Story #4 — FR-AUTH-05
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user());
    }
}
