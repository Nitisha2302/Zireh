<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta,
        ], $status);
    }

    protected function errorResponse(
        string $message = 'Something went wrong.',
        array $errors = [],
        int $status = 422,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => [],
        ], $status);
    }
}
