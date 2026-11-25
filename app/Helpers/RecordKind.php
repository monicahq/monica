<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ContactGroupingEnum;
use App\Enums\StructureAppliesToEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecordKind
{
    public const string CONTACTS = 'contacts';

    private function __construct(
        public readonly Vault $vault,
        public readonly ?EntityType $entityType,
    ) {}

    public static function forContacts(Vault $vault): self
    {
        return new self(vault: $vault, entityType: null);
    }

    public static function forEntityType(Vault $vault, EntityType $entityType): self
    {
        if ($entityType->account_id !== $vault->account_id) {
            throw new ModelNotFoundException('This kind of record is in another account.');
        }

        return new self(vault: $vault, entityType: $entityType);
    }

    public static function fromApiSegment(Vault $vault, string $segment): self
    {
        if ($segment === self::CONTACTS) {
            return self::forContacts($vault);
        }

        $entityType = EntityType::query()
            ->where('account_id', $vault->account_id)
            ->where('uuid', $segment)
            ->firstOrFail();

        return self::forEntityType($vault, $entityType);
    }

    public function isPeople(): bool
    {
        return ! $this->entityType instanceof EntityType;
    }

    public function apiSegment(): string
    {
        return $this->isPeople() ? self::CONTACTS : $this->entityType->uuid;
    }

    public function title(): string
    {
        return $this->isPeople() ? __('People') : $this->entityType->name_plural;
    }

    public function singular(): string
    {
        return $this->isPeople() ? __('contact') : $this->entityType->name_singular;
    }

    public function plural(): string
    {
        return $this->isPeople() ? __('contacts') : $this->entityType->name_plural;
    }

    public function appliesTo(): StructureAppliesToEnum
    {
        return $this->isPeople() ? StructureAppliesToEnum::Contact : StructureAppliesToEnum::Entity;
    }

    public function morphClass(): string
    {
        if ($this->isPeople()) {
            return new Contact()->getMorphClass();
        }

        return new Entity()->getMorphClass();
    }

    /** @return Builder<Contact>|Builder<Entity> */
    public function recordQuery(): Builder
    {
        if ($this->isPeople()) {
            return Contact::query()->where('contacts.vault_id', $this->vault->id);
        }

        return Entity::query()
            ->where('entities.vault_id', $this->vault->id)
            ->where('entities.entity_type_id', $this->entityType->id);
    }

    public function recordTable(): string
    {
        return $this->isPeople() ? 'contacts' : 'entities';
    }

    /** @return Collection<int, Template> */
    public function templates(): Collection
    {
        return Template::query()
            ->where('account_id', $this->vault->account_id)
            ->whereHas('structure', function (Builder $query): void {
                $query->where('applies_to', $this->appliesTo());

                if (! $this->isPeople()) {
                    $query->where('entity_type_id', $this->entityType->id);
                }
            })
            ->with('structure.publishedVersion')
            ->orderBy('position')
            ->get();
    }

    public function template(string $uuid): Template
    {
        $template = $this->templates()->firstWhere('uuid', $uuid);

        if (! $template instanceof Template) {
            throw new ModelNotFoundException()->setModel(Template::class, [$uuid]);
        }

        return $template;
    }

    public function describes(Template $template): bool
    {
        $structure = $template->structure;

        if ($template->account_id !== $this->vault->account_id || $structure === null) {
            return false;
        }

        if ($structure->applies_to !== $this->appliesTo()) {
            return false;
        }

        return $this->isPeople() || $structure->entity_type_id === $this->entityType->id;
    }

    public function defaultTemplateId(): ?int
    {
        $versionId = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', $this->appliesTo())
            ->when(! $this->isPeople(), fn (Builder $query) => $query->where('entity_type_id', $this->entityType->id))
            ->value('structure_version_id');

        if ($versionId === null) {
            return null;
        }

        $structure = StructureVersion::query()->find($versionId)?->structure;

        return $structure?->owner_type === new Template()->getMorphClass() ? $structure->owner_id : null;
    }

    /** @param array<string, int|string> $filters */
    public function listUrl(array $filters = []): string
    {
        if ($this->isPeople()) {
            return route('contacts.index', ['vault' => $this->vault, 'filter' => ContactGroupingEnum::Everyone->value, ...$filters]);
        }

        return route('entities.index', ['vault' => $this->vault, 'entityType' => $this->entityType, ...$filters]);
    }

    /** @param array<string, int|string> $parameters */
    public function route(string $name, array $parameters = []): string
    {
        if ($this->isPeople()) {
            return route("contacts.{$name}", ['vault' => $this->vault, ...$parameters]);
        }

        return route("entities.{$name}", ['vault' => $this->vault, 'entityType' => $this->entityType, ...$parameters]);
    }
}
