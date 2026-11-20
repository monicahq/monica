<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use Illuminate\Support\Collection;

/**
 * The profiles that name somebody in one of their relation fields, such as the animal they look
 * after, each with the field that names them and what the profile says they are to it.
 */
class ContactProfilesBlock
{
    public function __construct(
        private readonly Contact $contact,
    ) {}

    public function isEmpty(): bool
    {
        return $this->profiles() === [];
    }

    /** @return list<array{name: string, type: string, icon: string, url: string, detail: string}> */
    public function profiles(): array
    {
        return once(fn (): array => Entity::query()
            ->naming($this->contact)
            ->with([
                'entityType',
                'vault',
                'fieldValues' => fn ($query) => $query->attached()->where('related_contact_id', $this->contact->id)->with(['field.parent', 'row.values']),
            ])
            ->orderBy('sort_name')
            ->get()
            ->map(fn (Entity $entity): array => [
                'name' => $entity->name,
                'type' => $entity->entityType->name_singular,
                'icon' => $entity->entityType->icon,
                'url' => route('entities.show', ['vault' => $entity->vault, 'entityType' => $entity->entityType, 'entity' => $entity]),
                'detail' => $this->detailOf($entity->fieldValues),
            ])
            ->values()
            ->all());
    }

    /**
     * The field that names the contact, then what the rest of its row says, such as the role beside
     * the person in a list of caregivers.
     *
     * @param  Collection<int, FieldValue>  $values
     */
    private function detailOf(Collection $values): string
    {
        return $values
            ->map(function (FieldValue $value): string {
                $label = $value->field->parent->label ?? $value->field->label;

                $role = $value->row?->values
                    ->reject(fn (FieldValue $other): bool => $other->is($value) || $other->is_detached)
                    ->map(fn (FieldValue $other): ?string => $other->value_string)
                    ->filter()
                    ->implode(', ');

                return $role === null || $role === '' ? $label : "{$label} · {$role}";
            })
            ->unique()
            ->implode(' · ');
    }
}
