<?php

namespace App\Security;

use App\Entity\User;

final class JwtTokenManager
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds = 3600,
    ) {
    }

    public function getTtlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    public function createToken(User $user, ?int $now = null): string
    {
        $issuedAt = $now ?? time();

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttlSeconds,
        ];

        $encodedHeader = self::base64UrlEncode(json_encode($header, \JSON_THROW_ON_ERROR));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, \JSON_THROW_ON_ERROR));
        $signingInput = $encodedHeader.'.'.$encodedPayload;
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);

        return $signingInput.'.'.self::base64UrlEncode($signature);
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $token, ?int $now = null): array
    {
        $parts = explode('.', $token);
        if (\count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $headerJson = self::base64UrlDecode($encodedHeader);
        $payloadJson = self::base64UrlDecode($encodedPayload);

        $header = json_decode($headerJson, true, 512, \JSON_THROW_ON_ERROR);
        $payload = json_decode($payloadJson, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($header) || !\is_array($payload)) {
            throw new \InvalidArgumentException('Invalid token');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new \InvalidArgumentException('Unsupported token algorithm');
        }

        $signingInput = $encodedHeader.'.'.$encodedPayload;
        $expectedSig = hash_hmac('sha256', $signingInput, $this->secret, true);
        $providedSig = self::base64UrlDecode($encodedSignature);

        if (!hash_equals($expectedSig, $providedSig)) {
            throw new \InvalidArgumentException('Invalid token signature');
        }

        $timestamp = $now ?? time();
        $exp = $payload['exp'] ?? null;
        if (!\is_int($exp) || $exp < $timestamp) {
            throw new \InvalidArgumentException('Token expired');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64url');
        }

        return $decoded;
    }
}
