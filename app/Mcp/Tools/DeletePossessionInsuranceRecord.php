<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionInsuranceRecord;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete an insurance record of a possession. The history of how the possession was covered loses that policy period. No tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionInsuranceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'insurance_record' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        new DestroyPossessionInsuranceRecord(
            user: $this->user($request),
            record: $this->possessionInsuranceRecord($possession, $validated['insurance_record']),
        )->execute();

        return Response::text(__('The insurance record is deleted.'));
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
