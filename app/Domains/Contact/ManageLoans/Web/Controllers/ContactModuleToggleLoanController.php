<?php

namespace App\Domains\Contact\ManageLoans\Web\Controllers;

use App\Domains\Contact\ManageLoans\Services\ToggleLoan;
use App\Domains\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleToggleLoanController extends Controller
{
    public function update(Request $request, string $vaultId, string $contactId, int $loanId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'loan_id' => $loanId,
        ];

        $loan = (new ToggleLoan)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleLoanViewHelper::dtoLoan($loan, $contact, Auth::user()),
        ], 200);
    }
}
