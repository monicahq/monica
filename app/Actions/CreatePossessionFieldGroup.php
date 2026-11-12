<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Add a group of fields to a possession type, placed after the others. Without a name it is called
 * "Untitled group".
 */
class CreatePossessionFieldGroup
{
    private PossessionFieldGroup $group;

    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
        private ?string $name = null,
    ) {}

    public function execute(): PossessionFieldGroup
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->group;
    }

    private function sanitize(): void
    {
        $this->name = $this->name === null
            ? __('Untitled group')
            : TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this group.'),
            ]);
        }
    }

    private function create(): void
    {
        $lastPosition = PossessionFieldGroup::query()
            ->where('possession_type_id', $this->possessionType->id)
            ->max('position');

        $this->group = PossessionFieldGroup::query()->create([
            'account_id' => $this->possessionType->account_id,
            'possession_type_id' => $this->possessionType->id,
            'name' => $this->name,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldGroupCreation,
            parameters: ['name' => $this->group->name, 'type' => $this->possessionType->name],
            loggable: $this->group,
        )->onQueue('low');
    }
}
