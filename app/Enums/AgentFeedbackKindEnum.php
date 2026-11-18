<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AgentFeedbackKindEnum: string implements Translatable
{
    case Bug = 'bug';
    case MissingTool = 'missing_tool';
    case UnclearDescription = 'unclear_description';
    case Improvement = 'improvement';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::MissingTool => 'Missing tool',
            self::UnclearDescription => 'Unclear description',
            self::Improvement => 'Improvement',
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
