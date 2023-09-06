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
use Illuminate\Support\Traits\Localizable;

class SynchronizeAddressBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Localizable, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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
            $logid = $this->subscription->current_logid ?? 0;
            $this->subscription->current_logid = $logid + 1;
            $this->subscription->save();

            Log::shareContext([
                'addressbook_subscription_id' => $this->subscription->id,
            ]);

            $this->withLocale($this->subscription->user->preferredLocale(), fn () => $this->synchronize());
        } finally {
            Log::flushSharedContext();
        }
    }

    /**
     * Run synchronization.
     */
    private function synchronize(): void
    {
        Log::channel('database')->info("Synchronize addressbook '{$this->subscription->vault->name}'");

        try {
            $batchId = app(SynchronizeAddressBook::class)->execute([
                'account_id' => $this->subscription->user->account_id,
                'addressbook_subscription_id' => $this->subscription->id,
                'force' => $this->force,
            ]);

            $this->subscription->last_batch = $batchId;
        } catch (\Exception $e) {
            Log::stack([config('logging.default'), 'database'])->error(__CLASS__.' '.__FUNCTION__.':'.$e->getMessage(), [$e]);
            $this->fail($e);
        } finally {
            $this->subscription->last_synchronized_at = now();
            $this->subscription->save();
        }
    }
}
