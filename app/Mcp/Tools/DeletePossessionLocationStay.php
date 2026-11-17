<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete one stay of a possession. The other stays are not moved, so its period shows as unrecorded. Deleting the stay where it is now leaves the possession with no current location until it is moved. No tool brings the stay back. The change is written to the account log.')]
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

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $stay = $this->possessionLocationStay($possession, $validated['stay']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $stay->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The stay is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'stay' => $schema->string()->description('The id of the stay, as list-possession-location-stays gives it.')->required(),
        ];
    }
}
