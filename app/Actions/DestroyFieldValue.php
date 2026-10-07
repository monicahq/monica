<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Empty one field of one record.
 */
class DestroyFieldValue
{
    private string $label;

    public function __construct(
        private readonly User $user,
        private readonly FieldValue $fieldValue,
        private readonly bool $writesToTheJournal = true,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->fieldValue->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->label = $this->fieldValue->field->label;

        DB::transaction(function (): void {
            if ($this->fieldValue->file !== null) {
                new DetachFile(user: $this->user, file: $this->fieldValue->file)->execute();
            }

            $chosen = collect([$this->fieldValue->value_list_item_id])
                ->merge($this->fieldValue->value_list_item_ids ?? [])
                ->filter();

            if ($chosen->isNotEmpty()) {
                ValueListItem::query()
                    ->whereIn('id', $chosen->all())
                    ->where('usage_count', '>', 0)
                    ->decrement('usage_count');
            }

            $rule = $this->fieldValue->reminder;

            if ($rule instanceof Reminder && $rule->is_active) {
                new DeactivateReminders(user: $this->user, subject: $rule, reason: ReminderInactiveReasonEnum::ValueCleared)->execute();
            }

            $this->fieldValue->delete();
        });
    }

    private function log(): void
    {
        if (! $this->writesToTheJournal) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValueCleared,
            vault: $this->fieldValue->vault,
            parameters: ['label' => $this->label, 'name' => $this->fieldValue->valuable->name],
            loggable: $this->fieldValue,
        )->onQueue('low');
    }
}
