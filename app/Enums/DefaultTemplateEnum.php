<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultTemplateEnum: string implements Translatable
{
    case BasicContact = 'basic-contact';
    case BasicAnimal = 'basic-animal';
    case Essentials = 'essentials';
    case FriendsAndFamily = 'friends-and-family';
    case ProfessionalNetwork = 'professional-network';
    case FullPortrait = 'full-portrait';
    case Animal = 'animal';

    public function label(): string
    {
        return match ($this) {
            self::BasicContact => 'Basic contact',
            self::BasicAnimal => 'Basic animal',
            self::Essentials => 'Essentials',
            self::FriendsAndFamily => 'Friends and family',
            self::ProfessionalNetwork => 'Professional network',
            self::FullPortrait => 'Full portrait',
            self::Animal => 'Animal',
        };
    }

    public function description(): ?string
    {
        return match ($this) {
            self::BasicContact, self::BasicAnimal => null,
            self::Essentials => 'Keep your contacts’ details and essential landmarks at hand every day.',
            self::FriendsAndFamily => 'Remember the small things that matter, to take care of your relationships.',
            self::ProfessionalNetwork => 'Remember people, their work and the common ground that feeds the relationship.',
            self::FullPortrait => 'Build a rich record over time.',
            self::Animal => 'Remember the animals that matter, their habits and the people who look after them.',
        };
    }

    /** Whether a vault starts from this template when nobody picks one. */
    public function isVaultDefault(): bool
    {
        return in_array($this, [self::BasicContact, self::BasicAnimal], true);
    }

    public function appliesTo(): StructureAppliesToEnum
    {
        return match ($this) {
            self::BasicAnimal, self::Animal => StructureAppliesToEnum::Entity,
            default => StructureAppliesToEnum::Contact,
        };
    }

    /** @return list<DefaultPageEnum> */
    public function pages(): array
    {
        return match ($this) {
            self::BasicContact => [
                DefaultPageEnum::General,
                DefaultPageEnum::WaysToConnect,
                DefaultPageEnum::Information,
            ],
            self::BasicAnimal => [
                DefaultPageEnum::AnimalGeneral,
            ],
            self::Essentials => [
                DefaultPageEnum::EssentialsEveryday,
            ],
            self::FriendsAndFamily => [
                DefaultPageEnum::FriendsEveryday,
                DefaultPageEnum::FriendsTastesAndAttentions,
            ],
            self::ProfessionalNetwork => [
                DefaultPageEnum::ProfessionalProfile,
                DefaultPageEnum::ProfessionalRelationship,
            ],
            self::FullPortrait => [
                DefaultPageEnum::PortraitProfile,
                DefaultPageEnum::PortraitDailyLife,
                DefaultPageEnum::PortraitMemories,
            ],
            self::Animal => [
                DefaultPageEnum::AnimalProfile,
                DefaultPageEnum::AnimalDailyLife,
            ],
        };
    }

    /**
     * @return array{slug: string, singular: string, plural: string, description: string, icon: string, colour: string}|null
     */
    public function entityType(): ?array
    {
        return match ($this) {
            self::BasicAnimal, self::Animal => [
                'slug' => 'animals',
                'singular' => 'Animal',
                'plural' => 'Animals',
                'description' => 'Pets and other animals you want to remember.',
                'icon' => 'pet',
                'colour' => '#9a6a24',
            ],
            default => null,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        $type = $this->entityType();
        $keys = [$this->label()];

        if ($this->description() !== null) {
            $keys[] = $this->description();
        }

        if ($type === null) {
            return $keys;
        }

        return [...$keys, $type['singular'], $type['plural'], $type['description']];
    }
}
