<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Models\StructureVersionPage;
use Override;

enum DefaultPageEnum: string implements Translatable
{
    case General = 'general';
    case WaysToConnect = 'ways-to-connect';
    case Information = 'information';
    case AnimalGeneral = 'animal-general';
    case EssentialsEveryday = 'essentials-everyday';
    case FriendsEveryday = 'friends-everyday';
    case FriendsTastesAndAttentions = 'friends-tastes-and-attentions';
    case ProfessionalProfile = 'professional-profile';
    case ProfessionalRelationship = 'professional-relationship';
    case PortraitProfile = 'portrait-profile';
    case PortraitDailyLife = 'portrait-daily-life';
    case PortraitMemories = 'portrait-memories';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::WaysToConnect => 'Ways to connect',
            self::Information => 'Information',
            self::AnimalGeneral => 'General',
            self::EssentialsEveryday, self::FriendsEveryday => 'Everyday',
            self::FriendsTastesAndAttentions => 'Tastes and attentions',
            self::ProfessionalProfile => 'Professional profile',
            self::ProfessionalRelationship => 'Relationship',
            self::PortraitProfile => 'Profile',
            self::PortraitDailyLife => 'Daily life',
            self::PortraitMemories => 'Memories',
        };
    }

    /**
     * The key the page is stored under. A template with one page calls it general, whatever the
     * enum case has to be called to stay apart from the pages of Basic contact.
     */
    public function key(): string
    {
        return match ($this) {
            self::AnimalGeneral => StructureVersionPage::SUPPLIED_KEY,
            self::EssentialsEveryday, self::FriendsEveryday => 'everyday',
            self::FriendsTastesAndAttentions => 'tastes-and-attentions',
            self::ProfessionalRelationship => 'relationship',
            self::PortraitProfile => 'profile',
            self::PortraitDailyLife => 'daily-life',
            self::PortraitMemories => 'memories',
            default => $this->value,
        };
    }

    /** @return list<DefaultSectionEnum> */
    public function sections(): array
    {
        return match ($this) {
            self::General => [
                DefaultSectionEnum::PersonalDetails,
                DefaultSectionEnum::Notes,
            ],
            self::WaysToConnect => [
                DefaultSectionEnum::ContactInformation,
                DefaultSectionEnum::Addresses,
            ],
            self::Information => [
                DefaultSectionEnum::Documents,
                DefaultSectionEnum::Photos,
            ],
            self::AnimalGeneral => [
                DefaultSectionEnum::AnimalDetails,
                DefaultSectionEnum::AnimalPeople,
                DefaultSectionEnum::AnimalCare,
                DefaultSectionEnum::AnimalNotes,
            ],
            self::EssentialsEveryday => [
                DefaultSectionEnum::EssentialsLandmarks,
                DefaultSectionEnum::EssentialsContactDetails,
                DefaultSectionEnum::EssentialsHowWeMet,
                DefaultSectionEnum::EssentialsNotes,
            ],
            self::FriendsEveryday => [
                DefaultSectionEnum::FriendsLandmarks,
                DefaultSectionEnum::FriendsContactDetails,
                DefaultSectionEnum::FriendsHowWeMet,
            ],
            self::FriendsTastesAndAttentions => [
                DefaultSectionEnum::FriendsInterests,
                DefaultSectionEnum::FriendsAtTheTable,
                DefaultSectionEnum::FriendsGiftIdeas,
                DefaultSectionEnum::FriendsNotes,
            ],
            self::ProfessionalProfile => [
                DefaultSectionEnum::ProfessionalCurrentRole,
                DefaultSectionEnum::ProfessionalContactDetails,
                DefaultSectionEnum::ProfessionalCareer,
            ],
            self::ProfessionalRelationship => [
                DefaultSectionEnum::ProfessionalFirstMeeting,
                DefaultSectionEnum::ProfessionalCommonGround,
                DefaultSectionEnum::ProfessionalDocuments,
                DefaultSectionEnum::ProfessionalNotes,
            ],
            self::PortraitProfile => [
                DefaultSectionEnum::PortraitLandmarks,
                DefaultSectionEnum::PortraitContactDetails,
                DefaultSectionEnum::PortraitHowWeMet,
            ],
            self::PortraitDailyLife => [
                DefaultSectionEnum::PortraitWorkAndStudies,
                DefaultSectionEnum::PortraitTastesAndHabits,
                DefaultSectionEnum::PortraitCommunication,
                DefaultSectionEnum::PortraitAttentions,
            ],
            self::PortraitMemories => [
                DefaultSectionEnum::PortraitPhotos,
                DefaultSectionEnum::PortraitDocuments,
                DefaultSectionEnum::PortraitNotes,
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
