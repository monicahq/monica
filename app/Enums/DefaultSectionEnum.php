<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultSectionEnum: string implements Translatable
{
    case PersonalDetails = 'personal-details';
    case ContactInformation = 'contact-information';
    case Addresses = 'addresses';
    case Documents = 'documents';
    case Photos = 'photos';
    case Notes = 'notes';
    case AnimalDetails = 'animal-details';
    case AnimalPeople = 'animal-people';
    case AnimalCare = 'animal-care';
    case AnimalNotes = 'animal-notes';

    public function label(): string
    {
        return match ($this) {
            self::PersonalDetails => 'Personal details',
            self::ContactInformation => 'Contact information',
            self::Addresses => 'Addresses',
            self::Documents => 'Documents',
            self::Photos => 'Photos',
            self::Notes => 'Notes',
            self::AnimalDetails => 'Animal details',
            self::AnimalPeople => 'People',
            self::AnimalCare => 'Care',
            self::AnimalNotes => 'Notes',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PersonalDetails => 'profile',
            self::ContactInformation => 'mail',
            self::Addresses => 'place',
            self::Documents => 'file',
            self::Photos => 'memory',
            self::Notes => 'note',
            self::AnimalDetails => 'pet',
            self::AnimalPeople => 'people',
            self::AnimalCare => 'health',
            self::AnimalNotes => 'note',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::PersonalDetails => VaultColourEnum::Blue,
            self::Notes => VaultColourEnum::Green,
            self::ContactInformation => VaultColourEnum::Teal,
            self::Addresses => VaultColourEnum::Amber,
            self::Documents => VaultColourEnum::Slate,
            self::Photos => VaultColourEnum::Violet,
            self::AnimalDetails => VaultColourEnum::Amber,
            self::AnimalPeople => VaultColourEnum::Blue,
            self::AnimalCare => VaultColourEnum::Teal,
            self::AnimalNotes => VaultColourEnum::Green,
        };
    }

    /** @return list<DefaultFieldEnum> */
    public function fields(): array
    {
        return match ($this) {
            self::PersonalDetails => [
                DefaultFieldEnum::Category,
                DefaultFieldEnum::Gender,
                DefaultFieldEnum::Birthday,
            ],
            self::ContactInformation => [
                DefaultFieldEnum::Emails,
                DefaultFieldEnum::Phones,
                DefaultFieldEnum::Website,
            ],
            self::Addresses => [
                DefaultFieldEnum::Address,
            ],
            self::Documents => [
                DefaultFieldEnum::Document,
            ],
            self::Photos => [
                DefaultFieldEnum::Photo,
            ],
            self::Notes => [
                DefaultFieldEnum::Notes,
            ],
            self::AnimalDetails => [
                DefaultFieldEnum::Species,
                DefaultFieldEnum::Breed,
                DefaultFieldEnum::Sex,
                DefaultFieldEnum::Birthday,
                DefaultFieldEnum::AdoptionDate,
                DefaultFieldEnum::MicrochipNumber,
            ],
            self::AnimalPeople => [
                DefaultFieldEnum::Caregivers,
            ],
            self::AnimalCare => [
                DefaultFieldEnum::FoodAndRoutine,
                DefaultFieldEnum::HealthAndCare,
            ],
            self::AnimalNotes => [
                DefaultFieldEnum::AnimalNotes,
            ],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
