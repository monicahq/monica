<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum IndustryEnum: string implements SuppliedValueListItem
{
    case Agriculture = 'agriculture';
    case ArtsAndCulture = 'arts_and_culture';
    case Construction = 'construction';
    case Consulting = 'consulting';
    case Education = 'education';
    case Energy = 'energy';
    case Finance = 'finance';
    case Health = 'health';
    case Hospitality = 'hospitality';
    case InformationTechnology = 'information_technology';
    case Legal = 'legal';
    case Manufacturing = 'manufacturing';
    case Media = 'media';
    case Nonprofit = 'nonprofit';
    case PublicSector = 'public_sector';
    case RealEstate = 'real_estate';
    case Retail = 'retail';
    case Transport = 'transport';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Agriculture => 'Agriculture',
            self::ArtsAndCulture => 'Arts and culture',
            self::Construction => 'Construction',
            self::Consulting => 'Consulting',
            self::Education => 'Education',
            self::Energy => 'Energy',
            self::Finance => 'Finance',
            self::Health => 'Health',
            self::Hospitality => 'Hospitality',
            self::InformationTechnology => 'Information technology',
            self::Legal => 'Legal',
            self::Manufacturing => 'Manufacturing',
            self::Media => 'Media',
            self::Nonprofit => 'Non-profit',
            self::PublicSector => 'Public sector',
            self::RealEstate => 'Real estate',
            self::Retail => 'Retail',
            self::Transport => 'Transport',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
