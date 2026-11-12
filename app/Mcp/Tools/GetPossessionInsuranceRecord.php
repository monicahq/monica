<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionInsuranceRecordResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one insurance record of a possession: the insurer, the status, the insured value and the excess, the period, who to call at the insurer and whether it is the current record.')]
#[IsReadOnly]
class GetPossessionInsuranceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'insurance_record' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        return Response::json(new PossessionInsuranceRecordResource($this->possessionInsuranceRecord($possession, $validated['insurance_record']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'insurance_record' => $schema->string()->description('The identifier of the insurance record, as list-possession-insurance-records gives it.')->required(),
        ];
    }
}
