<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DismissReminderOccurrence;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Http\Controllers\Controller;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReminderOccurrenceDismissalController extends Controller
{
    public function update(Request $request, Vault $vault, ReminderOccurrence $reminderOccurrence): RedirectResponse
    {
        try {
            new DismissReminderOccurrence(
                user: $request->user(),
                occurrence: $reminderOccurrence,
            )->execute();
        } catch (ReminderOccurrenceIsFinished $e) {
            return redirect()
                ->back(fallback: route('reminders.index', $vault))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->back(fallback: route('reminders.index', $vault))
            ->with('status', __('Done with it.'))
            ->with('status_description', __('The rule is still on, so the next one is still coming.'));
    }
}
