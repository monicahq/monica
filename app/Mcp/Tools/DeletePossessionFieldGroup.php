<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionFieldGroup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove a group from a possession type. Its fields are kept and become standalone fields of the type, after the ones already there. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionFieldGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFieldGroups.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'group' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new DestroyPossessionFieldGroup(
            user: $this->user($request),
            group: $this->possessionFieldGroup($possessionType, $validated['group']),
        )->execute();

        return Response::text(__('The group is removed. Its fields now stand alone.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'group' => $schema->string()->description('The identifier of the group, as get-possession-type gives it.')->required(),
        ];
    }
}
