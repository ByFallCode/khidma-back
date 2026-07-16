<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request, JwtService $jwt): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! $user->statut || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'httpCode' => 400,
                'code' => 'AUTH_BAD_CREDENTIALS',
                'message' => 'Bad credentials',
                'errors' => [],
                'validationErrors' => [],
            ], 400);
        }

        return response()->json(['token' => $jwt->issue($user)]);
    }
}
