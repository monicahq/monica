<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SendTestReminderNotification;
use App\Http\Controllers\Controller;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReminderOccurrenceTestNotificationController extends Controller
{
    public function create(Request $request, Vault $vault, ReminderOccurrence $reminderOccurrence): RedirectResponse
    {
        new SendTestReminderNotification(
            user: $request->user(),
            occurrence: $reminderOccurrence,
        )->execute();

        return back(fallback: route('reminders.index', $vault))
            ->with('status', __('On its way.'))
            ->with('status_description', __('This one was sent to you now. The real one still arrives on the day.'));
    }
}
