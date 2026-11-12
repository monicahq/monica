<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionLoan as UpdatePossessionLoanAction;
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

#[Description('Change one loan of a possession, including marking it as returned with the day it came back. What you leave out keeps its value, and what you send as null is cleared. A loan that is not returned loses its return day and the condition it came back in. Only one loan of a possession can be active at a time. The change is written to the account journal.')]
class UpdatePossessionLoan extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.loans.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'loan' => ['required', 'string'],
            'direction' => ['sometimes', Rule::enum(PossessionLoanDirectionEnum::class)],
            'status' => ['sometimes', Rule::enum(PossessionLoanStatusEnum::class)],
            'contact' => ['sometimes', 'string'],
            'started_on' => ['sometimes', 'date_format:Y-m-d'],
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
        $loan = $this->possessionLoan($this->possession($vault, $validated['possession']), $validated['loan']);
        $date = fn (string $field): ?CarbonImmutable => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : CarbonImmutable::parse($validated[$field]))
            : $loan->{$field};
        $kept = fn (string $field, string $column): mixed => array_key_exists($field, $validated) ? $validated[$field] : $loan->{$column};
        $condition = fn (string $field, string $relation): mixed => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : $this->possessionCondition($request, $validated[$field]))
            : $loan->{$relation};

        new UpdatePossessionLoanAction(
            user: $this->user($request),
            loan: $loan,
            direction: isset($validated['direction']) ? PossessionLoanDirectionEnum::from($validated['direction']) : $loan->direction,
            status: isset($validated['status']) ? PossessionLoanStatusEnum::from($validated['status']) : $loan->status,
            startedOn: $date('started_on') ?? $loan->started_on,
            contact: isset($validated['contact']) ? $this->contact($vault, $validated['contact']) : null,
            dueOn: $date('due_on'),
            returnedOn: $date('returned_on'),
            conditionOut: $condition('condition_out', 'conditionOut'),
            conditionBack: $condition('condition_back', 'conditionBack'),
            depositAmount: $kept('deposit_amount', 'deposit_amount') === null ? null : (string) $kept('deposit_amount', 'deposit_amount'),
            depositCurrency: array_key_exists('deposit_currency', $validated)
                ? CurrencyEnum::tryFrom((string) $validated['deposit_currency'])
                : $loan->deposit_currency,
            purpose: $kept('purpose', 'purpose'),
            isInProvenance: (bool) $kept('is_in_provenance', 'is_in_provenance'),
        )->execute();

        return Response::json(new PossessionLoanResource($loan->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'loan' => $schema->string()->description('The identifier of the loan, as list-possession-loans gives it.')->required(),
            'direction' => $schema->string()->enum(array_column(PossessionLoanDirectionEnum::cases(), 'value'))->description('lent when it leaves your hands, borrowed when it comes into them.'),
            'status' => $schema->string()->enum(array_column(PossessionLoanStatusEnum::cases(), 'value'))->description('planned, active or returned.'),
            'contact' => $schema->string()->description('The identifier of a contact of the vault, as list-contacts gives it.'),
            'started_on' => $schema->string()->description('The day the loan started, as YYYY-MM-DD.'),
            'due_on' => $schema->string()->description('The day it is due back, as YYYY-MM-DD, or null.'),
            'returned_on' => $schema->string()->description('The day it came back, as YYYY-MM-DD. Required for a returned loan.'),
            'condition_out' => $schema->string()->description('The condition it left in, as list-possession-conditions gives it, or null.'),
            'condition_back' => $schema->string()->description('The condition it came back in, as list-possession-conditions gives it, or null.'),
            'deposit_amount' => $schema->number()->description('A deposit left against the loan, or null.'),
            'deposit_currency' => $schema->string()->description('The currency of the deposit, as a lowercase ISO code.'),
            'purpose' => $schema->string()->description('Why it was lent or borrowed, or null.'),
            'is_in_provenance' => $schema->boolean()->description('Whether the loan records changes of custody in the provenance.'),
        ];
    }
}
