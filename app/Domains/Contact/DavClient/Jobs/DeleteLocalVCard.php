<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteLocalVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AddressBookSubscription $subscription,
        private string $uri
    ) {
        $this->subscription = $subscription->withoutRelations();
    }

    /**
     * Send Delete contact.
     */
    public function handle(): void
    {
        Log::shareContext([
            'addressbook_subscription_id' => $this->subscription->id,
        ]);

        try {
            $this->run();
        } finally {
            Log::flushSharedContext();
        }
    }

    /**
     * Run the job.
     */
    private function run(): void
    {
        Log::channel('database')->debug("Delete local card {$this->uri}");

        $backend = app(CardDAVBackend::class)->withUser($this->subscription->user);
        $backend->deleteCard($this->subscription->vault_id, $this->uri);
    }
}
