<?php

namespace App\Http\Controllers\Vault\Contact\ImportantDates;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ContactImportantDate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Services\Contact\ManageContactImportantDate\CreateContactImportantDate;
use App\Services\Contact\ManageContactImportantDate\UpdateContactImportantDate;
use App\Services\Contact\ManageContactImportantDate\DestroyContactImportantDate;
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
        if ($request->input('choice') === ContactImportantDate::TYPE_FULL_DATE) {
            $year = Carbon::parse($request->input('date'))->year;
            $month = Carbon::parse($request->input('date'))->month;
            $day = Carbon::parse($request->input('date'))->day;
        }

        if ($request->input('choice') === ContactImportantDate::TYPE_MONTH_DAY) {
            $month = $request->input('month');
            $day = $request->input('day');
            $year = '';
        }

        if ($request->input('choice') === ContactImportantDate::TYPE_YEAR) {
            $month = '';
            $day = '';
            $year = Carbon::now()->subYears($request->input('age'))->format('Y');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'type' => $request->input('type'),
        ];

        $date = (new CreateContactImportantDate)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ContactImportantDatesViewHelper::dto($contact, $date, Auth::user()),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $dateId)
    {
        if ($request->input('choice') === ContactImportantDate::TYPE_FULL_DATE) {
            $year = Carbon::parse($request->input('date'))->year;
            $month = Carbon::parse($request->input('date'))->month;
            $day = Carbon::parse($request->input('date'))->day;
        }

        if ($request->input('choice') === ContactImportantDate::TYPE_MONTH_DAY) {
            $month = $request->input('month');
            $day = $request->input('day');
            $year = '';
        }

        if ($request->input('choice') === ContactImportantDate::TYPE_YEAR) {
            $month = '';
            $day = '';
            $year = Carbon::now()->subYears($request->input('age'))->format('Y');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_important_date_id' => $dateId,
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'type' => $request->input('type'),
        ];

        $date = (new UpdateContactImportantDate)->execute($data);

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
            'contact_important_date_id' => $dateId,
        ];

        (new DestroyContactImportantDate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
