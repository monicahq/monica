<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionMaintenanceRecord as UpdatePossessionMaintenanceRecordAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Http\Resources\PossessionMaintenanceRecordResource;
use App\Models\PossessionCondition;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a maintenance record of a possession. A field left out keeps what the record says; a field sent as null is cleared. The change is written to the account journal.')]
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
            'maintenance_type' => ['sometimes', Rule::enum(PossessionMaintenanceTypeEnum::class)],
            'title' => ['sometimes', 'string', 'max:255'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'performed_on' => ['sometimes', 'date_format:Y-m-d'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'condition_before' => ['nullable', 'string'],
            'condition_after' => ['nullable', 'string'],
            'next_due_on' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $record = $this->possessionMaintenanceRecord($possession, $validated['maintenance_record']);

        $kept = function (string $field) use ($validated, $record): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $record->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        $condition = fn (string $field): ?PossessionCondition => $validated[$field] === null ? null : $this->possessionCondition($request, $validated[$field]);

        new UpdatePossessionMaintenanceRecordAction(
            user: $this->user($request),
            record: $record,
            type: PossessionMaintenanceTypeEnum::from($validated['maintenance_type'] ?? $record->type->value),
            title: $kept('title') ?? $record->title,
            performedOn: CarbonImmutable::parse($kept('performed_on') ?? $record->performed_on),
            performedBy: $kept('performed_by'),
            cost: $kept('cost'),
            currency: CurrencyEnum::tryFrom((string) $kept('currency')),
            conditionBefore: array_key_exists('condition_before', $validated) ? $condition('condition_before') : $record->conditionBefore,
            conditionAfter: array_key_exists('condition_after', $validated) ? $condition('condition_after') : $record->conditionAfter,
            nextDueOn: $kept('next_due_on') === null ? null : CarbonImmutable::parse($kept('next_due_on')),
            description: $kept('description'),
        )->execute();

        return Response::json(new PossessionMaintenanceRecordResource($record->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'maintenance_record' => $schema->string()->description('The identifier of the maintenance record, as list-possession-maintenance-records gives it.')->required(),
            'maintenance_type' => $schema->string()->enum(array_column(PossessionMaintenanceTypeEnum::cases(), 'value'))->description('The kind of job.'),
            'title' => $schema->string()->description('What was done.'),
            'performed_by' => $schema->string()->description('Who did the job, as text.'),
            'performed_on' => $schema->string()->description('The day the job was done, as YYYY-MM-DD, not after today.'),
            'cost' => $schema->number()->description('What the job cost, 0 or more. Needs a currency.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the cost.'),
            'condition_before' => $schema->string()->description('The identifier of the condition before the job, as list-possession-conditions gives it.'),
            'condition_after' => $schema->string()->description('The identifier of the condition after the job.'),
            'next_due_on' => $schema->string()->description('The day the job should be done again, as YYYY-MM-DD, not before the job.'),
            'description' => $schema->string()->description('What was found and what was done.'),
        ];
    }
}
