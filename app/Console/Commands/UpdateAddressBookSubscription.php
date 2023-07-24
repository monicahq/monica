<?php

namespace App\Console\Commands;

use App\Domains\Contact\DavClient\Jobs\SynchronizeAddressBooks;
use App\Models\AddressBookSubscription;
use Illuminate\Console\Command;

class UpdateAddressBookSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:updateaddressbooksubscription
                            {--subscriptionId= : Id of the vault to add subscription to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a subscription';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $subscription = AddressBookSubscription::findOrFail($this->option('subscriptionId'));

        SynchronizeAddressBooks::dispatch($subscription, true);
    }
}
