<?php

namespace App\Http\Controllers\Vault\Contact\Modules\Reminder;

use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\ContactReminder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Contact\ManageReminder\CreateReminder;
use App\Services\Contact\ManageReminder\UpdateReminder;
use App\Services\Contact\ManageReminder\DestroyReminder;
use App\Http\Controllers\Vault\Contact\Modules\Reminder\ViewHelpers\ModuleRemindersViewHelper;

class ContactModuleReminderController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        if ($request->input('choice') == 'full_date') {
            $carbonDate = Carbon::parse($request->input('date'));
            $day = $carbonDate->day;
            $month = $carbonDate->month;
            $year = $carbonDate->year;
        } else {
            $day = $request->input('day');
            $month = $request->input('month');
            $year = null;
        }

        if ($request->input('reminderChoice') == ContactReminder::TYPE_ONE_TIME) {
            $frequencyNumber = 1;
            $type = ContactReminder::TYPE_ONE_TIME;
        } else {
            $frequencyNumber = $request->input('frequencyNumber');
            $type = $request->input('frequencyType');
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
            'type' => $type,
            'frequency_number' => $frequencyNumber,
        ];

        $reminder = (new CreateReminder)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleRemindersViewHelper::dtoReminder($contact, $reminder, Auth::user()),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $reminderId)
    {
        if ($request->input('choice') == 'full_date') {
            $carbonDate = Carbon::parse($request->input('date'));
            $day = $carbonDate->day;
            $month = $carbonDate->month;
            $year = $carbonDate->year;
        } else {
            $day = $request->input('day');
            $month = $request->input('month');
            $year = null;
        }

        if ($request->input('reminderChoice') == ContactReminder::TYPE_ONE_TIME) {
            $frequencyNumber = 1;
            $type = ContactReminder::TYPE_ONE_TIME;
        } else {
            $frequencyNumber = $request->input('frequencyNumber');
            $type = $request->input('frequencyType');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_reminder_id' => $reminderId,
            'label' => $request->input('label'),
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'type' => $type,
            'frequency_number' => $frequencyNumber,
        ];

        $reminder = (new UpdateReminder)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleRemindersViewHelper::dtoReminder($contact, $reminder, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $reminderId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_reminder_id' => $reminderId,
        ];

        (new DestroyReminder)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
