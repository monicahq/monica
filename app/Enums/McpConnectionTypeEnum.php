<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum McpConnectionTypeEnum: string implements Translatable
{
    case ApiKey = 'api_key';
    case OauthClient = 'oauth_client';

    public function label(): string
    {
        return match ($this) {
            self::ApiKey => 'API key',
            self::OauthClient => 'Connected assistant',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
