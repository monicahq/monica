<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ApiKeyScopeEnum;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the instance-wide part of the API closed to a key that was not minted for it. Every key holds
 * `*`, so asking `tokenCan()` would let any key of an administrator through: the key has to carry
 * the administration scope by name. A token of a phone never carries it, so it is always refused.
 * The application's own screens hold no key and pass. Refused as
 * not found, like the rest of the admin space.
 */
class EnsureKeyCanAdminister
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof AccessToken) {
            abort(404);
        }

        if ($token instanceof PersonalAccessToken && ! in_array(ApiKeyScopeEnum::Administration->value, $token->abilities ?? [], true)) {
            abort(404);
        }

        return $next($request);
    }
}
