<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionFieldPosition;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a field to another place among the fields of its group, or among the standalone fields of its possession type. The change is written to the account journal.')]
class SetPossessionFieldPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.position';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'field' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new UpdatePossessionFieldPosition(
            user: $this->user($request),
            field: $this->possessionField($possessionType, $validated['field']),
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'field' => $schema->string()->description('The identifier of the field, as get-possession-type gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes among its siblings, counting from 1.')->required(),
        ];
    }
}
