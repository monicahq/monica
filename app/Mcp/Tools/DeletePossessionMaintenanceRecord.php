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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Delete a maintenance record of a possession. The history of the work done on it loses that job. No tool brings it back. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
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

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $record = $this->possessionMaintenanceRecord($possession, $validated['maintenance_record']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $record->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The maintenance record is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'maintenance_record' => $schema->string()->description('The id of the maintenance record, as list-possession-maintenance-records gives it.')->required(),
        ];
    }
}
