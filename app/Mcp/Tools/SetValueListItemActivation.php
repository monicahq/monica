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

#[Description('Stop offering a value of a value list, or offer it again. Everything that already has it keeps it. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetValueListItemActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.valueLists.items.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'value_list' => ['required'],
            'value_list_item' => ['required'],
        ]);

        $valueList = $this->valueList($request, (string) $validated['value_list']);
        $item = $this->valueListItem($valueList, (string) $validated['value_list_item']);

        return $this->answerThroughApi($request, (string) $valueList->id, (string) $item->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value_list' => $schema->integer()->description('The id of the value list, as list-value-lists gives it.')->required(),
            'value_list_item' => $schema->integer()->description('The id of the value, as list-value-lists gives it among the items of its list.')->required(),
            'is_active' => $schema->boolean()->description('True to offer the value, false to stop offering it.')->required(),
        ];
    }
}
