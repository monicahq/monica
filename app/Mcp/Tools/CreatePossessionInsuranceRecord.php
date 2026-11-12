<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionInsuranceRecord as CreatePossessionInsuranceRecordAction;
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

#[Description('Record one policy period covering a possession: the insurer, the status and the insured value in its currency are required. When a policy renews with a new insured value, or the insurer changes, record a new one rather than changing the old one, so the history of the coverage is kept. The change is written to the account journal.')]
class CreatePossessionInsuranceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'insurer' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PossessionInsuranceStatusEnum::class)],
            'policy_number' => ['nullable', 'string', 'max:255'],
            'coverage' => ['nullable', 'string', 'max:255'],
            'insured_value' => ['required', 'numeric', 'min:0'],
            'excess' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d'],
            'is_listed_individually' => ['sometimes', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        $record = new CreatePossessionInsuranceRecordAction(
            user: $this->user($request),
            possession: $possession,
            insurer: $validated['insurer'],
            status: PossessionInsuranceStatusEnum::from($validated['status']),
            insuredValue: (string) $validated['insured_value'],
            currency: CurrencyEnum::from($validated['currency']),
            policyNumber: $validated['policy_number'] ?? null,
            coverage: $validated['coverage'] ?? null,
            excess: isset($validated['excess']) ? (string) $validated['excess'] : null,
            startsOn: isset($validated['starts_on']) ? CarbonImmutable::parse($validated['starts_on']) : null,
            endsOn: isset($validated['ends_on']) ? CarbonImmutable::parse($validated['ends_on']) : null,
            isListedIndividually: (bool) ($validated['is_listed_individually'] ?? false),
            contactName: $validated['contact_name'] ?? null,
            contactEmail: $validated['contact_email'] ?? null,
            contactPhone: $validated['contact_phone'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return Response::json(new PossessionInsuranceRecordResource($record)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'insurer' => $schema->string()->description('Who insures the possession, as text.')->required(),
            'status' => $schema->string()->enum(array_column(PossessionInsuranceStatusEnum::cases(), 'value'))->description('Where the policy stands.')->required(),
            'policy_number' => $schema->string()->description('The number of the policy.'),
            'coverage' => $schema->string()->description('What the policy covers, such as theft, fire and accidental damage.'),
            'insured_value' => $schema->number()->description('The value the possession is insured for, 0 or more.')->required(),
            'excess' => $schema->number()->description('What is paid before the insurer pays, 0 or more.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the insured value and the excess.')->required(),
            'starts_on' => $schema->string()->description('The day the policy period starts, as YYYY-MM-DD.'),
            'ends_on' => $schema->string()->description('The day the policy period ends or renews, as YYYY-MM-DD. Left out for a policy with no end date.'),
            'is_listed_individually' => $schema->boolean()->description('Whether the policy names this possession, rather than covering it under the general contents of the home.'),
            'contact_name' => $schema->string()->description('Who to call at the insurer.'),
            'contact_email' => $schema->string()->description('The email of that person.'),
            'contact_phone' => $schema->string()->description('The phone of that person.'),
            'note' => $schema->string()->description('Conditions, exclusions, what the insurer asked for.'),
        ];
    }
}
