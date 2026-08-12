<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\EntityTypeIsNotActive;
use App\Exceptions\VaultHasNoEntityDefault;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write down one of the things an account invented: a specific dog, a specific
 * company, a specific bottle. The name is all the product imposes, and
 * everything else the page will hold comes from the template version the record
 * is put on.
 *
 * The version is the vault's default for this type unless the caller names
 * another one. A type belongs to the account and is visible in every vault;
 * whether it is used in a given vault is decided there, by whether a default
 * template is set for it, so a vault with none refuses rather than guesses.
 *
 * A deactivated type takes nothing new. That is the whole difference between
 * deactivating it and deleting it: what exists stays readable and editable.
 */
class CreateEntity
{
    private Entity $entity;

    private TemplateVersion $version;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
        private string $name,
        private readonly ?TemplateVersion $templateVersion = null,
    ) {}

    public function execute(): Entity
    {
        $this->name = TextSanitizer::plainText($this->name);

        $this->readTheVaultDefault();
        $this->validate();
        $this->create();
        $this->log();

        return $this->entity;
    }

    /**
     * Find the structure the vault starts a record of this type on, unless the
     * caller named one. The vault and the type are checked here rather than in
     * the validation that follows, since reading the default of a vault in
     * another account would answer a question nobody is allowed to ask.
     */
    private function readTheVaultDefault(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }

        if ($this->templateVersion instanceof TemplateVersion) {
            $this->version = $this->templateVersion;

            return;
        }

        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Entity)
            ->where('entity_type_id', $this->entityType->id)
            ->first();

        if ($default === null) {
            throw new VaultHasNoEntityDefault("This vault has no template for new {$this->entityType->name_plural} to start from.");
        }

        $this->version = $default->templateVersion;
    }

    private function validate(): void
    {
        if (! $this->entityType->is_active) {
            throw new EntityTypeIsNotActive("New {$this->entityType->name_plural} are not being created any more.");
        }

        if ($this->version->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->version->template->applies_to !== TemplateAppliesToEnum::Entity) {
            throw new InvalidArgumentException('This template does not describe an entity.');
        }

        if ($this->version->template->entity_type_id !== $this->entityType->id) {
            throw new InvalidArgumentException('This template describes another kind of record.');
        }

        if ($this->version->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A record can only be put on a published version.');
        }
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->entity = Entity::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->vault->id,
                'entity_type_id' => $this->entityType->id,
                'template_version_id' => $this->version->id,
                'name' => $this->name,
                'avatar_colour' => Arr::random(Entity::AVATAR_COLOURS),
            ]);

            $this->entityType->increment('entity_count');
            $this->version->increment('record_count');

            new WriteDefaultValues(user: $this->user, record: $this->entity)->execute();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityCreation,
            vault: $this->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        )->onQueue('low');
    }
}
