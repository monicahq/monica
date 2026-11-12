<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one possession type: its name, its colour, its icon, its groups and its fields in order, each field with its kind and, for a choice field, its choices.')]
#[IsReadOnly]
class GetPossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        return Response::json(new PossessionTypeResource($this->possessionType($request, $validated['possession_type']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
        ];
    }
}
