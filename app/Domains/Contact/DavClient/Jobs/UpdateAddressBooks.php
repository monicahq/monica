<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use Carbon\Carbon;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;

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
            ->chunkById(200, function (Collection $subscriptions) use ($now) {
                $subscriptions
                    ->filter(fn ($subscription) => $this->isTimeToRunSync($subscription, $now))
                    ->each(fn ($subscription) => SynchronizeAddressBooks::dispatch($subscription));
            });
    }

    /**
     * Test if the last synchronized timestamp is older than the subscription's frequency time.
     */
    private function isTimeToRunSync(AddressBookSubscription $subscription, Carbon $now): bool
    {
        return $subscription->last_synchronized_at === null
            || $subscription->last_synchronized_at->clone()->addMinutes($subscription->frequency)->lessThan($now);
    }
}
