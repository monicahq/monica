<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionFieldOptions;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rename a field of a possession type, change its kind, and set its choices. A field that stops
 * being a select field loses its choices, and a field changing kind loses the values possessions
 * held for it, since they were written for the old kind.
 */
class UpdatePossessionField
{
    /** @param  list<string>|null  $options */
    public function __construct(
        private readonly User $user,
        private readonly PossessionField $field,
        private string $name,
        private readonly PossessionFieldKindEnum $kind,
        private ?array $options = null,
    ) {}

    public function execute(): PossessionField
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->field;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->options = PossessionFieldOptions::clean($this->kind, $this->options);
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this field.'),
            ]);
        }

        PossessionFieldOptions::validate($this->options);
    }

    private function update(): void
    {
        $kindChanges = $this->field->kind !== $this->kind;

        $this->field->name = $this->name;
        $this->field->kind = $this->kind;
        $this->field->options = $this->options;

        DB::transaction(function () use ($kindChanges): void {
            $this->field->save();

            if ($kindChanges) {
                $this->field->values()->delete();
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldUpdate,
            parameters: ['name' => $this->field->name, 'type' => $this->field->possessionType->name],
            loggable: $this->field,
        )->onQueue('low');
    }
}
