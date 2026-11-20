<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Change a maintenance record of a possession. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdatePossessionMaintenanceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.maintenanceRecords.update';

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

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $record->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'maintenance_record' => $schema->string()->description('The id of the maintenance record, as list-possession-maintenance-records gives it.')->required(),
            'maintenance_type' => $schema->string()->enum(array_column(PossessionMaintenanceTypeEnum::cases(), 'value'))->description('The kind of job.'),
            'title' => $schema->string()->description('What was done, such as re-plating or a new gasket.'),
            'performed_by' => $schema->string()->description('Who did the job, a person or a workshop, as text.'),
            'performed_on' => $schema->string()->description('The day the job was done, as YYYY-MM-DD, not after today.'),
            'cost' => $schema->number()->description('What the job cost, 0 or more. Needs a currency.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the cost.'),
            'condition_before_id' => $schema->string()->description('The id of the condition the possession was in before the job, as list-possession-conditions gives it.'),
            'condition_after_id' => $schema->string()->description('The id of the condition the possession was in after the job, as list-possession-conditions gives it.'),
            'next_due_on' => $schema->string()->description('The day the job should be done again, as YYYY-MM-DD, not before the job.'),
            'description' => $schema->string()->description('What was found and what was done.'),
        ];
    }
}
