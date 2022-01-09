<?php

namespace App\Jobs\Dav;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Account\AddressBookSubscription;

class DeleteVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var AddressBookSubscription
     */
    private $subscription;

    /**
     * @var string
     */
    private $uri;

    /**
     * Create a new job instance.
     *
     * @param  AddressBookSubscription  $subscription
     * @param  string  $uri
     * @return void
     */
    public function __construct(AddressBookSubscription $subscription, string $uri)
    {
        $this->subscription = $subscription->withoutRelations();
        $this->uri = $uri;
    }

    /**
     * Send Delete contact.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return;
        }

        Log::info(__CLASS__.' '.$this->uri);

        $this->subscription->getClient()
            ->request('DELETE', $this->uri);
    }
}
