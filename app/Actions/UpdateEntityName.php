<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a record is called. It is the one thing about an entity the
 * product imposes, since it lists it, searches it and draws initials from it,
 * and a record left with nothing is refused by the model.
 *
 * The string the lists sort on is rewritten by the model on save, so nothing
 * here has to remember to keep it in step.
 */
class UpdateEntityName
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly Entity $entity,
        private string $name,
    ) {}

    public function execute(): Entity
    {
        $this->name = TextSanitizer::plainText($this->name);

        $this->validate();
        $this->update();
        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity is in another account.');
        }
    }

    private function update(): void
    {
        $before = $this->entity->name;

        $this->entity->name = $this->name;
        $this->entity->save();

        if ($before !== $this->entity->name) {
            $this->changes['name'] = ['before' => $before, 'after' => $this->entity->name];
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityNameUpdate,
            vault: $this->entity->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
            changes: $this->changes,
        )->onQueue('low');
    }
}
