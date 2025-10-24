<?php

namespace App\Services\Auth;

use App\Models\User;
use Firebase\JWT\JWT;

class JwtTokenGenerator
{
    private const EXPIRATION_TIME = 1800;
    private string $secretKey = 'secret';
    private string $issuer = 'app';


    public function generate(User $user): string
    {
        $issuedAt = time();

        $payload = [
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'exp' => $issuedAt + self::EXPIRATION_TIME,
            'sub' => $user->id(),
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}
