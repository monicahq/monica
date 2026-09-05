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
 * Set aside everything ever written into a field, because the field has left the structure.
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
