<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionFieldGroup as UpdatePossessionFieldGroupAction;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a group of fields of a possession type. The change is written to the account journal.')]
class UpdatePossessionFieldGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFieldGroups.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'group' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new UpdatePossessionFieldGroupAction(
            user: $this->user($request),
            group: $this->possessionFieldGroup($possessionType, $validated['group']),
            name: $validated['name'],
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'group' => $schema->string()->description('The identifier of the group, as get-possession-type gives it.')->required(),
            'name' => $schema->string()->description('The new name.')->required(),
        ];
    }
}
