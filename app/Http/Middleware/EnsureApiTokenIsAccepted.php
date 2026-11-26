<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\MobileSession;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API answers API keys, and OAuth tokens that carry the API scope, which only the mobile apps
 * can obtain. An assistant's OAuth token reaches the MCP server and nothing else.
 */
class EnsureApiTokenIsAccepted
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        abort_if($token instanceof AccessToken && $token->cant(MobileSession::SCOPE), 403);

        return $next($request);
    }
}
