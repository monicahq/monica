<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionFieldGroup as CreatePossessionFieldGroupAction;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a named group of fields to a possession type, such as Purchase or Technical details, placed after the other groups. The change is written to the account journal.')]
class CreatePossessionFieldGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFieldGroups.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new CreatePossessionFieldGroupAction(
            user: $this->user($request),
            possessionType: $possessionType,
            name: $validated['name'],
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'name' => $schema->string()->description('What the group is called.')->required(),
        ];
    }
}
