<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OAuth\AuthorizationServerMetadataController;
use App\Http\Controllers\Api\OAuth\RevocationController;
use App\Http\Middleware\EnsureMcpConnectionIsAccepted;
use App\Http\Middleware\SetMcpLogSource;
use App\Mcp\Servers\MonicaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// How the mobile apps and assistants find the authorization server, and where a phone signs out.
// Declared before the MCP routes, which then leave their own description out.
Route::get('.well-known/oauth-authorization-server', [AuthorizationServerMetadataController::class, 'show'])->middleware('throttle:60,1')->name('oauth.metadata.show');
Route::post('oauth/revoke', [RevocationController::class, 'create'])->middleware('throttle:oauth')->name('oauth.revocations.create');

Route::middleware('throttle:10,1')->group(function (): void {
    Mcp::oauthRoutes();
});

Mcp::web('mcp', MonicaServer::class)
    ->middleware(['api', 'auth:sanctum,api', 'flag:mcp-server', EnsureMcpConnectionIsAccepted::class, 'throttle:mcp', SetMcpLogSource::class])
    ->withoutMiddleware('throttle:api');
