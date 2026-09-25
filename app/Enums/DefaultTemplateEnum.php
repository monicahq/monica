<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultTemplateEnum: string implements Translatable
{
    case BasicContact = 'basic-contact';
    case BasicAnimal = 'basic-animal';

    public function label(): string
    {
        return match ($this) {
            self::BasicContact => 'Basic contact',
            self::BasicAnimal => 'Basic animal',
        };
    }

    public function appliesTo(): StructureAppliesToEnum
    {
        return match ($this) {
            self::BasicContact => StructureAppliesToEnum::Contact,
            self::BasicAnimal => StructureAppliesToEnum::Entity,
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
        };
    }

    /**
     * @return array{slug: string, singular: string, plural: string, description: string, icon: string, colour: string}|null
     */
    public function entityType(): ?array
    {
        return match ($this) {
            self::BasicContact => null,
            self::BasicAnimal => [
                'slug' => 'animals',
                'singular' => 'Animal',
                'plural' => 'Animals',
                'description' => 'Pets and other animals you want to remember.',
                'icon' => 'pet',
                'colour' => '#9a6a24',
            ],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        $type = $this->entityType();

        if ($type === null) {
            return [$this->label()];
        }

        return [$this->label(), $type['singular'], $type['plural'], $type['description']];
    }
}
