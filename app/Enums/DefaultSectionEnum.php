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

    public function label(): string
    {
        return match ($this) {
            self::PersonalDetails => 'Personal details',
            self::ContactInformation => 'Contact information',
            self::Addresses => 'Addresses',
            self::Documents => 'Documents',
            self::Photos => 'Photos',
            self::Notes => 'Notes',
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
                DefaultFieldEnum::Email,
                DefaultFieldEnum::Phone,
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
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
