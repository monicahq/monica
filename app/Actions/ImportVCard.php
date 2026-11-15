<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactNameKindEnum;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\TooManyRepetitions;
use App\Exceptions\ValueListItemIsStopped;
use App\Helpers\FlagState;
use App\Http\Dav\OwnedFields;
use App\Http\Dav\VCardMerger;
use App\Http\Dav\VCardReader;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
            $this->writeTheCategories($reader, $contact);
            $this->writeThePhoto($reader, $contact);
            $this->keepTheCard($contact);

            return $contact->refresh();
        });
    }

    private function writeTheName(VCardReader $reader): Contact
    {
        $rows = array_slice($reader->nameRepresentations(), 0, UpdateContactNameRepresentations::MAXIMUM);

        if (collect($rows)->every(fn (array $row): bool => $row['kind'] === 'phonetic')) {
            $rows = array_slice([$this->borrowedName($reader), ...$rows], 0, UpdateContactNameRepresentations::MAXIMUM);
        }

        if ($this->contact === null) {
            return new CreateContact(
                user: $this->user,
                vault: $this->vault,
                nickname: $reader->nickname(),
                nameRepresentations: $rows,
                display: $this->preferredIn($rows),
            )->execute();
        }

        $nameCount = count($reader->propertiesOf('N'));
        $single = $nameCount === 1 && $rows[0]['kind'] !== 'phonetic' ? $rows[0] : $rows[$this->preferredIn($rows)];

        [$nameRepresentations, $display] = $nameCount > 1
            ? $this->everyNameFrom($rows)
            : $this->oneNameFrom($single);

        new UpdateContactNameRepresentations(
            user: $this->user,
            contact: $this->contact,
            nameRepresentations: $nameRepresentations,
            display: $display,
            nickname: $reader->nickname(),
            maidenName: $this->contact->maiden_name,
        )->execute();

        return $this->contact;
    }

    /**
     * @return array{kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null, vcard_alt_id: string|null, vcard_property_id: string|null, is_preferred: bool}
     */
    private function borrowedName(VCardReader $reader): array
    {
        $email = $reader->first('EMAIL');
        $phone = $reader->first('TEL');

        $borrowed = collect([
            $reader->first('ORG')?->getParts()[0] ?? null,
            $reader->first('NICKNAME')?->getParts()[0] ?? null,
            $email === null ? null : $this->text($email),
            $phone === null ? null : $this->text($phone),
        ])
            ->map(fn (?string $candidate): string => mb_trim((string) $candidate))
            ->first(fn (string $candidate): bool => $candidate !== '');

        if ($borrowed === null) {
            throw new BadRequest('A card needs a name, an organisation, a nickname, an email address or a phone number.');
        }

        return [
            'kind' => 'written',
            'language' => null,
            'script' => null,
            'phonetic_system' => null,
            'formatted_name' => mb_substr($borrowed, 0, 255),
            'prefix' => null,
            'given_name' => null,
            'additional_name' => null,
            'family_name' => null,
            'suffix' => null,
            'name_order' => null,
            'vcard_alt_id' => null,
            'vcard_property_id' => null,
            'is_preferred' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    private function everyNameFrom(array $rows): array
    {
        $existing = $this->contact->nameRepresentations()->get();
        $displayedUuid = $existing->firstWhere('id', $this->contact->display_name_representation_id)?->uuid;
        $taken = [];
        $seenPropertyIds = [];

        foreach ($rows as $index => $row) {
            $propertyId = in_array($row['vcard_property_id'], $seenPropertyIds, true) ? null : $row['vcard_property_id'];
            $seenPropertyIds[] = $propertyId;

            $match = $existing->first(fn (ContactNameRepresentation $representation): bool => $propertyId !== null
                && ! in_array($representation->id, $taken, true)
                && in_array($propertyId, [$representation->uuid, $representation->vcard_property_id], true));

            if ($match !== null) {
                $taken[] = $match->id;
            }

            $rows[$index] = [...$row, 'id' => $match?->uuid, 'vcard_property_id' => $match !== null && $match->uuid === $propertyId ? null : $propertyId];
        }

        $display = collect($rows)->search(fn (array $row): bool => $row['is_preferred']);

        if ($display === false) {
            $display = collect($rows)->search(fn (array $row): bool => $displayedUuid !== null && $row['id'] === $displayedUuid && $row['kind'] !== 'phonetic');
        }

        return [$rows, $display === false ? $this->preferredIn($rows) : $display];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    private function oneNameFrom(array $row): array
    {
        $existing = $this->contact->nameRepresentations()->get();
        $target = $existing->first(fn (ContactNameRepresentation $representation): bool => $row['vcard_property_id'] !== null
            && $representation->kind !== ContactNameKindEnum::Phonetic
            && in_array($row['vcard_property_id'], [$representation->uuid, $representation->vcard_property_id], true))
            ?? $existing->firstWhere('id', $this->contact->display_name_representation_id);

        $rows = [];
        $display = null;

        foreach ($existing as $index => $representation) {
            $current = ['id' => $representation->uuid, ...$representation->toComparable(), 'vcard_alt_id' => $representation->vcard_alt_id, 'vcard_property_id' => $representation->vcard_property_id];

            if ($target !== null && $representation->id === $target->id) {
                $current = [
                    ...$current,
                    'formatted_name' => $row['formatted_name'],
                    'prefix' => $row['prefix'],
                    'given_name' => $row['given_name'],
                    'additional_name' => $row['additional_name'],
                    'family_name' => $row['family_name'],
                    'suffix' => $row['suffix'],
                    'name_order' => $row['name_order'],
                    'language' => $row['language'] ?? $current['language'],
                    'script' => $row['script'] ?? $current['script'],
                ];
            }

            if ($representation->id === $this->contact->display_name_representation_id) {
                $display = $index;
            }

            $rows[] = $current;
        }

        if ($target === null) {
            $rows[] = $row;
            $display = count($rows) - 1;
        }

        return [$rows, $display ?? 0];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function preferredIn(array $rows): int
    {
        foreach ($rows as $index => $row) {
            if ($row['is_preferred']) {
                return $index;
            }
        }

        foreach ($rows as $index => $row) {
            if ($row['kind'] !== 'phonetic') {
                return $index;
            }
        }

        return 0;
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
            $this->removeTheValue($held);

            return;
        }

        $written = $this->valueOf($field, $onTheCard);

        if ($written['value'] === null) {
            $this->removeTheValue($held);

            return;
        }

        $repeater = $growable ? $field->parent : null;

        try {
            DB::transaction(function () use ($contact, $field, $held, $onTheCard, $written, $repeater): void {
                $row = $held?->field_value_row_id === null
                    ? null
                    : FieldValueRow::query()->find($held->field_value_row_id);

                if ($held === null && $repeater !== null) {
                    $row = new CreateFieldValueRow(user: $this->user, valuable: $contact, field: $repeater)->execute();
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
            });
        } catch (InvalidArgumentException|TooManyRepetitions) {
            return;
        }
    }

    private function writeTheCategories(VCardReader $reader, Contact $contact): void
    {
        $field = new OwnedFields($contact)->categoryFields()[0] ?? null;

        if ($field === null || ($field->is_sensitive && ! $this->reachesSensitiveFields)) {
            return;
        }

        $held = $this->valuesOf($contact, $field)->first();
        $heldIds = array_map(intval(...), $held->value_list_item_ids ?? []);

        $wordings = collect($reader->propertiesOf('CATEGORIES'))
            ->flatMap(fn (Property $categories): array => $categories->getParts())
            ->map(fn (mixed $wording): string => mb_trim((string) $wording))
            ->filter(fn (string $wording): bool => $wording !== '');

        $chosen = [];

        foreach ($wordings as $wording) {
            try {
                $item = new FindOrCreateValueListItem(
                    user: $this->user,
                    valueList: $field->valueList,
                    wording: $wording,
                    heldItemIds: $heldIds,
                )->execute();
            } catch (AuthorizationException|ValueListItemIsStopped) {
                continue;
            }

            $chosen[$item->id] = $item;
        }

        if ($chosen === []) {
            $this->removeTheValue($held);

            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $contact,
            field: $field,
            valueListItems: collect($chosen)->sortBy('position')->values()->all(),
        )->execute();
    }

    private function removeTheValue(?FieldValue $held): void
    {
        if ($held === null) {
            return;
        }

        $row = $held->row;

        new DestroyFieldValue(user: $this->user, fieldValue: $held)->execute();

        if ($row === null || $row->values()->exists()) {
            return;
        }

        try {
            new DestroyFieldValueRow(user: $this->user, row: $row, writesToTheJournal: false)->execute();
        } catch (NotEnoughRepetitions) {
            return;
        }
    }

    /**
     * Which of the field's labels the card is naming. The wording a device wrote wins over the
     * standard type, because an account that renamed a label meant that wording. Nothing is ever
     * added to the list: the account owns it, and a synchronising device may not write to it. The
     * one exception is a category, which a device may add, as `writeTheCategories()` does.
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
        if (! FlagState::isOn('file-upload', $this->user->account)) {
            return;
        }

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

        try {
            file_put_contents($path, $sent);

            new UpdateContactAvatar(
                user: $this->user,
                vault: $this->vault,
                contact: $contact,
                upload: new UploadedFile($path, Str::uuid()->toString(), mime_content_type($path) ?: null, test: true),
            )->execute();
        } catch (InvalidArgumentException|ImageException $exception) {
            Log::warning('Left out the photo a vCard sent that could not be read.', [
                'contact' => $contact->id,
                'exception' => $exception->getMessage(),
            ]);
        } finally {
            @unlink($path);
        }
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
