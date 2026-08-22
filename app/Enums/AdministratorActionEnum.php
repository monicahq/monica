<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

/**
 * What an administrator did in the admin space.
 *
 * It is deliberately not {@see UserActionEnum}. That one describes what
 * somebody did inside their own account, and this one what an operator did to
 * an account or to a marketplace submission. The two are read on different
 * screens by different people, and a case belongs to exactly one of them.
 */
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

    /**
     * What the action reads as in the record, written about the administrator
     * who took it. The sentence doubles as the translation key, and its
     * placeholders are filled with the parameters the action was recorded with.
     */
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

    /**
     * The sentence this action reads as in the record.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
