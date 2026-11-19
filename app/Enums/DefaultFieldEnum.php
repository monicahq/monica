<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultFieldEnum: string implements Translatable
{
    case Category = 'category';
    case Gender = 'gender';
    case Birthday = 'birthday';
    case Emails = 'emails';
    case Email = 'email';
    case Phones = 'phones';
    case Phone = 'phone';
    case Website = 'website';
    case Addresses = 'addresses';
    case Address = 'address';
    case Document = 'document';
    case File = 'file';
    case Description = 'description';
    case Photo = 'photo';
    case Image = 'image';
    case Caption = 'caption';
    case Notes = 'notes';
    case Note = 'note';
    case Species = 'species';
    case Breed = 'breed';
    case Sex = 'sex';
    case AdoptionDate = 'adoption_date';
    case MicrochipNumber = 'microchip_number';
    case Caregivers = 'caregivers';
    case CaregiverPerson = 'caregiver_person';
    case CaregiverRole = 'caregiver_role';
    case FoodAndRoutine = 'food_and_routine';
    case HealthAndCare = 'health_and_care';
    case AnimalNotes = 'animal_notes';
    case Pronouns = 'pronouns';
    case LanguagesSpoken = 'languages_spoken';
    case LanguagesToUse = 'languages_to_use';
    case PreferredChannel = 'preferred_channel';
    case MetOn = 'met_on';
    case MetWhere = 'met_where';
    case MetContext = 'met_context';
    case IntroducedBy = 'introduced_by';
    case Hobbies = 'hobbies';
    case Passions = 'passions';
    case FavouriteDishes = 'favourite_dishes';
    case FavouriteDrinks = 'favourite_drinks';
    case DietaryPreferences = 'dietary_preferences';
    case Allergies = 'allergies';
    case GiftIdeas = 'gift_ideas';
    case GiftIdea = 'gift_idea';
    case GiftIdeaLink = 'gift_idea_link';
    case GiftIdeaNote = 'gift_idea_note';
    case OutingIdeas = 'outing_ideas';
    case OutingIdea = 'outing_idea';
    case OutingIdeaLink = 'outing_idea_link';
    case OutingIdeaNote = 'outing_idea_note';
    case JobRole = 'job_role';
    case Organisation = 'organisation';
    case Industry = 'industry';
    case Expertise = 'expertise';
    case WorkAddress = 'work_address';
    case ProfessionalProfile = 'professional_profile';
    case Experiences = 'experiences';
    case ExperienceOrganisation = 'experience_organisation';
    case ExperienceRole = 'experience_role';
    case ExperienceStartedOn = 'experience_started_on';
    case ExperienceEndedOn = 'experience_ended_on';
    case SharedInterests = 'shared_interests';
    case TopicsToDiscuss = 'topics_to_discuss';
    case HowToHelp = 'how_to_help';
    case OnlineProfiles = 'online_profiles';
    case OnlineProfileLabel = 'online_profile_label';
    case OnlineProfileLink = 'online_profile_link';
    case Education = 'education';
    case School = 'school';
    case FieldOfStudy = 'field_of_study';
    case EducationStartedOn = 'education_started_on';
    case EducationEndedOn = 'education_ended_on';
    case CulturalTastes = 'cultural_tastes';
    case Habits = 'habits';
    case Availability = 'availability';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Category',
            self::Gender => 'Gender',
            self::Birthday => 'Birthday',
            self::Emails => 'Emails',
            self::Email => 'Email',
            self::Phones => 'Phones',
            self::Phone => 'Phone',
            self::Website => 'Website',
            self::Addresses => 'Addresses',
            self::Address => 'Address',
            self::Document => 'Document',
            self::File => 'File',
            self::Description => 'Description',
            self::Photo => 'Photo',
            self::Image => 'Image',
            self::Caption => 'Caption',
            self::Notes => 'Notes',
            self::Note => 'Note',
            self::Species => 'Species',
            self::Breed => 'Breed',
            self::Sex => 'Sex',
            self::AdoptionDate => 'Adoption date',
            self::MicrochipNumber => 'Microchip number',
            self::Caregivers => 'Caregivers',
            self::CaregiverPerson => 'Person',
            self::CaregiverRole => 'Role',
            self::FoodAndRoutine => 'Food and routine',
            self::HealthAndCare => 'Health and care notes',
            self::AnimalNotes => 'Notes',
            self::Pronouns => 'Pronouns',
            self::LanguagesSpoken => 'Languages spoken',
            self::LanguagesToUse => 'Languages to use',
            self::PreferredChannel => 'Preferred way to reach them',
            self::MetOn => 'Date we met',
            self::MetWhere => 'Place or context',
            self::MetContext => 'Context',
            self::IntroducedBy => 'Introduced by',
            self::Hobbies => 'Hobbies',
            self::Passions => 'Topics this person loves',
            self::FavouriteDishes => 'Favourite dishes',
            self::FavouriteDrinks => 'Favourite drinks',
            self::DietaryPreferences => 'Dietary preferences',
            self::Allergies => 'Allergies to know about',
            self::GiftIdeas => 'Gift ideas',
            self::GiftIdea, self::OutingIdea => 'Idea',
            self::GiftIdeaLink, self::OutingIdeaLink, self::OnlineProfileLink => 'Link',
            self::GiftIdeaNote, self::OutingIdeaNote => 'Note',
            self::OutingIdeas => 'Outing ideas to share',
            self::JobRole, self::ExperienceRole => 'Job role',
            self::Organisation, self::ExperienceOrganisation => 'Organisation',
            self::Industry => 'Industry',
            self::Expertise => 'Areas of expertise',
            self::WorkAddress => 'Work address',
            self::ProfessionalProfile => 'Professional profile',
            self::Experiences => 'Experience',
            self::ExperienceStartedOn, self::EducationStartedOn => 'Start date',
            self::ExperienceEndedOn, self::EducationEndedOn => 'End date',
            self::SharedInterests => 'Shared interests',
            self::TopicsToDiscuss => 'Topics to bring up',
            self::HowToHelp => 'How I can help this person',
            self::OnlineProfiles => 'Websites and online profiles',
            self::OnlineProfileLabel => 'Label',
            self::Education => 'Education',
            self::School => 'School',
            self::FieldOfStudy => 'Field of study',
            self::CulturalTastes => 'Cultural tastes',
            self::Habits => 'Habits to remember',
            self::Availability => 'Usual availability',
        };
    }

    public function type(): FieldTypeEnum
    {
        return match ($this) {
            self::Category, self::LanguagesSpoken, self::LanguagesToUse, self::Hobbies, self::Expertise => FieldTypeEnum::Multiselect,
            self::Gender, self::Sex, self::PreferredChannel, self::Industry => FieldTypeEnum::Select,
            self::Birthday, self::AdoptionDate => FieldTypeEnum::RecurringDate,
            self::Emails, self::Phones, self::Addresses, self::Document, self::Photo, self::Notes, self::Caregivers,
            self::GiftIdeas, self::OutingIdeas, self::Experiences, self::OnlineProfiles, self::Education => FieldTypeEnum::Repeater,
            self::Email => FieldTypeEnum::Email,
            self::Phone => FieldTypeEnum::Phone,
            self::Website, self::GiftIdeaLink, self::OutingIdeaLink, self::ProfessionalProfile, self::OnlineProfileLink => FieldTypeEnum::Url,
            self::Address, self::WorkAddress => FieldTypeEnum::Address,
            self::File => FieldTypeEnum::File,
            self::Image => FieldTypeEnum::Image,
            self::Description, self::Caption, self::Species, self::Breed, self::MicrochipNumber, self::CaregiverRole,
            self::Pronouns, self::MetWhere, self::GiftIdea, self::OutingIdea, self::JobRole, self::Organisation,
            self::ExperienceOrganisation, self::ExperienceRole, self::OnlineProfileLabel, self::School, self::FieldOfStudy => FieldTypeEnum::Text,
            self::Note, self::FoodAndRoutine, self::HealthAndCare, self::AnimalNotes, self::MetContext, self::Passions,
            self::FavouriteDishes, self::FavouriteDrinks, self::DietaryPreferences, self::Allergies, self::GiftIdeaNote,
            self::OutingIdeaNote, self::SharedInterests, self::TopicsToDiscuss, self::HowToHelp, self::CulturalTastes,
            self::Habits, self::Availability => FieldTypeEnum::Textarea,
            self::MetOn, self::ExperienceStartedOn, self::ExperienceEndedOn, self::EducationStartedOn, self::EducationEndedOn => FieldTypeEnum::Date,
            self::CaregiverPerson, self::IntroducedBy => FieldTypeEnum::Relation,
        };
    }

    /** Whether two records sharing this value are likely the same record. */
    public function isIdentifying(): bool
    {
        return in_array($this, [self::Email, self::Phone], true);
    }

    /** @return list<self> */
    public function children(): array
    {
        return match ($this) {
            self::Document => [self::File, self::Description],
            self::Photo => [self::Image, self::Caption],
            self::Notes => [self::Note],
            self::Emails => [self::Email],
            self::Phones => [self::Phone],
            self::Addresses => [self::Address],
            self::Caregivers => [self::CaregiverPerson, self::CaregiverRole],
            self::GiftIdeas => [self::GiftIdea, self::GiftIdeaLink, self::GiftIdeaNote],
            self::OutingIdeas => [self::OutingIdea, self::OutingIdeaLink, self::OutingIdeaNote],
            self::Experiences => [self::ExperienceOrganisation, self::ExperienceRole, self::ExperienceStartedOn, self::ExperienceEndedOn],
            self::OnlineProfiles => [self::OnlineProfileLabel, self::OnlineProfileLink],
            self::Education => [self::School, self::FieldOfStudy, self::EducationStartedOn, self::EducationEndedOn],
            default => [],
        };
    }

    /** @return array<string, list<string>|bool>|null */
    public function options(): ?array
    {
        return match ($this) {
            self::File => ['accepted_mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'text/csv',
                'image/jpeg',
                'image/png',
            ]],
            self::Image => ['accepted_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ]],
            self::CaregiverPerson => ['distinct_in_list' => true],
            default => null,
        };
    }

    public function valueList(): ?ValueListEnum
    {
        return match ($this) {
            self::Category => ValueListEnum::ContactCategories,
            self::Gender => ValueListEnum::Genders,
            self::Email => ValueListEnum::EmailLabels,
            self::Phone => ValueListEnum::PhoneLabels,
            self::Address, self::WorkAddress => ValueListEnum::AddressLabels,
            self::Sex => ValueListEnum::AnimalSexes,
            self::LanguagesSpoken, self::LanguagesToUse => ValueListEnum::Languages,
            self::PreferredChannel => ValueListEnum::CommunicationChannels,
            self::Hobbies => ValueListEnum::Hobbies,
            self::Industry => ValueListEnum::Industries,
            self::Expertise => ValueListEnum::AreasOfExpertise,
            default => null,
        };
    }

    public function repetitionOrder(): ?RepetitionOrderEnum
    {
        return match ($this) {
            self::Notes => RepetitionOrderEnum::NewestFirst,
            self::Emails, self::Phones, self::Addresses, self::Caregivers, self::GiftIdeas, self::OutingIdeas,
            self::Experiences, self::OnlineProfiles, self::Education => RepetitionOrderEnum::NewestLast,
            default => null,
        };
    }

    public function isRequired(): bool
    {
        return $this === self::CaregiverPerson;
    }

    public function relationTarget(): ?RelationTargetEnum
    {
        return in_array($this, [self::CaregiverPerson, self::IntroducedBy], true) ? RelationTargetEnum::Contact : null;
    }

    public function isHighlighted(): bool
    {
        return in_array($this, [self::Category, self::Birthday], true);
    }

    /** Whether a link stays off the address book card, which would show it as a website. */
    public function isKeptOffTheCard(): bool
    {
        return in_array($this, [self::GiftIdeaLink, self::OutingIdeaLink], true);
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
