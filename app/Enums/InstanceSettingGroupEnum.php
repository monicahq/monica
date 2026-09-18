<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum InstanceSettingGroupEnum: string implements Translatable
{
    case Instance = 'instance';
    case Accounts = 'accounts';
    case SigningIn = 'signing_in';
    case SingleSignOn = 'single_sign_on';
    case RemindersAndNotifications = 'reminders_and_notifications';
    case Files = 'files';
    case Api = 'api';
    case OutboundServices = 'outbound_services';

    public function label(): string
    {
        return match ($this) {
            self::Instance => 'This instance',
            self::Accounts => 'Accounts',
            self::SigningIn => 'Signing in',
            self::SingleSignOn => 'Signing in with an outside provider',
            self::RemindersAndNotifications => 'Reminders and notifications',
            self::Files => 'Files',
            self::Api => 'API',
            self::OutboundServices => 'Outbound services',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
