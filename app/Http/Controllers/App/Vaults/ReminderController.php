<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyReminder;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One reminder, on a screen of its own.
 *
 * A reminder of another account never reaches here, because
 * `resolveRouteBinding()` refuses it. The vault is this controller's to check:
 * the binding proves the reminder belongs to the account, and nothing in it
 * proves the reminder belongs to the vault the address names, so a reminder of
 * another vault of the same account would otherwise be readable through the
 * wrong compartment.
 *
 * Deleting is the same gesture as unticking the box under the date, and it reaches
 * the same action. The screen warns first, because a reminder carries a history
 * nothing else holds; the date it read is untouched either way.
 */
class ReminderController extends Controller
{
    public function show(Request $request, Vault $vault, Reminder $reminder): View
    {
        abort_unless($reminder->vault_id === $vault->id, 404);

        return view('app.vaults.reminders.show', [
            'viewModel' => new ReminderViewModel(
                user: $request->user(),
                vault: $vault,
                reminder: $reminder,
            ),
        ]);
    }

    public function destroy(Request $request, Vault $vault, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->vault_id === $vault->id, 404);

        $name = $reminder->name;

        new DestroyReminder(user: $request->user(), reminder: $reminder)->execute();

        return redirect()
            ->route('reminders.index', $vault)
            ->with('status', __('Reminder deleted.'))
            ->with('status_description', __(':name is gone. The date it read is still on the record.', ['name' => $name]));
    }
}
