<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureMcpConnectionIsAccepted;
use App\Http\Middleware\SetMcpLogSource;
use App\Mcp\Servers\EricaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware('throttle:10,1')->group(function (): void {
    Mcp::oauthRoutes();
});

Mcp::web('mcp', EricaServer::class)
    ->middleware(['api', 'auth:sanctum,api', 'flag:mcp-server', EnsureMcpConnectionIsAccepted::class, 'throttle:mcp', SetMcpLogSource::class])
    ->withoutMiddleware('throttle:api');
