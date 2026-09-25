<?php

declare(strict_types=1);

namespace App\Enums;

enum FieldTypeEnum: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Url = 'url';
    case Email = 'email';
    case Phone = 'phone';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Radio = 'radio';
    case Date = 'date';
    case RecurringDate = 'recurring_date';
    case Relation = 'relation';
    case File = 'file';
    case Image = 'image';
    case Avatar = 'avatar';
    case Address = 'address';
    case Repeater = 'repeater';
    case Divider = 'divider';
    case Heading = 'heading';
    case Help = 'help';

    public function storesValue(): bool
    {
        return ! in_array($this, [self::Divider, self::Heading, self::Help], true);
    }

    public function valueColumn(): ?string
    {
        return match ($this) {
            self::Text, self::Url, self::Email, self::Phone => 'value_string',
            self::Textarea => 'value_text',
            self::Number => 'value_number',
            self::Boolean => 'value_boolean',
            self::Date, self::RecurringDate => 'value_date',
            self::Select, self::Radio => 'value_list_item_id',
            self::Multiselect => 'value_list_item_ids',
            self::File, self::Image, self::Avatar => 'value_file_id',
            self::Address => 'value_json',
            self::Relation, self::Repeater, self::Divider, self::Heading, self::Help => null,
        };
    }

    public function hasAnInput(): bool
    {
        return in_array($this, [
            self::Text, self::Textarea, self::Number, self::Boolean, self::Url,
            self::Email, self::Phone, self::Select, self::Multiselect, self::Radio,
            self::Date, self::RecurringDate, self::Relation, self::File, self::Image, self::Address,
            self::Repeater,
        ], true);
    }

    public function holdsAFile(): bool
    {
        return in_array($this, [self::File, self::Image, self::Avatar], true);
    }

    public function usesValueList(): bool
    {
        return in_array($this, [self::Select, self::Multiselect, self::Radio], true);
    }

    /**
     * Whether a value of this type carries a label of its own, picked from the
     * list the field names. The list is not the value, which is what keeps this
     * apart from usesValueList().
     */
    public function usesALabelList(): bool
    {
        return in_array($this, [self::Email, self::Phone, self::Address], true);
    }

    public function suppliedLabelList(): ?ValueListEnum
    {
        return match ($this) {
            self::Email => ValueListEnum::EmailLabels,
            self::Phone => ValueListEnum::PhoneLabels,
            self::Address => ValueListEnum::AddressLabels,
            default => null,
        };
    }

    public function isRelation(): bool
    {
        return $this === self::Relation;
    }

    public function canDriveAReminder(): bool
    {
        return $this === self::Date || $this === self::RecurringDate;
    }

    public function mayHaveAnUnknownPart(): bool
    {
        return $this === self::RecurringDate;
    }

    public function isRepeater(): bool
    {
        return $this === self::Repeater;
    }
}
