<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\SynchronizeAddressBook;

class SynchronizeAddressBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $addressBookSubscription;

    /**
     * Create a new job instance.
     *
     * @param AddressBookSubscription $addressBookSubscription
     * @return void
     */
    public function __construct(AddressBookSubscription $addressBookSubscription)
    {
        $this->addressBookSubscription = $addressBookSubscription;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(SynchronizeAddressBook::class)->execute([
            'account_id' => $this->addressBookSubscription->account_id,
            'user_id' => $this->addressBookSubscription->user_id,
            'addressbook_subscription_id' => $this->addressBookSubscription->id,
        ]);
    }
}
