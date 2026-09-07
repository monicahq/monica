<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DeactivateReminders;
use App\Actions\ReactivateReminders;
use App\Enums\ReminderInactiveReasonEnum;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            ->with('status_description', __('It keeps the schedule it had, so the next notification is unchanged.'));
    }
}
