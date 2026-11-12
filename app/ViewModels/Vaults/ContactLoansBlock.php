<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\PossessionLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The possessions somebody has had or lent, newest first, each leading to the loans of that possession.
 */
class ContactLoansBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function isEmpty(): bool
    {
        return $this->loans() === [];
    }

    /** @return list<array{possession: string, url: string, verb: string, status: string, tone: string, period: string}> */
    public function loans(): array
    {
        return once(function (): array {
            $today = CarbonImmutable::parse(CarbonImmutable::now($this->user->timezone)->toDateString());

            return $this->contact->possessionLoans()
                ->with('possession.vault')
                ->get()
                ->map(fn (PossessionLoan $loan): array => [
                    'possession' => $loan->possession->name,
                    'url' => route('possessions.loans.index', [$loan->possession->vault, $loan->possession]),
                    'verb' => __($loan->direction->verb()),
                    ...$this->status($loan, $today),
                    'period' => $this->period($loan),
                ])
                ->values()
                ->all();
        });
    }

    /** @return array{status: string, tone: string} */
    private function status(PossessionLoan $loan, CarbonImmutable $today): array
    {
        return match (true) {
            $loan->status === PossessionLoanStatusEnum::Returned => ['status' => __('Returned'), 'tone' => 'neutral'],
            $loan->status === PossessionLoanStatusEnum::Planned => ['status' => __('Planned'), 'tone' => 'info'],
            $loan->isOverdueOn($today) => ['status' => __('Overdue'), 'tone' => 'danger'],
            default => ['status' => __('With them now'), 'tone' => 'warning'],
        };
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

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
