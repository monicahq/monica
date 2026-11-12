<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionMaintenanceTypeEnum: string implements Translatable
{
    case Inspection = 'inspection';
    case Cleaning = 'cleaning';
    case Servicing = 'servicing';
    case Repair = 'repair';
    case Restoration = 'restoration';
    case PartReplacement = 'part_replacement';
    case Conservation = 'conservation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Inspection => 'Inspection',
            self::Cleaning => 'Cleaning',
            self::Servicing => 'Servicing',
            self::Repair => 'Repair',
            self::Restoration => 'Restoration',
            self::PartReplacement => 'Part replacement',
            self::Conservation => 'Conservation',
            self::Other => 'Other',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::Inspection => VaultColourEnum::Blue,
            self::Cleaning => VaultColourEnum::Teal,
            self::Servicing => VaultColourEnum::Green,
            self::Repair => VaultColourEnum::Rust,
            self::Restoration => VaultColourEnum::Plum,
            self::PartReplacement => VaultColourEnum::Amber,
            self::Conservation => VaultColourEnum::Violet,
            self::Other => VaultColourEnum::Slate,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
