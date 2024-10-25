<?php

namespace App\Domains\Contact\ManageLoans\Web\Controllers;

use App\Domains\Contact\ManageLoans\Services\CreateLoan;
use App\Domains\Contact\ManageLoans\Services\DestroyLoan;
use App\Domains\Contact\ManageLoans\Services\UpdateLoan;
use App\Domains\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleLoanController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $loaners = collect($request->input('loaners'))->pluck('id');
        $loanees = collect($request->input('loanees'))->pluck('id');

        $currency = $request->input('currency_id') ? Currency::find($request->input('currency_id')) : null;

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'currency_id' => $request->input('currency_id'),
            'type' => $request->input('type'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'loaner_ids' => $loaners,
            'loanee_ids' => $loanees,
            'amount_lent' => $request->input('amount_lent') ? MonetaryNumberHelper::parseInput($request->input('amount_lent'), optional($currency)->code) : null,
            'loaned_at' => $request->input('loaned_at'),
        ];

        $loan = (new CreateLoan)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleLoanViewHelper::dtoLoan($loan, $contact, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $loanId)
    {
        $loaners = collect($request->input('loaners'))->pluck('id');
        $loanees = collect($request->input('loanees'))->pluck('id');

        $currency = $request->input('currency_id') ? Currency::find($request->input('currency_id')) : null;

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'loan_id' => $loanId,
            'currency_id' => $request->input('currency_id'),
            'type' => $request->input('type'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'loaner_ids' => $loaners,
            'loanee_ids' => $loanees,
            'amount_lent' => $request->input('amount_lent') ? MonetaryNumberHelper::parseInput($request->input('amount_lent'), optional($currency)->code) : null,
            'loaned_at' => $request->input('loaned_at'),
        ];

        $loan = (new UpdateLoan)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleLoanViewHelper::dtoLoan($loan, $contact, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $loanId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'loan_id' => $loanId,
        ];

        (new DestroyLoan)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
