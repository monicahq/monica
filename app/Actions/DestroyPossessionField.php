<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Remove a field from its possession type. No thing records a value yet, so nothing else goes with
 * it.
 */
class DestroyPossessionField
{
    private string $name;

    private string $typeName;

    public function __construct(
        private readonly User $user,
        private readonly PossessionField $field,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->field->name;
        $this->typeName = $this->field->possessionType->name;

        $this->field->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldDeletion,
            parameters: ['name' => $this->name, 'type' => $this->typeName],
            loggable: $this->field,
        )->onQueue('low');
    }
}
