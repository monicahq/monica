<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\PostponeReminderOccurrence;
use App\Enums\ReminderPostponePresetEnum;
use App\Exceptions\ReminderCannotBePostponedIntoThePast;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Helpers\ReminderTime;
use App\Http\Controllers\Controller;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReminderOccurrencePostponementController extends Controller
{
    public function update(Request $request, Vault $vault, ReminderOccurrence $reminderOccurrence): RedirectResponse
    {
        $validated = $request->validate([
            'preset' => ['nullable', 'string', Rule::in(ReminderPostponePresetEnum::values())],
            'date' => ['required_without:preset', 'nullable', 'date_format:Y-m-d'],
            'time' => ['required_with:date', 'nullable', 'string'],
        ]);

        $until = $this->instantFrom($validated, $reminderOccurrence);

        if ($until === null) {
            return $this->refuse($vault, __('This is not a date and a time.'));
        }

        try {
            new PostponeReminderOccurrence(
                user: $request->user(),
                occurrence: $reminderOccurrence,
                until: $until,
            )->execute();
        } catch (ReminderCannotBePostponedIntoThePast|ReminderOccurrenceIsFinished $e) {
            return $this->refuse($vault, $e->getMessage());
        }

        return redirect()
            ->back(fallback: route('reminders.index', $vault))
            ->with('status', __('Put off.'))
            ->with('status_description', __('The reminder will come back at the moment you chose.'));
    }

    /** @param  array<string, mixed>  $validated */
    private function instantFrom(array $validated, ReminderOccurrence $occurrence): ?CarbonImmutable
    {
        $rule = $occurrence->reminder;

        if ($rule === null) {
            return null;
        }

        $preset = ReminderPostponePresetEnum::tryFrom((string) ($validated['preset'] ?? ''));

        if ($preset instanceof ReminderPostponePresetEnum) {
            return $preset->resolve($rule);
        }

        $time = (string) ($validated['time'] ?? '');

        if (! ReminderTime::isATime($time)) {
            return null;
        }

        return CarbonImmutable::parse((string) $validated['date'], $rule->timezone)
            ->setTimeFromTimeString($time)
            ->setTimezone('UTC');
    }

    private function refuse(Vault $vault, string $message): RedirectResponse
    {
        return redirect()
            ->back(fallback: route('reminders.index', $vault))
            ->withInput()
            ->withErrors(['postponement' => $message]);
    }
}
