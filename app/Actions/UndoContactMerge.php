<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactMergeCannotBeUndone;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Reminder;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put two merged contacts back the way they were.
 *
 * It replays the merge's own ledger backwards rather than restoring the
 * survivor's snapshot. A snapshot describes one record, and a merge touched two:
 * restoring the survivor alone would leave the absorbed contact's rows sitting
 * on it. The ledger says which row moved and where from, which is exactly what
 * reversing needs.
 *
 * Either the whole thing comes back or nothing does. An undo that cannot be
 * completed is refused before anything is written, so a half-reversed merge is
 * not a state the product can reach.
 */
class UndoContactMerge
{
    private Contact $kept;

    private Contact $absorbed;

    public function __construct(
        private readonly User $user,
        private readonly ContactMerge $merge,
    ) {}

    public function execute(): ContactMerge
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->absorbed->restore();

            foreach (array_reverse($this->merge->ledger) as $entry) {
                $this->reverse($entry);
            }

            $this->merge->undone_at = now();
            $this->merge->undone_by_user_id = $this->user->id;
            $this->merge->save();
        });

        $this->log();

        return $this->merge;
    }

    private function validate(): void
    {
        if ($this->merge->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This merge happened in another account.');
        }

        if (! $this->merge->stands()) {
            throw new ContactMergeCannotBeUndone('This merge has already been undone.');
        }

        $absorbed = Contact::withTrashed()->find($this->merge->merged_contact_id);

        if ($absorbed === null) {
            throw new ContactMergeCannotBeUndone('The contact that was absorbed is no longer there to bring back.');
        }

        $kept = Contact::query()->find($this->merge->kept_contact_id);

        if ($kept === null) {
            throw new ContactMergeCannotBeUndone('The contact that survived the merge is gone.');
        }

        $this->absorbed = $absorbed;
        $this->kept = $kept;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function reverse(array $entry): void
    {
        match ($entry['kind']) {
            'value_moved', 'value_set_aside' => $this->giveTheValueBack($entry),
            'value_detached' => $this->attachTheValueAgain($entry),
            'row_moved' => $this->giveTheRowBack($entry),
            'pointer_moved' => $this->pointBackAtTheAbsorbedContact($entry),
            'reminder_moved' => $this->giveTheReminderBack($entry),
            'reminder_deactivated' => $this->wakeTheReminderUp($entry),
            'share_link_moved' => $this->giveTheShareLinkBack($entry),
            'avatar_replaced' => $this->putTheOldAvatarBack($entry),
            'partial_cleared' => $this->makeItPartialAgain(),
            default => null,
        };
    }

    /** @param array<string, mixed> $entry */
    private function giveTheValueBack(array $entry): void
    {
        $value = FieldValue::query()->find($entry['id']);

        if ($value === null) {
            return;
        }

        $value->valuable_id = $this->absorbed->id;

        if ($entry['kind'] === 'value_set_aside') {
            $value->is_detached = false;
            $value->detached_at = null;
        }

        $value->save();
    }

    /** @param array<string, mixed> $entry */
    private function attachTheValueAgain(array $entry): void
    {
        FieldValue::query()
            ->whereKey($entry['id'])
            ->update(['is_detached' => false, 'detached_at' => null]);
    }

    /** @param array<string, mixed> $entry */
    private function giveTheRowBack(array $entry): void
    {
        FieldValueRow::query()->whereKey($entry['id'])->update(['valuable_id' => $this->absorbed->id]);

        FieldValue::query()
            ->where('field_value_row_id', $entry['id'])
            ->update(['valuable_id' => $this->absorbed->id]);
    }

    /** @param array<string, mixed> $entry */
    private function pointBackAtTheAbsorbedContact(array $entry): void
    {
        FieldValue::query()->whereKey($entry['id'])->update(['related_contact_id' => $this->absorbed->id]);
    }

    /** @param array<string, mixed> $entry */
    private function giveTheReminderBack(array $entry): void
    {
        Reminder::query()->whereKey($entry['id'])->update(['remindable_id' => $this->absorbed->id]);
    }

    /**
     * A reminder the merge stopped because its value stayed behind starts again,
     * since that value is back where it was.
     *
     * @param  array<string, mixed>  $entry
     */
    private function wakeTheReminderUp(array $entry): void
    {
        $reminder = Reminder::query()->find($entry['id']);

        if ($reminder === null) {
            return;
        }

        new ReactivateReminders(
            user: $this->user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();
    }

    /** @param array<string, mixed> $entry */
    private function giveTheShareLinkBack(array $entry): void
    {
        ShareLink::query()->whereKey($entry['id'])->update(['shareable_id' => $this->absorbed->id]);
    }

    /** @param array<string, mixed> $entry */
    private function putTheOldAvatarBack(array $entry): void
    {
        $this->kept->avatar_file_id = $entry['was'];
        $this->kept->save();
    }

    private function makeItPartialAgain(): void
    {
        $this->kept->is_partial = true;
        $this->kept->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactMergeUndone,
            vault: $this->kept->vault,
            parameters: [
                'name' => $this->kept->name,
                'other' => $this->absorbed->name,
            ],
            loggable: $this->kept,
        )->onQueue('low');
    }
}
