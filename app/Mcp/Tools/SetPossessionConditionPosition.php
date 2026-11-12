<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionConditionPosition;
use App\Http\Resources\PossessionConditionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a possession condition to another place in the order, which runs from best to worst. A position past the end puts it last. The change is written to the account journal.')]
class SetPossessionConditionPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.position';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_condition' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $possessionCondition = new UpdatePossessionConditionPosition(
            user: $this->user($request),
            possessionCondition: $this->possessionCondition($request, $validated['possession_condition']),
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new PossessionConditionResource($possessionCondition)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_condition' => $schema->string()->description('The identifier of the possession condition, as list-possession-conditions gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the order, counting from 1.')->required(),
        ];
    }
}
