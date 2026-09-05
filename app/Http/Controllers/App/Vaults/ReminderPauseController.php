<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DeactivateReminders;
use App\Actions\ReactivateReminders;
use App\Actions\RestoreReminderOccurrences;
use App\Enums\ReminderInactiveReasonEnum;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Silence one reminder by hand, and start it again.
 *
 * A pause is a thing that exists rather than a flag that is flipped, so it is put
 * there and taken away rather than toggled: `update` starts it and `destroy` ends
 * it. That is what makes each address say what it does, and what stops two people
 * in two tabs flipping a rule past each other.
 *
 * Both call the actions every other surface calls. The reason is what makes it
 * safe: {@see ReactivateReminders} revives only the rules a given reason silenced,
 * and {@see RestoreReminderOccurrences} only the deadlines cancelled
 * under the matching one, so resuming what somebody paused can never undo what
 * archiving a record did.
 *
 * Resuming a rule the system silenced does nothing and says so. It is not an
 * error: the screen does not offer the button, so reaching here means an address
 * somebody kept, and the honest answer is that the cause is elsewhere.
 *
 * A reminder of another account never reaches here, because `resolveRouteBinding()`
 * refuses it. The vault is this controller's to check.
 */
class ReminderPauseController extends Controller
{
    public function update(Request $request, Vault $vault, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->vault_id === $vault->id, 404);

        new DeactivateReminders(
            user: $request->user(),
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::PausedByUser,
        )->execute();

        return redirect()
            ->route('reminders.show', ['vault' => $vault, 'reminder' => $reminder])
            ->with('status', __('Paused.'))
            ->with('status_description', __('Nothing will be delivered until you start it again. The schedule is kept.'));
    }

    public function destroy(Request $request, Vault $vault, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->vault_id === $vault->id, 404);

        $revived = new ReactivateReminders(
            user: $request->user(),
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::PausedByUser,
        )->execute();

        if ($revived === 0) {
            return redirect()
                ->route('reminders.show', ['vault' => $vault, 'reminder' => $reminder])
                ->with('error', __('This reminder is not paused, so there is nothing to start again.'));
        }

        return redirect()
            ->route('reminders.show', ['vault' => $vault, 'reminder' => $reminder])
            ->with('status', __('Started again.'))
            ->with('status_description', __('It picks up the schedule it had, so the next notification is where it was.'));
    }
}
