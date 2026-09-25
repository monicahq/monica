<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\RelationTargetEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\User;
use App\Models\Vault;

class FieldChoicesViewModel
{
    public const int CANDIDATE_LIMIT = 25;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Field $field,
        private readonly string $query,
    ) {}

    public function query(): string
    {
        return $this->query;
    }

    /**
     * The records of this vault a relation field could name, narrowed by what
     * was typed: somebody in the vault, or a record of the kind the field asks for.
     *
     * @return list<array{uuid: string, name: string}>
     */
    public function candidates(): array
    {
        if ($this->field->relation_target === RelationTargetEnum::Contact) {
            return Contact::query()
                ->where('vault_id', $this->vault->id)
                ->matchingSearch($this->query, $this->user->locale)
                ->orderBy('sort_name')
                ->limit(self::CANDIDATE_LIMIT)
                ->get()
                ->map(fn (Contact $contact): array => ['uuid' => $contact->uuid, 'name' => $contact->name])
                ->values()
                ->all();
        }

        return Entity::query()
            ->where('vault_id', $this->vault->id)
            ->when($this->field->relation_entity_type_id !== null, fn ($query) => $query->where('entity_type_id', $this->field->relation_entity_type_id))
            ->matchingSearch($this->query, $this->user->locale)
            ->orderBy('sort_name')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Entity $entity): array => ['uuid' => $entity->uuid, 'name' => $entity->name])
            ->values()
            ->all();
    }

    public function noCandidatesLine(): string
    {
        if (mb_trim($this->query) === '') {
            return __('Nothing in this vault to choose yet.');
        }

        return __('Nothing in this vault matches “:query”.', ['query' => $this->query]);
    }
}
