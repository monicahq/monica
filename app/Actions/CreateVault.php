<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;

/**
 * Open a new compartment in the account. Nothing is put in it: a vault is a
 * boundary, and it starts empty.
 *
 * It goes at the end of the list, since the order vaults appear in is the
 * user's to decide and a new one has not been placed yet. The very first vault
 * of an account becomes the default one, because exactly one vault is where
 * records land when nobody says which, and there is nothing else to be it.
 */
class CreateVault
{
    private Vault $vault;

    public function __construct(
        private readonly User $user,
        private string $name,
        private ?string $description = null,
        private ?string $colour = null,
    ) {}

    public function execute(): Vault
    {
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->vault;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? Vault::DEFAULT_COLOUR);
    }

    private function create(): void
    {
        $positions = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->vault = Vault::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $this->name,
            'description' => $this->description,
            'colour' => $this->colour,
            'position' => ($positions->max() ?? 0) + 1,
            'is_default' => $positions->isEmpty(),
            'contact_count' => 0,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultCreation,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
