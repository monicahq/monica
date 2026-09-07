<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

enum RelationshipTypeEnum: string implements Translatable
{
    case ParentChild = 'parent_child';
    case Sibling = 'sibling';
    case GrandparentGrandchild = 'grandparent_grandchild';
    case AuntUncleNieceNephew = 'aunt_uncle_niece_nephew';
    case Cousin = 'cousin';
    case Partner = 'partner';
    case Fiance = 'fiance';
    case Spouse = 'spouse';
    case Friend = 'friend';
    case Colleague = 'colleague';

    public function label(): string
    {
        return match ($this) {
            self::ParentChild => 'Parent / Child',
            self::Sibling => 'Sibling',
            self::GrandparentGrandchild => 'Grandparent / Grandchild',
            self::AuntUncleNieceNephew => 'Aunt or Uncle / Niece or Nephew',
            self::Cousin => 'Cousin',
            self::Partner => 'Partner',
            self::Fiance => 'Fiancé',
            self::Spouse => 'Spouse',
            self::Friend => 'Friend',
            self::Colleague => 'Colleague',
        };
    }

    public function roleA(): string
    {
        return match ($this) {
            self::ParentChild => 'Parent',
            self::Sibling => 'Sibling',
            self::GrandparentGrandchild => 'Grandparent',
            self::AuntUncleNieceNephew => 'Aunt or Uncle',
            self::Cousin => 'Cousin',
            self::Partner => 'Partner',
            self::Fiance => 'Fiancé',
            self::Spouse => 'Spouse',
            self::Friend => 'Friend',
            self::Colleague => 'Colleague',
        };
    }

    public function roleB(): string
    {
        return match ($this) {
            self::ParentChild => 'Child',
            self::GrandparentGrandchild => 'Grandchild',
            self::AuntUncleNieceNephew => 'Niece or Nephew',
            default => $this->roleA(),
        };
    }

    public function isSymmetric(): bool
    {
        return match ($this) {
            self::ParentChild, self::GrandparentGrandchild, self::AuntUncleNieceNephew => false,
            default => true,
        };
    }

    public function category(): RelationshipCategoryEnum
    {
        return match ($this) {
            self::ParentChild, self::Sibling, self::GrandparentGrandchild, self::AuntUncleNieceNephew, self::Cousin => RelationshipCategoryEnum::Family,
            self::Partner, self::Fiance, self::Spouse => RelationshipCategoryEnum::Romantic,
            self::Friend => RelationshipCategoryEnum::Friendship,
            self::Colleague => RelationshipCategoryEnum::Professional,
        };
    }

    public function behavior(): ?RelationshipBehaviorEnum
    {
        return match ($this) {
            self::ParentChild => RelationshipBehaviorEnum::GenealogyParentage,
            self::Partner, self::Fiance, self::Spouse => RelationshipBehaviorEnum::Romantic,
            default => null,
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return array_values(array_unique([$this->label(), $this->roleA(), $this->roleB()]));
    }
}
