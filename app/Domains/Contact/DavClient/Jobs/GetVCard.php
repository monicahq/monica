<?php

namespace App\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetVCard implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AddressBookSubscription $subscription,
        private ContactDto $contact
    ) {
        $this->subscription = $subscription->withoutRelations();
    }

    /**
     * Update the Last Consulted At field for the given contact.
     */
    public function handle(): void
    {
        if (! $this->batching()) {
            return; // @codeCoverageIgnore
        }

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
        Log::channel('database')->debug("Get card {$this->contact->uri}");

        $response = $this->subscription->getClient()
            ->request('GET', $this->contact->uri);

        $job = $this->updateVCard($response->body());

        $this->batch()->add($job);
    }

    private function updateVCard(string $card): UpdateVCard
    {
        return new UpdateVCard([
            'account_id' => $this->subscription->vault->account_id,
            'author_id' => $this->subscription->user_id,
            'vault_id' => $this->subscription->vault_id,
            'uri' => $this->contact->uri,
            'etag' => $this->contact->etag,
            'card' => $card,
            'external' => true,
        ]);
    }
}
