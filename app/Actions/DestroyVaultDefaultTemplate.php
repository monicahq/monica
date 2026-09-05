<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\VaultAlwaysHasAContactDefault;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering a structure by default for a kind of record in a vault.
 */
class DestroyVaultDefaultTemplate
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly VaultDefaultTemplate $default,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->default->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This default is in another account.');
        }

        if ($this->default->applies_to === TemplateAppliesToEnum::Contact) {
            throw new VaultAlwaysHasAContactDefault('A vault always has a template new contacts start from.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->default->templateVersion->template->name;

        $this->default->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::VaultDefaultTemplateRemoved,
            vault: $this->default->vault,
            parameters: ['name' => $this->name],
            loggable: $this->default,
        )->onQueue('low');
    }
}
