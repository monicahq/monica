<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Choose which of People, Things and Knowledge the vault shows in its sidebar and its search.
 * Nothing is deleted: a hidden section stays reachable by its address, the API and MCP.
 */
class UpdateVaultSectionVisibility
{
    /** @var array<string, array{before: bool, after: bool}> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly bool $showsPeople,
        private readonly bool $showsThings,
        private readonly bool $showsKnowledge,
    ) {}

    public function execute(): Vault
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->vault;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function update(): void
    {
        $after = [
            'shows_people' => $this->showsPeople,
            'shows_things' => $this->showsThings,
            'shows_knowledge' => $this->showsKnowledge,
        ];

        foreach ($after as $field => $value) {
            if ($value !== $this->vault->$field) {
                $this->changes[$field] = ['before' => $this->vault->$field, 'after' => $value];
            }

            $this->vault->$field = $value;
        }

        $this->vault->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultSectionVisibilityUpdate,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
            changes: $this->changes,
        );
    }
}
