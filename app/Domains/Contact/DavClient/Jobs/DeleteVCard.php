<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteVCard implements ShouldQueue
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
        Log::debug(__CLASS__.' '.$this->uri);

        $this->subscription->getClient()
            ->request('DELETE', $this->uri);
    }
}
