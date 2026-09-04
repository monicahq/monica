<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SendTestReminderNotification;
use App\Http\Controllers\Controller;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Send one reminder now, to the person asking, so they can see it arrive.
 *
 * A deadline of another account or of another vault never reaches here: the
 * route binding refuses it.
 */
class ReminderOccurrenceTestNotificationController extends Controller
{
    public function create(Request $request, Vault $vault, ReminderOccurrence $reminderOccurrence): RedirectResponse
    {
        new SendTestReminderNotification(
            user: $request->user(),
            occurrence: $reminderOccurrence,
        )->execute();

        return redirect()
            ->route('reminders.index', $vault)
            ->with('status', __('On its way.'))
            ->with('status_description', __('This one was sent to you now. The real one still arrives on the day.'));
    }
}
