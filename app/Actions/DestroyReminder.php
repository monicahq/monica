<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop being reminded of a date, without touching the date.
 *
 * It is what unticking the box means, and it is deliberately not the same as
 * silencing a rule. Silencing is what the product does when something else
 * happened; this is somebody saying they no longer want the reminder, which is
 * the one gesture that removes it.
 *
 * The row survives anyway, because section 3.3 does not make an exception for a
 * removal somebody asked for: an accidental untick has to be undoable, and a
 * rule taken away is restorable rather than gone.
 *
 * The value it read is left exactly as it was. Somebody who no longer wants to
 * hear about a birthday has not forgotten when it is.
 */
class DestroyReminder
{
    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->log();
        $this->reminder->delete();
    }

    private function validate(): void
    {
        if ($this->reminder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This reminder is in another account.');
        }
    }

    /**
     * The line is written before the row goes, so the label and the name it
     * reads are still there to be read.
     */
    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderRemoval,
            vault: $this->reminder->vault,
            parameters: ['label' => $this->reminder->field->label, 'name' => $this->reminder->remindable->name],
            loggable: $this->reminder,
        )->onQueue('low');
    }
}
