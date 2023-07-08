<?php

namespace App\Console\Commands;

use App\Domains\Contact\DavClient\Jobs\SynchronizeAddressBooks;
use App\Models\AddressBookSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $subscriptions
            ->filter(fn ($subscription) => $this->isTimeToRunSync($subscription, $now))
            ->each(fn ($subscription) => SynchronizeAddressBooks::dispatch($subscription));
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
