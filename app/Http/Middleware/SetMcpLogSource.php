<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\LogSourceEnum;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class SetMcpLogSource
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('log_source', LogSourceEnum::Mcp);
        $request->attributes->set('log_source_detail', $this->callerName($request));

        return $next($request);
    }

    private function callerName(Request $request): ?string
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            return $token->name;
        }

        if ($token instanceof AccessToken) {
            return Passport::client()->newQuery()->find($token->oauth_client_id)?->name;
        }

        return null;
    }
}
