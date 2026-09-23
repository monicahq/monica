<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultFieldEnum: string implements Translatable
{
    case Category = 'category';
    case Gender = 'gender';
    case Birthday = 'birthday';
    case Emails = 'emails';
    case Email = 'email';
    case Phones = 'phones';
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
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Category',
            self::Gender => 'Gender',
            self::Birthday => 'Birthday',
            self::Emails => 'Emails',
            self::Email => 'Email',
            self::Phones => 'Phones',
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
            self::Note => 'Note',
        };
    }

    public function type(): FieldTypeEnum
    {
        return match ($this) {
            self::Category => FieldTypeEnum::Multiselect,
            self::Gender => FieldTypeEnum::Select,
            self::Birthday => FieldTypeEnum::RecurringDate,
            self::Emails => FieldTypeEnum::Repeater,
            self::Email => FieldTypeEnum::Email,
            self::Phones => FieldTypeEnum::Repeater,
            self::Phone => FieldTypeEnum::Phone,
            self::Website => FieldTypeEnum::Url,
            self::Address => FieldTypeEnum::Address,
            self::Document => FieldTypeEnum::Repeater,
            self::File => FieldTypeEnum::File,
            self::Description => FieldTypeEnum::Text,
            self::Photo => FieldTypeEnum::Repeater,
            self::Image => FieldTypeEnum::Image,
            self::Caption => FieldTypeEnum::Text,
            self::Notes => FieldTypeEnum::Repeater,
            self::Note => FieldTypeEnum::Textarea,
        };
    }

    /** @return list<self> */
    /** Whether two records sharing this value are likely the same record. */
    public function isIdentifying(): bool
    {
        return in_array($this, [self::Email, self::Phone], true);
    }

    public function children(): array
    {
        return match ($this) {
            self::Document => [self::File, self::Description],
            self::Photo => [self::Image, self::Caption],
            self::Notes => [self::Note],
            self::Emails => [self::Email],
            self::Phones => [self::Phone],
            default => [],
        };
    }

    /** @return array<string, list<string>>|null */
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

    public function valueList(): ?ValueListEnum
    {
        return match ($this) {
            self::Category => ValueListEnum::ContactCategories,
            self::Gender => ValueListEnum::Genders,
            self::Email => ValueListEnum::EmailLabels,
            self::Phone => ValueListEnum::PhoneLabels,
            self::Address => ValueListEnum::AddressLabels,
            default => null,
        };
    }

    public function repetitionOrder(): ?RepetitionOrderEnum
    {
        return match ($this) {
            self::Notes => RepetitionOrderEnum::NewestFirst,
            self::Emails => RepetitionOrderEnum::NewestLast,
            self::Phones => RepetitionOrderEnum::NewestLast,
            default => null,
        };
    }

    public function isHighlighted(): bool
    {
        return in_array($this, [self::Category, self::Birthday], true);
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
