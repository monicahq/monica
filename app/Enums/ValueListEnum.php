<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use App\Contracts\Translatable;

/**
 * The value lists an account is created with, and what goes in each of them.
 *
 * The backing value is the `key` column of the list, and it is the only part
 * anything may rely on: the name, the items and the order are all the user's to
 * change the moment the account exists.
 */
enum ValueListEnum: string implements Translatable
{
    case Genders = 'genders';
    case PhoneLabels = 'phone_labels';
    case ContactCategories = 'contact_categories';

    /**
     * What the list is called on the settings screen. The name doubles as the
     * translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Genders => 'Genders',
            self::PhoneLabels => 'Phone labels',
            self::ContactCategories => 'Contact categories',
        };
    }

    /**
     * What the list is for, shown under its name in the field builder so
     * nobody has to open it to know what it holds.
     */
    public function description(): string
    {
        return match ($this) {
            self::Genders => 'How somebody is addressed and referred to',
            self::PhoneLabels => 'What a phone number is for',
            self::ContactCategories => 'How close somebody is to you',
        };
    }

    /**
     * What the list holds when the account is created, in the order it is
     * offered in.
     *
     * @return list<SuppliedValueListItem>
     */
    public function items(): array
    {
        return match ($this) {
            self::Genders => GenderEnum::cases(),
            self::PhoneLabels => PhoneLabelEnum::cases(),
            self::ContactCategories => ContactCategoryEnum::cases(),
        };
    }

    /**
     * The name and the purpose of the list. Its items carry their own, since
     * each is a case of an enum of its own.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
