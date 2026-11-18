<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AgentFeedbackStatusEnum: string implements Translatable
{
    case New = 'new';
    case Accepted = 'accepted';
    case Fixed = 'fixed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Accepted => 'Accepted',
            self::Fixed => 'Fixed',
            self::Dismissed => 'Dismissed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'accent',
            self::Accepted => 'info',
            self::Fixed => 'success',
            self::Dismissed => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Accepted => 'check-circle',
            self::Fixed => 'check',
            self::Dismissed => 'close',
        };
    }

    /** @return list<self> */
    public function allowedTargets(): array
    {
        return match ($this) {
            self::New => [self::Accepted, self::Dismissed],
            self::Accepted => [self::Fixed, self::Dismissed],
            self::Fixed, self::Dismissed => [self::New],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
