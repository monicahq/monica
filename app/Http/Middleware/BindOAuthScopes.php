<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\OAuthConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps each scope with the client it was made for, which is the one rule that tells clients apart.
 * The API scope belongs to the mobile apps and the MCP scope to everybody else, so an assistant
 * registered on its own cannot reach the whole API and the app cannot reach the MCP server.
 *
 * Every client sees the consent page at every sign-in: a return address can be claimed by another
 * app, so nothing is approved without the person saying so.
 */
class BindOAuthScopes
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.authorizations.authorize', 'passport.token')) {
            return $next($request);
        }

        $isMobileApp = OAuthConnection::clientIdOf($request) === OAuthConnection::MOBILE_CLIENT_ID;
        $scopes = $request->string('scope')->explode(' ')->filter()->values();

        $refused = $isMobileApp
            ? $scopes->contains(Registrar::OAUTH_SCOPE)
            : $scopes->contains(OAuthConnection::API_SCOPE);

        if ($refused) {
            return $this->refuse($request);
        }

        if ($request->routeIs('passport.authorizations.authorize')) {
            // Passport drops every other value next to `none` and then approves silently, so `none` goes.
            $prompt = $request->string('prompt')->explode(' ')->filter()->reject(fn (string $value): bool => $value === 'none')
                ->push('consent')->unique()->implode(' ');
            $request->query->set('prompt', $prompt);
        }

        return $next($request);
    }

    private function refuse(Request $request): Response
    {
        $redirectUri = $request->string('redirect_uri')->toString();

        if ($request->routeIs('passport.authorizations.authorize') && $this->belongsToTheClient($request, $redirectUri)) {
            return redirect()->away($redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query([
                'error' => 'invalid_scope',
                'state' => $request->string('state')->toString(),
            ]));
        }

        return response()->json([
            'error' => 'invalid_scope',
            'error_description' => 'This client may not ask for this scope.',
        ], 400);
    }

    /**
     * Whether the client registered this return address. A client id that is not a UUID names no
     * client, and is not sent to the database, which would refuse it on PostgreSQL.
     */
    private function belongsToTheClient(Request $request, string $redirectUri): bool
    {
        $clientId = OAuthConnection::clientIdOf($request);

        if ($redirectUri === '' || ! Str::isUuid($clientId)) {
            return false;
        }

        $client = Client::query()->find($clientId);

        return $client !== null && in_array($redirectUri, $client->getAttribute('redirect_uris'), true);
    }
}
