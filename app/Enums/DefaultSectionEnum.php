<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

/**
 * The sections the template an account starts with is assembled from.
 *
 * The backing value is the `slug` column of the section, and which page each of
 * them sits on is settled by {@see DefaultPageEnum}. Several small sections
 * rather than one long page, because a section is what somebody later reuses on
 * a template of their own, and one that holds everything is a section nobody
 * can reuse.
 */
enum DefaultSectionEnum: string implements Translatable
{
    case PersonalDetails = 'personal-details';
    case ContactInformation = 'contact-information';
    case Addresses = 'addresses';
    case Documents = 'documents';
    case Photos = 'photos';
    case Notes = 'notes';

    /**
     * What the section is called on the page. The name doubles as the
     * translation key.
     */
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

    /**
     * What the section asks for, in the order it asks.
     *
     * @return list<DefaultFieldEnum>
     */
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

    /**
     * The name this section is seeded with. Its fields carry their own.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
