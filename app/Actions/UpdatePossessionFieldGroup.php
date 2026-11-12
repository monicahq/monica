<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionFieldGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a group of fields of a possession type.
 */
class UpdatePossessionFieldGroup
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionFieldGroup $group,
        private string $name,
    ) {}

    public function execute(): PossessionFieldGroup
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->group;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->group->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This group is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this group.'),
            ]);
        }
    }

    private function update(): void
    {
        $this->group->name = $this->name;
        $this->group->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldGroupUpdate,
            parameters: ['name' => $this->group->name, 'type' => $this->group->possessionType->name],
            loggable: $this->group,
        )->onQueue('low');
    }
}
