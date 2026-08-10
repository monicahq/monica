<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\CreateFile;
use App\Contracts\Translatable;

/**
 * The fields the template an account starts with asks for.
 *
 * The backing value is the `key` column of the field, which is what an import,
 * an export or an API caller holds on to. Everything else here, the label
 * included, is the user's to change the moment the account exists.
 *
 * None of them is required. A contact somebody met once is worth writing down
 * with nothing but a name, and a form that refuses a birthday nobody knows is a
 * form people stop using.
 *
 * Two of them are repeaters, since a contact carries as many documents and as
 * many photos as they carry, and a field holding one of each would be a field
 * somebody outgrows on the second passport. What sits inside a repeater is its
 * children, and a repeater inside a repeater is refused, so the nesting here is
 * one level and stays that way.
 */
enum DefaultFieldEnum: string implements Translatable
{
    case Category = 'category';
    case Gender = 'gender';
    case Birthday = 'birthday';
    case Email = 'email';
    case Phone = 'phone';
    case Website = 'website';
    case Address = 'address';
    case Document = 'document';
    case File = 'file';
    case Description = 'description';
    case Photo = 'photo';
    case Image = 'image';
    case Caption = 'caption';
    case Notes = 'notes';

    /**
     * What the field is called next to its input. The label doubles as the
     * translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Category => 'Category',
            self::Gender => 'Gender',
            self::Birthday => 'Birthday',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Website => 'Website',
            self::Address => 'Address',
            self::Document => 'Document',
            self::File => 'File',
            self::Description => 'Description',
            self::Photo => 'Photo',
            self::Image => 'Image',
            self::Caption => 'Caption',
            self::Notes => 'Notes',
        };
    }

    /**
     * What kind of input the field is, which is what decides how it renders,
     * how it is stored and what may be searched on it.
     */
    public function type(): FieldTypeEnum
    {
        return match ($this) {
            self::Category => FieldTypeEnum::Multiselect,
            self::Gender => FieldTypeEnum::Select,
            self::Birthday => FieldTypeEnum::RecurringDate,
            self::Email => FieldTypeEnum::Email,
            self::Phone => FieldTypeEnum::Phone,
            self::Website => FieldTypeEnum::Url,
            self::Address => FieldTypeEnum::Address,
            self::Document => FieldTypeEnum::Repeater,
            self::File => FieldTypeEnum::File,
            self::Description => FieldTypeEnum::Text,
            self::Photo => FieldTypeEnum::Repeater,
            self::Image => FieldTypeEnum::Image,
            self::Caption => FieldTypeEnum::Text,
            self::Notes => FieldTypeEnum::Textarea,
        };
    }

    /**
     * What a repeater asks for on each repetition, and nothing for every other
     * field. One document is a file and a line saying what it is, which is what
     * makes the block worth repeating rather than an upload repeated on its own.
     *
     * A repeater cannot sit inside another, so this never goes deeper than one
     * level.
     *
     * @return list<self>
     */
    public function children(): array
    {
        return match ($this) {
            self::Document => [self::File, self::Description],
            self::Photo => [self::Image, self::Caption],
            default => [],
        };
    }

    /**
     * The settings the type reads, which for a file is what may be uploaded
     * into it. Nothing interprets them yet: they are written so the row says
     * what the field is for rather than leaving it to whoever renders it.
     *
     * No ceiling on the size of one file, because the product has none. What it
     * has is the storage an account is allowed, and {@see CreateFile} refuses an
     * upload that would go past it.
     *
     * @return array<string, list<string>>|null
     */
    public function options(): ?array
    {
        return match ($this) {
            self::File => ['accepted_mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'text/csv',
                'image/jpeg',
                'image/png',
            ]],
            self::Image => ['accepted_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ]],
            default => null,
        };
    }

    /**
     * The list the choices come from, which only a choice field has.
     */
    public function valueList(): ?ValueListEnum
    {
        return match ($this) {
            self::Category => ValueListEnum::ContactCategories,
            self::Gender => ValueListEnum::Genders,
            default => null,
        };
    }

    /**
     * Whether the field is worth reading at a glance, above everything else the
     * contact holds. How close somebody is and when their birthday falls are
     * the two the rest of the page is read in the light of.
     */
    public function isHighlighted(): bool
    {
        return in_array($this, [self::Category, self::Birthday], true);
    }

    /**
     * Whether a reminder rule may read from the field. A birthday says yes
     * because that is what people ask a birthday for; nothing yet reads the
     * flag, and the reminder machinery finds it here when it arrives rather
     * than special casing the key.
     */
    public function isReminderSource(): bool
    {
        return $this === self::Birthday;
    }

    /**
     * The label this field is seeded with.
     *
     * Nothing seeds a line of help under it. A field carries its help as words
     * rather than as a translation key, and a sentence written here in English
     * would stay English for somebody reading in French, which is the one thing
     * the seeding is careful never to do.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
