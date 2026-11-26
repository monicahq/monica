<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\EndReplayedMobileSession;
use App\Actions\RenewMobileSession;
use App\Actions\StartMobileSession;
use App\Models\MobileSession;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps one session per phone across the token requests of the mobile apps: started when a code is
 * exchanged, moved along at every renewal, and ended when a code or a replaced refresh token comes
 * back. Passport gives every renewal a new token, so nothing else ties them to one phone.
 */
class TrackMobileSessions
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.token') || MobileSession::clientIdOf($request) !== MobileSession::CLIENT_ID) {
            return $next($request);
        }

        $grantType = $request->string('grant_type')->toString();
        $code = $request->string('code')->toString();
        $presentedRefreshToken = $request->string('refresh_token')->toString();

        new EndReplayedMobileSession(
            authorizationCode: $grantType === 'authorization_code' && $code !== '' ? $code : null,
            refreshToken: $grantType === 'refresh_token' && $presentedRefreshToken !== '' ? $presentedRefreshToken : null,
        )->execute();

        $response = $next($request);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        /** @var array{access_token?: string, refresh_token?: string} $tokens */
        $tokens = json_decode((string) $response->getContent(), true) ?: [];

        if (! isset($tokens['access_token'], $tokens['refresh_token'])) {
            return $response;
        }

        try {
            if ($grantType === 'authorization_code') {
                new StartMobileSession(
                    accessToken: $tokens['access_token'],
                    refreshToken: $tokens['refresh_token'],
                    authorizationCode: $code,
                    deviceName: $request->string('device_name')->toString(),
                )->execute();
            }

            if ($grantType === 'refresh_token') {
                new RenewMobileSession(
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
