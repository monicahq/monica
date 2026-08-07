<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailType: string
{
    case LoginFailed = 'login_failed';
    case NewLogin = 'new_login';
    case UserIpChanged = 'user_ip_changed';
    case MagicLinkCreated = 'magic_link_created';
    case EmailVerification = 'email_verification';
}
