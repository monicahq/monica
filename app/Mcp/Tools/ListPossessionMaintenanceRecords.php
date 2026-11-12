<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionMaintenanceRecordResource;
use App\Models\PossessionMaintenanceRecord;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the maintenance records of a possession, the latest job first: the type, the title, who did it and when, the cost, the condition before and after, the next due date and whether it is overdue. Only the latest record can be overdue.')]
#[IsReadOnly]
class ListPossessionMaintenanceRecords extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.maintenanceRecords';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $possession->load('maintenanceRecords.conditionBefore', 'maintenanceRecords.conditionAfter');

        $records = $possession->maintenanceRecords
            ->each(fn (PossessionMaintenanceRecord $record) => $record->setRelation('possession', $possession));

        return Response::json(['data' => PossessionMaintenanceRecordResource::collection($records)->resolve()]);
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
