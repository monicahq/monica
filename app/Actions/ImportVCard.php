<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Dav\OwnedFields;
use App\Http\Dav\VCardMerger;
use App\Http\Dav\VCardReader;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Component;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;

/**
 * Write a card a device saved into a record, and keep the card whole. What the structure has a
 * field for is written through the same actions the browser and the API call; what it has no field
 * for stays on the card, which the record keeps and the next read merges its values over.
 */
class ImportVCard
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $card,
        private readonly bool $reachesSensitiveFields = false,
        private readonly ?Contact $contact = null,
        private readonly ?string $davUri = null,
    ) {}

    public function execute(): Contact
    {
        $reader = new VCardReader($this->card);

        return DB::transaction(function () use ($reader): Contact {
            $contact = $this->writeTheName($reader);

            $this->writeTheFields($reader, $contact);
            $this->writeThePhoto($reader, $contact);
            $this->keepTheCard($contact);

            return $contact->refresh();
        });
    }

    private function writeTheName(VCardReader $reader): Contact
    {
        $parts = $reader->nameParts();

        if (! $reader->hasAName()) {
            $parts['first_name'] = $reader->fallbackFirstName();
        }

        if ($parts['first_name'] === null && ! $reader->hasAName()) {
            throw new BadRequest('A card needs at least one part of a name.');
        }

        if ($this->contact === null) {
            return new CreateContact(
                user: $this->user,
                vault: $this->vault,
                prefix: $parts['prefix'],
                firstName: $parts['first_name'],
                middleName: $parts['middle_name'],
                lastName: $parts['last_name'],
                suffix: $parts['suffix'],
                nickname: $parts['nickname'],
            )->execute();
        }

        new UpdateContactName(
            user: $this->user,
            contact: $this->contact,
            prefix: $parts['prefix'],
            firstName: $parts['first_name'],
            middleName: $parts['middle_name'],
            lastName: $parts['last_name'],
            suffix: $parts['suffix'],
            maidenName: $this->contact->maiden_name,
            nickname: $parts['nickname'],
        )->execute();

        return $this->contact;
    }

    private function writeTheFields(VCardReader $reader, Contact $contact): void
    {
        $owned = new OwnedFields($contact);

        foreach ($owned->properties() as $property) {
            $onTheCard = $reader->propertiesOf($property);
            $index = 0;

            foreach ($owned->for($property) as $field) {
                $held = $this->valuesOf($contact, $field);
                $growable = $field->parent_field_id !== null;

                $slots = $growable
                    ? max($held->count(), count($onTheCard) - $index)
                    : max(1, $held->count());

                for ($slot = 0; $slot < $slots; $slot++) {
                    $this->writeSlot(
                        $contact,
                        $field,
                        $held->get($slot),
                        $onTheCard[$index] ?? null,
                        $growable,
                    );

                    $index++;
                }
            }
        }
    }

    private function writeSlot(Contact $contact, Field $field, ?FieldValue $held, ?Property $onTheCard, bool $growable): void
    {
        if ($field->is_sensitive && ! $this->reachesSensitiveFields) {
            return;
        }

        if ($onTheCard === null) {
            if ($held !== null) {
                new DestroyFieldValue(user: $this->user, fieldValue: $held)->execute();
            }

            return;
        }

        $written = $this->valueOf($field, $onTheCard);

        if ($written['value'] === null) {
            if ($held !== null) {
                new DestroyFieldValue(user: $this->user, fieldValue: $held)->execute();
            }

            return;
        }

        $row = $held?->field_value_row_id === null
            ? null
            : FieldValueRow::query()->find($held->field_value_row_id);

        if ($held === null && $growable) {
            $row = new CreateFieldValueRow(user: $this->user, valuable: $contact, field: $field)->execute();
        }

        $label = $this->labelOf($field, $onTheCard);

        new SetFieldValue(
            user: $this->user,
            valuable: $contact,
            field: $field,
            row: $row,
            value: $written['value'],
            valueListItem: $label['item'],
            labelWording: $label['wording'],
            yearIsUnknown: $written['yearIsUnknown'],
        )->execute();
    }

    /**
     * Which of the field's labels the card is naming. The wording a device wrote wins over the
     * standard type, because an account that renamed a label meant that wording. Nothing is ever
     * added to the list: the account owns it, and a synchronising device may not write to it.
     */
    /** @return array{item: ?ValueListItem, wording: ?string} */
    private function labelOf(Field $field, Property $onTheCard): array
    {
        if (! $field->type->usesALabelList() || $field->value_list_id === null) {
            return ['item' => null, 'wording' => null];
        }

        $offered = ValueListItem::query()
            ->where('value_list_id', $field->value_list_id)
            ->get();

        $wording = $this->wordingBeside($onTheCard);

        if ($wording !== null && $wording !== '') {
            $found = $offered->first(fn (ValueListItem $item): bool => mb_strtolower($item->label) === mb_strtolower($wording));

            if ($found instanceof ValueListItem) {
                return ['item' => $found, 'wording' => null];
            }
        }

        foreach ($this->typesOn($onTheCard) as $type) {
            $key = $type === 'cell' ? 'mobile' : $type;
            $found = $offered->first(fn (ValueListItem $item): bool => $item->key === $key);

            if ($found instanceof ValueListItem && ! $found->takesATypedWording()) {
                return ['item' => $found, 'wording' => null];
            }
        }

        if ($wording === null || $wording === '') {
            return ['item' => null, 'wording' => null];
        }

        $open = $offered->first(fn (ValueListItem $item): bool => $item->takesATypedWording());

        return $open instanceof ValueListItem
            ? ['item' => $open, 'wording' => $wording]
            : ['item' => null, 'wording' => null];
    }

    private function wordingBeside(Property $onTheCard): ?string
    {
        $group = mb_strtolower((string) $onTheCard->group);

        if ($group === '') {
            return null;
        }

        $card = $onTheCard->parent;

        if (! $card instanceof Component) {
            return null;
        }

        foreach ($card->children() as $child) {
            if ($child instanceof Property && mb_strtolower($child->name) === 'x-ablabel' && mb_strtolower((string) $child->group) === $group) {
                return mb_trim((string) $child->getValue());
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function typesOn(Property $onTheCard): array
    {
        $types = $onTheCard['TYPE'];

        if (! $types instanceof Parameter) {
            return [];
        }

        return array_map(mb_strtolower(...), $types->getParts());
    }

    /** @return array{value: mixed, yearIsUnknown: bool} */
    private function valueOf(Field $field, Property $onTheCard): array
    {
        return match (OwnedFields::propertyFor($field)) {
            'ADR' => ['value' => $this->address($onTheCard), 'yearIsUnknown' => false],
            'BDAY', 'X-ABDATE' => $this->date($onTheCard),
            default => ['value' => $this->text($onTheCard), 'yearIsUnknown' => false],
        };
    }

    /** @return array<string, string>|null */
    private function address(Property $onTheCard): ?array
    {
        $parts = $onTheCard->getParts();
        $address = [];

        foreach (FieldValue::ADDRESS_PARTS as $index => $key) {
            $address[$key] = trim((string) ($parts[$index] ?? ''));
        }

        return implode('', $address) === '' ? null : $address;
    }

    /** @return array{value: string|null, yearIsUnknown: bool} */
    private function date(Property $onTheCard): array
    {
        $written = preg_replace('/[^0-9-]/', '', (string) $onTheCard->getValue());

        if (preg_match('/^--(\d{2})-?(\d{2})$/', (string) $written, $yearless) === 1) {
            return [
                'value' => FieldValue::PLACEHOLDER_YEAR.'-'.$yearless[1].'-'.$yearless[2],
                'yearIsUnknown' => true,
            ];
        }

        if (preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', (string) $written, $full) === 1) {
            return [
                'value' => $full[1].'-'.$full[2].'-'.$full[3],
                'yearIsUnknown' => false,
            ];
        }

        return ['value' => null, 'yearIsUnknown' => false];
    }

    private function text(Property $onTheCard): ?string
    {
        $value = mb_trim((string) $onTheCard->getValue());

        foreach (['tel:', 'mailto:'] as $scheme) {
            if (str_starts_with(mb_strtolower($value), $scheme)) {
                $value = mb_substr($value, mb_strlen($scheme));
            }
        }

        return $value === '' ? null : $value;
    }

    private function writeThePhoto(VCardReader $reader, Contact $contact): void
    {
        $sent = $reader->photo();
        $avatar = $contact->avatar;

        if ($sent === null) {
            if ($avatar !== null) {
                new DestroyContactAvatar(user: $this->user, vault: $this->vault, contact: $contact)->execute();
            }

            return;
        }

        if ($avatar !== null && $avatar->disk()->exists($avatar->path) && $avatar->disk()->get($avatar->path) === $sent) {
            return;
        }

        $path = tempnam(sys_get_temp_dir(), 'dav-photo-');

        if ($path === false) {
            return;
        }

        file_put_contents($path, $sent);

        new UpdateContactAvatar(
            user: $this->user,
            vault: $this->vault,
            contact: $contact,
            upload: new UploadedFile($path, Str::uuid()->toString(), mime_content_type($path) ?: null, test: true),
        )->execute();
    }

    private function keepTheCard(Contact $contact): void
    {
        $contact->dav_uri = $this->davUri ?? $contact->dav_uri;
        $contact->vcard = new VCardMerger()->forKeeping($this->card);
        $contact->save();
    }

    /** @return Collection<int, FieldValue> */
    private function valuesOf(Contact $contact, Field $field): Collection
    {
        return $contact->fieldValues()
            ->attached()
            ->where('field_id', $field->id)
            ->orderByRaw('field_value_row_id is null desc')
            ->orderBy('field_value_row_id')
            ->get()
            ->values();
    }
}
