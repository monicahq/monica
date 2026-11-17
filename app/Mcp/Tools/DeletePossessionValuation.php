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

#[Description('Delete a valuation of a possession, with the documents attached to it. When it gave the current value, the latest valuation left gives it instead. To correct a figure, record a new valuation rather than deleting this one. No tool brings it back. The change is written to the account log.')]
#[IsDestructive]
class DeletePossessionValuation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.valuations.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'valuation' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $record = $this->possessionValuation($possession, $validated['valuation']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $record->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The valuation is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'valuation' => $schema->string()->description('The id of the valuation, as list-possession-valuations gives it.')->required(),
        ];
    }
}
