<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * List all users with stats. Story #58
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->withCount('watchHistory')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return $this->successResponse(
            $users->items(),
            [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        );
    }

    /**
     * Ban user + revoke all tokens. Story #58
     */
    public function ban(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', status: 404);
        }

        $user->update(['is_banned' => true]);
        $user->tokens()->delete();

        return $this->successResponse(['message' => 'User banned and all tokens revoked.']);
    }

    /**
     * Unban user. Story #58
     */
    public function unban(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', status: 404);
        }

        $user->update(['is_banned' => false]);

        return $this->successResponse(['message' => 'User unbanned.']);
    }

    /**
     * Reset password with temp password. Story #58
     */
    public function resetPassword(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', status: 404);
        }

        $tempPassword = Str::random(12);
        $user->update(['password' => bcrypt($tempPassword)]);
        $user->tokens()->delete();

        return $this->successResponse([
            'message' => 'Password reset successfully.',
            'temp_password' => $tempPassword,
        ]);
    }
}
