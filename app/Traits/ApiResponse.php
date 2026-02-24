<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function successResponse(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $response = ['data' => $data];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $response = [
            'error' => true,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
