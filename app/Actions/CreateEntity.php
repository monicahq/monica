<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTemplateChanged;
use App\Exceptions\EntityTemplateNotOffered;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Write down a record of a type the account invented, with everything its template was filled in
 * with, on the template the vault starts that type from or on another of that type somebody picked.
 */
class CreateEntity
{
    private Entity $entity;

    private StructureVersion $version;

    /**
     * @param  StructureVersion|null  $structureVersion  the version the form was drawn from, null to take the current default
     * @param  array<int, mixed>  $values  what was typed, keyed by field id, uploads included; a field left out takes its default
     * @param  array<int, mixed>  $labels  the label picked beside a value, keyed by field id
     * @param  array<int, mixed>  $labelWordings  the wording typed when that label is the open one
     * @param  array<int, bool>  $yearIsUnknown
     * @param  array<int, bool>  $monthIsUnknown
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
        private string $name,
        private readonly ?StructureVersion $structureVersion = null,
        private readonly array $values = [],
        private readonly array $labels = [],
        private readonly array $labelWordings = [],
        private readonly array $yearIsUnknown = [],
        private readonly array $monthIsUnknown = [],
    ) {}

    public function execute(): Entity
    {
        $this->name = (string) TextSanitizer::nullablePlainText($this->name);

        $this->validate();

        DB::transaction(function (): void {
            $this->entity = Entity::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->vault->id,
                'entity_type_id' => $this->entityType->id,
                'structure_version_id' => $this->version->id,
                'name' => $this->name,
                'avatar_colour' => Arr::random(Entity::AVATAR_COLOURS),
            ]);

            $this->version->increment('record_count');
            $this->entityType->increment('entity_count');

            $values = $this->values + $this->defaults();

            foreach ($this->version->assembly as $section) {
                new SetFieldValues(
                    user: $this->user,
                    record: $this->entity,
                    section: $section,
                    values: $values,
                    labels: $this->labels,
                    labelWordings: $this->labelWordings,
                    yearIsUnknown: $this->yearIsUnknown,
                    monthIsUnknown: $this->monthIsUnknown,
                )->execute();
            }
        });

        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new EntityNeedsAName(__('Write a name for this record.'));
        }

        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }

        $this->user->authorize('addEntity', [$this->entityType, $this->vault]);

        if ($this->structureVersion instanceof StructureVersion) {
            $this->validateTheChosenVersion($this->structureVersion);
            $this->version = $this->structureVersion;

            return;
        }

        $version = $this->entityType->versionsOnOfferIn($this->vault)->first();

        if (! $version instanceof StructureVersion) {
            throw new ModelNotFoundException('This vault has no template for this type.');
        }

        $this->version = $version;
    }

    private function validateTheChosenVersion(StructureVersion $version): void
    {
        if ($version->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($version->structure->entity_type_id !== $this->entityType->id) {
            throw new EntityTemplateNotOffered(__('This template cannot be used for this type.'));
        }

        if ($version->structure->published_version_id !== $version->id) {
            throw new EntityTemplateChanged(__('The template changed since the form was opened. Open the form again to see what it asks now.'));
        }

        if (! $this->entityType->versionsOnOfferIn($this->vault)->contains('id', $version->id)) {
            throw new EntityTemplateNotOffered(__('This template cannot be used for this type.'));
        }
    }

    /** @return array<int, mixed> */
    private function defaults(): array
    {
        return $this->version->inputFields()
            ->reject(fn (Field $field): bool => $field->type->isRelation())
            ->filter(fn (Field $field): bool => $field->default_value !== null)
            ->mapWithKeys(fn (Field $field): array => [
                $field->id => is_array($field->default_value) && $field->type !== FieldTypeEnum::Multiselect
                    ? ($field->default_value[0] ?? null)
                    : $field->default_value,
            ])
            ->all();
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
        );
    }
}
