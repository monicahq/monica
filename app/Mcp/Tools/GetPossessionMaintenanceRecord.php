<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionMaintenanceRecordResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one maintenance record of a possession: the type, the title, who did it and when, the cost, the condition before and after, the next due date and whether it is overdue.')]
#[IsReadOnly]
class GetPossessionMaintenanceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.maintenanceRecords.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'maintenance_record' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        return Response::json(new PossessionMaintenanceRecordResource($this->possessionMaintenanceRecord($possession, $validated['maintenance_record']))->resolve());
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
