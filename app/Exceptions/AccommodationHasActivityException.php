<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class AccommodationHasActivityException extends RuntimeException
{
    public function __construct(string $code)
    {
        parent::__construct($code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'httpCode' => 409,
            'code' => $this->getMessage(),
            'message' => $this->getMessage(),
            'errors' => [],
            'validationErrors' => [],
        ], 409);
    }
}
