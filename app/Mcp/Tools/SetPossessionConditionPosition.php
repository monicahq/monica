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

#[Description('Move a possession condition to another place in the order, which runs from best to worst. A position past the end puts it last. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetPossessionConditionPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.position';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_condition' => ['required', 'string'],
        ]);

        $possessionCondition = $this->possessionCondition($request, $validated['possession_condition']);

        return $this->answerThroughApi($request, $possessionCondition->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_condition' => $schema->string()->description('The id of the possession condition, as list-possession-conditions gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the order, counting from 1.')->required(),
        ];
    }
}
