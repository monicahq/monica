<?php

namespace App\Contact\ManageLoans\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Models\Contact;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;

class ModuleLoanViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $loansAsLoaner = $contact->loansAsLoaner()->where('settled', false)->get();
        $loansAsLoanee = $contact->loansAsLoanee()->where('settled', false)->get();

        $loans = $loansAsLoaner->concat($loansAsLoanee)->sortBy('loaned_at')->unique('id');

        $loansAssociatedWithContactCollection = $loans->map(function ($loan) use ($contact, $user) {
            return self::dtoLoan($loan, $contact, $user);
        });

        return [
            'loans' => $loansAssociatedWithContactCollection,
            'current_date' => Carbon::now()->format('Y-m-d'),
            'url' => [
                'currencies' => route('currencies.index'),
                'store' => route('contact.loan.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dtoLoan(Loan $loan, Contact $contact, User $user): array
    {
        $loaners = $loan->loaners->unique('id');
        $loanees = $loan->loanees->unique('id');
        $loanersCollection = $loaners->map(function ($loaner) {
            return [
                'id' => $loaner->id,
                'name' => $loaner->name,
            ];
        });
        $loaneesCollection = $loanees->map(function ($loanee) {
            return [
                'id' => $loanee->id,
                'name' => $loanee->name,
            ];
        });

        return [
            'id' => $loan->id,
            'type' => $loan->type,
            'name' => $loan->name,
            'description' => $loan->description,
            'amount_lent' => $loan->amount_lent ? MonetaryNumberHelper::format($user, $loan->amount_lent) : null,
            'amount_lent_int' => $loan->amount_lent / 100,
            'currency_id' => $loan->currency_id,
            'currency_name' => $loan->currency ? $loan->currency->code : null,
            'loaned_at' => $loan->loaned_at->format('Y-m-d'),
            'loaned_at_human_format' => DateHelper::format($loan->loaned_at, $user),
            'loaners' => $loanersCollection,
            'loanees' => $loaneesCollection,
            'settled' => $loan->settled,
            'settled_at_human_format' => $loan->settled_at ? DateHelper::format($loan->settled_at, $user) : null,
            'url' => [
                'update' => route('contact.loan.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'loan' => $loan->id,
                ]),
                'toggle' => route('contact.loan.toggle', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'loan' => $loan->id,
                ]),
                'destroy' => route('contact.loan.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'loan' => $loan->id,
                ]),
            ],
        ];
    }
}
