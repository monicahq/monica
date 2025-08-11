<?php

namespace App\Domains\Contact\ManageContactImportantDates\Web\Controllers;

use App\Domains\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\DestroyContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\UpdateContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers\ContactImportantDatesViewHelper;
use App\Domains\Contact\ManageReminders\Services\CreateContactReminder;
use App\Domains\Contact\ManageReminders\Services\UpdateContactReminder;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactImportantDatesController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/ImportantDates/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactImportantDatesViewHelper::data($contact, Auth::user()),
        ]);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        [$day, $month, $year] = $this->getDateParts($request);

        $date = (new CreateContactImportantDate)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_important_date_type_id' => $request->input('contact_important_date_type_id') <= 0 ? null : $request->input('contact_important_date_type_id'),
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ]);

        if ($request->input('reminder')) {
            (new CreateContactReminder)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
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

    public function update(Request $request, string $vaultId, string $contactId, string $dateId)
    {
        [$day, $month, $year] = $this->getDateParts($request);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_important_date_id' => $dateId,
            'contact_important_date_type_id' => $request->input('contact_important_date_type_id') == 0 ? null : $request->input('contact_important_date_type_id'),
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ];

        $date = (new UpdateContactImportantDate)->execute($data);

        if ($request->input('reminder')) {
            // TODO - not working yet
            (new UpdateContactReminder)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'contact_id' => $contactId,
                'contact_reminder_id' => $request->input('contact_reminder_id'),
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
        ], 200);
    }

    private function getDateParts(Request $request): array
    {
        $day = '';
        $month = '';
        $year = '';

        switch ($request->input('choice')) {
            case ContactImportantDate::TYPE_FULL_DATE:
                $year = Carbon::parse($request->input('date'))->year;
                $month = Carbon::parse($request->input('date'))->month;
                $day = Carbon::parse($request->input('date'))->day;
                break;
            case ContactImportantDate::TYPE_MONTH_DAY:
                $month = $request->input('month');
                $day = $request->input('day');
                break;
            case ContactImportantDate::TYPE_YEAR:
                $year = Carbon::now()->subYears($request->input('age'))->format('Y');
                break;
            default:
                throw new \InvalidArgumentException('Invalid date type');
        }

        return [$day, $month, $year];
    }

    public function destroy(Request $request, string $vaultId, string $contactId, string $dateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_important_date_id' => $dateId,
        ];

        (new DestroyContactImportantDate)->execute($data);

        // TODO - delete the reminder if it exists

        return response()->json([
            'data' => true,
        ], 200);
    }
}
