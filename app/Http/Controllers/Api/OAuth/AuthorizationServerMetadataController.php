<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\OAuth;

use App\Http\Controllers\Controller;
use App\Models\MobileSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server\Registrar;

/**
 * The description of Monica's authorization server (RFC 8414). It keeps every member the MCP
 * server published for assistants, and adds what the mobile apps need: where to sign a phone out,
 * the API scope, and the prompt that opens the registration page.
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
            'scopes_supported' => [Registrar::OAUTH_SCOPE, MobileSession::SCOPE],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'prompt_values_supported' => ['none', 'login', 'consent', 'create'],
        ], fn (mixed $value): bool => $value !== null));
    }
}
