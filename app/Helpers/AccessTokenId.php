<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Reads the id Passport writes into an access token it has just issued. The token is Monica's own
 * answer, so its payload is read without checking the signature.
 */
class AccessTokenId
{
    public static function of(string $accessToken): ?string
    {
        $parts = explode('.', $accessToken);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);

        return is_array($payload) && is_string($payload['jti'] ?? null) ? $payload['jti'] : null;
    }
}
