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

/**
 * Close one deadline without switching its rule off.
 *
 * There is no confirmation. Dismissing this year's birthday leaves the rule
 * producing and next year's deadline scheduled, and the row itself stays, so
 * nothing here is lost and nothing needs a warning.
 *
 * A deadline of another account or of another vault never reaches here: the
 * route binding refuses it.
 *
 * The answer lands back where the request came from. The same deadline is offered
 * on the list and on the screen of the rule behind it, and somebody who closed
 * one from the second wants to stay there. The list is the fallback for a request
 * that arrived without a referrer.
 */
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
