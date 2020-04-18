<?php

namespace App\Console\Commands;

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
     * @return mixed
     */
    public function handle()
    {
        $subscriptions = AddressBookSubscription::all();

        $now = now();
        $subscriptions->filter(function ($subscription) use ($now) {
            return is_null($subscription->lastsync)
                || $subscription->lastsync->addMinutes($subscription->frequency)->lessThan($now);
        })->each(function ($subscription) {
            SynchronizeAddressBooks::dispatch($subscription);
        });
    }
}
