<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server\Registrar;

/**
 * The description of Monica's authorization server (RFC 8414). It keeps every member the MCP
 * server published, and adds where an app signs itself out and the prompt that opens the
 * registration page. `none` is not offered, since every client is asked for consent every time.
 * The API scope is left out of the scopes listed:
 * an assistant asking for every scope listed would be refused, and the app reads it from the status.
 */
class AuthorizationServerMetadataController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(array_filter([
            'issuer' => config('mcp.authorization_server') ?? url('/'),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'revocation_endpoint' => route('oauth.revocations.create'),
            'revocation_endpoint_auth_methods_supported' => ['none'],
            'registration_endpoint' => Route::has('mcp.oauth.register') ? route('mcp.oauth.register') : null,
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => [Registrar::OAUTH_SCOPE],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'prompt_values_supported' => ['login', 'consent', 'create'],
        ], fn (mixed $value): bool => $value !== null));
    }
}
