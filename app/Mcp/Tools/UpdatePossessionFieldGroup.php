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

#[Description('Rename a group of fields of a possession type. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdatePossessionFieldGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFieldGroups.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'group' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);
        $group = $this->possessionFieldGroup($possessionType, $validated['group']);

        return $this->answerThroughApi($request, $possessionType->uuid, $group->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The id of the possession type, as list-possession-types gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as get-possession-type gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
        ];
    }
}
