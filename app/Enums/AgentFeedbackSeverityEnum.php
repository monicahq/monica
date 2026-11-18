<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AgentFeedbackSeverityEnum: string implements Translatable
{
    case BlocksTask = 'blocks_task';
    case NeedsWorkaround = 'needs_workaround';
    case Minor = 'minor';

    public function label(): string
    {
        return match ($this) {
            self::BlocksTask => 'Blocks the task',
            self::NeedsWorkaround => 'Needs a workaround',
            self::Minor => 'Minor',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BlocksTask => 'danger',
            self::NeedsWorkaround => 'warning',
            self::Minor => 'neutral',
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
