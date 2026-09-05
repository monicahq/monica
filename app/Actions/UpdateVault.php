<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a vault, say what it is for, or change the colour it is told apart by.
 */
class UpdateVault
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private string $name,
        private ?string $description = null,
        private ?string $colour = null,
    ) {}

    public function execute(): Vault
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->vault;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? Vault::DEFAULT_COLOUR);
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function update(): void
    {
        $before = [
            'name' => $this->vault->name,
            'description' => $this->vault->description,
            'colour' => $this->vault->colour,
        ];

        $this->vault->name = $this->name;
        $this->vault->description = $this->description;
        $this->vault->colour = $this->colour;
        $this->vault->save();

        foreach ($before as $field => $value) {
            if ($value !== $this->vault->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->vault->$field];
            }
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultUpdate,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
            changes: $this->changes,
        )->onQueue('low');
    }
}
