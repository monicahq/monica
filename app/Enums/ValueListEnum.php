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
 *
 * The cases are in the order the lists are seeded and read on the screen, which
 * is why they run by subject rather than alphabetically: who somebody is, where
 * they are and what they speak, then what a detail beside their name is for.
 */
enum ValueListEnum: string implements Translatable
{
    case Genders = 'genders';
    case ContactCategories = 'contact_categories';
    case Countries = 'countries';
    case Languages = 'languages';
    case Currencies = 'currencies';
    case PhoneLabels = 'phone_labels';
    case EmailLabels = 'email_labels';
    case AddressLabels = 'address_labels';
    case WebsiteLabels = 'website_labels';

    /**
     * What the list is called on the settings screen. The name doubles as the
     * translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Genders => 'Genders',
            self::ContactCategories => 'Contact categories',
            self::Countries => 'Countries',
            self::Languages => 'Languages',
            self::Currencies => 'Currencies',
            self::PhoneLabels => 'Phone labels',
            self::EmailLabels => 'Email labels',
            self::AddressLabels => 'Address labels',
            self::WebsiteLabels => 'Website labels',
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
            self::ContactCategories => 'How close somebody is to you',
            self::Countries => 'Where somebody lives, was born or works',
            self::Languages => 'What somebody speaks, so you know how to write to them',
            self::Currencies => 'What an amount you note down is counted in',
            self::PhoneLabels => 'What a phone number is for',
            self::EmailLabels => 'What an email address is for',
            self::AddressLabels => 'What an address is for',
            self::WebsiteLabels => 'What a link points at',
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
            self::ContactCategories => ContactCategoryEnum::cases(),
            self::Countries => CountryEnum::cases(),
            self::Languages => LanguageEnum::cases(),
            self::Currencies => CurrencyEnum::cases(),
            self::PhoneLabels => PhoneLabelEnum::cases(),
            self::EmailLabels => EmailLabelEnum::cases(),
            self::AddressLabels => AddressLabelEnum::cases(),
            self::WebsiteLabels => WebsiteLabelEnum::cases(),
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
