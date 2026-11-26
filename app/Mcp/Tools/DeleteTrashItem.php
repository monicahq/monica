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

#[Description('Delete one item of the trash of a vault for good, with everything deleted at the same time as it. This cannot be undone. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteTrashItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.trash.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'kind' => ['required', 'string'],
            'item' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $item = $this->trashItem($vault, $validated['kind'], $validated['item']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $item->kind->value, $item->uuid());

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('Deleted for good.'));
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
