<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateManualReminder;
use App\Actions\DestroyReminder;
use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactRemindersViewModel;
use App\ViewModels\Vaults\ReminderForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactReminderController extends Controller
{
    public function index(Request $request, Vault $vault, Contact $contact, ?string $filter = null): View
    {
        return view('app.vaults.contacts.reminders.index', [
            'viewModel' => new ContactRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                filter: $filter ?? ContactRemindersViewModel::FilterAll,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        return view('app.vaults.reminders.form', [
            'viewModel' => new ReminderForm(user: $request->user(), vault: $vault, record: $contact),
        ]);
    }

    public function create(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'return_to' => ['nullable', Rule::in(['contact'])],
        ], $this->messages());

        try {
            new CreateManualReminder(
                user: $request->user(),
                record: $contact,
                name: $validated['name'],
                startsOn: $validated['starts_on'],
                cadence: ReminderCadenceEnum::from($validated['cadence']),
                timeOfDay: $validated['time'],
                frequencyInterval: (int) ($validated['interval'] ?? 1),
                timezone: $validated['timezone'],
            )->execute();
        } catch (ReminderIsNotValid $e) {
            return back()->withErrors([$e->field ?? 'starts_on' => $e->getMessage()])->withInput();
        }

        $destination = ($validated['return_to'] ?? null) === 'contact'
            ? route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            : route('contacts.reminders.index', ['vault' => $vault, 'contact' => $contact]);

        return redirect($destination)->with('status', __('The reminder is added.'));
    }

    public function edit(Request $request, Vault $vault, Contact $contact, Reminder $reminder): View|RedirectResponse
    {
        abort_unless($this->isAbout($reminder, $contact), 404);

        if ($reminder->source !== ReminderSourceEnum::Manual) {
            return redirect()->route('reminders.show', ['vault' => $vault, 'reminder' => $reminder]);
        }

        return view('app.vaults.reminders.form', [
            'viewModel' => new ReminderForm(user: $request->user(), vault: $vault, record: $contact, reminder: $reminder),
        ]);
    }

    public function update(Request $request, Vault $vault, Contact $contact, Reminder $reminder): RedirectResponse
    {
        abort_unless($this->isAbout($reminder, $contact), 404);

        $validated = $request->validate($this->rules(), $this->messages());

        try {
            new UpdateReminder(
                user: $request->user(),
                reminder: $reminder,
                cadence: ReminderCadenceEnum::from($validated['cadence']),
                timeOfDay: $validated['time'],
                name: $validated['name'],
                frequencyInterval: (int) ($validated['interval'] ?? 1),
                timezone: $validated['timezone'],
                startsOn: $validated['starts_on'],
            )->execute();
        } catch (ReminderIsNotValid $e) {
            return back()->withErrors([$e->field ?? 'starts_on' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('reminders.show', ['vault' => $vault, 'reminder' => $reminder])
            ->with('status', __('Changes saved'));
    }

    public function destroy(Request $request, Vault $vault, Contact $contact, Reminder $reminder): RedirectResponse
    {
        abort_unless($this->isAbout($reminder, $contact), 404);

        new DestroyReminder(user: $request->user(), reminder: $reminder)->execute();

        return back(fallback: route('contacts.reminders.index', ['vault' => $vault, 'contact' => $contact]))
            ->with('status', __('The reminder is deleted.'))
            ->with('status_description', __('The answers you gave are kept.'));
    }

    /** @return array<string, list<mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'cadence' => ['required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'string', 'timezone'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => __('Say what the reminder is about.'),
            'starts_on.required' => __('Pick a date.'),
        ];
    }

    private function isAbout(Reminder $reminder, Contact $contact): bool
    {
        return $reminder->remindable_type === $contact->getMorphClass()
            && $reminder->remindable_id === $contact->id;
    }
}
