<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use App\Contracts\Translatable;
use Override;

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
    case AnimalSexes = 'animal_sexes';

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
            self::AnimalSexes => 'Animal sexes',
        };
    }

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
            self::AnimalSexes => 'Whether an animal is female or male',
        };
    }

    /** @return list<SuppliedValueListItem> */
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
            self::AnimalSexes => AnimalSexEnum::cases(),
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
