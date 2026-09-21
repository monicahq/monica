<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

enum LifeEventCategoryEnum: string implements Translatable
{
    case RelationshipsAndFamily = 'relationships_and_family';
    case WorkAndEducation = 'work_and_education';
    case HealthAndWellbeing = 'health_and_wellbeing';
    case HomeAndDailyLife = 'home_and_daily_life';
    case LeisureAndCulture = 'leisure_and_culture';
    case TravelAndExperiences = 'travel_and_experiences';
    case Personal = 'personal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RelationshipsAndFamily => 'Relationships and family',
            self::WorkAndEducation => 'Work and education',
            self::HealthAndWellbeing => 'Health and wellbeing',
            self::HomeAndDailyLife => 'Home and daily life',
            self::LeisureAndCulture => 'Leisure and culture',
            self::TravelAndExperiences => 'Travel and experiences',
            self::Personal => 'Personal',
            self::Other => 'Other',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
