<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Services\SynchronizeAddressBook;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SynchronizeAddressBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AddressBookSubscription $subscription,
        public bool $force = false
    ) {
        $this->subscription = $subscription->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            app(SynchronizeAddressBook::class)->execute([
                'account_id' => $this->subscription->user->account_id,
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
