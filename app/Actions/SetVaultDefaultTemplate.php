<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Say which structure a new record starts on in this vault.
 */
class SetVaultDefaultTemplate
{
    private VaultDefaultTemplate $default;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly StructureVersion $structureVersion,
    ) {}

    public function execute(): VaultDefaultTemplate
    {
        $this->validate();
        $this->set();
        $this->log();

        return $this->default;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->structureVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A record can only start on a published version.');
        }
    }

    private function set(): void
    {
        $template = $this->structureVersion->structure->owner;

        $this->default = VaultDefaultTemplate::query()->updateOrCreate(
            [
                'vault_id' => $this->vault->id,
                'applies_to' => $template->structure->applies_to,
                'entity_type_id' => $template->structure->entity_type_id,
            ],
            [
                'account_id' => $this->user->account_id,
                'structure_version_id' => $this->structureVersion->id,
            ],
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::VaultDefaultTemplateSet,
            vault: $this->vault,
            parameters: ['name' => $this->structureVersion->structure->owner->name],
            loggable: $this->default,
        )->onQueue('low');
    }
}
