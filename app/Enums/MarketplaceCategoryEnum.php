<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * The categories the catalogue is filed under.
 *
 * The list is ours and there is no free tagging. A catalogue with open keywords
 * becomes unreadable, and the whole value of the marketplace is that somebody
 * can find the medical follow up block without wading through forty near
 * duplicates.
 *
 * It is deliberately short. A category nobody publishes into is a dead corner
 * of the catalogue, and there is no way to tell in advance which ones those
 * would be, so the list grows from what people actually publish rather than
 * from what we imagine they will.
 *
 * The cases run by how close a subject sits to the person the record is about,
 * from their body outwards, which is also the order they are listed in. The
 * backing value is the `key` column, and it is the only part anything may rely
 * on: the name and the description are read through the locale files.
 */
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

    /**
     * What the category is called in the catalogue. The name doubles as the
     * translation key.
     */
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

    /**
     * What belongs in it, shown under the name so nobody has to open the
     * category to know whether what they built goes there.
     */
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

    /**
     * The glyph on the category card, taken from the icon language.
     */
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

    /**
     * The name and the description, which are the two strings the catalogue
     * reads in the language of whoever is browsing it.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
