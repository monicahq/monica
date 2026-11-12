<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionLocationStay;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete one stay of a possession. The other stays are not moved, so its period shows as unrecorded. Deleting the stay where it is now leaves the possession with no current location until it is moved. No tool brings the stay back. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionLocationStay extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.locationStays.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'stay' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        new DestroyPossessionLocationStay(
            user: $this->user($request),
            stay: $this->possessionLocationStay($possession, $validated['stay']),
        )->execute();

        return Response::text(__('The stay is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'stay' => $schema->string()->description('The identifier of the stay, as list-possession-location-stays gives it.')->required(),
        ];
    }
}
