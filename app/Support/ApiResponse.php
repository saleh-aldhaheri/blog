<?php

namespace App\Support;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse
{
    public function success(mixed $data, string $message, int $code): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $data = $data->resolve();
        }

        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error(string $message, int $code, $errors = [])
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
