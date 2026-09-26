<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\EmailSentResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the emails sent to the account, newest first, a page at a time, and whether each arrived. The copy of each email is left out; read one to see it.')]
#[IsReadOnly]
class ListEmailsSent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.emailsSent';

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

        $emails = $this->user($request)->account->emailsSent()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => EmailSentResource::collection($emails->items())->resolve(),
            'meta' => [
                'page' => $emails->currentPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
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
            'per_page' => $schema->integer()->description("How many emails a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
