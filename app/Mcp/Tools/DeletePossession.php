<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossession;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a possession and the values of its fields. It leaves every list and no tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        new DestroyPossession(
            user: $this->user($request),
            possession: $this->possession($this->vault($request, $validated['vault']), $validated['possession']),
        )->execute();

        return Response::text(__('The possession is deleted.'));
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
