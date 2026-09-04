<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Silence every reminder rule a subject carries, for the reason given.
 *
 * Nothing is deleted and nothing is hidden. A rule that stops applying says
 * which of the things that can silence it happened, and {@see
 * ReactivateReminders} brings back exactly the rules silenced by the cause being
 * reversed. That is section 3.3 applied to reminders: an archived contact is a
 * silent contact, and a field that has left the structure takes its rules quiet
 * with it, but in both cases the rule is still there to be explained.
 *
 * The subject is whatever the thing that happened was about. A contact or an
 * entity silences the rules about it, a field silences the rules reading it
 * wherever they are, and one rule silences only itself, which is what a date
 * somebody cleared needs: the value is gone and the rule that read it is quiet,
 * while every other rule on the record goes on firing.
 *
 * A rule already quiet for another reason is left exactly as it is. Overwriting
 * its reason would lose the first explanation and make the wrong reversal bring
 * it back.
 *
 * Silencing a rule takes its deadlines ahead away too, under the reason matching
 * the one that silenced it, so that reversing the cause brings back exactly what
 * it silenced. What is behind is history and is never touched.
 */
class DeactivateReminders
{
    private int $count = 0;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity|Field|Reminder $subject,
        private readonly ReminderInactiveReasonEnum $reason,
    ) {}

    /**
     * How many rules went quiet.
     */
    public function execute(): int
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->subject->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }
    }

    private function deactivate(): void
    {
        DB::transaction(function (): void {
            $silenced = $this->rules()->get();

            $this->count = $this->rules()->update([
                'is_active' => false,
                'inactive_reason' => $this->reason,
                'deactivated_at' => now(),
            ]);

            foreach ($silenced as $rule) {
                new CancelReminderOccurrences(
                    reminder: $rule,
                    reason: ReminderOccurrenceCancelledReasonEnum::forInactiveReason($this->reason),
                )->execute();
            }
        });
    }

    /**
     * @return Builder<Reminder>
     */
    private function rules(): Builder
    {
        $query = Reminder::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true);

        if ($this->subject instanceof Reminder) {
            return $query->whereKey($this->subject->id);
        }

        if ($this->subject instanceof Field) {
            return $query->where('field_id', $this->subject->id);
        }

        return $query
            ->where('remindable_type', $this->subject->getMorphClass())
            ->where('remindable_id', $this->subject->id);
    }

    private function log(): void
    {
        if ($this->count === 0) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderDeactivation,
            vault: $this->subject instanceof Field ? null : $this->subject->vault,
            parameters: ['name' => $this->name(), 'count' => $this->count],
            loggable: $this->subject,
        )->onQueue('low');
    }

    /**
     * What the line in the journal calls the thing this was about: a field by its
     * label, a rule by the record it is about, and a record by its own name.
     */
    private function name(): string
    {
        if ($this->subject instanceof Field) {
            return $this->subject->label;
        }

        if ($this->subject instanceof Reminder) {
            return $this->subject->remindable->name;
        }

        return $this->subject->name;
    }
}
