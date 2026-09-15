<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\FieldTypeEnum;
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

    /** @return Collection<int, Field> */
    private function fields(): Collection
    {
        return $this->contact->structureVersion
            ->sectionVersions()
            ->with('fields')
            ->get()
            ->flatMap(fn (SectionVersion $section): Collection => $section->fields->values());
    }
}
