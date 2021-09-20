<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\SynchronizeAddressBooks;
use App\Models\Account\AddressBookSubscription;

class DavClientsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:davclients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all dav subscriptions';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $subscriptions = AddressBookSubscription::active()->get();

        $now = now();
        $subscriptions->filter(function ($subscription) use ($now) {
            return $this->isTimeToRunSync($subscription, $now);
        })->each(function ($subscription) {
            SynchronizeAddressBooks::dispatch($subscription);
        });
    }

    /**
     * Test if the last synchronized timestamp is older than the subscription's frequency time.
     *
     * @param  AddressBookSubscription  $subscription
     * @param  Carbon  $now
     * @return bool
     */
    private function isTimeToRunSync(AddressBookSubscription $subscription, Carbon $now): bool
    {
        return is_null($subscription->last_synchronized_at)
            || $subscription->last_synchronized_at->addMinutes($subscription->frequency)->lessThan($now);
    }
}
