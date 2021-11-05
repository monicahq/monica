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
     * @var bool
     */
    private $force;

    /**
     * Create a new job instance.
     *
     * @param  AddressBookSubscription  $subscription
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription, bool $force = false)
    {
        $this->subscription = $subscription;
        $this->force = $force;
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
                'addressbook_subscription_id' => $this->subscription->id,
                'force' => $this->force,
            ]);
        } catch (\Exception $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.':'.$e->getMessage(), [$e]);
        }
        $this->subscription->last_synchronized_at = now();
        $this->subscription->save();
    }
}
