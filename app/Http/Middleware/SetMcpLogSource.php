<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\LogSourceEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class SetMcpLogSource
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $key = Auth::guard('sanctum')->user()?->currentAccessToken();

        $request->attributes->set('log_source', LogSourceEnum::Mcp);
        $request->attributes->set('log_source_detail', $key instanceof PersonalAccessToken ? $key->name : null);

        return $next($request);
    }
}
