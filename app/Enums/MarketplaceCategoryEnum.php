<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum MarketplaceCategoryEnum: string implements Translatable
{
    case Health = 'health';
    case Family = 'family';
    case Work = 'work';
    case Finance = 'finance';
    case Animals = 'animals';
    case Home = 'home';
    case Hobbies = 'hobbies';
    case Travel = 'travel';

    public function label(): string
    {
        return match ($this) {
            self::Health => 'Health',
            self::Family => 'Family',
            self::Work => 'Work',
            self::Finance => 'Finance',
            self::Animals => 'Animals',
            self::Home => 'Home',
            self::Hobbies => 'Hobbies',
            self::Travel => 'Travel',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Health => 'Appointments, treatments and everything worth following about a body',
            self::Family => 'How people are related, and what a household keeps track of',
            self::Work => 'Colleagues, clients and the shape of a professional life',
            self::Finance => 'Accounts, subscriptions and what money is owed to whom',
            self::Animals => 'Pets and the animals somebody looks after',
            self::Home => 'Places somebody lives in, and what has to be maintained there',
            self::Hobbies => 'Collections, practice and what somebody does for the love of it',
            self::Travel => 'Trips, stays and the people met along the way',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Health => 'heart',
            self::Family => 'family',
            self::Work => 'organization',
            self::Finance => 'vault',
            self::Animals => 'pet',
            self::Home => 'place',
            self::Hobbies => 'star',
            self::Travel => 'activity',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
