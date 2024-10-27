<?php

namespace App\Console\Commands\Local;

use App\Domains\Contact\DavClient\Jobs\SynchronizeAddressBooks;
use App\Models\AddressBookSubscription;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'monica:updateaddressbooksubscription')]
class UpdateAddressBookSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:updateaddressbooksubscription
                            {--subscriptionId= : Id of the subscription to synchronize}
                            {--force : Force sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a subscription';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscription = AddressBookSubscription::findOrFail($this->option('subscriptionId'));

        SynchronizeAddressBooks::dispatch($subscription, $this->option('force'))->onQueue('high');
    }
}
