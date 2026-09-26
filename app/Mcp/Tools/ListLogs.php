<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\LogResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List what has been done in the account, newest first, a page at a time: who did it, what it was, where it came from and the address it came from.')]
#[IsReadOnly]
class ListLogs extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.logs';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $logs = $this->user($request)->account->logs()
            ->with('user')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => LogResource::collection($logs->items())->resolve(),
            'meta' => [
                'page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many entries a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
