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
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Silence every reminder rule a subject carries, for the reason given.
 */
class DeactivateReminders
{
    private int $count = 0;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity|Field|Group|Reminder $subject,
        private readonly ReminderInactiveReasonEnum $reason,
    ) {}

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
                    includePast: true,
                )->execute();
            }
        });
    }

    /** @return Builder<Reminder> */
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
        );
    }

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
