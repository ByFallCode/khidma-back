<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public function issue(User $user): string
    {
        $now = time();

        return JWT::encode([
            'roles' => ['ROLE_'.$user->account_type],
            'sub' => $user->username,
            'iat' => $now,
            'exp' => $now + config('jwt.ttl'),
        ], config('jwt.secret'), 'HS256');
    }

    public function subject(string $token): string
    {
        $payload = JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));

        return (string) $payload->sub;
    }
}
