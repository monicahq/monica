<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum SupportRequestStatusEnum: string implements Translatable
{
    case ToHandle = 'to_handle';
    case WaitingOnUser = 'waiting_on_user';
    case Solved = 'solved';

    public function administratorLabel(): string
    {
        return match ($this) {
            self::ToHandle => 'To handle',
            self::WaitingOnUser => 'Waiting on the user',
            self::Solved => 'Solved',
        };
    }

    public function userLabel(): string
    {
        return match ($this) {
            self::ToHandle => 'Waiting for support',
            self::WaitingOnUser => 'Reply from support',
            self::Solved => 'Solved',
        };
    }

    public function administratorTone(): string
    {
        return match ($this) {
            self::ToHandle => 'warning',
            self::WaitingOnUser => 'neutral',
            self::Solved => 'success',
        };
    }

    public function userTone(): string
    {
        return match ($this) {
            self::ToHandle => 'neutral',
            self::WaitingOnUser => 'info',
            self::Solved => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ToHandle => 'clock',
            self::WaitingOnUser => 'mail',
            self::Solved => 'check-circle',
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
        return [$this->administratorLabel(), $this->userLabel()];
    }
}
