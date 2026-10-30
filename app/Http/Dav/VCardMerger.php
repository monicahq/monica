<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\AddressLabelEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\EmailLabelEnum;
use App\Enums\PhoneLabelEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\ValueListItem;
use Closure;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class VCardMerger
{
    private const array LABELLED = ['EMAIL', 'TEL', 'URL', 'ADR', 'X-ABDATE'];

    private const array MANAGED_NAME_PARAMETERS = ['ALTID', 'PROP-ID', 'LANGUAGE', 'SCRIPT', 'PHONETIC', 'PREF', 'X-NAME-KIND', 'VALUE'];

    /**
     * A merger told the contact arrives preloaded reads the relations it needs from the contact
     * rather than from the database. Only a caller that loaded them for this very merge says so,
     * since a relation loaded earlier may no longer say what the database says.
     */
    public function __construct(
        private readonly bool $reachesSensitiveFields = false,
        private readonly bool $preloaded = false,
    ) {}

    /**
     * The relations a preloaded contact carries, loaded up front for a batch of cards, so that
     * merging each card asks nothing more of the database.
     *
     * @return array<int|string, string|Closure>
     */
    public static function relations(): array
    {
        return [
            'avatar',
            'nameRepresentations',
            'fieldValues' => fn (MorphMany $query): MorphMany => self::values($query),
            'structureVersion.sectionVersions.fields.valueList.items',
        ];
    }

    public function merge(Contact $contact): string
    {
        $card = $this->document($contact);

        $this->writeName($card, $contact);
        $this->writeNickname($card, $contact);
        $this->writePhoto($card, $contact);
        $this->writeFields($card, $contact);
        $this->writeCategories($card, $contact);
        $this->writeIdentity($card, $contact);

        return $card->serialize();
    }

    public function forKeeping(string $card): string
    {
        $document = $this->read($card);

        foreach ($document->select('PHOTO') as $photo) {
            $document->remove($photo);
        }

        return $document->serialize();
    }

    private function document(Contact $contact): VCard
    {
        if ($contact->vcard === null) {
            return new VCard(['VERSION' => '4.0', 'UID' => $contact->uuid]);
        }

        return $this->read($contact->vcard);
    }

    private function read(string $card): VCard
    {
        $document = Reader::read($card, Reader::OPTION_FORGIVING);

        if (! $document instanceof VCard) {
            throw new \UnexpectedValueException('That document is not a card.');
        }

        $converted = $document->convert(Document::VCARD40);

        if (! $converted instanceof VCard) {
            throw new \UnexpectedValueException('That card cannot be read as vCard 4.0.');
        }

        return $converted;
    }

    private function writeName(VCard $card, Contact $contact): void
    {
        $kept = ['N' => $this->unmanagedParameters($card, 'N'), 'FN' => $this->unmanagedParameters($card, 'FN')];

        $card->remove('N');
        $card->remove('FN');

        $representations = $this->preloaded
            ? $contact->nameRepresentations
            : $contact->nameRepresentations()->get();
        $only = $representations->count() === 1 ? $representations->first() : null;

        if ($representations->isEmpty() || ($only !== null && $only->kind === ContactNameKindEnum::Written && $only->language_tag === null && $only->script_code === null)) {
            $this->put($card, 'N', [
                (string) $contact->last_name,
                (string) $contact->first_name,
                (string) $contact->middle_name,
                (string) $contact->prefix,
                (string) $contact->suffix,
            ]);
            $this->put($card, 'FN', $contact->name);

            return;
        }

        $altId = $representations->pluck('vcard_alt_id')->filter()->first() ?? '1';

        foreach ($representations as $representation) {
            $propertyId = $representation->vcard_property_id ?? $representation->uuid;
            $isPhonetic = $representation->kind === ContactNameKindEnum::Phonetic;
            $parameters = array_filter([
                'ALTID' => $altId,
                'PROP-ID' => $propertyId,
                'LANGUAGE' => $representation->language_tag,
                'SCRIPT' => $representation->script_code,
                'X-NAME-KIND' => $representation->kind === ContactNameKindEnum::Transliteration ? 'transliteration' : null,
            ]);
            $signature = $this->signature($representation->language_tag, $representation->script_code, $isPhonetic);

            $name = $this->put($card, 'N', [
                (string) $representation->family_name,
                (string) $representation->given_name,
                (string) $representation->additional_name,
                (string) $representation->prefix,
                (string) $representation->suffix,
            ]);
            $this->dress($name, [
                ...$kept['N'][$propertyId] ?? $kept['N'][$signature] ?? [],
                ...$parameters,
                ...($isPhonetic ? ['PHONETIC' => (string) $representation->phonetic_system] : []),
            ]);

            if ($isPhonetic) {
                continue;
            }

            $formatted = $this->put($card, 'FN', $representation->displayText());
            $this->dress($formatted, [
                ...$kept['FN'][$propertyId] ?? $kept['FN'][$signature] ?? [],
                ...$parameters,
                ...($representation->id === $contact->display_name_representation_id ? ['PREF' => '1'] : []),
            ]);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function unmanagedParameters(VCard $card, string $property): array
    {
        $kept = [];

        foreach ($card->select($property) as $found) {
            if (! $found instanceof Property) {
                continue;
            }

            $parameters = [];

            foreach ($found->parameters() as $parameter) {
                if (! in_array($parameter->name, self::MANAGED_NAME_PARAMETERS, true)) {
                    $parameters[$parameter->name] = implode(',', $parameter->getParts());
                }
            }

            $key = $this->parameterOf($found, 'PROP-ID') ?? $this->signature(
                $this->parameterOf($found, 'LANGUAGE'),
                $this->parameterOf($found, 'SCRIPT'),
                $this->parameterOf($found, 'PHONETIC') !== null,
            );

            $kept[$key] ??= $parameters;
        }

        return $kept;
    }

    private function parameterOf(Property $property, string $name): ?string
    {
        $parameter = $property->parameters()[$name] ?? null;

        return $parameter instanceof Parameter ? implode(',', $parameter->getParts()) : null;
    }

    private function signature(?string $language, ?string $script, bool $isPhonetic): string
    {
        return implode('|', [$language ?? '', $script ?? '', $isPhonetic ? 'phonetic' : 'written']);
    }

    /** @param  array<string, string>  $parameters */
    private function dress(Property $property, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $property[$name] = $value;
        }
    }

    private function writeNickname(VCard $card, Contact $contact): void
    {
        $this->set($card, 'NICKNAME', $contact->nickname);
    }

    private function writePhoto(VCard $card, Contact $contact): void
    {
        foreach ($card->select('PHOTO') as $photo) {
            $card->remove($photo);
        }

        $avatar = $contact->avatar;

        if ($avatar === null) {
            return;
        }

        $disk = $avatar->disk();

        if (! $disk->exists($avatar->path)) {
            return;
        }

        $card->add('PHOTO', 'data:'.$avatar->mime_type.';base64,'.base64_encode((string) $disk->get($avatar->path)));
    }

    private function writeFields(VCard $card, Contact $contact): void
    {
        $owned = new OwnedFields($contact, preloaded: $this->preloaded);
        $values = $this->valuesOf($contact);

        foreach ($owned->properties() as $property) {
            $onTheCard = array_values(iterator_to_array($card->select($property), false));
            $slot = 0;

            foreach ($owned->for($property) as $field) {
                foreach ($this->slotsOf($field, $values) as $value) {
                    $this->writeSlot($card, $property, $field, $value, $onTheCard[$slot] ?? null);
                    $slot++;
                }
            }
        }
    }

    /**
     * Every category the contact holds, in the order of the list, as the one `CATEGORIES` a card
     * carries. A structure with no field for categories leaves the property the device wrote as
     * it is, since the contact holds nothing it could replace it with.
     */
    private function writeCategories(VCard $card, Contact $contact): void
    {
        $fields = collect(new OwnedFields($contact, preloaded: $this->preloaded)->categoryFields())
            ->reject(fn (Field $field): bool => $field->is_sensitive && ! $this->reachesSensitiveFields);

        if ($fields->isEmpty()) {
            return;
        }

        $held = $this->valuesOf($contact)
            ->whereIn('field_id', $fields->pluck('id'))
            ->flatMap(fn (FieldValue $value): array => array_map(intval(...), $value->value_list_item_ids ?? []));

        $labels = $fields->first()->valueList->items
            ->whereIn('id', $held->unique()->all())
            ->sortBy('position')
            ->map(fn (ValueListItem $item): string => $item->label)
            ->values()
            ->all();

        foreach ($card->select('CATEGORIES') as $categories) {
            $card->remove($categories);
        }

        if ($labels !== []) {
            $card->add('CATEGORIES', $labels);
        }
    }

    /** @param  Collection<int, FieldValue>  $values */
    private function slotsOf(Field $field, Collection $values): array
    {
        $held = $values->where('field_id', $field->id)->values();

        if ($held->isEmpty()) {
            return [null];
        }

        return $held->all();
    }

    private function writeSlot(VCard $card, string $property, Field $field, ?FieldValue $value, ?Property $onTheCard): void
    {
        if ($field->is_sensitive && ! $this->reachesSensitiveFields) {
            return;
        }

        $written = $value === null ? null : $this->valueOf($field, $value);

        if ($written === null) {
            if ($onTheCard !== null) {
                $card->remove($onTheCard);
            }

            return;
        }

        if ($onTheCard !== null) {
            $onTheCard->setValue($written);
            $this->label($card, $onTheCard, $field, $value);

            return;
        }

        $this->append($card, $property, $written, $field, $value);
    }

    /** @param  string|array<int, string>  $written */
    private function append(VCard $card, string $property, string|array $written, Field $field, ?FieldValue $value): void
    {
        $this->label($card, $this->put($card, $property, $written), $field, $value);
    }

    /**
     * Say what a property is for. The value's own label goes on as the type every address book
     * reads, or as a wording of its own when nothing standard fits. A value carrying no label
     * gets none, and keeps whatever the device itself wrote beside it: the name of the field would
     * come back from the device as a label nobody chose. A date is the exception, since the name
     * of its field is the only thing saying which date it is.
     */
    private function label(VCard $card, Property $property, Field $field, ?FieldValue $value): void
    {
        if (! in_array($property->name, self::LABELLED, true)) {
            return;
        }

        $item = $value?->valueListItem;
        $typed = $value?->label_wording;
        $standard = $typed === null || $typed === ''
            ? self::standardType($property->name, $item?->key)
            : null;

        if ($standard !== null) {
            $property['TYPE'] = $standard;

            return;
        }

        $wording = $typed === null || $typed === ''
            ? ($item->label ?? ($property->name === 'X-ABDATE' ? $field->label : null))
            : $typed;

        if ($wording === null) {
            return;
        }

        $group = (string) $property->group;

        if ($group === '') {
            $group = $this->nextGroup($card);
            $property->group = $group;
        }

        foreach ($card->children() as $child) {
            if ($child instanceof Property && mb_strtolower($child->name) === 'x-ablabel' && mb_strtolower((string) $child->group) === mb_strtolower($group)) {
                $child->setValue($wording);

                return;
            }
        }

        $label = $this->put($card, 'X-ABLabel', $wording);
        $label->group = $group;
        $label->name = 'X-ABLabel';
    }

    private static function standardType(string $property, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return match ($property) {
            'TEL' => match ($key) {
                (PhoneLabelEnum::Mobile)->value => 'cell',
                (PhoneLabelEnum::Home)->value => 'home',
                (PhoneLabelEnum::Work)->value => 'work',
                (PhoneLabelEnum::Fax)->value => 'fax',
                (PhoneLabelEnum::Other)->value => 'other',
                default => null,
            },
            'EMAIL' => match ($key) {
                (EmailLabelEnum::Home)->value => 'home',
                (EmailLabelEnum::Personal)->value => 'personal',
                (EmailLabelEnum::Work)->value => 'work',
                (EmailLabelEnum::Other)->value => 'other',
                default => null,
            },
            'ADR' => match ($key) {
                (AddressLabelEnum::Home)->value => 'home',
                (AddressLabelEnum::Work)->value => 'work',
                default => null,
            },
            default => null,
        };
    }

    private function nextGroup(VCard $card): string
    {
        $taken = [0];

        foreach ($card->children() as $child) {
            if (! $child instanceof Property) {
                continue;
            }

            if (preg_match('/^item(\d+)$/i', (string) $child->group, $matches) === 1) {
                $taken[] = (int) $matches[1];
            }
        }

        return 'item'.(max($taken) + 1);
    }

    /** @return string|array<int, string>|null */
    private function valueOf(Field $field, FieldValue $value): string|array|null
    {
        return match (OwnedFields::propertyFor($field)) {
            'ADR' => $this->address($value),
            'BDAY', 'X-ABDATE' => $this->recurringDate($value),
            default => $this->text($value->value_string),
        };
    }

    /** @return array<int, string>|null */
    private function address(FieldValue $value): ?array
    {
        $parts = $value->value_json;

        if (! is_array($parts)) {
            return null;
        }

        $address = array_map(
            fn (string $part): string => (string) ($parts[$part] ?? ''),
            FieldValue::ADDRESS_PARTS,
        );

        return implode('', $address) === '' ? null : $address;
    }

    private function recurringDate(FieldValue $value): ?string
    {
        $date = $value->value_date;

        if ($date === null || $value->month_is_unknown) {
            return null;
        }

        if ($value->year_is_unknown) {
            return $date->format('--md');
        }

        return $date->format('Y-m-d');
    }

    private function text(?string $value): ?string
    {
        return $value === null || mb_trim($value) === '' ? null : $value;
    }

    private function set(VCard $card, string $property, ?string $value): void
    {
        $written = $this->text($value);

        foreach ($card->select($property) as $existing) {
            if ($written !== null) {
                $existing->setValue($written);

                return;
            }

            $card->remove($existing);
        }

        if ($written !== null) {
            $this->put($card, $property, $written);
        }
    }

    /** @param  string|array<int, string>  $value */
    private function put(VCard $card, string $property, string|array $value): Property
    {
        $added = $card->add($property, $value);

        if (! $added instanceof Property) {
            throw new \UnexpectedValueException('A card property was expected.');
        }

        return $added;
    }

    private function first(VCard $card, string $property): ?Property
    {
        foreach ($card->select($property) as $found) {
            if ($found instanceof Property) {
                return $found;
            }
        }

        return null;
    }

    private function writeIdentity(VCard $card, Contact $contact): void
    {
        if ($this->text((string) $this->first($card, 'UID')?->getValue()) === null) {
            $card->remove('UID');
            $this->put($card, 'UID', $contact->uuid);
        }

        $card->remove('REV');
        $this->put($card, 'REV', (string) $contact->updated_at?->utc()->format('Ymd\THis\Z'));
    }

    /** @return Collection<int, FieldValue> */
    private function valuesOf(Contact $contact): Collection
    {
        return $this->preloaded
            ? $contact->fieldValues
            : self::values($contact->fieldValues())->get();
    }

    /**
     * @param  MorphMany<FieldValue, Contact>  $query
     * @return MorphMany<FieldValue, Contact>
     */
    private static function values(MorphMany $query): MorphMany
    {
        return $query
            ->attached()
            ->with('field')
            ->orderBy('field_id')
            ->orderByRaw('field_value_row_id is null desc')
            ->orderBy('field_value_row_id');
    }
}
