<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionInsuranceRecord as UpdatePossessionInsuranceRecordAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Http\Resources\PossessionInsuranceRecordResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change an insurance record of a possession. What you leave out keeps its value, and what you send as null is cleared. The other records are not touched. The change is written to the account journal.')]
class UpdatePossessionInsuranceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'insurance_record' => ['required', 'string'],
            'insurer' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(PossessionInsuranceStatusEnum::class)],
            'policy_number' => ['nullable', 'string', 'max:255'],
            'coverage' => ['nullable', 'string', 'max:255'],
            'insured_value' => ['sometimes', 'numeric', 'min:0'],
            'excess' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d'],
            'is_listed_individually' => ['sometimes', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $record = $this->possessionInsuranceRecord($possession, $validated['insurance_record']);

        $kept = function (string $field) use ($validated, $record): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $record->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        new UpdatePossessionInsuranceRecordAction(
            user: $this->user($request),
            record: $record,
            insurer: $kept('insurer') ?? $record->insurer,
            status: PossessionInsuranceStatusEnum::from($kept('status') ?? $record->status->value),
            insuredValue: $kept('insured_value') ?? $record->insured_value,
            currency: CurrencyEnum::from($kept('currency') ?? $record->currency->value),
            policyNumber: $kept('policy_number'),
            coverage: $kept('coverage'),
            excess: $kept('excess'),
            startsOn: $kept('starts_on') === null ? null : CarbonImmutable::parse($kept('starts_on')),
            endsOn: $kept('ends_on') === null ? null : CarbonImmutable::parse($kept('ends_on')),
            isListedIndividually: (bool) $kept('is_listed_individually'),
            contactName: $kept('contact_name'),
            contactEmail: $kept('contact_email'),
            contactPhone: $kept('contact_phone'),
            note: $kept('note'),
        )->execute();

        return Response::json(new PossessionInsuranceRecordResource($record->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'insurance_record' => $schema->string()->description('The identifier of the insurance record, as list-possession-insurance-records gives it.')->required(),
            'insurer' => $schema->string()->description('Who insures the possession, as text.'),
            'status' => $schema->string()->enum(array_column(PossessionInsuranceStatusEnum::cases(), 'value'))->description('Where the policy stands.'),
            'policy_number' => $schema->string()->description('The number of the policy.'),
            'coverage' => $schema->string()->description('What the policy covers.'),
            'insured_value' => $schema->number()->description('The value the possession is insured for, 0 or more.'),
            'excess' => $schema->number()->description('What is paid before the insurer pays, 0 or more.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the insured value and the excess.'),
            'starts_on' => $schema->string()->description('The day the policy period starts, as YYYY-MM-DD, or null when it is not known.'),
            'ends_on' => $schema->string()->description('The day the policy period ends or renews, as YYYY-MM-DD, or null for a policy with no end date.'),
            'is_listed_individually' => $schema->boolean()->description('Whether the policy names this possession, rather than covering it under the general contents of the home.'),
            'contact_name' => $schema->string()->description('Who to call at the insurer.'),
            'contact_email' => $schema->string()->description('The email of that person.'),
            'contact_phone' => $schema->string()->description('The phone of that person.'),
            'note' => $schema->string()->description('Conditions, exclusions, what the insurer asked for.'),
        ];
    }
}
