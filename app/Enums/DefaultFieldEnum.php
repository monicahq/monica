<?php

declare(strict_types=1);

namespace App\Enums;

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
 */
enum DefaultFieldEnum: string implements Translatable
{
    case Category = 'category';
    case Gender = 'gender';
    case Birthday = 'birthday';
    case Email = 'email';
    case Phone = 'phone';
    case Website = 'website';
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
            self::Notes => FieldTypeEnum::Textarea,
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
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
