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
    case EssentialsLandmarks = 'essentials-landmarks';
    case EssentialsContactDetails = 'essentials-contact-details';
    case EssentialsHowWeMet = 'essentials-how-we-met';
    case EssentialsNotes = 'essentials-notes';
    case FriendsLandmarks = 'friends-landmarks';
    case FriendsContactDetails = 'friends-contact-details';
    case FriendsHowWeMet = 'friends-how-we-met';
    case FriendsInterests = 'friends-interests';
    case FriendsAtTheTable = 'friends-at-the-table';
    case FriendsGiftIdeas = 'friends-gift-ideas';
    case FriendsNotes = 'friends-notes';
    case ProfessionalCurrentRole = 'professional-current-role';
    case ProfessionalContactDetails = 'professional-contact-details';
    case ProfessionalCareer = 'professional-career';
    case ProfessionalFirstMeeting = 'professional-first-meeting';
    case ProfessionalCommonGround = 'professional-common-ground';
    case ProfessionalDocuments = 'professional-documents';
    case ProfessionalNotes = 'professional-notes';
    case PortraitLandmarks = 'portrait-landmarks';
    case PortraitContactDetails = 'portrait-contact-details';
    case PortraitHowWeMet = 'portrait-how-we-met';
    case PortraitWorkAndStudies = 'portrait-work-and-studies';
    case PortraitTastesAndHabits = 'portrait-tastes-and-habits';
    case PortraitCommunication = 'portrait-communication';
    case PortraitAttentions = 'portrait-attentions';
    case PortraitPhotos = 'portrait-photos';
    case PortraitDocuments = 'portrait-documents';
    case PortraitNotes = 'portrait-notes';

    public function label(): string
    {
        return match ($this) {
            self::PersonalDetails => 'Personal details',
            self::ContactInformation => 'Contact information',
            self::Addresses => 'Addresses',
            self::Documents, self::ProfessionalDocuments, self::PortraitDocuments => 'Documents',
            self::Photos, self::PortraitPhotos => 'Photos',
            self::Notes, self::EssentialsNotes, self::FriendsNotes, self::ProfessionalNotes, self::PortraitNotes => 'Notes',
            self::AnimalDetails => 'Animal details',
            self::AnimalPeople => 'People',
            self::AnimalCare => 'Care',
            self::AnimalNotes => 'Notes',
            self::EssentialsLandmarks => 'Landmarks',
            self::FriendsLandmarks, self::PortraitLandmarks => 'Personal landmarks',
            self::EssentialsContactDetails, self::FriendsContactDetails, self::PortraitContactDetails => 'Contact details',
            self::EssentialsHowWeMet, self::FriendsHowWeMet, self::PortraitHowWeMet => 'How we met',
            self::FriendsInterests => 'Interests',
            self::FriendsAtTheTable => 'At the table',
            self::FriendsGiftIdeas => 'Gift ideas',
            self::ProfessionalCurrentRole => 'Current role',
            self::ProfessionalContactDetails => 'Work contact details',
            self::ProfessionalCareer => 'Career',
            self::ProfessionalFirstMeeting => 'First meeting',
            self::ProfessionalCommonGround => 'Common ground',
            self::PortraitWorkAndStudies => 'Work and studies',
            self::PortraitTastesAndHabits => 'Tastes and habits',
            self::PortraitCommunication => 'Communication',
            self::PortraitAttentions => 'Thoughtful gestures',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PersonalDetails, self::FriendsLandmarks, self::PortraitLandmarks => 'profile',
            self::ContactInformation, self::EssentialsContactDetails, self::FriendsContactDetails,
            self::ProfessionalContactDetails, self::PortraitContactDetails => 'mail',
            self::Addresses => 'place',
            self::Documents, self::ProfessionalDocuments, self::PortraitDocuments => 'file',
            self::Photos, self::PortraitPhotos => 'memory',
            self::Notes, self::AnimalNotes, self::EssentialsNotes, self::FriendsNotes, self::ProfessionalNotes, self::PortraitNotes => 'note',
            self::AnimalDetails => 'pet',
            self::AnimalPeople => 'people',
            self::AnimalCare => 'health',
            self::EssentialsLandmarks => 'star',
            self::EssentialsHowWeMet, self::FriendsHowWeMet, self::ProfessionalFirstMeeting, self::PortraitHowWeMet => 'relationship',
            self::FriendsInterests => 'heart',
            self::FriendsAtTheTable, self::PortraitTastesAndHabits => 'food',
            self::FriendsGiftIdeas, self::PortraitAttentions => 'gift',
            self::ProfessionalCurrentRole => 'briefcase',
            self::ProfessionalCareer => 'timeline',
            self::ProfessionalCommonGround => 'target',
            self::PortraitWorkAndStudies => 'graduation',
            self::PortraitCommunication => 'phone',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::PersonalDetails, self::EssentialsLandmarks, self::FriendsLandmarks, self::PortraitLandmarks,
            self::ProfessionalCurrentRole, self::AnimalPeople => VaultColourEnum::Blue,
            self::Notes, self::AnimalNotes, self::EssentialsNotes, self::FriendsNotes, self::ProfessionalNotes,
            self::PortraitNotes => VaultColourEnum::Green,
            self::ContactInformation, self::EssentialsContactDetails, self::FriendsContactDetails,
            self::ProfessionalContactDetails, self::PortraitContactDetails, self::PortraitCommunication,
            self::AnimalCare => VaultColourEnum::Teal,
            self::Addresses, self::AnimalDetails, self::FriendsAtTheTable, self::PortraitTastesAndHabits => VaultColourEnum::Amber,
            self::Documents, self::ProfessionalDocuments, self::PortraitDocuments, self::ProfessionalCareer,
            self::PortraitWorkAndStudies => VaultColourEnum::Slate,
            self::Photos, self::PortraitPhotos, self::EssentialsHowWeMet, self::FriendsHowWeMet,
            self::ProfessionalFirstMeeting, self::PortraitHowWeMet => VaultColourEnum::Violet,
            self::FriendsInterests, self::ProfessionalCommonGround => VaultColourEnum::Plum,
            self::FriendsGiftIdeas, self::PortraitAttentions => VaultColourEnum::Rust,
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
                DefaultFieldEnum::Addresses,
            ],
            self::Documents, self::ProfessionalDocuments, self::PortraitDocuments => [
                DefaultFieldEnum::Document,
            ],
            self::Photos, self::PortraitPhotos => [
                DefaultFieldEnum::Photo,
            ],
            self::Notes, self::EssentialsNotes, self::FriendsNotes, self::ProfessionalNotes, self::PortraitNotes => [
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
            self::EssentialsLandmarks => [
                DefaultFieldEnum::Category,
                DefaultFieldEnum::Birthday,
            ],
            self::EssentialsContactDetails => [
                DefaultFieldEnum::Emails,
                DefaultFieldEnum::Phones,
                DefaultFieldEnum::Addresses,
                DefaultFieldEnum::Website,
            ],
            self::EssentialsHowWeMet, self::FriendsHowWeMet, self::ProfessionalFirstMeeting => [
                DefaultFieldEnum::MetOn,
                DefaultFieldEnum::MetWhere,
                DefaultFieldEnum::IntroducedBy,
            ],
            self::FriendsLandmarks => [
                DefaultFieldEnum::Birthday,
                DefaultFieldEnum::Pronouns,
                DefaultFieldEnum::LanguagesSpoken,
            ],
            self::FriendsContactDetails => [
                DefaultFieldEnum::Emails,
                DefaultFieldEnum::Phones,
                DefaultFieldEnum::Addresses,
                DefaultFieldEnum::PreferredChannel,
            ],
            self::FriendsInterests => [
                DefaultFieldEnum::Hobbies,
                DefaultFieldEnum::Passions,
            ],
            self::FriendsAtTheTable => [
                DefaultFieldEnum::FavouriteDishes,
                DefaultFieldEnum::FavouriteDrinks,
                DefaultFieldEnum::DietaryPreferences,
                DefaultFieldEnum::Allergies,
            ],
            self::FriendsGiftIdeas => [
                DefaultFieldEnum::GiftIdeas,
            ],
            self::ProfessionalCurrentRole => [
                DefaultFieldEnum::JobRole,
                DefaultFieldEnum::Organisation,
                DefaultFieldEnum::Industry,
                DefaultFieldEnum::Expertise,
            ],
            self::ProfessionalContactDetails => [
                DefaultFieldEnum::Emails,
                DefaultFieldEnum::Phones,
                DefaultFieldEnum::WorkAddress,
                DefaultFieldEnum::Website,
                DefaultFieldEnum::ProfessionalProfile,
            ],
            self::ProfessionalCareer => [
                DefaultFieldEnum::Experiences,
            ],
            self::ProfessionalCommonGround => [
                DefaultFieldEnum::SharedInterests,
                DefaultFieldEnum::TopicsToDiscuss,
                DefaultFieldEnum::HowToHelp,
            ],
            self::PortraitLandmarks => [
                DefaultFieldEnum::Category,
                DefaultFieldEnum::Birthday,
                DefaultFieldEnum::Gender,
                DefaultFieldEnum::Pronouns,
                DefaultFieldEnum::LanguagesSpoken,
            ],
            self::PortraitContactDetails => [
                DefaultFieldEnum::Emails,
                DefaultFieldEnum::Phones,
                DefaultFieldEnum::Addresses,
                DefaultFieldEnum::OnlineProfiles,
            ],
            self::PortraitHowWeMet => [
                DefaultFieldEnum::MetOn,
                DefaultFieldEnum::MetWhere,
                DefaultFieldEnum::MetContext,
                DefaultFieldEnum::IntroducedBy,
            ],
            self::PortraitWorkAndStudies => [
                DefaultFieldEnum::JobRole,
                DefaultFieldEnum::Organisation,
                DefaultFieldEnum::Education,
            ],
            self::PortraitTastesAndHabits => [
                DefaultFieldEnum::Hobbies,
                DefaultFieldEnum::CulturalTastes,
                DefaultFieldEnum::FavouriteDishes,
                DefaultFieldEnum::FavouriteDrinks,
                DefaultFieldEnum::DietaryPreferences,
                DefaultFieldEnum::Habits,
            ],
            self::PortraitCommunication => [
                DefaultFieldEnum::PreferredChannel,
                DefaultFieldEnum::LanguagesToUse,
                DefaultFieldEnum::Availability,
            ],
            self::PortraitAttentions => [
                DefaultFieldEnum::GiftIdeas,
                DefaultFieldEnum::OutingIdeas,
            ],
        };
    }

    /**
     * The label a top level field shows in this section. One concept keeps one key everywhere,
     * while its wording may change from one template to the next.
     */
    public function fieldLabel(DefaultFieldEnum $field): string
    {
        return $this->labelOverrides()[$field->value] ?? $field->label();
    }

    /** @return array<string, string> */
    private function labelOverrides(): array
    {
        return match ($this) {
            self::EssentialsLandmarks, self::PortraitLandmarks => [DefaultFieldEnum::Category->value => 'Categories'],
            self::EssentialsNotes, self::FriendsNotes, self::ProfessionalNotes, self::PortraitNotes => [DefaultFieldEnum::Notes->value => 'Free-form notes'],
            self::FriendsGiftIdeas => [DefaultFieldEnum::GiftIdeas->value => 'Ideas'],
            self::ProfessionalFirstMeeting => [DefaultFieldEnum::MetWhere->value => 'Event or context'],
            self::PortraitHowWeMet => [DefaultFieldEnum::MetWhere->value => 'Place'],
            self::PortraitWorkAndStudies => [DefaultFieldEnum::JobRole->value => 'Current job role'],
            self::ProfessionalDocuments, self::PortraitDocuments => [DefaultFieldEnum::Document->value => 'Documents'],
            self::PortraitPhotos => [DefaultFieldEnum::Photo->value => 'Photos'],
            default => [],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), ...array_values($this->labelOverrides())];
    }
}
