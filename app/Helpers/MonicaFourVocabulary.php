<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\GenderEnum;
use App\Enums\LifeEventCategoryEnum;
use App\Enums\LifeEventTypeEnum;
use App\Enums\RelationshipCategoryEnum;
use App\Enums\RelationshipTypeEnum;

/**
 * What the words of a Monica 4 export mean in Monica: its relationship types, its life event
 * natures, its genders and its categories. The reader and the writer of an import both ask it, so
 * one Monica 4 word never reaches two Monica meanings (contracts/monica-4-mapping.md).
 */
class MonicaFourVocabulary
{
    /**
     * Monica 4 relationship names that pair with another name in the other direction.
     *
     * @var array<string, string>
     */
    private const array REVERSE = [
        'parent' => 'child',
        'child' => 'parent',
        'grandparent' => 'grandchild',
        'grandchild' => 'grandparent',
        'uncle' => 'nephew',
        'nephew' => 'uncle',
        'stepparent' => 'stepchild',
        'stepchild' => 'stepparent',
        'godfather' => 'godson',
        'godson' => 'godfather',
        'boss' => 'subordinate',
        'subordinate' => 'boss',
        'mentor' => 'protege',
        'protege' => 'mentor',
        'inlovewith' => 'lovedby',
        'lovedby' => 'inlovewith',
    ];

    /**
     * Monica 4 relationship names Monica ships a type for, and whether the Monica 4 side is the
     * second role of that type.
     *
     * @var array<string, array{0: RelationshipTypeEnum, 1: bool}>
     */
    private const array RELATIONSHIP_TYPES = [
        'parent' => [RelationshipTypeEnum::ParentChild, false],
        'child' => [RelationshipTypeEnum::ParentChild, true],
        'grandparent' => [RelationshipTypeEnum::GrandparentGrandchild, false],
        'grandchild' => [RelationshipTypeEnum::GrandparentGrandchild, true],
        'uncle' => [RelationshipTypeEnum::AuntUncleNieceNephew, false],
        'nephew' => [RelationshipTypeEnum::AuntUncleNieceNephew, true],
        'sibling' => [RelationshipTypeEnum::Sibling, false],
        'cousin' => [RelationshipTypeEnum::Cousin, false],
        'partner' => [RelationshipTypeEnum::Partner, false],
        'date' => [RelationshipTypeEnum::Partner, false],
        'lover' => [RelationshipTypeEnum::Partner, false],
        'spouse' => [RelationshipTypeEnum::Spouse, false],
        'friend' => [RelationshipTypeEnum::Friend, false],
        'bestfriend' => [RelationshipTypeEnum::Friend, false],
        'colleague' => [RelationshipTypeEnum::Colleague, false],
    ];

    /**
     * Monica 4 life event natures Monica ships a nature for.
     *
     * @var array<string, LifeEventTypeEnum>
     */
    private const array NATURES = [
        'new_job' => LifeEventTypeEnum::NewJob,
        'marriage' => LifeEventTypeEnum::Marriage,
        'home_improvement' => LifeEventTypeEnum::HomeImprovement,
        'achievement_or_award' => LifeEventTypeEnum::Achievement,
        'new_child' => LifeEventTypeEnum::Birth,
        'moved' => LifeEventTypeEnum::Move,
        'travel' => LifeEventTypeEnum::Trip,
        'holidays' => LifeEventTypeEnum::Holiday,
        'end_of_relationship' => LifeEventTypeEnum::Separation,
        'bought_a_home' => LifeEventTypeEnum::MajorPurchase,
        'new_vehicle' => LifeEventTypeEnum::MajorPurchase,
        'surgery' => LifeEventTypeEnum::HospitalStay,
        'dentist' => LifeEventTypeEnum::MedicalAppointment,
        'new_sport' => LifeEventTypeEnum::Exercise,
        'new_hobby' => LifeEventTypeEnum::Hobby,
        'new_instrument' => LifeEventTypeEnum::Hobby,
    ];

    /**
     * Monica 4 life event categories, and the Monica category an account's own nature goes into.
     *
     * @var array<string, LifeEventCategoryEnum>
     */
    private const array NATURE_CATEGORIES = [
        'work_education' => LifeEventCategoryEnum::WorkAndEducation,
        'family_relationships' => LifeEventCategoryEnum::RelationshipsAndFamily,
        'home_living' => LifeEventCategoryEnum::HomeAndDailyLife,
        'travel_experiences' => LifeEventCategoryEnum::TravelAndExperiences,
        'health_wellness' => LifeEventCategoryEnum::HealthAndWellbeing,
    ];

    public static function reverseOf(string $relationship): string
    {
        return self::REVERSE[$relationship] ?? $relationship;
    }

    /**
     * The Monica type a Monica 4 relationship name is, and whether its Monica 4 side is the second
     * role of it. Null when Monica ships no such type and the account gets its own.
     *
     * @return array{0: RelationshipTypeEnum, 1: bool}|null
     */
    public static function relationshipType(string $relationship): ?array
    {
        return self::RELATIONSHIP_TYPES[$relationship] ?? null;
    }

    /**
     * Whether a Monica 4 relationship name has a distinct name in the other direction, which
     * makes the account's own type for it directed rather than symmetric.
     */
    public static function isDirected(string $relationship): bool
    {
        return array_key_exists($relationship, self::REVERSE);
    }

    public static function ownRelationshipCategory(string $relationship): RelationshipCategoryEnum
    {
        return match ($relationship) {
            'stepparent', 'stepchild', 'godfather', 'godson', 'family' => RelationshipCategoryEnum::Family,
            'ex', 'love', 'inlovewith', 'lovedby' => RelationshipCategoryEnum::Romantic,
            'boss', 'subordinate', 'mentor', 'protege', 'work' => RelationshipCategoryEnum::Professional,
            default => RelationshipCategoryEnum::Other,
        };
    }

    /**
     * The wording of one side of a relationship the account gets a type of its own for.
     */
    public static function roleName(string $relationship): string
    {
        return match ($relationship) {
            'stepparent' => __('Stepparent'),
            'stepchild' => __('Stepchild'),
            'godfather' => __('Godparent'),
            'godson' => __('Godchild'),
            'family' => __('Family'),
            'ex' => __('Former partner'),
            'love' => __('Love'),
            'inlovewith' => __('In love with'),
            'lovedby' => __('Loved by'),
            'boss' => __('Manager'),
            'subordinate' => __('Report'),
            'mentor' => __('Mentor'),
            'protege' => __('Mentee'),
            'work' => __('Work'),
            default => self::nameFrom($relationship),
        };
    }

    public static function nature(string $key): ?LifeEventTypeEnum
    {
        return self::NATURES[$key] ?? null;
    }

    public static function natureCategory(?string $category): LifeEventCategoryEnum
    {
        return self::NATURE_CATEGORIES[$category] ?? LifeEventCategoryEnum::Other;
    }

    /**
     * The name of a nature the account gets of its own, for a Monica 4 nature Monica ships no
     * equivalent of, and for the two the import writes from a contact's own dates.
     */
    public static function natureName(string $key): string
    {
        return match ($key) {
            'death' => __('Death'),
            'first_meeting' => __('First meeting'),
            'retirement' => __('Retirement'),
            'new_school' => __('New school'),
            'study_abroad' => __('Study abroad'),
            'volunteer_work' => __('Volunteer work'),
            'published_book_or_paper' => __('Published a book or a paper'),
            'military_service' => __('Military service'),
            'new_relationship' => __('New relationship'),
            'engagement' => __('Engagement'),
            'anniversary' => __('Anniversary'),
            'expecting_a_baby' => __('Expecting a baby'),
            'new_family_member' => __('New family member'),
            'new_pet' => __('New pet'),
            'loss_of_a_loved_one' => __('Loss of a loved one'),
            'new_roommate' => __('New roommate'),
            'overcame_an_illness' => __('Overcame an illness'),
            'quit_a_habit' => __('Quit a habit'),
            'new_eating_habits' => __('New eating habits'),
            'weight_loss' => __('Weight loss'),
            'wear_glass_or_contact' => __('Glasses or contact lenses'),
            'broken_bone' => __('Broken bone'),
            'removed_braces' => __('Braces removed'),
            'new_language' => __('New language'),
            'tattoo_or_piercing' => __('Tattoo or piercing'),
            'new_license' => __('New licence'),
            'changed_beliefs' => __('Changed beliefs'),
            'first_word' => __('First word'),
            'first_kiss' => __('First kiss'),
            default => self::nameFrom($key),
        };
    }

    /**
     * The species written on an animal for a Monica 4 pet category. A pet filed as other carries
     * no species, since the category said nothing about it.
     */
    public static function species(?string $category): ?string
    {
        return match ($category) {
            'dog' => __('Dog'),
            'cat' => __('Cat'),
            'bird' => __('Bird'),
            'fish' => __('Fish'),
            'hamster' => __('Hamster'),
            'horse' => __('Horse'),
            'rabbit' => __('Rabbit'),
            'rat' => __('Rat'),
            'reptile' => __('Reptile'),
            'small_animal' => __('Small animal'),
            default => null,
        };
    }

    /**
     * The Monica gender a Monica 4 gender type stands for: `M` and `F` by their letter, `U` is
     * the choice not to say, and anything an account invented is Other.
     */
    public static function gender(?string $type): GenderEnum
    {
        return match ($type) {
            'M' => GenderEnum::Man,
            'F' => GenderEnum::Woman,
            'U' => GenderEnum::PreferNotToSay,
            default => GenderEnum::Other,
        };
    }

    /**
     * A readable name made from a key a file invented, which no translation can know.
     */
    private static function nameFrom(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
