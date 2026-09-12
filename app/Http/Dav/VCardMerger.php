<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class VCardMerger
{
    private const array LABELLED = ['EMAIL', 'TEL', 'URL', 'ADR', 'X-ABDATE'];

    public function __construct(
        private readonly bool $reachesSensitiveFields = false,
    ) {}

    public function merge(Contact $contact): string
    {
        $card = $this->document($contact);

        $this->writeName($card, $contact);
        $this->writeNickname($card, $contact);
        $this->writePhoto($card, $contact);
        $this->writeFields($card, $contact);
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
        $card->remove('N');
        $card->remove('FN');

        $this->put($card, 'N', [
            (string) $contact->last_name,
            (string) $contact->first_name,
            (string) $contact->middle_name,
            (string) $contact->prefix,
            (string) $contact->suffix,
        ]);
        $this->put($card, 'FN', $contact->name);
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
        $owned = new OwnedFields($contact);
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

            return;
        }

        $this->append($card, $property, $written, $field);
    }

    /** @param  string|array<int, string>  $written */
    private function append(VCard $card, string $property, string|array $written, Field $field): void
    {
        $added = $this->put($card, $property, $written);

        if (! in_array($property, self::LABELLED, true)) {
            return;
        }

        $group = $this->nextGroup($card);
        $added->group = $group;

        $label = $this->put($card, 'X-ABLabel', $field->label);
        $label->group = $group;
        $label->name = 'X-ABLabel';
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
            ['po_box', 'extended', 'street', 'city', 'region', 'postal_code', 'country'],
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
        return $contact->fieldValues()
            ->attached()
            ->with('field')
            ->orderBy('field_id')
            ->orderByRaw('field_value_row_id is null desc')
            ->orderBy('field_value_row_id')
            ->get();
    }
}
