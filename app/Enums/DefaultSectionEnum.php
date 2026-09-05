<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Models\Section;
use Override;

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
     * The glyph the section is seeded marked with.
     *
     * Every one of these is a name {@see Section::ICONS} offers, which a test
     * holds to: a name outside that list would draw the blank page and the seed
     * would quietly look unmarked.
     *
     * Documents and Notes take the two pages of the icon set that differ by
     * their detail rather than their shape, the folded corner and the ruled
     * lines, because that is the real difference between the two sections.
     */
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

    /**
     * The colour it is seeded in.
     *
     * The two sections that share a page are given hues that are nowhere near
     * each other, since telling those two apart at a glance is the whole of
     * what the colour is for. Nothing here is neutral: an account that starts
     * with every mark grey never finds out the colour can be changed.
     */
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
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
