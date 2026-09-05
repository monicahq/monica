<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AdministratorActionEnum: string implements Translatable
{
    case MarketplaceVersionApproved = 'marketplace_version_approved';
    case MarketplaceVersionRejected = 'marketplace_version_rejected';
    case UserDeactivated = 'user_deactivated';
    case UserReactivated = 'user_reactivated';
    case AccountStorageLimitUpdated = 'account_storage_limit_updated';
    case LifetimeAccessGranted = 'lifetime_access_granted';
    case LifetimeAccessWithdrawn = 'lifetime_access_withdrawn';
    case AdministratorAccessGranted = 'administrator_access_granted';
    case AdministratorAccessWithdrawn = 'administrator_access_withdrawn';

    public function description(): string
    {
        return match ($this) {
            self::MarketplaceVersionApproved => 'Approved version :version of :name',
            self::MarketplaceVersionRejected => 'Rejected version :version of :name',
            self::UserDeactivated => 'Deactivated :name',
            self::UserReactivated => 'Reactivated :name',
            self::AccountStorageLimitUpdated => 'Changed the storage limit of the account of :name',
            self::LifetimeAccessGranted => 'Granted lifetime access to the account of :name',
            self::LifetimeAccessWithdrawn => 'Withdrew lifetime access from the account of :name',
            self::AdministratorAccessGranted => 'Made :name an administrator',
            self::AdministratorAccessWithdrawn => 'Withdrew the administrator capacity from :name',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
