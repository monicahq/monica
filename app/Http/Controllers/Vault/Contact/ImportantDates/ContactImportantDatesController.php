<?php

namespace App\Http\Controllers\Vault\Contact\ImportantDates;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Contact\ManageContactDate\CreateContactDate;
use App\Services\Contact\ManageContactDate\UpdateContactDate;
use App\Services\Contact\ManageContactDate\DestroyContactDate;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Vault\Contact\ImportantDates\ViewHelpers\ContactImportantDatesViewHelper;

class ContactImportantDatesController extends Controller
{
    public function index(Request $request, int $vaultId, int $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/ImportantDates/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactImportantDatesViewHelper::data($contact, Auth::user()),
        ]);
    }

    public function store(Request $request, int $vaultId, int $contactId)
    {
        if ($request->input('choice') === 'exactDate') {
            $date = Carbon::parse($request->input('date'))->format('Y-m-d');
        }

        if ($request->input('choice') === 'monthDay') {
            $month = Str::padLeft($request->input('month'), 2, '0');
            $day = Str::padLeft($request->input('day'), 2, '0');
            $date = $month.'-'.$day;
        }

        if ($request->input('choice') === 'age') {
            $date = Carbon::now()->subYears($request->input('age'))->format('Y');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'label' => $request->input('label'),
            'date' => $date,
            'type' => $request->input('type'),
        ];

        $date = (new CreateContactDate)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ContactImportantDatesViewHelper::dto($contact, $date, Auth::user()),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $dateId)
    {
        if ($request->input('choice') === 'exactDate') {
            $date = Carbon::parse($request->input('date'))->format('Y-m-d');
        }

        if ($request->input('choice') === 'monthDay') {
            $month = Str::padLeft($request->input('month'), 2, '0');
            $day = Str::padLeft($request->input('day'), 2, '0');
            $date = $month.'-'.$day;
        }

        if ($request->input('choice') === 'age') {
            $date = Carbon::now()->subYears($request->input('age'))->format('Y');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_date_id' => $dateId,
            'label' => $request->input('label'),
            'date' => $date,
            'type' => $request->input('type'),
        ];

        $date = (new UpdateContactDate)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ContactImportantDatesViewHelper::dto($contact, $date, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $dateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_date_id' => $dateId,
        ];

        (new DestroyContactDate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
