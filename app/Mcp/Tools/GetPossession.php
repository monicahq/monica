<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Look one thing owned up in a vault, with its type, its location and the values of its fields.')]
#[IsReadOnly]
class GetPossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return Response::json(new PossessionResource($this->possession($vault, $validated['possession']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
        ];
    }
}
