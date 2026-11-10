<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateManualReminder;
use App\Actions\DestroyReminder;
use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupReminderController extends Controller
{
    public function new(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.reminders.form', [
            'viewModel' => new ReminderForm(user: $request->user(), vault: $vault, record: $group),
        ]);
    }

    public function create(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            new CreateManualReminder(
                user: $request->user(),
                record: $group,
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

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The reminder is added.'));
    }

    public function edit(Request $request, Vault $vault, Group $group, Reminder $reminder): View
    {
        abort_unless($this->isAbout($reminder, $group), 404);

        return view('app.vaults.reminders.form', [
            'viewModel' => new ReminderForm(user: $request->user(), vault: $vault, record: $group, reminder: $reminder),
        ]);
    }

    public function update(Request $request, Vault $vault, Group $group, Reminder $reminder): RedirectResponse
    {
        abort_unless($this->isAbout($reminder, $group), 404);

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

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('Changes saved'));
    }

    public function destroy(Request $request, Vault $vault, Group $group, Reminder $reminder): RedirectResponse
    {
        abort_unless($this->isAbout($reminder, $group), 404);

        new DestroyReminder(user: $request->user(), reminder: $reminder)->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
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

    private function isAbout(Reminder $reminder, Group $group): bool
    {
        return $reminder->remindable_type === $group->getMorphClass()
            && $reminder->remindable_id === $group->id;
    }
}
