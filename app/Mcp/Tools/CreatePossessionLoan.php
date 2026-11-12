<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionLoan as CreatePossessionLoanAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Http\Resources\PossessionLoanResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record a possession lent to a contact of its vault, or borrowed from one, without any change of owner. Name an existing contact, as list-contacts gives it, or give the first name of a new one to write down with the loan. Only one loan of a possession can be active at a time. A loan in the provenance also records a change of custody when it starts and when it ends. The change is written to the account journal.')]
class CreatePossessionLoan extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.loans.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'direction' => ['required', Rule::enum(PossessionLoanDirectionEnum::class)],
            'status' => ['required', Rule::enum(PossessionLoanStatusEnum::class)],
            'contact' => ['nullable', 'string', 'required_without:new_contact_first_name'],
            'new_contact_first_name' => ['nullable', 'string', 'max:255'],
            'new_contact_last_name' => ['nullable', 'string', 'max:255'],
            'started_on' => ['required', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
            'returned_on' => ['nullable', 'date_format:Y-m-d'],
            'condition_out' => ['nullable', 'string'],
            'condition_back' => ['nullable', 'string'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'is_in_provenance' => ['sometimes', 'boolean'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);

        $loan = new CreatePossessionLoanAction(
            user: $this->user($request),
            possession: $possession,
            direction: PossessionLoanDirectionEnum::from($validated['direction']),
            status: PossessionLoanStatusEnum::from($validated['status']),
            startedOn: CarbonImmutable::parse($validated['started_on']),
            contact: isset($validated['contact']) ? $this->contact($vault, $validated['contact']) : null,
            newContactFirstName: isset($validated['contact']) ? null : ($validated['new_contact_first_name'] ?? null),
            newContactLastName: isset($validated['contact']) ? null : ($validated['new_contact_last_name'] ?? null),
            dueOn: isset($validated['due_on']) ? CarbonImmutable::parse($validated['due_on']) : null,
            returnedOn: isset($validated['returned_on']) ? CarbonImmutable::parse($validated['returned_on']) : null,
            conditionOut: isset($validated['condition_out']) ? $this->possessionCondition($request, $validated['condition_out']) : null,
            conditionBack: isset($validated['condition_back']) ? $this->possessionCondition($request, $validated['condition_back']) : null,
            depositAmount: isset($validated['deposit_amount']) ? (string) $validated['deposit_amount'] : null,
            depositCurrency: CurrencyEnum::tryFrom((string) ($validated['deposit_currency'] ?? '')),
            purpose: $validated['purpose'] ?? null,
            isInProvenance: (bool) ($validated['is_in_provenance'] ?? false),
        )->execute();

        return Response::json(new PossessionLoanResource($loan)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'direction' => $schema->string()->enum(array_column(PossessionLoanDirectionEnum::cases(), 'value'))->description('lent when it leaves your hands, borrowed when it comes into them.')->required(),
            'status' => $schema->string()->enum(array_column(PossessionLoanStatusEnum::cases(), 'value'))->description('planned, active or returned.')->required(),
            'contact' => $schema->string()->description('The identifier of a contact of the vault, as list-contacts gives it.'),
            'new_contact_first_name' => $schema->string()->description('The first name of a new contact to write down with the loan, when no contact is given.'),
            'new_contact_last_name' => $schema->string()->description('The last name of that new contact.'),
            'started_on' => $schema->string()->description('The day the loan started, as YYYY-MM-DD.')->required(),
            'due_on' => $schema->string()->description('The day it is due back, as YYYY-MM-DD.'),
            'returned_on' => $schema->string()->description('The day it came back, as YYYY-MM-DD. Required for a returned loan.'),
            'condition_out' => $schema->string()->description('The condition it left in, as list-possession-conditions gives it.'),
            'condition_back' => $schema->string()->description('The condition it came back in, as list-possession-conditions gives it.'),
            'deposit_amount' => $schema->number()->description('A deposit left against the loan.'),
            'deposit_currency' => $schema->string()->description('The currency of the deposit, as a lowercase ISO code.'),
            'purpose' => $schema->string()->description('Why it was lent or borrowed.'),
            'is_in_provenance' => $schema->boolean()->description('Whether the loan records changes of custody in the provenance.'),
        ];
    }
}
