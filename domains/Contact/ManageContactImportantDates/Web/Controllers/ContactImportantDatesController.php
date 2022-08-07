<?php

namespace App\Contact\ManageContactImportantDates\Web\Controllers;

use App\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Contact\ManageContactImportantDates\Services\DestroyContactImportantDate;
use App\Contact\ManageContactImportantDates\Services\UpdateContactImportantDate;
use App\Contact\ManageContactImportantDates\Web\ViewHelpers\ContactImportantDatesViewHelper;
use App\Contact\ManageReminders\Services\CreateContactReminder;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        $day = '';
        $month = '';
        $year = '';

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

        $date = (new CreateContactImportantDate())->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_important_date_type_id' => $request->input('contact_important_date_type_id') == 0 ? null : $request->input('contact_important_date_type_id'),
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ]);

        if ($request->input('reminder')) {
            (new CreateContactReminder())->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::user()->id,
                'vault_id' => $vaultId,
                'contact_id' => $contactId,
                'label' => $request->input('label'),
                'day' => $day,
                'month' => $month,
                'year' => $year,
                'type' => $request->input('reminderChoice'),
                'frequency_number' => 1,
            ]);
        }

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ContactImportantDatesViewHelper::dto($contact, $date, Auth::user()),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $dateId)
    {
        $day = '';
        $month = '';
        $year = '';

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
            'contact_important_date_type_id' => $request->input('contact_important_date_type_id') == 0 ? null : $request->input('contact_important_date_type_id'),
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ];

        $date = (new UpdateContactImportantDate())->execute($data);

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

        (new DestroyContactImportantDate())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
