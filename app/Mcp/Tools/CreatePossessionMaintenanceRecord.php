<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionMaintenanceRecord as CreatePossessionMaintenanceRecordAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Http\Resources\PossessionMaintenanceRecordResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record one job done on a possession: the type, a title and the day it was performed are required. Record a new one for every job rather than changing an earlier one, so the history of the work is kept. The conditions are the account\'s possession conditions, as list-possession-conditions gives them. The change is written to the account journal.')]
class CreatePossessionMaintenanceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.maintenanceRecords.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'maintenance_type' => ['required', Rule::enum(PossessionMaintenanceTypeEnum::class)],
            'title' => ['required', 'string', 'max:255'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'performed_on' => ['required', 'date_format:Y-m-d'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'condition_before' => ['nullable', 'string'],
            'condition_after' => ['nullable', 'string'],
            'next_due_on' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        $record = new CreatePossessionMaintenanceRecordAction(
            user: $this->user($request),
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::from($validated['maintenance_type']),
            title: $validated['title'],
            performedOn: CarbonImmutable::parse($validated['performed_on']),
            performedBy: $validated['performed_by'] ?? null,
            cost: isset($validated['cost']) ? (string) $validated['cost'] : null,
            currency: isset($validated['currency']) ? CurrencyEnum::from($validated['currency']) : null,
            conditionBefore: isset($validated['condition_before']) ? $this->possessionCondition($request, $validated['condition_before']) : null,
            conditionAfter: isset($validated['condition_after']) ? $this->possessionCondition($request, $validated['condition_after']) : null,
            nextDueOn: isset($validated['next_due_on']) ? CarbonImmutable::parse($validated['next_due_on']) : null,
            description: $validated['description'] ?? null,
        )->execute();

        return Response::json(new PossessionMaintenanceRecordResource($record)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'maintenance_type' => $schema->string()->enum(array_column(PossessionMaintenanceTypeEnum::cases(), 'value'))->description('The kind of job.')->required(),
            'title' => $schema->string()->description('What was done, such as re-plating or a new gasket.')->required(),
            'performed_by' => $schema->string()->description('Who did the job, a person or a workshop, as text.'),
            'performed_on' => $schema->string()->description('The day the job was done, as YYYY-MM-DD, not after today.')->required(),
            'cost' => $schema->number()->description('What the job cost, 0 or more. Needs a currency.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the cost.'),
            'condition_before' => $schema->string()->description('The identifier of the condition the possession was in before the job, as list-possession-conditions gives it.'),
            'condition_after' => $schema->string()->description('The identifier of the condition the possession was in after the job.'),
            'next_due_on' => $schema->string()->description('The day the job should be done again, as YYYY-MM-DD, not before the job.'),
            'description' => $schema->string()->description('What was found and what was done.'),
        ];
    }
}
