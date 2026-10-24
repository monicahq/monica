<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mcp\Tools;

use App\Models\McpCall;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Say what the MCP history holds about this call while it is still running.')]
class ReadOwnCallHistory extends Tool
{
    public function handle(Request $request): Response
    {
        $call = McpCall::query()->where('tool', $this->name())->latest('id')->first();

        return Response::json([
            'rows' => McpCall::query()->count(),
            'result' => $call?->result->value,
            'duration_in_milliseconds' => $call?->duration_in_milliseconds,
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
