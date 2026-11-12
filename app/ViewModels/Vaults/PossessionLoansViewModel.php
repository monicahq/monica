<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\DateFormatEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The Loans section of a possession: who has it now, and every loan newest first. Whether a loan is
 * overdue, how long it lasted and the counts are worked out from the loans each time.
 */
class PossessionLoansViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionLoan $editing = null,
        private readonly bool $isReturning = false,
    ) {
        $this->possession->load('loans.contact', 'loans.conditionOut', 'loans.conditionBack');
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /** @return array{name: string, sentence: string, due: string, isOverdue: bool, returnUrl: string}|null */
    public function active(): ?array
    {
        $loan = $this->possession->loans->first(fn (PossessionLoan $loan): bool => $loan->status === PossessionLoanStatusEnum::Active);

        if (! $loan instanceof PossessionLoan) {
            return null;
        }

        $name = $this->nameOf($loan);
        $since = $this->writeDate($loan->started_on);

        return [
            'name' => $name,
            'sentence' => $loan->direction === PossessionLoanDirectionEnum::Lent
                ? __(':name has had it since :date.', ['name' => $name, 'date' => $since])
                : __('You have had it from :name since :date.', ['name' => $name, 'date' => $since]),
            'due' => $this->dueLine($loan),
            'isOverdue' => $loan->isOverdueOn($this->today()),
            'returnUrl' => route('possessions.loans.return.new', [$this->possession->vault, $this->possession, $loan]),
        ];
    }

    public function countLine(): string
    {
        $loans = $this->possession->loans;

        if ($loans->isEmpty()) {
            return '';
        }

        $lent = $loans->filter(fn (PossessionLoan $loan): bool => $loan->direction === PossessionLoanDirectionEnum::Lent)->count();

        return trans_choice(':count loan, :lent|:count loans, :lent', $loans->count(), [
            'lent' => trans_choice(':count lent out|:count lent out', $lent),
        ]);
    }

    /**
     * Every loan, newest first. The loan being edited is left out, since the form opens above the list
     * in its place.
     *
     * @return list<array{id: string, verb: string, name: string, contactUrl: ?string, status: string, tone: string, isInProvenance: bool, period: string, span: string, purpose: ?string, facts: list<array{label: string, value: string, isSet: bool}>, canReturn: bool, returnUrl: string, editUrl: string, destroyUrl: string, deleteTitle: string, deleteWarning: string}>
     */
    public function rows(): array
    {
        return $this->possession->loans
            ->reverse()
            ->reject(fn (PossessionLoan $loan): bool => $loan->id === $this->editing?->id)
            ->map(fn (PossessionLoan $loan): array => $this->row($loan))
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    public function conditions(): array
    {
        return $this->user->account->possessionConditions()
            ->get()
            ->map(fn (PossessionCondition $condition): array => ['value' => $condition->uuid, 'label' => $condition->name])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    public function directions(): array
    {
        return array_map(fn (PossessionLoanDirectionEnum $direction): array => [
            'value' => $direction->value,
            'label' => __($direction->label()),
        ], PossessionLoanDirectionEnum::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public function statuses(): array
    {
        return array_map(fn (PossessionLoanStatusEnum $status): array => [
            'value' => $status->value,
            'label' => __($status->label()),
        ], PossessionLoanStatusEnum::cases());
    }

    public function candidatesUrl(): string
    {
        return route('possessions.loanContactCandidates.index', [$this->possession->vault, $this->possession]);
    }

    /**
     * What the form starts with: the loan being edited, the same loan marked as returned today, or a
     * new loan lent out today.
     *
     * @return array{isEditing: bool, action: string, method: string, cancelUrl: ?string, title: string, submitLabel: string, contactName: string, currency: ?CurrencyEnum, values: array<string, string>}
     */
    public function form(): array
    {
        $editing = $this->editing;

        if (! $editing instanceof PossessionLoan) {
            return [
                'isEditing' => false,
                'action' => route('possessions.loans.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'title' => __('New loan'),
                'submitLabel' => __('Add loan'),
                'contactName' => '',
                'currency' => CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency),
                'values' => [
                    'direction' => PossessionLoanDirectionEnum::Lent->value,
                    'status' => PossessionLoanStatusEnum::Active->value,
                    'started_on' => $this->today()->toDateString(),
                ],
            ];
        }

        $isReturned = $this->isReturning || $editing->status === PossessionLoanStatusEnum::Returned;

        return [
            'isEditing' => true,
            'action' => route('possessions.loans.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.loans.index', [$this->possession->vault, $this->possession]),
            'title' => __('Edit loan'),
            'submitLabel' => __('Save changes'),
            'contactName' => (string) $editing->contactName(),
            'currency' => $editing->deposit_currency ?? CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'contact_id' => $editing->contactName() === null ? null : $editing->contact?->uuid,
                'direction' => $editing->direction->value,
                'status' => $isReturned ? PossessionLoanStatusEnum::Returned->value : $editing->status->value,
                'started_on' => $editing->started_on->toDateString(),
                'due_on' => $editing->due_on?->toDateString(),
                'returned_on' => $editing->returned_on?->toDateString() ?? ($isReturned ? $this->today()->toDateString() : null),
                'condition_out' => $editing->conditionOut?->uuid,
                'condition_back' => $editing->conditionBack?->uuid,
                'deposit_amount' => $editing->deposit_amount,
                'purpose' => $editing->purpose,
                'is_in_provenance' => $editing->is_in_provenance ? '1' : '',
            ]),
        ];
    }

    /**
     * The wordings of the line saying what the form records. The page puts the contact chosen in
     * place of `:name` as the fields change.
     *
     * @return array<string, string>
     */
    public function summaryWordings(): array
    {
        return [
            'empty' => __('Choose who has the possession to see a summary of this loan.'),
            'lentPlanned' => __(':name will have the possession.'),
            'lentActive' => __(':name has the possession now.'),
            'lentReturned' => __(':name had the possession and gave it back.'),
            'borrowedPlanned' => __('You will have the possession from :name.'),
            'borrowedActive' => __('You have the possession from :name now.'),
            'borrowedReturned' => __('You had the possession from :name and gave it back.'),
            'due' => __('A day is agreed for its return.'),
            'noDue' => __('No return date is agreed.'),
            'provenance' => __('Two provenance events will be created.'),
            'newContact' => __('the new contact'),
        ];
    }

    /** @return array{id: string, verb: string, name: string, contactUrl: ?string, status: string, tone: string, isInProvenance: bool, period: string, span: string, purpose: ?string, facts: list<array{label: string, value: string, isSet: bool}>, canReturn: bool, returnUrl: string, editUrl: string, destroyUrl: string, deleteTitle: string, deleteWarning: string} */
    private function row(PossessionLoan $loan): array
    {
        $name = $this->nameOf($loan);
        $isReturned = $loan->status === PossessionLoanStatusEnum::Returned;
        [$status, $tone] = match (true) {
            $isReturned => [__('Returned'), 'neutral'],
            $loan->status === PossessionLoanStatusEnum::Planned => [__('Planned'), 'info'],
            $loan->isOverdueOn($this->today()) => [__('Overdue'), 'danger'],
            default => [__('With them now'), 'warning'],
        };

        return [
            'id' => $loan->uuid,
            'verb' => __($loan->direction->verb()),
            'name' => $name,
            'contactUrl' => $loan->contactName() === null || $loan->contact === null
                ? null
                : route('contacts.show', [$this->possession->vault, $loan->contact]),
            'status' => $status,
            'tone' => $tone,
            'isInProvenance' => $loan->is_in_provenance,
            'period' => $this->period($loan),
            'span' => $isReturned && $loan->returned_on !== null
                ? $this->length($loan->started_on, $loan->returned_on)
                : $this->dueLine($loan),
            'purpose' => $loan->purpose,
            'facts' => [
                $this->fact(__('Condition out'), $loan->conditionOut?->name),
                $isReturned
                    ? $this->fact(__('Condition back'), $loan->conditionBack?->name)
                    : $this->fact(__('Due back'), $loan->due_on === null ? null : $this->writeDate($loan->due_on)),
                $this->fact(__('Deposit'), $loan->deposit_amount === null
                    ? null
                    : new PossessionValueFormatter($this->user)->formatExact($loan->deposit_amount, $loan->deposit_currency?->value)),
            ],
            'canReturn' => ! $isReturned,
            'returnUrl' => route('possessions.loans.return.new', [$this->possession->vault, $this->possession, $loan]),
            'editUrl' => route('possessions.loans.edit', [$this->possession->vault, $this->possession, $loan]),
            'destroyUrl' => route('possessions.loans.destroy', [$this->possession->vault, $this->possession, $loan]),
            'deleteTitle' => __('Delete the loan to :name of :date?', ['name' => $name, 'date' => $this->writeDate($loan->started_on)]),
            'deleteWarning' => $this->deleteWarning($loan, $name),
        ];
    }

    /** @return array{label: string, value: string, isSet: bool} */
    private function fact(string $label, ?string $value): array
    {
        return ['label' => $label, 'value' => $value ?? __('Not recorded'), 'isSet' => $value !== null];
    }

    private function period(PossessionLoan $loan): string
    {
        $from = $this->writeDate($loan->started_on);

        return match ($loan->status) {
            PossessionLoanStatusEnum::Returned => __(':from to :to', [
                'from' => $from,
                'to' => $loan->returned_on === null ? __('Date unknown') : $this->writeDate($loan->returned_on),
            ]),
            PossessionLoanStatusEnum::Planned => __(':from, planned', ['from' => $from]),
            PossessionLoanStatusEnum::Active => __(':from to :to', ['from' => $from, 'to' => __('now')]),
        };
    }

    private function dueLine(PossessionLoan $loan): string
    {
        if ($loan->due_on === null) {
            return __('No return date agreed');
        }

        $due = $this->writeDate($loan->due_on);
        $days = (int) $this->today()->diffInDays($loan->due_on, false);

        if ($days < 0) {
            return trans_choice('Was due back :date, :count day ago|Was due back :date, :count days ago', -$days, ['date' => $due]);
        }

        if ($days === 0) {
            return __('Due back today');
        }

        return trans_choice('Due back :date, in :count day|Due back :date, in :count days', $days, ['date' => $due]);
    }

    private function deleteWarning(PossessionLoan $loan, string $name): string
    {
        $sentences = [__('This cannot be undone.')];

        if ($loan->is_in_provenance) {
            $sentences[] = __('Its provenance events are deleted with it.');
        }

        if ($loan->contactName() !== null) {
            $sentences[] = __(':name stays in your contacts.', ['name' => $name]);
        }

        if ($loan->status === PossessionLoanStatusEnum::Active) {
            $sentences[] = __('The possession will show as in your hands.');
        }

        return implode(' ', $sentences);
    }

    /**
     * How long a returned loan lasted: in days under a month, then in years and months.
     */
    private function length(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $days = (int) $from->diffInDays($to);

        if ($days < 1) {
            return __('less than a day');
        }

        if ($days < 31) {
            return trans_choice(':count day|:count days', $days);
        }

        $months = (int) $from->diffInMonths($to);
        $years = intdiv($months, 12);
        $months %= 12;

        if ($years === 0) {
            return trans_choice(':count month|:count months', $months);
        }

        if ($months === 0) {
            return trans_choice(':count year|:count years', $years);
        }

        return __(':years and :months', [
            'years' => trans_choice(':count year|:count years', $years),
            'months' => trans_choice(':count month|:count months', $months),
        ]);
    }

    private function nameOf(PossessionLoan $loan): string
    {
        return $loan->contactName() ?? __('A deleted contact');
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::now($this->user->timezone)->toDateString());
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
