<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionPosition;
use App\Http\Resources\PossessionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a featured possession to another place among the featured possessions of its vault, counted from 1. The others shift. Only a featured possession has a place. The change is written to the account journal.')]
class SetPossessionPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.position';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        new UpdatePossessionPosition(
            user: $this->user($request),
            possession: $possession,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new PossessionResource($possession->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'position' => $schema->integer()->description('Its new place among the featured possessions, from 1.')->required(),
        ];
    }
}
