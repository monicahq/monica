<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum WebsiteLabelEnum: string implements SuppliedValueListItem
{
    case Personal = 'personal';
    case Work = 'work';
    case Blog = 'blog';
    case SocialProfile = 'social_profile';
    case Other = 'other';

    /**
     * What the link points at, shown beside it on a record. The word doubles as
     * the translation key.
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Work => 'Work',
            self::Blog => 'Blog',
            self::SocialProfile => 'Social profile',
            self::Other => 'Other',
        };
    }

    /**
     * The label this item is seeded with.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
