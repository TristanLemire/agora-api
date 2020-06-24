<?php

namespace App\Services;

class ErrorJsonHelper
{
    public static function errorMessage(int $response, string $message): array
    {
        return [
            'status' => $response,
            'message' => $message,
        ];
    }
}