<?php

declare(strict_types=1);

namespace App\Enums;

enum SignInMethodEnum: string
{
    case EmailLink = 'email_link';
    case Passkey = 'passkey';
    case Provider = 'provider';
}
