<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Set aside everything ever written into a field, because the field has left
 * the structure.
 *
 * Nothing is destroyed. A field removed from a new version takes its values off
 * the page and off nothing else: they are listed on a screen of their own with
 * an explanation, and {@see ReattachFieldValues} brings them back the day
 * somebody puts the field back. Silent deletion is exactly what the product
 * refuses to do.
 *
 * A reminder rule reading the field goes quiet for the same reason and in the
 * same way. It says the field has left the structure rather than disappearing,
 * which is what section 3.3 asks of it by name.
 *
 * The files the values held are let go of, so a value set aside and never
 * restored does not hold a file alive for ever. Letting go is not deleting: the
 * grace period is what makes restoring an older version of a record find its
 * file still on the disk.
 */
class DetachFieldValues
{
    private int $count = 0;

    public function __construct(
        private readonly User $user,
        private readonly Field $field,
    ) {}

    public function execute(): int
    {
        $this->validate();
        $this->detach();
        $this->log();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }
    }

    private function detach(): void
    {
        DB::transaction(function (): void {
            FieldValue::query()
                ->where('field_id', $this->field->id)
                ->where('is_detached', false)
                ->whereNotNull('value_file_id')
                ->with('file')
                ->get()
                ->each(function (FieldValue $value): void {
                    if ($value->file !== null) {
                        new DetachFile(user: $this->user, file: $value->file)->execute();
                    }
                });

            new DeactivateReminders(
                user: $this->user,
                subject: $this->field,
                reason: ReminderInactiveReasonEnum::FieldRemoved,
            )->execute();

            $this->count = FieldValue::query()
                ->where('field_id', $this->field->id)
                ->where('is_detached', false)
                ->update([
                    'is_detached' => true,
                    'detached_at' => now(),
                ]);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValuesDetached,
            parameters: ['label' => $this->field->label, 'count' => $this->count],
            loggable: $this->field,
        )->onQueue('low');
    }
}
