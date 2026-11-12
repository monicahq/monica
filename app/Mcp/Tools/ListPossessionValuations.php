<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionValuationResource;
use App\Models\PossessionValuation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the valuations of a possession, the latest by date first: the type, the date, the amount in its currency, the valuer, the confidence and which valuation gives the current value.')]
#[IsReadOnly]
class ListPossessionValuations extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.valuations';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $possession->load('valuations.documents');

        $valuations = $possession->valuations
            ->each(fn (PossessionValuation $valuation) => $valuation->setRelation('possession', $possession));

        return Response::json(['data' => PossessionValuationResource::collection($valuations)->resolve()]);
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
