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

#[Description('Move a value of a value list to another place in its list, the first being 1. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetValueListItemPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.valueLists.items.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'value_list' => ['required'],
            'value_list_item' => ['required'],
        ]);

        $valueList = $this->valueList($request, (string) $validated['value_list']);
        $item = $this->valueListItem($valueList, (string) $validated['value_list_item']);

        return $this->answerThroughApi($request, $valueList->id, $item->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value_list' => $schema->integer()->description('The id of the value list, as list-value-lists gives it.')->required(),
            'value_list_item' => $schema->integer()->description('The id of the value, as list-value-lists gives it among the items of its list.')->required(),
            'position' => $schema->integer()->description('The place to move it to, the first being 1.')->required(),
        ];
    }
}
