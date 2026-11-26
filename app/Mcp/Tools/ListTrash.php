<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List what was deleted from one vault and still waits in its trash, newest first, a page at a time: each item with its kind, when it is deleted for good, what it was deleted with, and whether it can be restored now. Without a kind, an item deleted with a person or a group is left out, since it comes back with them.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListTrash extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.trash';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'kind' => $schema->string()->enum(['contact', 'entity', 'group', 'relationship', 'life-event', 'reminder', 'group-membership', 'group-note', 'group-file'])->description('Only the items of this kind, those deleted with a person or a group included.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many items a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
