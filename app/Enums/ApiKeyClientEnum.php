<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ApiKeyClientEnum: string implements Translatable
{
    case Script = 'script';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::Script => 'Personal script',
            self::Assistant => 'AI Assistant',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Script => 'Something you wrote yourself that calls the API.',
            self::Assistant => 'An assistant connecting over MCP. It reaches exactly what you reach, and every change it makes says so in your journal.',
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
        return [$this->label(), $this->description()];
    }
}
