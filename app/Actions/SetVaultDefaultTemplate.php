<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Say which structure a new record starts on in this vault.
 *
 * What kind of record the default is for is read off the version rather than
 * asked for. A template already knows whether it describes a contact or one of
 * the kinds of record the account invented, and asking twice only creates a way
 * for the two answers to disagree.
 *
 * There is one default per vault and per kind of record, so setting it again
 * replaces what was there. Records already created keep the version they were
 * created on: moving those is a deliberate act of its own, which takes a
 * snapshot first.
 *
 * The version has to be published, for the same reason a record can only be put
 * on a published one: a draft is something somebody is still editing.
 */
class SetVaultDefaultTemplate
{
    private VaultDefaultTemplate $default;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly TemplateVersion $templateVersion,
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

        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->templateVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A record can only start on a published version.');
        }
    }

    private function set(): void
    {
        $template = $this->templateVersion->template;

        $this->default = VaultDefaultTemplate::query()->updateOrCreate(
            [
                'vault_id' => $this->vault->id,
                'applies_to' => $template->applies_to,
                'entity_type_id' => $template->entity_type_id,
            ],
            [
                'account_id' => $this->user->account_id,
                'template_version_id' => $this->templateVersion->id,
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
            parameters: ['name' => $this->templateVersion->template->name],
            loggable: $this->default,
        )->onQueue('low');
    }
}
