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

/**
 * Move one deadline to a later instant.
 *
 * A deadline of another account or of another vault never reaches here: the
 * route binding refuses it, so there is nothing left for this controller to
 * check.
 *
 * The instant is built in the zone frozen on the rule, not in the reader's. A
 * rule made in Paris fires at nine in Paris, and somebody putting it off from
 * Toronto is moving a Paris deadline. Reading the typed date against their own
 * clock would land it on a different hour than the dialog promised.
 *
 * Two refusals come back as messages rather than as errors: an instant already
 * past, and a deadline somebody has since dealt with in another tab. Both are
 * things a person can do by accident, and neither is a bug to be reported.
 */
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
            ->route('reminders.index', $vault)
            ->with('status', __('Put off.'))
            ->with('status_description', __('The reminder will come back at the moment you chose.'));
    }

    /**
     * The instant to move to: a preset resolved against the rule, or the date
     * and time somebody typed, read in the rule's own zone.
     *
     * @param  array<string, mixed>  $validated
     */
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
            ->route('reminders.index', $vault)
            ->withInput()
            ->withErrors(['postponement' => $message]);
    }
}
