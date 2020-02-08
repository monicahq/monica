<?php

namespace App\Helpers;

use App\Models\Account\Account;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AccountHelper
{
    /**
     * Check if the account can be downgraded, based on a set of rules.
     *
     * @return bool
     */
    public static function canDowngrade(Account $account)
    {
        $canDowngrade = true;
        $numberOfUsers = $account->users()->count();
        $numberPendingInvitations = $account->invitations()->count();
        $numberContacts = $account->contacts()->count();

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
            $canDowngrade = false;
        }

        // there should not be more than the number of contacts allowed
        if ($numberContacts > config('monica.number_of_allowed_contacts_free_account')) {
            $canDowngrade = false;
        }

        return $canDowngrade;
    }
}
