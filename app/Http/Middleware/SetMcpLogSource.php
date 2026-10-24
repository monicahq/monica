<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\LogSourceEnum;
use App\Enums\McpConnectionTypeEnum;
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
        $connection = $this->connection($request);

        $request->attributes->set('log_source', LogSourceEnum::Mcp);
        $request->attributes->set('log_source_detail', $connection['name'] ?? null);

        if ($connection !== null) {
            $request->attributes->set('mcp_connection_type', $connection['type']);
            $request->attributes->set('mcp_connection_id', $connection['id']);
            $request->attributes->set('mcp_connection_name', $connection['name']);
        }

        return $next($request);
    }

    /** @return array{type: McpConnectionTypeEnum, id: string, name: ?string}|null */
    private function connection(Request $request): ?array
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            return ['type' => McpConnectionTypeEnum::ApiKey, 'id' => (string) $token->getKey(), 'name' => $token->name];
        }

        if ($token instanceof AccessToken) {
            return [
                'type' => McpConnectionTypeEnum::OauthClient,
                'id' => (string) $token->oauth_client_id,
                'name' => Passport::client()->newQuery()->find($token->oauth_client_id)?->name,
            ];
        }

        return null;
    }
}
