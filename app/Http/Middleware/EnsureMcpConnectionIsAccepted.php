<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\AccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The MCP server answers an API key or a connected assistant, and nothing else. A browser session
 * reaching it would be answered without ever being written in the MCP history, and an OAuth token
 * issued without the scope the MCP server advertises has no business there.
 */
class EnsureMcpConnectionIsAccepted
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof AccessToken) {
            abort_if($token->cant(Registrar::OAUTH_SCOPE), 403);

            return $next($request);
        }

        abort_unless($token instanceof PersonalAccessToken, 401);

        return $next($request);
    }
}
