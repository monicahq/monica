<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class UpdateAddressBooks implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Update all address book subscriptions.
     */
    public function handle(): void
    {
        $now = now();

        AddressBookSubscription::active()
            ->chunkById(200, fn (Collection $subscriptions) => $this->manageSubscriptions($subscriptions, $now));
    }

    /**
     * Manage the subscriptions.
     */
    private function manageSubscriptions(Collection $subscriptions, Carbon $now): void
    {
        $subscriptions
            ->filter(fn (AddressBookSubscription $subscription): bool => $this->isTimeToRunSync($subscription, $now))
            ->each(fn (AddressBookSubscription $subscription) => SynchronizeAddressBooks::dispatch($subscription)->onQueue('high'));
    }

    /**
     * Test if the last synchronized timestamp is older than the subscription's frequency time.
     */
    private function isTimeToRunSync($subscription, Carbon $now): bool
    {
        return $subscription->last_synchronized_at === null
            || $subscription->last_synchronized_at->clone()->addMinutes($subscription->frequency)->lessThan($now);
    }
}
