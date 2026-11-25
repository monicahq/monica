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

#[Description('Rename a value list. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateValueList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.valueLists.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'value_list' => ['required'],
        ]);

        $valueList = $this->valueList($request, (string) $validated['value_list']);

        return $this->answerThroughApi($request, $valueList->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value_list' => $schema->integer()->description('The id of the value list, as list-value-lists gives it.')->required(),
            'name' => $schema->string()->description('The new name of the list, up to 255 characters.')->required(),
        ];
    }
}
