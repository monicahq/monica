<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\SectionVersion;
use Illuminate\Support\Collection;

class OwnedFields
{
    public const string BIRTHDAY_KEY = 'birthday';

    /** @var array<string, array<int, Field>>|null */
    private ?array $byProperty = null;

    public function __construct(
        private readonly Contact $contact,
        private readonly bool $preloaded = false,
    ) {}

    /** @return array<int, Field> */
    public function for(string $property): array
    {
        return $this->byProperty()[$property] ?? [];
    }

    /** @return array<int, string> */
    public function properties(): array
    {
        return array_keys($this->byProperty());
    }

    /**
     * The fields holding the contact's categories: every multiple choice drawing on the account's
     * "Contact categories" list. They share one `CATEGORIES` property, a list of labels, so they
     * stay out of the properties written one value at a time.
     *
     * @return array<int, Field>
     */
    public function categoryFields(): array
    {
        return $this->fields()
            ->filter(fn (Field $field): bool => $field->type === FieldTypeEnum::Multiselect
                && $field->valueList?->key === ValueListEnum::ContactCategories->value)
            ->values()
            ->all();
    }

    public static function propertyFor(Field $field): ?string
    {
        return match ($field->type) {
            FieldTypeEnum::Email => 'EMAIL',
            FieldTypeEnum::Phone => 'TEL',
            FieldTypeEnum::Url => 'URL',
            FieldTypeEnum::Address => 'ADR',
            FieldTypeEnum::RecurringDate => $field->key === self::BIRTHDAY_KEY ? 'BDAY' : 'X-ABDATE',
            default => null,
        };
    }

    /** @return array<string, array<int, Field>> */
    private function byProperty(): array
    {
        if ($this->byProperty !== null) {
            return $this->byProperty;
        }

        $grouped = [];

        foreach ($this->fields() as $field) {
            $property = self::propertyFor($field);

            if ($property === null) {
                continue;
            }

            $grouped[$property][] = $field;
        }

        return $this->byProperty = $grouped;
    }

    /**
     * A preloaded contact carries its structure with the sections and fields, so a batch of cards
     * sharing one structure asks for it once rather than once per card.
     *
     * @return Collection<int, Field>
     */
    private function fields(): Collection
    {
        $structure = $this->contact->structureVersion;

        $sections = $this->preloaded
            ? $structure->sectionVersions
            : $structure->sectionVersions()->with('fields')->get();

        return $sections
            ->flatMap(fn (SectionVersion $section): Collection => $section->fields->values());
    }
}
