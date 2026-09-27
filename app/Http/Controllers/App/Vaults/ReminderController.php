<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyReminder;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $contact = $reminder->remindable;

        new DestroyReminder(user: $request->user(), reminder: $reminder)->execute();

        $destination = $contact instanceof Contact
            ? route('contacts.reminders.index', ['vault' => $vault, 'contact' => $contact])
            : route('reminders.index', $vault);

        return redirect($destination)
            ->with('status', __('The reminder is deleted.'))
            ->with('status_description', __('The answers you gave are kept.'));
    }
}
