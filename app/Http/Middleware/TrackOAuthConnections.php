<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\EndReplayedOAuthConnection;
use App\Actions\RenewOAuthConnection;
use App\Actions\StartOAuthConnection;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps one connection per app across its token requests, whatever the client: started when a code
 * is exchanged, moved along at every renewal, and ended when a code or a replaced refresh token comes
 * back. Passport gives every renewal a new token, so nothing else ties them to one app.
 */
class TrackOAuthConnections
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.token')) {
            return $next($request);
        }

        $grantType = $request->string('grant_type')->toString();
        $code = $request->string('code')->toString();
        $presentedRefreshToken = $request->string('refresh_token')->toString();

        new EndReplayedOAuthConnection(
            authorizationCode: $grantType === 'authorization_code' && $code !== '' ? $code : null,
            refreshToken: $grantType === 'refresh_token' && $presentedRefreshToken !== '' ? $presentedRefreshToken : null,
        )->execute();

        $response = $next($request);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        /** @var array{access_token?: string, refresh_token?: string} $tokens */
        $tokens = json_decode((string) $response->getContent(), true) ?: [];

        if (! isset($tokens['access_token'])) {
            return $response;
        }

        try {
            if ($grantType === 'authorization_code') {
                new StartOAuthConnection(
                    accessToken: $tokens['access_token'],
                    refreshToken: $tokens['refresh_token'] ?? null,
                    authorizationCode: $code,
                    label: $request->string('device_name')->toString(),
                )->execute();
            }

            if ($grantType === 'refresh_token' && isset($tokens['refresh_token'])) {
                new RenewOAuthConnection(
                    presentedRefreshToken: $presentedRefreshToken,
                    accessToken: $tokens['access_token'],
                    refreshToken: $tokens['refresh_token'],
                )->execute();
            }
        } catch (ModelNotFoundException $e) {
            report($e);
        }

        return $response;
    }
}
