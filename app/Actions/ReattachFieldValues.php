<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put back on the page everything a field held before it left the structure.
 *
 * It is the other half of {@see DetachFieldValues}, and the reason values are
 * set aside rather than deleted: putting a field back has to bring back what
 * was written in it, or removing one would be a deletion with a longer name.
 *
 * The files the values held are pointed at again, which takes them back out of
 * the sweep.
 */
class ReattachFieldValues
{
    private int $count = 0;

    public function __construct(
        private readonly User $user,
        private readonly Field $field,
    ) {}

    public function execute(): int
    {
        $this->validate();
        $this->reattach();
        $this->log();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }
    }

    private function reattach(): void
    {
        DB::transaction(function (): void {
            FieldValue::query()
                ->where('field_id', $this->field->id)
                ->where('is_detached', true)
                ->whereNotNull('value_file_id')
                ->with('file')
                ->get()
                ->each(function (FieldValue $value): void {
                    if ($value->file !== null) {
                        new AttachFile(user: $this->user, file: $value->file)->execute();
                    }
                });

            $this->count = FieldValue::query()
                ->where('field_id', $this->field->id)
                ->where('is_detached', true)
                ->update([
                    'is_detached' => false,
                    'detached_at' => null,
                ]);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValuesReattached,
            parameters: ['label' => $this->field->label, 'count' => $this->count],
            loggable: $this->field,
        )->onQueue('low');
    }
}
