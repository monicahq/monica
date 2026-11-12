<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionInsuranceRecordResource;
use App\Models\PossessionInsuranceRecord;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the insurance records of a possession, one per policy period, active first, then pending, expired and cancelled, the latest start first within each: the insurer, the insured value, the period and which record is the current one.')]
#[IsReadOnly]
class ListPossessionInsuranceRecords extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $possession->load('insuranceRecords');

        $records = $possession->insuranceRecordsInOrder()
            ->each(fn (PossessionInsuranceRecord $record) => $record->setRelation('possession', $possession));

        return Response::json(['data' => PossessionInsuranceRecordResource::collection($records)->resolve()]);
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
