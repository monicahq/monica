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

#[Description('Bring one item back from the trash of a vault, with everything deleted at the same time as it. An item deleted with a person or a group still in the trash cannot come back before them. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class RestoreTrashItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.trash.restoration.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'kind' => ['required', 'string'],
            'item' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $item = $this->trashItem($vault, $validated['kind'], $validated['item']);

        return $this->answerThroughApi($request, $vault->uuid, $item->kind->value, $item->uuid());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'kind' => $schema->string()->enum(['contact', 'entity', 'group', 'relationship', 'life-event', 'reminder', 'group-membership', 'group-note', 'group-file'])->description('The kind of the item, as list-trash gives it.')->required(),
            'item' => $schema->string()->description('The id of the item, as list-trash gives it.')->required(),
        ];
    }
}
