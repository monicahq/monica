<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\SynchronizeAddressBook;

class SynchronizeAddressBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var AddressBookSubscription
     */
    public $subscription;

    /**
     * Create a new job instance.
     *
     * @param  AddressBookSubscription  $subscription
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            app(SynchronizeAddressBook::class)->execute([
                'account_id' => $this->subscription->account_id,
                'user_id' => $this->subscription->user_id,
                'addressbook_subscription_id' => $this->subscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [$e]);
        }
        $this->subscription->last_synchronized_at = now();
        $this->subscription->save();
    }
}
