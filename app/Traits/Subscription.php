<?php

namespace App\Traits;

trait Subscription
{

    /**
     * Check if the account is currently subscribed to a plan.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        if ($this->has_access_to_paid_version_for_free) {
            return true;
        }

        if (! $this->licence_key) {
            return false;
        }

        if ($this->valid_until_at->isPast()) {
            return false;
        }

        return true;
    }
}
