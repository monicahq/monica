<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionMaintenanceRecord;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a maintenance record of a possession. The history of the work done on it loses that job. No tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionMaintenanceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.maintenanceRecords.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'maintenance_record' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        new DestroyPossessionMaintenanceRecord(
            user: $this->user($request),
            record: $this->possessionMaintenanceRecord($possession, $validated['maintenance_record']),
        )->execute();

        return Response::text(__('The maintenance record is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'maintenance_record' => $schema->string()->description('The identifier of the maintenance record, as list-possession-maintenance-records gives it.')->required(),
        ];
    }
}
