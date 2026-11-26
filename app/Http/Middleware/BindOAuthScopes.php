<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\MobileSession;
use Closure;
use Illuminate\Http\Request;
use Laravel\Mcp\Server\Registrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps each scope with the client it was made for. The API scope belongs to the mobile apps and
 * the MCP scope to assistants, so an assistant registered on its own cannot reach the whole API
 * and the app cannot reach the MCP server.
 *
 * The mobile client also sees the consent page at every sign-in: its return address is a private
 * scheme another app could claim, so nothing is approved without the person saying so.
 */
class BindOAuthScopes
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.authorizations.authorize', 'passport.token')) {
            return $next($request);
        }

        $isMobileApp = MobileSession::clientIdOf($request) === MobileSession::CLIENT_ID;
        $scopes = $request->string('scope')->explode(' ')->filter()->values();

        $refused = $isMobileApp
            ? $scopes->contains(Registrar::OAUTH_SCOPE)
            : $scopes->contains(MobileSession::SCOPE);

        if ($refused) {
            return $this->refuse($request, $isMobileApp);
        }

        if ($isMobileApp && $request->routeIs('passport.authorizations.authorize')) {
            // Passport drops every other value next to `none` and then approves silently, so `none` goes.
            $prompt = $request->string('prompt')->explode(' ')->filter()->reject(fn (string $value): bool => $value === 'none')
                ->push('consent')->unique()->implode(' ');
            $request->query->set('prompt', $prompt);
        }

        return $next($request);
    }

    private function refuse(Request $request, bool $isMobileApp): Response
    {
        $sendsBackToTheApp = $isMobileApp
            && $request->routeIs('passport.authorizations.authorize')
            && $request->string('redirect_uri')->toString() === MobileSession::REDIRECT_URI;

        if ($sendsBackToTheApp) {
            return redirect()->away(MobileSession::REDIRECT_URI.'?'.http_build_query([
                'error' => 'invalid_scope',
                'state' => $request->string('state')->toString(),
            ]));
        }

        return response()->json([
            'error' => 'invalid_scope',
            'error_description' => 'This client may not ask for this scope.',
        ], 400);
    }
}
