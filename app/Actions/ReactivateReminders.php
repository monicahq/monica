<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring back the reminder rules one cause silenced, now that the cause has been
 * reversed.
 *
 * The reason is the whole point. Unarchiving a contact reverses archiving and
 * nothing else, so a rule quiet because the field it read has left the structure
 * stays quiet and goes on saying so. Reviving every quiet rule would be undoing
 * something nobody undid, and would leave the explanation on a rule that is
 * firing again.
 *
 * A rule somebody removed by unticking the box is not here at all: that is a
 * soft delete, and it comes back by being restored rather than by being
 * reactivated.
 */
class ReactivateReminders
{
    private int $count = 0;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity|Field|Reminder $subject,
        private readonly ReminderInactiveReasonEnum $reason,
    ) {}

    /**
     * How many rules came back.
     */
    public function execute(): int
    {
        $this->validate();
        $this->reactivate();
        $this->log();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->subject->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }
    }

    private function reactivate(): void
    {
        $this->count = $this->rules()->update([
            'is_active' => true,
            'inactive_reason' => null,
            'deactivated_at' => null,
        ]);
    }

    /**
     * @return Builder<Reminder>
     */
    private function rules(): Builder
    {
        $query = Reminder::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', false)
            ->where('inactive_reason', $this->reason);

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
            action: UserActionEnum::ReminderReactivation,
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
